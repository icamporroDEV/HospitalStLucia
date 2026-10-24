FROM nginxinc/nginx-unprivileged:1.30.1-alpine

USER root

RUN apk upgrade --no-cache \
    && mkdir -p /etc/nginx/active /etc/nginx/upstreams \
    && chown -R 101:101 /etc/nginx/active /etc/nginx/upstreams

COPY --chown=101:101 nginx.conf /etc/nginx/nginx.conf
COPY --chown=101:101 upstreams/ /etc/nginx/upstreams/
COPY --chown=101:101 upstreams/blue.conf /etc/nginx/active/active.conf

USER 101

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=5s --retries=3 \
    CMD ["wget", "-q", "-O", "-", "http://127.0.0.1:8080/health/live"]
