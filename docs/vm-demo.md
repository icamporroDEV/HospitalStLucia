# Demo Blue/Green en una VM de AWS

Esta guía reproduce el laboratorio en una instancia EC2 Ubuntu 24.04 LTS x86_64. La aplicación y Jenkins escuchan solo en `127.0.0.1` de la VM; se accede desde Windows mediante un túnel SSH. No se usan datos clínicos reales.

Para recorrer los pasos de AWS de forma interactiva desde **Git Bash** en la PC, ejecutar `bash scripts/aws-ec2-wizard.sh`. El asistente abre las páginas oficiales y guarda solo región, DNS/IP y ruta local del `.pem` en `.env.vm` (ignorado por Git); las claves y los datos de pago nunca se copian al script. También se puede seguir esta guía sin el asistente.

## 1. Cuenta, costo y acceso

1. Si se crea una cuenta nueva, elegir el plan **Free** y comprobar en la consola los créditos y la elegibilidad de la instancia antes de lanzarla. AWS enumera `m7i-flex.large` (2 vCPU, 8 GiB) entre los tipos elegibles para cuentas nuevas desde julio de 2025. La elegibilidad depende de la cuenta y la región. Ver [AWS Free Tier](https://aws.amazon.com/free/) y [tipos EC2 elegibles](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/ec2-free-tier-usage.html).
2. En **Billing and Cost Management → Budgets**, crear un presupuesto de costo y una alerta por correo. El presupuesto avisa; no detiene por sí solo los recursos. Ver [guía de AWS Budgets](https://docs.aws.amazon.com/cost-management/latest/userguide/create-cost-budget.html).
3. En **EC2 → Launch instance**, usar Ubuntu Server 24.04 LTS (x86_64), `m7i-flex.large` si la consola confirma su elegibilidad, y un volumen `gp3` de unos 40 GiB para Jenkins, imágenes y builds. Crear un par de claves `.pem` y conservar la clave privada solo en la computadora personal. Elegir una subred con IP pública. Anotar región, ID de instancia y DNS/IP públicos.
4. En el security group, permitir **solo SSH/TCP 22 desde My IP**. No abrir 8080, 8081, 18081, 18082 ni el puerto del daemon Docker. AWS recomienda limitar SSH a la IP propia: [security groups](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/creating-security-group.html). Si cambia la IP de la computadora, actualizar esa regla antes de reconectar.

La cuenta, la selección de plan, el presupuesto y el lanzamiento deben completarse en la consola personal de AWS. No guardar contraseñas, claves `.pem` ni credenciales de AWS en Git, Jenkins o capturas compartidas.

## 2. Conectar por SSH

En PowerShell, sustituir la ruta del `.pem` y el DNS de EC2:

```powershell
ssh -i "C:\ruta\privada\hospital-demo.pem" ubuntu@DNS_PUBLICO_DE_EC2
```

Si OpenSSH muestra `UNPROTECTED PRIVATE KEY FILE` o `Bad permissions`, el `.pem` tiene permisos heredados para otros usuarios. En PowerShell, agregar lectura para la cuenta propia y quitar la herencia **solo a ese archivo** antes de repetir `ssh`:

```powershell
$key = 'C:\ruta\privada\hospital-demo.pem'
icacls.exe $key /grant:r "$($env:USERNAME):(R)"
icacls.exe $key /inheritance:r
icacls.exe $key
```

Ver [solución oficial de AWS para claves privadas sin protección](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/TroubleshootingInstancesConnecting.html). No copiar el contenido de la clave a chats, capturas ni al repositorio.

El usuario habitual de la AMI oficial de Ubuntu es `ubuntu`. Ver [conexión SSH a EC2](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/tutorial-launch-a-test-ec2-instance.html).

## 3. Instalar Docker rootless en Ubuntu

Ejecutar lo siguiente **dentro de la VM nueva**. Seguir las [instrucciones oficiales de Docker para Ubuntu](https://docs.docker.com/engine/install/ubuntu/) si su repositorio cambió:

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl git uidmap dbus-user-session
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin docker-ce-rootless-extras
sudo systemctl disable --now docker.service docker.socket
dockerd-rootless-setuptool.sh install
sudo loginctl enable-linger "$(whoami)"
docker context use rootless
docker info --format '{{json .SecurityOptions}}'
docker compose version
```

Confirmar que `docker info` incluya `rootless`. El daemon y el socket deben pertenecer al usuario `ubuntu`, bajo `/run/user/$(id -u)/docker.sock`. Si la instalación rootless indica un requisito pendiente, resolverlo según [Docker Rootless mode](https://docs.docker.com/engine/security/rootless/) antes de seguir. Esta guía no usa el socket Docker rootful `/var/run/docker.sock`.

## 4. Levantar aplicación y Jenkins

```bash
git clone --branch codex/jenkins-blue-green https://github.com/valenchillemi/HospitalStLucia.git
cd HospitalStLucia
export COMPOSE_PROJECT_NAME=hospital
export XDG_RUNTIME_DIR="/run/user/$(id -u)"
scripts/build-local-image 1.0.0
mkdir -p deploy/runtime
cp deploy/state.env.example deploy/runtime/state.env
mkdir -p deploy/runtime/jenkins-home
docker compose --env-file deploy/runtime/state.env -f docker-compose.yml -f docker-compose.vm.yml up -d --build
docker compose --env-file deploy/runtime/state.env -f docker-compose.yml -f docker-compose.vm.yml ps
curl -fsS http://127.0.0.1:8080/version
curl -fsS http://127.0.0.1:8080/api/turnos
```

La primera construcción de Jenkins instala PHP, Composer, Docker CLI y Compose. Puede tardar varios minutos. Jenkins guarda sus datos en `deploy/runtime/jenkins-home`, creado por el usuario de SSH, y comparte `deploy/runtime/state.env` con el host para que los cambios de Blue/Green sobrevivan a nuevas ejecuciones. La imagen Jenkins usa el socket del **daemon rootless** del usuario de SSH; su proceso `root` dentro del contenedor se mapea al usuario sin privilegios del host.

Para obtener la contraseña inicial de Jenkins:

```bash
docker compose --env-file deploy/runtime/state.env -f docker-compose.yml -f docker-compose.vm.yml exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
```

## 5. Abrir la demo desde Windows

En una PowerShell local distinta, dejar abierto el túnel:

```powershell
ssh -i "C:\ruta\privada\hospital-demo.pem" -N -L 28080:127.0.0.1:8080 -L 28081:127.0.0.1:8081 ubuntu@DNS_PUBLICO_DE_EC2
```

Abrir `http://localhost:28080/version` para la aplicación y `http://localhost:28081` para Jenkins. Los puertos locales 28080/28081 evitan chocar con el laboratorio Docker de la PC. Si la conexión se corta, volver a abrir el túnel; si la VM se detuvo y arrancó de nuevo, revisar su DNS/IP públicos.

## 6. Configurar Jenkins y ejecutar la prueba

1. Desbloquear Jenkins con la contraseña inicial, instalar los plugins sugeridos y crear una cuenta de administración personal. Comprobar que **Pipeline** y **Git** estén disponibles.
2. Crear una tarea **Pipeline**, por ejemplo `hospital-vm`.
3. En **Pipeline → Definition**, elegir **Pipeline script from SCM**. SCM: Git. URL: `https://github.com/valenchillemi/HospitalStLucia.git`. Branch: `*/codex/jenkins-blue-green`. Script Path: `Jenkinsfile`.
4. Guardar y ejecutar **Build with Parameters → happy**. Verificar `Finished: SUCCESS`, que `/version` muestre el nuevo color y que `/api/turnos` siga en 200.
5. Para mostrar rechazo previo y rollback, ejecutar `pre-test` y `post-deploy`. Sus resultados `FAILURE` son esperados; verificar el color público antes/después y el registro `Rollback OK` en el último.

Con el túnel activo, el monitor externo en la PC puede consultar `http://127.0.0.1:28080` con `scripts/availability_monitor.py`, igual que en [las pruebas locales](monitoring.md). Guardar capturas del Console Output, CSV y `/version` como evidencia de la VM, indicando región, tipo de instancia y commit. La vista del navegador y el monitor están fuera de la VM aunque el acceso viaje por SSH.

## 7. Al terminar

Guardar la evidencia antes de eliminar la VM. Desde la consola de EC2, **terminar** la instancia cuando ya no se necesite y comprobar si quedaron volúmenes EBS u otros recursos asociados. Detener una instancia no necesariamente elimina costos de almacenamiento. AWS recomienda terminarla al acabar: [ciclo de vida de EC2](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/LaunchingAndUsingInstances.html).
