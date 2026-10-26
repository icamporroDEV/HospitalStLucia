# Evidencia de disponibilidad de la PoC

El monitor `scripts/availability_monitor.py` se ejecuta en la computadora anfitriona, fuera de Jenkins y de los contenedores de la aplicación. Consulta la entrada pública de Nginx y guarda muestras con fecha UTC, código HTTP, latencia, versión y color. Solo registra metadatos de la demo; no guarda el contenido de Turnos.

## Una corrida

En una PowerShell separada, desde la raíz del repositorio, iniciar el monitor **antes** de pulsar Build en Jenkins:

```powershell
python .\scripts\availability_monitor.py record --url http://127.0.0.1:8080 --interval 0.25 --output .\deploy\runtime\evidence\A1.csv
```

Ejecutar el escenario elegido en Jenkins. Cuando el job haya terminado y la URL pública vuelva a responder correctamente, detener el monitor con Ctrl+C. El CSV queda en `deploy/runtime/evidence/`, directorio excluido de Git. Usar un nombre nuevo en cada corrida: `A1.csv` a `A3.csv` para `happy`, `B1.csv` a `B3.csv` para `pre-test`, y `C1.csv` a `C3.csv` para `post-deploy`.

Para resumir una corrida:

```powershell
python .\scripts\availability_monitor.py summary --input .\deploy\runtime\evidence\A1.csv --pipeline-duration-s 75
```

La duración se toma de `PIPELINE_DURATION_SECONDS` al final del Console Output de Jenkins. En el escenario C, agregar la fecha UTC de la línea de Jenkins donde aparece `fallo 3/3`:

```powershell
python .\scripts\availability_monitor.py summary --input .\deploy\runtime\evidence\C1.csv --detected-at 2026-10-26T00:10:20.123Z --pipeline-duration-s 75
```

La fecha y la duración de ese ejemplo se reemplazan por los valores de la corrida real.

## Interpretación

- `http_success_rate_percent` y `http_error_rate_percent`: proporción de respuestas HTTP exitosas y fallidas, contando por separado las dos peticiones de cada muestra.
- `success_rate_percent` y `error_rate_percent`: proporción de muestras donde `/api/turnos` responde con datos ficticios válidos y `/version` identifica la release.
- `mttd_s`: tiempo desde el primer fallo observado por el monitor hasta que Jenkins detecta tres fallos consecutivos. Se calcula solo para C con `--detected-at`.
- `observed_recovery_s`: tiempo desde el primer fallo observado hasta la primera muestra correcta tras el rollback. Es la medida observada del RTO. La resolución aproximada depende del intervalo de muestreo y la latencia HTTP.
- `mean_observed_recovery_s`: media de los períodos de recuperación dentro del CSV; para una única caída equivale a `observed_recovery_s`.
- `pipeline_duration_s`: duración reportada por Jenkins para la corrida.

En A y B no se esperan fallos de la URL pública. En C se espera una caída funcional breve seguida de recuperación en la versión y color anteriores. Registrar los resultados de las nueve corridas en la tabla del informe y adjuntar los CSV y los Console Output. No declarar cumplida la meta de RTO de 30 segundos hasta medir las tres corridas C.

Las nueve mediciones realizadas están documentadas en [la evidencia local](evidence/blue-green-local-2026-10-25/README.md).

| Corrida | Build Jenkins | Resultado esperado | Error HTTP % | Error funcional % | MTTD (s) | Recuperación observada (s) | Pipeline (s) |
| --- | --- | --- | ---: | ---: | ---: | ---: | ---: |
| A1 |  | SUCCESS |  |  | - | - |  |
| A2 |  | SUCCESS |  |  | - | - |  |
| A3 |  | SUCCESS |  |  | - | - |  |
| B1 |  | FAILURE previo |  |  | - | - |  |
| B2 |  | FAILURE previo |  |  | - | - |  |
| B3 |  | FAILURE previo |  |  | - | - |  |
| C1 |  | FAILURE con rollback |  |  |  |  |  |
| C2 |  | FAILURE con rollback |  |  |  |  |  |
| C3 |  | FAILURE con rollback |  |  |  |  |  |
