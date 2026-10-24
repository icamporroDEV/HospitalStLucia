# Hospital Santa Lucía — HIS legado simulado

Demo académica de un sistema hospitalario con estética de fines de los 2000. La aplicación es deliberadamente pequeña: una pantalla de turnos, datos ficticios y endpoints mínimos para la futura demostración de CI/CD y Blue-Green.

> No cargar datos clínicos ni personales reales.

## Ejecutar

Con PHP 8.3 y Composer:

```bash
composer install
composer test
php -S 127.0.0.1:8080 -t public
```

Abrir `http://127.0.0.1:8080/turnos`.

Si PHP no está instalado en el host, se puede usar Docker:

```bash
docker run --rm -v "$PWD:/app" -w /app composer:2 install
docker run --rm -v "$PWD:/app" -w /app composer:2 test
docker run --rm -p 8080:8080 -v "$PWD:/app" -w /app php:8.3-cli php -S 0.0.0.0:8080 -t public
```

## Construir la imagen de la aplicación

La imagen contiene solamente Apache, PHP y los archivos necesarios para ejecutar la demo. La versión, el commit y la fecha quedan grabados al construirla:

```bash
docker build --build-arg APP_VERSION=1.0.0 --build-arg APP_COMMIT_SHA="$(git rev-parse --short HEAD)" --build-arg APP_BUILD_TIME="$(date -u +%Y-%m-%dT%H:%M:%SZ)" -t hospital-st-lucia:1.0.0 .
```

Ejecutar la imagen indicando el color del entorno:

```bash
docker run --rm --name hospital-his -p 8080:8080 -e DEPLOYMENT_COLOR=blue hospital-st-lucia:1.0.0
```

En otra terminal, validar el mismo contrato que utilizará Jenkins:

```bash
tests/Contract/container-smoke.sh http://127.0.0.1:8080 1.0.0
```

Los argumentos `APP_VERSION`, `APP_COMMIT_SHA` y `APP_BUILD_TIME` identifican el artefacto inmutable. `DEPLOYMENT_COLOR` es configuración de ejecución porque la misma imagen puede desplegarse como Blue o Green.

## Rutas

| Ruta | Uso |
| --- | --- |
| `/` y `/turnos` | Pantalla principal de la demo |
| `/api/turnos` | Los tres turnos ficticios en JSON |
| `/version` | Versión y color del despliegue |
| `/health/live` | Estado técnico |
| `/health/ready` | Disponibilidad técnica |

La versión y el color se configuran con `APP_VERSION` y `DEPLOYMENT_COLOR`. Los módulos restantes aparecen solo como decoración y se incorporarán después, uno por release.

## Estructura mínima

```text
data/appointments.php       Datos ficticios
public/index.php            Toda la aplicación y sus rutas
public/assets/legacy.css    Diseño A aprobado
tests/DemoTest.php          Prueba básica del dataset
Dockerfile                  Imagen reproducible de la aplicación
apache/000-default.conf     Virtual host limitado a public/
tests/Contract/             Smoke test externo del contenedor
```
