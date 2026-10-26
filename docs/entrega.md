# Material de entrega de la PoC

| Material | Ubicación | Contenido |
| --- | --- | --- |
| Informe final para editar | [Word](informe-final.docx) y [Markdown](informe-final.md) | Problema, arquitectura, método, resultados, límites, riesgos y conclusiones. |
| Nueve corridas locales | [Evidencia local](evidence/blue-green-local-2026-10-25/README.md) | Tres A, tres B y tres C; CSV y Console Output de Jenkins. |
| Ensayo en EC2 | [Evidencia VM](evidence/blue-green-vm-2026-10-25/README.md) | Cinco CSV, despliegue exitoso y rollback; pendientes de logs #2 y #6. |
| Reproducción técnica | [Guía VM](vm-demo.md), [guía de despliegue](../deploy/README.md) y [monitoreo](monitoring.md) | Configuración, escenarios y comandos. |
| Presentación oral | [Guion de demo](guion-demo.md) | Secuencia sugerida, cifras que mostrar y aclaraciones metodológicas. |
| Trabajo propuesto para integrar | [PR #4](https://github.com/icamporroDEV/HospitalStLucia/pull/4) | Pipeline, laboratorio y documentación; requiere revisión del propietario. |

## Datos que debe confirmar el grupo antes de entregar

- Importe efectivamente consumido en AWS, tipo de instancia, volumen y estado del presupuesto.
- Estado final del tablero Trello y del Gantt citado en la segunda entrega.
- Resultado y duración de los builds VM #2 y #6 si se van a incluir como evidencia cerrada.
- Revisión de nombres, fecha de entrega, formato exigido por la cátedra y conclusiones firmadas por los cuatro integrantes.

No completar esos campos con estimaciones presentadas como hechos. Los resultados ya medidos y sus límites están desarrollados en el informe.
