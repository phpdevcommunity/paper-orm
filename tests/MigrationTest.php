<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\IntColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;

class MigrationTest extends TestCase
{
    private EntityManager $em;
    private string $migrationDir;
    private PaperMigration $paperMigration;
    protected function setUp(): void
    {
        $this->em = new EntityManager([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
        ]);
        $this->migrationDir = __DIR__ . '/migrations';
        $this->paperMigration = PaperMigration::create($this->em, 'mig_versions', $this->migrationDir);

    }
    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
        $folder = $this->migrationDir;
        array_map('unlink', glob("$folder/*.*"));
    }

    protected function execute(): void
    {
        $this->em->getConnection()->close();
        $this->testDiff();
        $this->testExecute();
        $this->testColumnModification();
        $this->testFailedMigration();
    }

    private function testDiff() :   void
    {
        $this->em->getConnection()->close();
        $migrationFile = $this->paperMigration->diffEntities([
            UserTest::class,
            PostTest::class
        ]);

        $this->assertStringContains(file_get_contents($migrationFile), '-- UP MIGRATION --');
        $this->assertStringContains(file_get_contents($migrationFile), 'CREATE TABLE user (id INTEGER PRIMARY KEY NOT NULL,firstname VARCHAR(255) NOT NULL,lastname VARCHAR(255) NOT NULL,email VARCHAR(255) NOT NULL,password VARCHAR(255) NOT NULL,is_active BOOLEAN NOT NULL,created_at DATETIME NOT NULL,last_post_id INTEGER NOT NULL,FOREIGN KEY (last_post_id) REFERENCES post (id));');
        $this->assertStringContains(file_get_contents($migrationFile), 'CREATE INDEX IX_8D93D6492D053F64 ON user (last_post_id);');
        $this->assertStringContains(file_get_contents($migrationFile), 'CREATE TABLE post (id INTEGER PRIMARY KEY NOT NULL,title VARCHAR(255) NOT NULL,content VARCHAR(255) NOT NULL,created_at DATETIME NOT NULL,user_id INTEGER NOT NULL,FOREIGN KEY (user_id) REFERENCES user (id));');
        $this->assertStringContains(file_get_contents($migrationFile), 'CREATE INDEX IX_5A8A6C8DA76ED395 ON post (user_id);');

        $this->assertStringContains(file_get_contents($migrationFile), '-- DOWN MIGRATION --');
        $this->assertStringContains(file_get_contents($migrationFile), 'DROP INDEX IX_8D93D6492D053F64;');
        $this->assertStringContains(file_get_contents($migrationFile), 'DROP TABLE user;');
        $this->assertStringContains(file_get_contents($migrationFile), 'DROP INDEX IX_5A8A6C8DA76ED395;');
        $this->assertStringContains(file_get_contents($migrationFile), 'DROP TABLE post;');
     }

    private function testExecute(): void
    {
        $this->paperMigration->migrate();
        $successList = $this->paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 1);

        $migrationFile = $this->paperMigration->diffEntities([UserTest::class]);
        $this->assertNull($migrationFile);
    }

    private function testColumnModification(): void
    {
        $userColumns = ColumnMapper::getColumns(UserTest::class);
        $userColumns[3] = new StringColumn('email', 'email', 255, true, null, true);
        $userColumns[] = new IntColumn('childs', 'childs', false, 0);
        $migrationFile = $this->paperMigration->diff([
            'user' => [
                'columns' => $userColumns,
                'indexes' => []
            ]
        ]);
        $this->paperMigration->migrate();
        $successList = $this->paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 1);
        $this->assertEquals(pathinfo($migrationFile, PATHINFO_FILENAME), $successList[0]);
        $this->assertStringContains(file_get_contents( $migrationFile ), 'ALTER TABLE user ADD childs INTEGER NOT NULL DEFAULT 0;');
        $this->assertStringContains(file_get_contents( $migrationFile ), 'CREATE UNIQUE INDEX IX_8D93D649E7927C74 ON user (email);');
        $this->assertStringContains(file_get_contents( $migrationFile ), 'DROP INDEX IX_8D93D649E7927C74;');
    }

    private function testFailedMigration(): void
    {
        $userColumns = ColumnMapper::getColumns(UserTest::class);
        $userColumns[3] = new StringColumn('email', 'email', 100, true, null, true);
        $this->paperMigration->diff([
            'user' => [
                'columns' => $userColumns,
                'indexes' => []
            ]
        ]);

        $this->expectException( \RuntimeException::class, function ()  {
            $this->paperMigration->migrate();
        });
        $successList = $this->paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 0);

    }
}
