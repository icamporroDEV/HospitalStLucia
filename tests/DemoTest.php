<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DemoTest extends TestCase
{
    public function testDemoHasFictionalAppointments(): void
    {
        $turnos = require dirname(__DIR__) . '/data/appointments.php';

        self::assertCount(3, $turnos);
        self::assertSame('T-1001', $turnos[0]['id']);
        self::assertSame('CONFIRMADO', $turnos[0]['estado']);
    }
}
