# Evidencia de las nueve corridas Blue/Green

Se ejecutaron tres repeticiones de cada escenario en el laboratorio local con Docker Desktop, Jenkins (`localhost:8081`) y la URL pública de la aplicación (`localhost:8080`). Jenkins leyó `Jenkinsfile` de la rama `codex/jenkins-blue-green` del fork `valenchillemi/HospitalStLucia`, en el commit `c819a5d810134364cf89d5f671bec530b0c1bcfc`.

Un proceso externo a Jenkins tomó una muestra cada 0,25 segundos. Cada muestra consultó `/api/turnos` y `/version`. Los CSV registran el estado HTTP, la validez funcional de Turnos, la versión y el color. Las fechas dentro de CSV y logs están en UTC, por eso indican 26 de octubre aunque la ejecución local comenzó el 25 de octubre en Argentina. Los logs adjuntos son los Console Output de Jenkins sin los metadatos internos `ha:`.

| Corrida | Jenkins | Resultado | Muestras | Error HTTP | Error funcional | MTTD | Recuperación observada | Pipeline |
| --- | ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| [A1](A1.csv) | [#8](jenkins-8.log) | SUCCESS | 483 | 0/966 (0 %) | 0/483 (0 %) | — | — | 87 s |
| [A2](A2.csv) | [#11](jenkins-11.log) | SUCCESS | 395 | 0/790 (0 %) | 0/395 (0 %) | — | — | 86 s |
| [A3](A3.csv) | [#14](jenkins-14.log) | SUCCESS | 384 | 0/768 (0 %) | 0/384 (0 %) | — | — | 85 s |
| [B1](B1.csv) | [#9](jenkins-9.log) | FAILURE esperado en pruebas | 116 | 0/232 (0 %) | 0/116 (0 %) | — | — | 6 s |
| [B2](B2.csv) | [#12](jenkins-12.log) | FAILURE esperado en pruebas | 68 | 0/136 (0 %) | 0/68 (0 %) | — | — | 5 s |
| [B3](B3.csv) | [#15](jenkins-15.log) | FAILURE esperado en pruebas | 63 | 0/126 (0 %) | 0/63 (0 %) | — | — | 6 s |
| [C1](C1.csv) | [#10](jenkins-10.log) | FAILURE esperado con rollback | 368 | 15/736 (2,038 %) | 15/368 (4,076 %) | 1,982 s | 3,75 s | 73 s |
| [C2](C2.csv) | [#13](jenkins-13.log) | FAILURE esperado con rollback | 332 | 14/664 (2,108 %) | 14/332 (4,217 %) | 2,187 s | 3,50 s | 73 s |
| [C3](C3.csv) | [#16](jenkins-16.log) | FAILURE esperado con rollback | 327 | 14/654 (2,141 %) | 14/327 (4,281 %) | 1,949 s | 3,50 s | 71 s |

## Qué demuestran las corridas

- **A:** Jenkins construyó y validó la candidata, cambió el tráfico y terminó en `SUCCESS`. Hubo 0 respuestas HTTP fallidas de 2524 peticiones externas en las tres repeticiones.
- **B:** la prueba PHPUnit de rechazo controlado falló antes de conectar con el laboratorio. Jenkins omitió construcción y despliegue; la versión pública se mantuvo. Hubo 0 respuestas HTTP fallidas de 494 peticiones externas. El `FAILURE` del job es el resultado esperado del escenario.
- **C:** la candidata pasó pruebas y smoke test, recibió el tráfico, falló funcionalmente y Jenkins registró tres fallos consecutivos antes de ejecutar rollback al color y versión anteriores. En las tres repeticiones, `/api/turnos` respondió temporalmente 503; `/version` siguió respondiendo 200. Hubo 43 respuestas HTTP fallidas de 2054 peticiones externas (2,093 %) y 43 muestras funcionales fallidas de 1027 (4,187 %). El `FAILURE` final del job refleja el rechazo de la candidata tras restaurar el servicio.

En C, el MTTD medio fue **2,039 s** desde el primer fallo visto por el monitor externo hasta el tercer fallo registrado por Jenkins. La recuperación observada media fue **3,583 s** y la peor fue **3,75 s**, por debajo de la meta de 30 s en este laboratorio. La duración media del pipeline fue 86 s en A, 5,667 s en B y 72,333 s en C.

El MTTD se calculó con el primer `timestamp_utc` fallido del CSV y el timestamp del tercer fallo en el log de Jenkins: `00:27:18.198Z` (C1), `00:40:16.290Z` (C2) y `00:48:12.123Z` (C3). La recuperación observada va del primer fallo a la primera muestra funcional correcta tras el rollback; su resolución depende del muestreo de 0,25 s y de la latencia HTTP. Las tasas son de esta ventana de medición, no una disponibilidad de producción.

Al terminar las pruebas quedó público **Green `ci-14-c819a5d81013`**. Blue se restauró a la imagen sana `ci-11-c819a5d81013`; ambos slots quedaron sin modo de fallo y `/api/turnos` respondió 200. El procedimiento para repetir las mediciones está en [monitoring.md](../../monitoring.md).
