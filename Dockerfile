FROM php:8.3.35-apache-trixie

ARG APP_VERSION=dev
ARG APP_COMMIT_SHA=unknown
ARG APP_BUILD_TIME=unknown

ENV APP_VERSION="$APP_VERSION" \
    APP_COMMIT_SHA="$APP_COMMIT_SHA" \
    APP_BUILD_TIME="$APP_BUILD_TIME" \
    DEPLOYMENT_COLOR=local

RUN sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && printf 'expose_php=Off\n' > /usr/local/etc/php/conf.d/security.ini \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

COPY --chown=www-data:www-data apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=www-data:www-data public/ /var/www/html/public/
COPY --chown=www-data:www-data data/ /var/www/html/data/

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=5s --retries=3 \
    CMD ["php", "-r", "$body = @file_get_contents('http://127.0.0.1:8080/health/live'); exit($body === false ? 1 : 0);"]

CMD ["apache2-foreground"]
