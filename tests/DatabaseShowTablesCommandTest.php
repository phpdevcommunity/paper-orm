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

class DatabaseShowTablesCommandTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
        ]);

    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    protected function execute(): void
    {
        $platform = $this->em->createDatabasePlatform();
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
            new JoinColumn('user', 'user_id', 'id', UserTest::class),
            new StringColumn('title'),
            new StringColumn('content'),
        ]);

        $runner = new CommandRunner([
            new ShowTablesCommand($this->em)
        ]);

        $code = $runner->run(new CommandParser(['', 'paper:show:tables', '--columns']), new Output(function ($message) use(&$countMessages) {
        }));
        $this->assertEquals(0, $code);
    }
}
