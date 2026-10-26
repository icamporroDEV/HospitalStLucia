## Summary

```text
Jenkinsfile                 Pipeline A/B/C: pruebas, candidata, switch y rollback
deploy/ y docker-compose*  Slots Blue/Green, Nginx y laboratorio EC2 rootless
scripts/                   Monitor externo y guía interactiva de EC2
docs/                      Informe final, guion y evidencias reproducibles
```

La PoC usa datos ficticios y no modifica un HIS de producción.

## Evidence

- **Antes:** el laboratorio no tenía un pipeline que desplegara en el color inactivo y revirtiera automáticamente una falla funcional posterior al switch.
- **Después, local:** nueve corridas (3 por escenario), con CSV y Console Output en [evidencia local](https://github.com/valenchillemi/HospitalStLucia/blob/1185a56/docs/evidence/blue-green-local-2026-10-25/README.md). Las A tuvieron 0/2524 errores HTTP; las C recuperaron el servicio en 3,50–3,75 s observados.
- **Después, EC2:** Jenkins #7 `SUCCESS` en 39 s, Blue → Green, 0/86 errores HTTP con timeout externo de 5 s. Jenkins #3 ejecutó rollback Blue → Green; recuperación observada 3,907 s. CSV y límites metodológicos en [evidencia VM](https://github.com/valenchillemi/HospitalStLucia/blob/1185a56/docs/evidence/blue-green-vm-2026-10-25/README.md).
- [Informe final editable](https://github.com/valenchillemi/HospitalStLucia/blob/1185a56/docs/informe-final.docx) · [Markdown](https://github.com/valenchillemi/HospitalStLucia/blob/1185a56/docs/informe-final.md) · [índice de entrega](https://github.com/valenchillemi/HospitalStLucia/blob/1185a56/docs/entrega.md).
- Pendiente de la entrega grupal: confirmar costo AWS y estado de Trello/Gantt; adjuntar Console Output VM #2 y #6 si se incluyen como resultados cerrados.

## Merge Danger

**Door:** two-way. El cambio se puede revertir por commit; la VM de demostración queda fuera del repositorio.

**Blast Radius:** laboratorio. La imagen y Jenkins usan Docker; la rama no incorpora datos clínicos reales. Revisar la configuración del socket rootless y el flujo de rollback antes de integrar.
