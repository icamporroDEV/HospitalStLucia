# Domain Docs

Reglas para consumir la documentación de dominio de este repositorio.

## Antes de explorar

- Leer `CONTEXT.md` en la raíz, si existe.
- Leer los ADR relevantes bajo `docs/adr/`, si existen.
- Si alguno de estos archivos todavía no existe, continuar sin bloquear el trabajo. La documentación de dominio se crea cuando se resuelven términos o decisiones reales.

## Layout

Este es un repositorio **single-context**:

```text
/
├── CONTEXT.md
├── docs/adr/
└── app/
```

## Vocabulario

Usar en issues, tests, documentación y código los términos definidos en `CONTEXT.md`. Si falta un concepto, registrarlo como una brecha de modelado en lugar de introducir sinónimos inconsistentes.

## Conflictos con ADR

Si una propuesta contradice un ADR existente, se debe señalar de forma explícita antes de modificar la decisión.
