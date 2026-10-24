# Local Blue-Green Lab Design

## Goal

Demostrar localmente un cambio de tráfico reversible entre dos contenedores del HIS sin que los usuarios cambien de URL.

## Topology

```text
127.0.0.1:8080 -> Nginx -> his-blue:8080
                           his-green:8080
```

- Nginx es la única entrada normal.
- Blue y Green se publican únicamente en `127.0.0.1:18081` y `127.0.0.1:18082` para diagnóstico local.
- Ambos slots se crean desde la imagen inmutable del PR #2.
- El upstream activo se conserva en un volumen Docker nombrado y comienza en Blue.

## Operational contract

- `current-color` consulta el endpoint público y devuelve `blue` o `green`.
- `deploy-color <color> <image>` recrea únicamente el slot indicado, espera salud y ejecuta smoke sin cambiar tráfico.
- `switch-color <color>` valida salud y función, cambia la configuración de forma atómica, ejecuta `nginx -t` y recarga Nginx.
- `rollback-color <color>` vuelve explícitamente al color LKG mediante el mismo switch seguro.

Los scripts son no interactivos, aceptan solo valores validados y devuelven códigos POSIX para que Jenkins pueda invocarlos después.

## Security and failure controls

- Colores limitados a `blue|green`; imágenes limitadas a caracteres seguros.
- Sin `eval`, modo privilegiado ni red del host.
- Contenedores sin privilegios adicionales y con `no-new-privileges`.
- No se cambia tráfico hacia un slot sin healthcheck y smoke funcional.
- Nginx valida la configuración antes de recargar y restaura el upstream anterior si falla.
- El volumen conserva el color activo cuando Nginx se recrea.

## Scope

Este PR no agrega Jenkins, AWS, fallas controladas ni módulos del HIS. Solo construye y prueba el laboratorio Blue-Green local.
