<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Database;
use FishyBoat21\ExtendOrm\ExtendORMException;

final class DatabaseTest extends TestCase
{
    public function testGetInstanceBeforeBootThrowsHelpfully(): void
    {
        Database::Reset();

        try {
            Database::GetInstance();
            $this->fail('Expected ExtendORMException before Boot().');
        } catch (ExtendORMException $e) {
            $this->assertStringContainsString('has not been booted', $e->getMessage());
        } finally {
            Database::Boot($this->pdo);
        }
    }

    public function testBootExposesTheConnection(): void
    {
        $this->assertSame($this->pdo, Database::GetInstance()->GetConnection());
    }

    public function testResetForgetsTheConnection(): void
    {
        Database::Reset();

        $this->expectException(ExtendORMException::class);
        Database::GetInstance();
    }

    public function testRebootingReplacesThePreviousConnection(): void
    {
        Database::Reset();
        $replacement = new \PDO('sqlite::memory:');
        Database::Boot($replacement);

        $this->assertSame($replacement, Database::GetInstance()->GetConnection());
    }
}
