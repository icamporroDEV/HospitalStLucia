# Legacy HIS PoC Implementation Plan

> Implementar con `executing-plans`, TDD y commits convencionales. Cada cambio funcional empieza con un test que falla por la razón esperada.

**Goal:** entregar primero una base funcional `v1.0.0`, instalar CI/CD y Blue-Green sobre ella, y utilizar nuevas releases de módulos read-only para demostrar que el proceso funciona repetidamente.

**Architecture:** monolito PHP moderno servido por Apache, páginas renderizadas en servidor y datos JSON ficticios. La aplicación conserva endpoints de versión y salud para la integración futura, sin implementar todavía mecanismos de despliegue.

**Tech stack de la fase activa:** PHP 8.3, Apache, Composer y PHPUnit.

## Delivery cycles

1. **Cycle 1 — Base `v1.0.0`:** Tasks 1-4. Termina cuando la aplicación corre localmente, expone Turnos, versión y salud, y pasa sus tests.
2. **Cycle 2 — Delivery pipeline:** Tasks 5-9. Dockeriza y despliega la base existente antes de agregar más funcionalidad.
3. **Cycle 3 — Incremental releases:** Tasks 10-11. Cada módulo nuevo atraviesa el pipeline ya probado y deja evidencia reutilizable para la presentación.

## Task 1: Bootstrap y documentación ejecutable

**Files:**

- `composer.json` (create)
- `.gitignore` (create)
- `.env.example` (create)
- `README.md` (modify)
- `app/`, `public/` y `tests/` (create)

**Work:**

- Configurar autoload PSR-4 para `Hospital\\` y `Hospital\\Tests\\`.
- Agregar PHPUnit como dependencia de desarrollo y scripts `test` y `test:integration`.
- Documentar ejecución local, variables admitidas y advertencia de datos ficticios.
- No agregar framework, base de datos, sesión ni autenticación.

**Verify:**

```bash
composer validate --strict
composer install
composer test
```

**Commit:** `chore(project): bootstrap legacy his app`

## Task 2: Contratos de configuración y versión

**Files:**

- `tests/Unit/AppConfigTest.php` (create first)
- `app/Config/AppConfig.php` (create after RED)
- `tests/Unit/VersionInfoTest.php` (create first)
- `app/Domain/VersionInfo.php` (create after RED)

**Behavior:**

- Aceptar `local`, `blue` y `green` como identificadores de entorno y rechazar cualquier otro valor.
- Usar defaults seguros para desarrollo: versión `dev`, SHA `unknown` y color `local` cuando no exista un despliegue Blue-Green.
- `/version` debe poder serializar `version`, `commitSha`, `buildTime` y `deploymentColor` sin exponer otras variables.

**Verify:**

```bash
composer test -- --testsuite unit
```

**Commit:** `feat(config): define release metadata contract`

## Task 3: Turnos ficticios de solo lectura

**Files:**

- `tests/Unit/AppointmentRepositoryTest.php` (create first)
- `app/Domain/Appointment.php` (create after RED)
- `app/Repository/JsonAppointmentRepository.php` (create after RED)
- `app/data/appointments.json` (create)

**Behavior:**

- Cargar una lista fija de turnos con identificadores inventados, fecha, hora, especialidad, profesional, sede y estado.
- Validar el JSON al iniciar; un archivo ausente o inválido hace fallar readiness.
- No incluir nombres, documentos, teléfonos ni información clínica de personas reales.
- No ofrecer operaciones de alta, modificación o cancelación.

**Verify:**

```bash
composer test -- --testsuite unit
```

**Commit:** `feat(appointments): add fictional read model`

## Task 4: Interfaz legacy y endpoints HTTP

**Files:**

- `tests/Integration/HttpEndpointsTest.php` (create first)
- `public/index.php` (create after RED)
- `app/Http/Router.php` (create after RED)
- `app/Http/Response.php` (create after RED)
- `app/Views/layout.php` y `app/Views/appointments.php` (create after RED)
- `public/assets/legacy.css` (create)

**Behavior:**

- Implementar exactamente las seis rutas definidas en el diseño.
- Implementar la variante A aprobada en `docs/prototypes/legacy-his-ui.html?variant=A`: cabecera y menú verde oscuro, superficies beige, navegación lateral y tabla de turnos densa.
- No trasladar el selector del prototipo ni las variantes B/C al código de producción.
- Renderizar una UI fija de escritorio inspirada en 2008-2010, sin copiar marcas ni assets del Hospital Italiano.
- Mostrar “Clínica Santa Lucía — Entorno de demostración” y la versión activa.
- Mantener Turnos como única navegación funcional; el resto son módulos decorativos claramente no disponibles.
- Producir JSON con `Content-Type: application/json` y HTML con UTF-8.

**Verify:**

```bash
composer test
php -S 127.0.0.1:8080 -t public
curl --fail http://127.0.0.1:8080/health/live
curl --fail http://127.0.0.1:8080/api/turnos
```

**Commit:** `feat(web): render legacy appointments portal`

## Cycle 2: CI/CD and Blue-Green

## Task 5: Fallas controladas y smoke tests

**Files:**

- `tests/Unit/FailurePolicyTest.php` (create first)
- `app/Failure/FailurePolicy.php` (create after RED)
- `tests/Integration/PostDeployFailureTest.php` (create first)
- `scripts/check-pre-deploy` (create)
- `scripts/smoke-test` (create)

**Behavior:**

- `check-pre-deploy` devuelve error solo para `pre-deploy` y nunca inicia un despliegue.
- `post-deploy` deja pasar validaciones durante el delay y luego degrada únicamente Turnos.
- `live` y `ready` siguen devolviendo 200 después de la degradación para probar que salud técnica no equivale a salud funcional.
- `smoke-test <base-url> <expected-version>` valida HTTP, esquema mínimo, lista no vacía y versión esperada.
- Aplicar timeouts y un máximo de tres intentos; nunca reintentar indefinidamente.

**Verify:**

```bash
composer test
DEMO_FAILURE_MODE=pre-deploy scripts/check-pre-deploy
DEMO_FAILURE_MODE=none scripts/check-pre-deploy
```

El primer comando de escenario debe finalizar distinto de cero y el segundo con cero.

**Commit:** `feat(demo): add deterministic failure modes`

## Task 6: Imagen Docker inmutable

**Files:**

- `Dockerfile` (create)
- `.dockerignore` (create)
- `apache/000-default.conf` (create)
- `tests/Contract/container-smoke.sh` (create first)

**Behavior:**

- Construir sobre una imagen oficial soportada de PHP 8.3 con Apache.
- Instalar dependencias Composer en una etapa separada y copiar solo artefactos necesarios.
- Ejecutar Apache como usuario sin privilegios cuando la imagen y el puerto elegido lo permitan.
- Inyectar versión, SHA y fecha como argumentos de build; color y modo de falla permanecen variables de runtime.
- Agregar healthcheck contra `/health/live` sin usarlo como smoke funcional.

**Verify:**

```bash
docker build --build-arg APP_VERSION=1.0.0 --build-arg APP_COMMIT_SHA=test -t hospital-st-lucia:test .
docker run --rm -d --name hospital-test -p 8080:8080 -e DEPLOYMENT_COLOR=blue hospital-st-lucia:test
tests/Contract/container-smoke.sh http://127.0.0.1:8080 1.0.0
docker stop hospital-test
```

**Commit:** `build(docker): package immutable his image`

## Task 7: Contrato Blue-Green con Infraestructura

**Files:**

- `deploy/README.md` (create)
- `tests/Contract/deploy-scripts.bats` (create first)
- `deploy/bin/` (integration boundary; implementations owned by Infraestructura)

**Behavior:**

- Especificar argumentos, salida y códigos de error de `current-color`, `deploy-color`, `switch-color` y `rollback-color`.
- Exigir idempotencia para consultar/desplegar y cambio atómico para switch/rollback.
- Proveer dobles locales para probar Jenkins sin depender de la VM.
- No incorporar dentro de Jenkins detalles internos de Nginx.

**Verify:**

```bash
bats tests/Contract/deploy-scripts.bats
```

**Commit:** `docs(deploy): define blue green contract`

## Task 8: Jenkinsfile y tres escenarios

**Files:**

- `Jenkinsfile` (create)
- `scripts/observe-release` (create)
- `tests/Contract/jenkinsfile_test.sh` (create first)

**Pipeline:**

1. Checkout y cálculo de `APP_VERSION`, `GIT_SHA` e imagen.
2. Dependencias y tests.
3. `check-pre-deploy`; si falla, terminar sin llamar `deploy-color`.
4. Build de una imagen inmutable.
5. Consultar color activo y derivar idle.
6. Desplegar al idle y validar `live`, `ready`, versión y Turnos.
7. Cambiar tráfico al idle.
8. Observar el endpoint público; tres fallos funcionales consecutivos disparan `rollback-color` al color anterior.
9. Archivar logs con timestamps, versión, SHA, colores, resultado, MTTD y MTTR calculables.

**Failure handling:**

- Un fallo antes del switch elimina o detiene el candidato sin tocar el activo.
- Un fallo después del switch intenta rollback una sola vez y verifica Turnos sobre la versión previa.
- Si el rollback falla, el job queda en `FAILURE` con un mensaje explícito de intervención humana.
- Los bloques `post` siempre archivan evidencia y limpian recursos temporales.

**Verify:**

```bash
tests/Contract/jenkinsfile_test.sh
```

Luego ejecutar en Jenkins: `happy-path`, `pre-deploy-failure` y `post-deploy-failure`, guardando logs de cada corrida.

**Commit:** `ci(jenkins): automate blue green release`

## Task 9: Validación integrada y handoff a QA

**Files:**

- `docs/runbooks/demo.md` (create)
- `docs/runbooks/evidence.md` (create)

**Scenarios:**

- Happy path: v2 queda activa, la versión pública cambia y Turnos mantiene 100% de respuestas exitosas.
- Rechazo previo: el pipeline falla antes de desplegar y el color público no cambia.
- Falla posterior: el cut-over ocurre, Turnos falla tras el delay, se registran tres fallos y vuelve la versión previa.
- Repetir cada escenario tres veces junto con QA y entregar rutas de logs/CSV para SLA, MTTD, MTTR y duración.

**Gates:**

- Ejecutar suite completa, análisis estático PHP, auditoría de dependencias, escaneo de secretos e imagen.
- Bloquear entrega ante hallazgos Critical/High.
- Verificar que todos los datos visibles sean ficticios y que no haya credenciales en archivos o logs.

**Commit:** `docs(demo): add validation runbook`

## Cycle 3: Incremental application releases

## Task 10: Estudios read-only (`v1.1.0`)

**Files:**

- `tests/Unit/StudyRepositoryTest.php` (create first)
- `tests/Integration/StudyEndpointsTest.php` (create first)
- `app/Domain/Study.php` (create after RED)
- `app/Repository/JsonStudyRepository.php` (create after RED)
- `app/data/studies.json` (create)
- `app/Views/studies.php` (create)

**Behavior:**

- Activar el módulo Estudios dentro del layout aprobado, sin modificar Turnos.
- Exponer `/estudios` y `/api/estudios` con datos ficticios de solo lectura.
- Mostrar tipo, fecha, institución, estado y un enlace decorativo de detalle; no incluir resultados clínicos reales.
- Publicar como `v1.1.0` mediante el pipeline ya validado y archivar la evidencia del happy path.

**Commit:** `feat(studies): add read only study history`

## Task 11: Medicamentos read-only (`v1.2.0`)

**Files:**

- `tests/Unit/MedicationRepositoryTest.php` (create first)
- `tests/Integration/MedicationEndpointsTest.php` (create first)
- `app/Domain/Medication.php` (create after RED)
- `app/Repository/JsonMedicationRepository.php` (create after RED)
- `app/data/medications.json` (create)
- `app/Views/medications.php` (create)

**Behavior:**

- Activar el módulo Medicamentos sin operaciones de compra, renovación ni prescripción.
- Exponer `/medicamentos` y `/api/medicamentos` con datos enteramente ficticios.
- Mostrar nombre genérico, presentación, pauta ficticia, vigencia y estado.
- Publicar como `v1.2.0` y conservar evidencias de build, despliegue, versión y observación.

**Commit:** `feat(medications): add read only medication list`

## Delivery order and ownership

- Cycle 1 de Ignacio: Tasks 1-4.
- Cycle 2 de Ignacio: Tasks 5, 6 y 8; documentación técnica de su implementación en 9.
- Cycle 3 de Ignacio: Tasks 10-11.
- Germán: implementación real de los scripts definidos en Task 7 y rollback de Nginx.
- Joaquín: carga, repetición de escenarios y métricas de Task 9.
- Valentina: seguimiento del tablero e integración de evidencias al informe.

La integración con Infraestructura puede comenzar cuando estén definidos los contratos de Task 7; la UI y los tests no deben esperar a la VM.
