# Evidencia Blue/Green en VM EC2

Laboratorio en EC2 Ubuntu 24.04 x86_64, región `us-east-2`, con Docker rootless y Jenkins. El monitor se ejecutó en Windows, fuera de Jenkins y de la VM, a través de un túnel SSH hacia la URL pública de Nginx. Los horarios de los CSV y del Console Output están en UTC. El commit desplegado en estas corridas fue `7f1accc587b7`.

## Resultado de la demo

La demostración en la VM **cumplió el flujo Blue/Green previsto**. En la corrida `happy` #7, Jenkins desplegó la candidata en el color inactivo, cambió el tráfico de Blue a Green y terminó en `SUCCESS` en 39 s. El monitor externo registró **0 fallos en 86 peticiones HTTP** durante esa corrida, usando un timeout de 5 s por petición. La aplicación y `/api/turnos` quedaron respondiendo. En `post-deploy`, Jenkins detectó tres fallos de la candidata, ejecutó el rollback a la versión Green anterior y el monitor observó la recuperación del fallo inducido 3,907 s después de su primera muestra fallida, por debajo de la meta de 30 s. El `FAILURE` de Jenkins en ese escenario es el resultado esperado de la prueba controlada.

Las corridas anteriores, medidas con el timeout inicial de 2 s por petición, registraron timeouts aislados antes del cambio de tráfico: de `/version` en #5 y #3, y de `/api/turnos` en #6. La causa no está determinada. Se conservaron en sus CSV y en las tasas de error de la tabla. El 0 % de errores corresponde **solo a la corrida #7**, no al conjunto de todas las corridas de la VM.

| Escenario | Jenkins | Muestras | Errores HTTP | Muestras fallidas | Duración del pipeline |
| --- | --- | ---: | ---: | ---: | ---: |
| [`pre-test`](VM-B1.csv) | [#2](jenkins-vm-2-extract.log), `FAILURE` esperado en pruebas | 15 | 0/30 (0 %) | 0/15 (0 %) | 2 s |
| [`post-deploy`](VM-C1.csv) | #3, `FAILURE` esperado con rollback | 32 | 4/64 (6,25 %) | 4/32 (12,5 %) | 29 s |
| [`happy`](VM-A1.csv) | #5, `SUCCESS` | 37 | 1/74 (1,351 %) | 1/37 (2,703 %) | 39 s |
| [`happy` repetido](VM-A2.csv) | [#6](jenkins-vm-6.log), `SUCCESS` | 42 | 1/84 (1,190 %) | 1/42 (2,381 %) | 39 s |
| [`happy` con timeout de 5 s](VM-A3.csv) | #7, `SUCCESS` | 43 | **0/86 (0 %)** | **0/43 (0 %)** | 39 s |

## Detalle de la corrida `post-deploy`

La salida de Jenkins registró el tercer fallo consecutivo a las `2026-10-26T01:54:46.630Z`, ejecutó `Rollback OK: blue -> green` y terminó en `FAILURE`. El monitor vio a Blue `ci-3-7f1accc587b7` responder 503 en `/api/turnos` tres veces, desde `01:54:43.345Z`; `/version` seguía respondiendo 200. Green `ci-1-7f1accc587b7` volvió a aparecer en la primera muestra correcta a las `01:54:47.241Z`. Para esa caída, el tiempo de detección fue **3,285 s** y la recuperación observada fue **3,907 s**, con la resolución del muestreo externo.

El CSV también contiene un timeout de `/version` a las `01:54:13.884Z`, recuperado a las `01:54:16.584Z`, antes de servir la candidata Blue. Su causa no está identificada. Se incluye en las tasas globales, pero no se atribuye a la caída inducida ni al rollback.

## Detalle de la corrida `happy`

Se ejecutó `happy` dos veces consecutivas: la primera dejó activo Blue `ci-4-7f1accc587b7` y la segunda desplegó Green `ci-5-7f1accc587b7`. El CSV adjunto corresponde a la segunda ejecución. El monitor observó el cambio de Blue a Green entre `02:00:09.730Z` y `02:00:11.046Z`. Las muestras inmediatamente anteriores y posteriores respondieron 200 en ambas rutas. Jenkins #5 terminó en `SUCCESS` y la consulta posterior a `/api/turnos` respondió con los tres turnos ficticios.

Hubo un timeout aislado de `/version` a las `01:59:48.509Z`, recuperado en la siguiente muestra a las `01:59:51.088Z`, **antes** del cambio de color. La causa no está identificada; por eso esta corrida no se presenta como 0 % de errores. La ventana completa tuvo 1/74 peticiones HTTP fallidas.

Una repetición con el mismo timeout de 2 s por petición quedó registrada en [`VM-A2.csv`](VM-A2.csv). Observó el paso de Green `ci-5-7f1accc587b7` a Blue `ci-6-7f1accc587b7`. Jenkins #6 terminó en `SUCCESS` en 39 s; su [Console Output](jenkins-vm-6.log) muestra smoke test, `Switch OK: green -> blue` y 20 observaciones públicas correctas. Hubo un timeout aislado de `/api/turnos` a las `02:05:32.391Z`, antes del cambio; luego ambas rutas respondieron 200 en todas las muestras del cambio y posteriores. La ventana completa tuvo 1/84 peticiones HTTP fallidas. La repetición ayuda a localizar los timeouts en el monitoreo externo previo al cambio, aunque no identifica su causa.

La tercera corrida [`VM-A3.csv`](VM-A3.csv), con `--timeout 5`, observó el paso de Blue `ci-6-7f1accc587b7` a Green `ci-7-7f1accc587b7` entre la última muestra Blue y la primera Green a las `02:08:55.259Z`. Las 43 muestras fueron correctas: 86/86 respuestas HTTP exitosas y 0 errores funcionales. Jenkins #7 informó `PIPELINE_DURATION_SECONDS=39` y `Finished: SUCCESS`. El aumento del timeout cambió la sensibilidad del monitor a las respuestas lentas; se declara para que la medida sea reproducible.

En `pre-test`, las 15 muestras externas mantuvieron Green `ci-1-7f1accc587b7` con las dos rutas en 200. El [extracto del build #2](jenkins-vm-2-extract.log) documenta el fallo PHPUnit controlado, la omisión de las etapas de conexión, construcción, despliegue y cambio de tráfico, `PIPELINE_DURATION_SECONDS=2` y `Finished: FAILURE`. Se trata del resultado esperado de B.

El intervalo solicitado al monitor fue 0,25 s. Por la latencia de las consultas a través del túnel SSH, la separación real entre muestras fue aproximadamente 1,3 s. Estas medidas describen la ventana observada en la demo; no equivalen a disponibilidad de producción.
