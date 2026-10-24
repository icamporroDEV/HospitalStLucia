# Local Blue-Green Lab Implementation Plan

**Goal:** levantar Blue, Green y Nginx localmente y ofrecer comandos seguros de deploy, switch y rollback.

**Architecture:** Docker Compose ejecuta dos slots simétricos del HIS y un Nginx no-root. Un volumen nombrado conserva el upstream activo. Los scripts ocultan los detalles de Compose/Nginx detrás del contrato que consumirá Jenkins.

**Tech stack:** Docker Compose, Nginx, POSIX shell, curl y PHPUnit.

## Task 1: Contract tests

Crear primero pruebas que exijan tres servicios, red aislada, puertos locales, configuración Nginx, volumen activo y cuatro scripts ejecutables. Ejecutar PHPUnit y observar RED por archivos ausentes.

## Task 2: Compose and Nginx

Crear `docker-compose.yml` y `nginx/` con una imagen no-root, upstreams Blue/Green y configuración inicial Blue. Verificar `docker compose config` y PHPUnit GREEN.

## Task 3: Operational scripts

Crear `deploy/bin/_common.sh`, `current-color`, `deploy-color`, `switch-color` y `rollback-color`. Validar argumentos, salud, smoke, reemplazo atómico, `nginx -t` y recarga graceful.

## Task 4: End-to-end contract

Desde un volumen limpio: construir la imagen local, levantar los tres servicios, comprobar Blue público, hacer switch a Green, recrear Nginx, comprobar persistencia y hacer rollback a Blue.

## Task 5: Documentation and review

Documentar el procedimiento manual en README, ejecutar suite completa, escaneo de secretos/patrones inseguros, revisión funcional y auditoría de seguridad.

**Commit:** `feat(deploy): add local blue green lab`
