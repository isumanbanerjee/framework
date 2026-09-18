<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private string $dir;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/migrations_' . uniqid();
        mkdir($this->dir);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    /**
     * Write a migration file that creates/drops a table. The class name is
     * unique per test to avoid redeclaration across the PHPUnit process.
     */
    private function writeMigration(string $timestampAndName, string $class, string $table): void
    {
        $php = <<<PHP
        <?php
        use Core\\Model\\Database\\Migration;
        use Core\\Model\\Database\\Schema;
        use Core\\Model\\Database\\Blueprint;

        class {$class} extends Migration
        {
            public function up(Schema \$schema): void
            {
                \$schema->create('{$table}', function (Blueprint \$t) {
                    \$t->id();
                    \$t->string('name');
                });
            }

            public function down(Schema \$schema): void
            {
                \$schema->dropIfExists('{$table}');
            }
        }
        PHP;

        file_put_contents($this->dir . '/' . $timestampAndName . '.php', $php);
    }

    public function testRunAppliesPendingMigrations(): void
    {
        $this->writeMigration('2026_01_01_000001_create_alpha_table', 'CreateAlphaTable', 'alpha');
        $this->writeMigration('2026_01_01_000002_create_beta_table', 'CreateBetaTable', 'beta');

        $migrator = new Migrator($this->pdo, $this->dir);
        $ran = $migrator->run();

        $this->assertCount(2, $ran);
        $this->assertContains('2026_01_01_000001_create_alpha_table', $ran);
        $this->assertTrue((new \Core\Model\Database\Schema($this->pdo))->hasTable('alpha'));
        $this->assertTrue((new \Core\Model\Database\Schema($this->pdo))->hasTable('beta'));
    }

    public function testRunIsIdempotent(): void
    {
        $this->writeMigration('2026_02_01_000001_create_gamma_table', 'CreateGammaTable', 'gamma');

        $migrator = new Migrator($this->pdo, $this->dir);
        $this->assertCount(1, $migrator->run());
        $this->assertCount(0, $migrator->run());
    }

    public function testAppliedMigrationsTracked(): void
    {
        $this->writeMigration('2026_03_01_000001_create_delta_table', 'CreateDeltaTable', 'delta');

        $migrator = new Migrator($this->pdo, $this->dir);
        $migrator->run();

        $this->assertSame(
            ['2026_03_01_000001_create_delta_table'],
            $migrator->appliedMigrations()
        );
    }

    public function testRollbackReversesLastBatch(): void
    {
        $this->writeMigration('2026_04_01_000001_create_epsilon_table', 'CreateEpsilonTable', 'epsilon');
        $this->writeMigration('2026_04_01_000002_create_zeta_table', 'CreateZetaTable', 'zeta');

        $migrator = new Migrator($this->pdo, $this->dir);
        $migrator->run();

        $schema = new \Core\Model\Database\Schema($this->pdo);
        $this->assertTrue($schema->hasTable('epsilon'));

        $rolledBack = $migrator->rollback();

        $this->assertCount(2, $rolledBack);
        $this->assertFalse($schema->hasTable('epsilon'));
        $this->assertFalse($schema->hasTable('zeta'));
        $this->assertSame([], $migrator->appliedMigrations());
    }

    public function testRollbackWithNothingAppliedReturnsEmpty(): void
    {
        $migrator = new Migrator($this->pdo, $this->dir);
        $this->assertSame([], $migrator->rollback());
    }

    public function testSeparateBatchesRollbackIndependently(): void
    {
        $this->writeMigration('2026_05_01_000001_create_eta_table', 'CreateEtaTable', 'eta');
        $migrator = new Migrator($this->pdo, $this->dir);
        $migrator->run(); // batch 1

        $this->writeMigration('2026_05_01_000002_create_theta_table', 'CreateThetaTable', 'theta');
        $migrator->run(); // batch 2

        $schema = new \Core\Model\Database\Schema($this->pdo);
        $migrator->rollback(); // reverses only batch 2

        $this->assertTrue($schema->hasTable('eta'));
        $this->assertFalse($schema->hasTable('theta'));
    }
}
