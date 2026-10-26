# Autorización del cambio de tráfico en Jenkins

El `Jenkinsfile` detiene la promoción después de desplegar y validar la candidata en el color inactivo. La pausa muestra la versión, el commit, el color activo, el color candidato y el escenario. Solo después de una aprobación registrada vuelve a comprobar el color activo y el smoke test, y ejecuta `switch-color`.

## Quién puede aprobar

En esta PoC, `PROMOTION_APPROVERS` del `Jenkinsfile` contiene el ID de usuario de Jenkins `clinica-santa-lucia`, confirmado en la ruta de su perfil `/user/clinica-santa-lucia/`. Es una **cuenta de laboratorio**; antes de usar el flujo en una clínica debe reemplazarse mediante revisión del pipeline por el ID o grupo administrado por TI y limitarse quién puede modificar el `Jenkinsfile`. No es un parámetro de `Build with Parameters`, para que quien inicia la corrida no pueda elegirse a sí mismo como aprobador.

El plugin Pipeline: Input Step usa `submitter` para restringir quién puede aprobar y `submitterParameter` para devolver el ID efectivo. Los administradores de Jenkins pueden responder aunque no figuren en `submitter`; por eso la política de administradores también forma parte del control de acceso. Ver la [referencia oficial del paso `input`](https://www.jenkins.io/doc/pipeline/steps/pipeline-input-step/).

## Decisión y plazo

1. Ejecutar el job con `Build with Parameters` y el escenario elegido. `pre-test` debe fallar antes de llegar a la autorización.
2. En `happy` y `post-deploy`, Jenkins valida la candidata y queda en **Autorizar cambio de tráfico**. Revisar la versión y el commit del mensaje, las pruebas y el smoke test en Console Output, además de confirmar que la URL pública conserva la versión anterior.
3. El usuario permitido inicia sesión y elige **Aprobar cambio** o **Abortar**. La espera máxima es de 10 minutos; el límite global del pipeline es de 30 minutos.
4. Si se aprueba, el Console Output registra `CAMBIO_APROBADO` con build, versión, commit, ID del aprobador, fecha UTC y color destino. Jenkins vuelve a comprobar el color activo y la candidata antes de cambiar el tráfico.
5. Si se rechaza, vence el plazo o falla el paso de autorización, se registra `CAMBIO_NO_APROBADO` y el pipeline termina sin ejecutar `switch-color`. La candidata puede quedar desplegada en el slot inactivo; no recibe tráfico público.

`CAMBIO_SOLICITADO` deja constancia de la versión presentada antes de la pausa. La fuente de auditoría es el Console Output del build, que debe conservarse o exportarse junto con la evidencia del ensayo. El mensaje `CAMBIO_NO_APROBADO` agrupa rechazo, vencimiento y error; el motivo detallado se consulta en las líneas de Jenkins inmediatamente anteriores al fin del build.

## Ensayo de aceptación pendiente

La implementación del gate debe verificarse en Jenkins local y en la VM; los builds históricos #2 a #7 de EC2 se ejecutaron **antes** de esta modificación y no prueban la aprobación manual.

| Caso | Acción | Evidencia esperada |
| --- | --- | --- |
| Aprobación | Iniciar `happy`, anotar `/version`, aprobar con la cuenta permitida. | `CAMBIO_APROBADO` muestra ID, versión y fecha; `switch-color` aparece después; `/version` cambia al commit candidato. |
| Rechazo | Iniciar otro `happy` y elegir **Abortar**. | `CAMBIO_NO_APROBADO`; no hay `switch-color`; `/version` público conserva el color y commit anteriores. |
| Vencimiento | Iniciar `happy` y no responder durante 10 minutos. | Jenkins termina por timeout, registra `CAMBIO_NO_APROBADO` y `/version` público no cambia. |

Registrar para cada caso el número de build, commit, salida de `/version` antes y después, y las líneas de autorización y resultado del Console Output. El ensayo `post-deploy` también requiere aprobación antes de inducir la falla y observar el rollback. Un resultado `ABORTED` en rechazo o timeout es esperado; no equivale a una falla de la aplicación.
