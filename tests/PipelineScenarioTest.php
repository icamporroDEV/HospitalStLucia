<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PipelineScenarioTest extends TestCase
{
    public function testCandidateIsRejectedBeforeDeploymentWhenRequested(): void
    {
        if (getenv('DEMO_SCENARIO') === 'pre-test') {
            self::fail('Falla controlada: rechazo en pruebas antes de desplegar');
        }

        self::assertTrue(true);
    }
}
