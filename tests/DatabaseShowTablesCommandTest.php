<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\Console\CommandParser;
use PhpDevCommunity\Console\CommandRunner;
use PhpDevCommunity\Console\Output;
use PhpDevCommunity\PaperORM\Command\ShowTablesCommand;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\IntColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class DatabaseShowTablesCommandTest extends TestCase
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
            $em = new EntityManager($params);
            $this->executeTest($em);
            $em->getConnection()->close();
        }
    }

    private function executeTest(EntityManager $em)
    {

        $platform = $em->createDatabasePlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();
        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $platform->createTable('post', [
            new PrimaryKeyColumn('id'),
            new JoinColumn('user_id', UserTest::class),
            new StringColumn('title'),
            new StringColumn('content'),
        ]);

        $runner = new CommandRunner([
            new ShowTablesCommand($em)
        ]);

        $code = $runner->run(new CommandParser(['', 'paper:show:tables', '--columns']), new Output(function ($message) use(&$countMessages) {
        }));
        $this->assertEquals(0, $code);

    }
}
