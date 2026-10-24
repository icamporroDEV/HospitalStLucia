<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DockerPackagingTest extends TestCase
{
    public function testDockerImageContractIsDeclared(): void
    {
        $root = dirname(__DIR__);
        $dockerfile = $this->readRequiredFile($root . '/Dockerfile');

        self::assertStringContainsString('FROM php:8.3.', $dockerfile);
        self::assertStringNotContainsString('COPY . .', $dockerfile);
        self::assertStringContainsString('USER www-data', $dockerfile);
        self::assertStringContainsString('EXPOSE 8080', $dockerfile);
        self::assertStringContainsString('HEALTHCHECK', $dockerfile);
        self::assertStringContainsString('APP_VERSION', $dockerfile);
        self::assertStringContainsString('APP_COMMIT_SHA', $dockerfile);
        self::assertStringContainsString('APP_BUILD_TIME', $dockerfile);
    }

    public function testApachePublishesOnlyThePublicDirectory(): void
    {
        $config = $this->readRequiredFile(dirname(__DIR__) . '/apache/000-default.conf');

        self::assertStringContainsString('DocumentRoot /var/www/html/public', $config);
        self::assertStringContainsString('Options -Indexes', $config);
        self::assertStringContainsString('FallbackResource /index.php', $config);
        self::assertStringContainsString('ServerTokens Prod', $config);
        self::assertStringContainsString('ServerSignature Off', $config);
    }

    public function testPhpVersionIsNotExposedInHttpHeaders(): void
    {
        $dockerfile = $this->readRequiredFile(dirname(__DIR__) . '/Dockerfile');

        self::assertStringContainsString('expose_php=Off', $dockerfile);
    }

    public function testDockerContextExcludesLocalAndSensitiveFiles(): void
    {
        $dockerignore = $this->readRequiredFile(dirname(__DIR__) . '/.dockerignore');

        foreach (['.git', '.env', 'vendor', 'docs'] as $excludedPath) {
            self::assertStringContainsString($excludedPath, $dockerignore);
        }
    }

    public function testSmokeTestUsesBoundedRetriesAndTimeouts(): void
    {
        $path = dirname(__DIR__) . '/tests/Contract/container-smoke.sh';
        $script = $this->readRequiredFile($path);

        self::assertTrue(is_executable($path));
        self::assertStringContainsString('--connect-timeout 2', $script);
        self::assertStringContainsString('--max-time 5', $script);
        self::assertStringContainsString('"$attempt" -le 3', $script);
    }

    private function readRequiredFile(string $path): string
    {
        self::assertFileExists($path);

        $contents = file_get_contents($path);
        self::assertIsString($contents);

        return $contents;
    }
}
