# Legacy HIS PoC Design

## Problem

La Clínica Santa Lucía necesita demostrar que puede incorporar una nueva versión de un portal hospitalario legado sin interrumpir una función crítica. La PoC debe hacer visible el riesgo de integrar cambios en un sistema antiguo, diferenciar disponibilidad técnica de disponibilidad funcional y permitir validar un despliegue Blue-Green automatizado.

## Incremental delivery strategy

El desarrollo usará un **walking skeleton**: primero se construye una porción pequeña pero completa y desplegable de la aplicación; luego se instala el flujo CI/CD sobre esa versión; finalmente se agregan módulos de solo lectura como releases sucesivas. El "30%" describe este alcance demostrativo y no un porcentaje de un HIS real.

### Fase 1 — Base funcional (~30%)

- Aplicación web stateless y de solo lectura.
- Datos completamente ficticios; no se procesa PII ni PHI.
- Una función real de demostración: consulta de turnos.
- Navegación decorativa para Inicio, Estudios, Historia clínica y Medicamentos.
- Release base `v1.0.0` identificable desde la UI y por API.
- Quedan fuera persistencia, autenticación real, interoperabilidad hospitalaria y lógica clínica.

### Fase 2 — Entrega continua sobre la base

- Docker e imagen inmutable.
- Fallas controladas pre-deploy y post-deploy.
- Jenkins, despliegue Blue-Green, Nginx, observación y rollback.
- Ejecución y medición de los tres escenarios de release.

### Fase 3 — Releases incrementales

- `v1.1.0`: módulo Estudios de solo lectura como happy path completo.
- `v1.2.0`: módulo Medicamentos de solo lectura para una segunda evolución visible.
- Cada release atraviesa el mismo pipeline y genera evidencia: tests, imagen, color idle, validación, cut-over, versión pública y resultado de observación.
- Los escenarios fallidos usan configuración o artefactos de demo controlados; no se dejan errores permanentes en las releases estables.

## Chosen approach

Se implementará un monolito PHP renderizado en servidor y servido por Apache. La interfaz simulará una intranet hospitalaria de 2008-2010: layout fijo de escritorio, menú lateral, tablas densas, tipografía Arial y controles visualmente antiguos.

Se usará una versión moderna y soportada de PHP dentro del contenedor. La antigüedad será parte del diseño visual y de la narrativa monolítica, no de la postura de seguridad.

## Approved visual design

El 20/10/2026 se aprobó la **variante A — Portal institucional** del prototipo `docs/prototypes/legacy-his-ui.html?variant=A`.

- Cabecera institucional verde oscuro con identidad ficticia de Clínica Santa Lucía.
- Menú lateral fijo con Turnos expandido y el resto de los módulos como navegación decorativa.
- Fondo, paneles, tabla y pie en una paleta beige envejecida; no se utilizará azul.
- Breadcrumb, aviso institucional, filtros compactos y tabla densa como estructura principal.
- Barra inferior con versión, entorno y marca `DEMO`.
- Datos ficticios y leyenda de entorno de demostración permanentemente visibles.

Las variantes B y C se consideran exploración descartada. No deben trasladarse a la aplicación PHP.

## Application contracts

| Ruta | Contrato |
| --- | --- |
| `GET /` | Redirige a `/turnos`. |
| `GET /turnos` | Renderiza la pantalla principal con turnos ficticios. |
| `GET /api/turnos` | Devuelve el contrato JSON usado por el smoke test funcional. |
| `GET /version` | Devuelve versión, SHA, fecha de build y color de despliegue. |
| `GET /health/live` | Devuelve 200 mientras Apache/PHP respondan. |
| `GET /health/ready` | Devuelve 200 si configuración y archivos estáticos requeridos están disponibles. |

Variables de runtime:

- `APP_VERSION`: versión humana visible, por ejemplo `1.0.0`.
- `APP_COMMIT_SHA`: commit incluido en la imagen.
- `DEPLOYMENT_COLOR`: `local` durante la fase 1; `blue` o `green` cuando se integre el despliegue.

Las variables `DEMO_FAILURE_MODE` y `DEMO_FAILURE_AFTER_SECONDS` se incorporarán recién en la fase 2.

## Future delivery design

Esta sección documenta la integración futura y no forma parte de la fase activa. La imagen se construirá una vez y se etiquetará con versión y SHA. Jenkins desplegará esa misma imagen al color idle, validará salud, versión y Turnos, solicitará el cambio de tráfico y observará el endpoint público. Si hay tres verificaciones funcionales consecutivas fallidas, invocará el rollback provisto por Infraestructura.

El pipeline consume estos contratos, cuya implementación pertenece al responsable de Infraestructura:

```text
deploy/bin/current-color
deploy/bin/deploy-color <blue|green> <image>
deploy/bin/switch-color <blue|green>
deploy/bin/rollback-color <blue|green>
```

Todos deben ser no interactivos, devolver códigos de salida POSIX y escribir logs sin secretos. La misma interfaz se usará localmente y en la VM; solo cambiarán variables de entorno y credenciales de Jenkins.

## Options rejected

- Node.js + Express + EJS: adecuado para comienzos de los 2010, pero poco representativo de un HIS nacido antes de 2010.
- React + API: agrega dos artefactos y complejidad de despliegue sin aportar valor al objetivo operativo.
- HTML estático: simplifica demasiado la falla funcional y debilita la diferencia entre liveness y disponibilidad clínica.

## Risks and mitigations

- La falla simulada puede parecer artificial: documentar el mecanismo y demostrar que liveness permanece sano mientras Turnos falla.
- El pipeline depende del contrato con Nginx: validar los cuatro scripts con dobles locales antes de integrar la infraestructura real.
- Un delay temporal puede volver inestable la demo: usar reloj monotónico, valor configurable y márgenes amplios en los tests.
- La UI podría confundirse con un HIS real: mostrar datos ficticios y una leyenda visible de entorno de demostración.

## Acceptance criteria

### Fase 1

- La release `v1.0.0` se identifica visualmente y mediante `/version`.
- La aplicación no escribe datos ni requiere base de datos.
- `/turnos` y `/api/turnos` presentan datos ficticios coherentes.
- `live` y `ready` responden correctamente en ejecución local.
- La suite unitaria y de integración pasa sin depender de Docker, Jenkins, Nginx ni una VM.

### Fase 2

- El escenario exitoso no produce errores durante el cut-over.
- El escenario pre-deploy se detiene antes de desplegar al color idle.
- El escenario post-deploy pasa salud inicial, falla funcionalmente y genera tres fallos consecutivos observables para el rollback.
- Los tests y logs no contienen datos clínicos ni secretos.

### Fase 3

- Estudios y Medicamentos permanecen stateless, read-only y basados en datos ficticios.
- Cada módulo tiene tests propios y una versión visible distinta.
- La presentación puede reconstruir el historial `v1.0.0` → `v1.1.0` → `v1.2.0` usando tags, logs y evidencias archivadas.
