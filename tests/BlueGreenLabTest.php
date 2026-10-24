<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BlueGreenLabTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__);
    }

    public function testComposeDeclaresTheThreeIsolatedServices(): void
    {
        $compose = $this->readRequiredFile('docker-compose.yml');

        foreach (['his-blue:', 'his-green:', 'nginx:'] as $service) {
            self::assertStringContainsString($service, $compose);
        }

        self::assertStringContainsString('127.0.0.1:8080:8080', $compose);
        self::assertStringContainsString('127.0.0.1:18081:8080', $compose);
        self::assertStringContainsString('127.0.0.1:18082:8080', $compose);
        self::assertStringContainsString('DEPLOYMENT_COLOR: blue', $compose);
        self::assertStringContainsString('DEPLOYMENT_COLOR: green', $compose);
        self::assertStringContainsString('no-new-privileges:true', $compose);
        self::assertStringContainsString('cap_drop:', $compose);
        self::assertStringContainsString('nginx-active-config:', $compose);
        self::assertStringNotContainsString('privileged: true', $compose);
        self::assertStringNotContainsString('network_mode: host', $compose);
    }

    public function testNginxIsUnprivilegedAndStartsOnBlue(): void
    {
        $dockerfile = $this->readRequiredFile('nginx/Dockerfile');
        $config = $this->readRequiredFile('nginx/nginx.conf');
        $blue = $this->readRequiredFile('nginx/upstreams/blue.conf');
        $green = $this->readRequiredFile('nginx/upstreams/green.conf');

        self::assertStringContainsString('nginx-unprivileged:', $dockerfile);
        self::assertStringContainsString('USER 101', $dockerfile);
        self::assertStringContainsString('listen 8080', $config);
        self::assertStringContainsString('server_tokens off', $config);
        self::assertStringContainsString('/etc/nginx/active/active.conf', $config);
        self::assertStringContainsString('server his-blue:8080', $blue);
        self::assertStringContainsString('server his-green:8080', $green);
    }

    public function testOperationalScriptsExistAndAvoidEval(): void
    {
        foreach (['current-color', 'deploy-color', 'switch-color', 'rollback-color'] as $name) {
            $path = $this->root . '/deploy/bin/' . $name;
            $script = $this->readRequiredFile('deploy/bin/' . $name);

            self::assertTrue(is_executable($path), $name . ' debe ser ejecutable');
            self::assertStringNotContainsString('eval ', $script);
        }

        $common = $this->readRequiredFile('deploy/bin/_common.sh');
        self::assertStringContainsString('blue|green', $common);
        self::assertStringContainsString('container-smoke.sh', $common);
    }

    public function testRuntimeStateIsIgnoredAndExampleIsVersioned(): void
    {
        $gitignore = $this->readRequiredFile('.gitignore');
        $state = $this->readRequiredFile('deploy/state.env.example');

        self::assertStringContainsString('/deploy/runtime/', $gitignore);
        self::assertStringContainsString('BLUE_IMAGE=', $state);
        self::assertStringContainsString('GREEN_IMAGE=', $state);
    }

    private function readRequiredFile(string $relativePath): string
    {
        $path = $this->root . '/' . $relativePath;
        self::assertFileExists($path);

        $contents = file_get_contents($path);
        self::assertIsString($contents);

        return $contents;
    }
}
