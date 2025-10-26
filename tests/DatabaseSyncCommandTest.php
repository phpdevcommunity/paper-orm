<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\Console\CommandParser;
use PhpDevCommunity\Console\CommandRunner;
use PhpDevCommunity\Console\Output;
use PhpDevCommunity\PaperORM\Collector\EntityDirCollector;
use PhpDevCommunity\PaperORM\Command\DatabaseSyncCommand;
use PhpDevCommunity\PaperORM\Command\ShowTablesCommand;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\IntColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\PaperORM\PaperConfiguration;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class DatabaseSyncCommandTest extends TestCase
{

    protected function setUp(): void
    {

    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            $this->executeTest($em);
            $em->getConnection()->close();
        }
    }

    private function executeTest(EntityManager $em)
    {
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();

        $paperMigration = PaperMigration::create($em, 'mig_versions', __DIR__ . '/migrations');
        $runner = new CommandRunner([
            new DatabaseSyncCommand($paperMigration, EntityDirCollector::bootstrap([__DIR__ . '/Entity']), 'test'),
        ]);

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:database:sync', '--no-execute']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));
        $this->assertEquals(0, $code);
        $this->assertStringContains( implode(' ', $out), "[INFO] Preview mode only — SQL statements were displayed but NOT executed.");

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:database:sync']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));

        $this->assertEquals(0, $code);
        $this->assertStringContains( implode(' ', $out), "✔ Executed:");
        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:database:sync']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));


        $this->assertEquals(0, $code);
        $this->assertStringContains( implode(' ', $out), "No differences detected — all entities are already in sync with the database schema.");

        $platform->dropDatabase();
    }
}
