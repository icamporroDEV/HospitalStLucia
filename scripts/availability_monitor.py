#!/usr/bin/env python3
"""Sample the public HIS endpoint from outside Jenkins and summarize availability."""

import argparse
import csv
import json
import re
import time
from datetime import datetime, timezone
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import urlopen


FIELDS = (
    "timestamp_utc",
    "elapsed_ms",
    "turnos_status",
    "version_status",
    "ok",
    "latency_ms",
    "version",
    "color",
    "error",
)


def fetch(url):
    try:
        with urlopen(url, timeout=2) as response:
            return response.status, response.read(), ""
    except HTTPError as exc:
        return exc.code, b"", f"HTTP {exc.code}"
    except (URLError, TimeoutError) as exc:
        return 0, b"", str(exc.reason if isinstance(exc, URLError) else exc)


def sample(base_url, started):
    taken = time.monotonic()
    timestamp = datetime.now(timezone.utc).isoformat(timespec="milliseconds")
    turnos_status, turnos_body, turnos_error = fetch(f"{base_url}/api/turnos")
    version_status, version_body, version_error = fetch(f"{base_url}/version")
    version = ""
    color = ""
    functional = False
    try:
        appointments = json.loads(turnos_body)
        functional = isinstance(appointments, list) and any(
            isinstance(item, dict) and re.fullmatch(r"T-\d+", str(item.get("id", "")))
            for item in appointments
        )
    except (ValueError, TypeError):
        pass
    try:
        release = json.loads(version_body)
        version = str(release.get("version", ""))
        color = str(release.get("deploymentColor", ""))
    except (ValueError, TypeError, AttributeError):
        pass
    ok = functional and turnos_status == 200 and version_status == 200 and bool(version)
    return {
        "timestamp_utc": timestamp,
        "elapsed_ms": round((taken - started) * 1000),
        "turnos_status": turnos_status,
        "version_status": version_status,
        "ok": int(ok),
        "latency_ms": round((time.monotonic() - taken) * 1000),
        "version": version,
        "color": color,
        "error": turnos_error or version_error or ("Contrato de Turnos inválido" if not functional else ""),
    }


def summarize(rows, detected_at=None, pipeline_duration_s=None):
    total = len(rows)
    successes = sum(row["ok"] == "1" for row in rows)
    outages = []
    first_failure = None
    third_failure = None
    consecutive = 0
    for row in rows:
        elapsed = int(row["elapsed_ms"])
        if row["ok"] == "1":
            if first_failure is not None:
                outages.append({
                    "first_failure_utc": first_failure["timestamp_utc"],
                    "recovered_utc": row["timestamp_utc"],
                    "observed_recovery_s": round((elapsed - int(first_failure["elapsed_ms"])) / 1000, 3),
                    "three_failure_window_s": (
                        round((third_failure - int(first_failure["elapsed_ms"])) / 1000, 3)
                        if third_failure is not None else None
                    ),
                })
            first_failure = None
            third_failure = None
            consecutive = 0
        else:
            consecutive += 1
            if first_failure is None:
                first_failure = row
            if consecutive == 3:
                third_failure = elapsed
    if first_failure is not None:
        outages.append({
            "first_failure_utc": first_failure["timestamp_utc"],
            "recovered_utc": None,
            "observed_recovery_s": None,
            "three_failure_window_s": (
                round((third_failure - int(first_failure["elapsed_ms"])) / 1000, 3)
                if third_failure is not None else None
            ),
        })
    recovered = [item["observed_recovery_s"] for item in outages if item["observed_recovery_s"] is not None]
    result = {
        "samples": total,
        "successful": successes,
        "failed": total - successes,
        "success_rate_percent": round(successes * 100 / total, 3) if total else None,
        "error_rate_percent": round((total - successes) * 100 / total, 3) if total else None,
        "mean_observed_recovery_s": round(sum(recovered) / len(recovered), 3) if recovered else None,
        "max_observed_recovery_s": max(recovered) if recovered else None,
        "outages": outages,
    }
    if detected_at is not None:
        if not outages:
            raise ValueError("No hay fallos en el CSV para calcular MTTD")
        detected = datetime.fromisoformat(detected_at.replace("Z", "+00:00"))
        if detected.tzinfo is None:
            raise ValueError("--detected-at debe incluir zona horaria UTC")
        first_failure_at = datetime.fromisoformat(outages[0]["first_failure_utc"])
        detection_delay = (detected - first_failure_at).total_seconds()
        if detection_delay < 0:
            raise ValueError("La detección antecede al primer fallo observado; revise los relojes")
        result["mttd_s"] = round(detection_delay, 3)
    if pipeline_duration_s is not None:
        result["pipeline_duration_s"] = pipeline_duration_s
    return result


def record(args):
    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)
    started = time.monotonic()
    deadline = started + args.duration if args.duration else None
    tick = started
    with output.open("x", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=FIELDS)
        writer.writeheader()
        try:
            while deadline is None or time.monotonic() < deadline:
                writer.writerow(sample(args.url.rstrip("/"), started))
                stream.flush()
                tick += args.interval
                time.sleep(max(0, tick - time.monotonic()))
        except KeyboardInterrupt:
            pass
    with output.open(encoding="utf-8", newline="") as stream:
        print(json.dumps(summarize(list(csv.DictReader(stream))), ensure_ascii=False, indent=2))
    print(f"CSV: {output.resolve()}")


def summary(args):
    with Path(args.input).open(encoding="utf-8", newline="") as stream:
        report = summarize(list(csv.DictReader(stream)), args.detected_at, args.pipeline_duration_s)
        print(json.dumps(report, ensure_ascii=False, indent=2))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    recording = commands.add_parser("record", help="record samples until Ctrl+C or duration")
    recording.add_argument("--url", default="http://127.0.0.1:8080")
    recording.add_argument("--interval", type=float, default=0.25)
    recording.add_argument("--duration", type=float, default=0, help="seconds; 0 means until Ctrl+C")
    recording.add_argument("--output", required=True)
    recording.set_defaults(func=record)
    analysis = commands.add_parser("summary", help="summarize a recorded CSV")
    analysis.add_argument("--input", required=True)
    analysis.add_argument("--detected-at", help="UTC timestamp of Jenkins's third consecutive failure")
    analysis.add_argument("--pipeline-duration-s", type=float, help="PIPELINE_DURATION_SECONDS from Jenkins")
    analysis.set_defaults(func=summary)
    args = parser.parse_args()
    if args.command == "record" and (args.interval <= 0 or args.duration < 0):
        parser.error("interval must be positive and duration cannot be negative")
    args.func(args)


if __name__ == "__main__":
    main()
