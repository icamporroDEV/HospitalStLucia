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

## Laboratorio Blue-Green local

Construir la imagen que utilizarán ambos slots e iniciar el laboratorio:

```bash
scripts/build-local-image 1.0.0
mkdir -p deploy/runtime
cp deploy/state.env.example deploy/runtime/state.env
docker compose --env-file deploy/runtime/state.env up -d --build
```

La URL pública es `http://127.0.0.1:8080`. Blue y Green quedan disponibles solamente desde la máquina local en los puertos `18081` y `18082` para diagnóstico.

Cambiar a Green y volver a Blue:

```bash
deploy/bin/current-color
deploy/bin/switch-color green
deploy/bin/rollback-color blue
```

Validar el ciclo completo desde un volumen limpio:

```bash
tests/Contract/blue-green-smoke.sh
```

La explicación de cada operación y del despliegue de una imagen candidata está en [`deploy/README.md`](deploy/README.md).

La toma de muestras externas, los CSV y el cálculo de métricas para las nueve corridas están en [`docs/monitoring.md`](docs/monitoring.md).

Los resultados de las nueve corridas locales, con CSV y logs de Jenkins, están en [`docs/evidence/blue-green-local-2026-10-25/README.md`](docs/evidence/blue-green-local-2026-10-25/README.md).

La reproducción del laboratorio en una VM EC2 está descrita en [`docs/vm-demo.md`](docs/vm-demo.md). El asistente interactivo `scripts/aws-ec2-wizard.sh` guía los pasos de la consola AWS desde Git Bash; solo guarda región, dirección de la VM y ruta local de la clave en `.env.vm` (ignorado por Git).

El [informe final editable](docs/informe-final.docx) y su [versión Markdown](docs/informe-final.md) integran la arquitectura, el método, las métricas locales y de EC2, los límites y los pendientes de la entrega grupal. La [evidencia de EC2](docs/evidence/blue-green-vm-2026-10-25/README.md) conserva los cinco CSV, incluida la corrida `happy` #7 con 0/86 peticiones fallidas.

El [índice del material de entrega](docs/entrega.md) reúne el informe, las evidencias, las guías y el [guion de demostración](docs/guion-demo.md).

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
