# Plan mínimo de implementación del HIS

**Objetivo:** construir una demo pequeña que permita mostrar evolución funcional y, más adelante, Jenkins con despliegue Blue-Green.

**Decisión:** PHP 8.3 sin framework, base de datos ni capas internas. La aplicación completa vive en un único punto de entrada, lee un array de datos ficticios y usa HTML renderizado en servidor.

## Etapa 1 — Base funcional (ahora)

- Pantalla legacy de Turnos basada en el diseño A aprobado.
- Tres turnos ficticios de solo lectura.
- Endpoints `/api/turnos`, `/version`, `/health/live` y `/health/ready`.
- Una prueba automática sobre el dataset.

Resultado esperado: una aplicación visible y demostrable, sin infraestructura adicional.

## Etapa 2 — Pipeline de la demo

- Un `Dockerfile` para empaquetar exactamente esta aplicación.
- Un `Jenkinsfile` corto: test, build, deploy al color inactivo, smoke test y cambio de tráfico.
- Nginx como único punto público para alternar entre Blue y Green.
- Un mecanismo simple y determinista para demostrar rechazo previo y rollback posterior.

Resultado esperado: tres ejecuciones grabables — despliegue exitoso, rechazo antes del despliegue y rollback.

## Etapa 3 — Releases incrementales

- `v1.1.0`: Estudios de solo lectura.
- `v1.2.0`: Medicamentos de solo lectura.

Cada módulo se agrega con otro archivo de datos y una vista sencilla. No se incorpora autenticación, escritura de datos ni base de datos porque no aportan valor a esta demostración.
