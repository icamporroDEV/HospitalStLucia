# Laboratorio Blue-Green local

## Componentes

- `his-blue`: slot Blue, diagnóstico en `127.0.0.1:18081`.
- `his-green`: slot Green, diagnóstico en `127.0.0.1:18082`.
- `nginx`: entrada pública única en `127.0.0.1:8080`.

Los tres servicios comparten una red Docker del proyecto. Nginx conserva el upstream activo en el volumen nombrado `nginx-active-config`; por eso recrearlo no cambia el color.

## Arranque manual

```bash
scripts/build-local-image 1.0.0
mkdir -p deploy/runtime
cp deploy/state.env.example deploy/runtime/state.env
docker compose --env-file deploy/runtime/state.env up -d --build
```

Comprobar el color público y ambos slots:

```bash
deploy/bin/current-color
curl http://127.0.0.1:8080/version
curl http://127.0.0.1:18081/version
curl http://127.0.0.1:18082/version
```

## Operaciones

```bash
deploy/bin/deploy-color green hospital-st-lucia:1.1.0
deploy/bin/switch-color green
deploy/bin/rollback-color blue
```

`deploy-color` solo recrea y valida el slot indicado. `switch-color` valida salud y Turnos antes de recargar Nginx. `rollback-color` recibe explícitamente el color estable anterior.

Los scripts rechazan desplegar sobre el color activo. El estado de las imágenes vive en `deploy/runtime/state.env`, que no se versiona.

## Prueba integral y limpieza

```bash
tests/Contract/blue-green-smoke.sh
docker compose --env-file deploy/runtime/state.env down -v --remove-orphans
```

La prueba integral comienza con un volumen vacío, ejecuta Blue → Green, recrea Nginx para validar persistencia y vuelve a Blue. Al finalizar elimina los contenedores, la red y el volumen de prueba.
