<?php

declare(strict_types=1);

$turnos = require dirname(__DIR__) . '/data/appointments.php';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$version = getenv('APP_VERSION') ?: 'dev';
$color = getenv('DEPLOYMENT_COLOR') ?: 'local';

if ($path === '/health/live' || $path === '/health/ready') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($path === '/version') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'version' => $version,
        'commitSha' => getenv('APP_COMMIT_SHA') ?: 'local',
        'buildTime' => getenv('APP_BUILD_TIME') ?: 'unknown',
        'deploymentColor' => $color,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($path === '/api/turnos') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($turnos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($path !== '/' && $path !== '/turnos') {
    http_response_code(404);
    echo 'Página no encontrada';
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$statusClasses = [
    'CONFIRMADO' => 'ok',
    'PENDIENTE' => 'wait',
    'REPROGRAMADO' => 'changed',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clínica Santa Lucía — Mis turnos</title>
    <link rel="stylesheet" href="/assets/legacy.css">
</head>
<body>
    <div class="demo-banner">ENTORNO DE DEMOSTRACIÓN — TODOS LOS DATOS SON FICTICIOS</div>
    <header class="header">
        <span class="logo" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
        <div class="brand"><strong>CLÍNICA SANTA LUCÍA</strong><span>Sistema Integrado de Atención</span></div>
        <div class="user">Bienvenido/a, Paciente Demo<br><a href="#">Ayuda</a> · <a href="#">Salir</a></div>
    </header>
    <div class="layout">
        <nav class="nav" aria-label="Módulos principales">
            <h2>Menú principal</h2>
            <a href="#">Inicio</a>
            <a class="active" href="/turnos">Turnos ›</a>
            <a class="sub active-sub" href="/turnos">Mis próximos turnos</a>
            <span>Estudios <small>próximamente</small></span>
            <span>Historia clínica <small>próximamente</small></span>
            <span>Medicamentos <small>próximamente</small></span>
            <span>Cartilla médica <small>próximamente</small></span>
            <span>Facturación <small>próximamente</small></span>
        </nav>
        <main>
            <div class="breadcrumb">Inicio &gt; Gestión de turnos &gt; Mis próximos turnos</div>
            <h1>Mis próximos turnos</h1>
            <p class="subtitle">Consulte aquí las citas programadas en las distintas sedes de la Clínica.</p>
            <div class="notice"><strong>Importante:</strong> Preséntese 15 minutos antes con documento y credencial.</div>
            <section class="filters">
                <h2>Criterios de búsqueda</h2>
                <div>
                    <label>Desde <input value="20/10/2026" readonly></label>
                    <label>Hasta <input value="31/12/2026" readonly></label>
                    <label>Especialidad <select><option>Todas</option></select></label>
                    <button type="button">BUSCAR</button>
                </div>
            </section>
            <p class="summary"><strong>Turnos encontrados: <?= count($turnos) ?></strong><span>Última actualización: 20/10/2026 18:42</span></p>
            <table>
                <thead><tr><th>Fecha</th><th>Hora</th><th>Especialidad / Estudio</th><th>Profesional</th><th>Sede</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ($turnos as $turno): ?>
                    <tr>
                        <td><?= e($turno['fecha']) ?></td>
                        <td><?= e($turno['hora']) ?></td>
                        <td><strong><?= e($turno['especialidad']) ?></strong></td>
                        <td><?= e($turno['profesional']) ?></td>
                        <td><?= e($turno['sede']) ?></td>
                        <td><span class="status <?= $statusClasses[$turno['estado']] ?? '' ?>"><?= e($turno['estado']) ?></span></td>
                        <td><a href="#">Ver detalle</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    <footer><span>Mesa de ayuda: 0800-000-0000</span><span><b>Sistema HSL v<?= e($version) ?></b> · Entorno <?= e($color) ?> · <strong>DEMO</strong></span></footer>
</body>
</html>
