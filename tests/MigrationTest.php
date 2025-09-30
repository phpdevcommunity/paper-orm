<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\Driver\MariaDBDriver;
use PhpDevCommunity\PaperORM\Driver\SqliteDriver;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\IntColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\UniTester\TestCase;
use RuntimeException;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class MigrationTest extends TestCase
{
    private string $migrationDir;

    protected function setUp(): void
    {
        $this->migrationDir = __DIR__ . '/migrations';
        $this->tearDown();
    }

    protected function tearDown(): void
    {
        $folder = $this->migrationDir;
        array_map('unlink', glob("$folder/*.*"));
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as $params) {
            $em = new EntityManager($params);
            $paperMigration = PaperMigration::create($em, 'mig_versions', $this->migrationDir);
            $platform = $em->createDatabasePlatform();
            $platform->createDatabaseIfNotExists();
            $platform->dropDatabase();
            $platform->createDatabaseIfNotExists();
            $this->testDiff($paperMigration);
            $this->testExecute($paperMigration);
            $this->testColumnModification($paperMigration);
            $this->testFailedMigration($paperMigration);
            $em->getConnection()->close();
            $this->tearDown();
        }
    }


    private function testDiff(PaperMigration $paperMigration): void
    {
        $em = $paperMigration->getEntityManager();
        $driver = $em->getConnection()->getDriver();
        $em->getConnection()->close();
        $migrationFile = $paperMigration->diffEntities([
            UserTest::class,
            PostTest::class
        ]);

        switch (get_class($driver)) {
            case SqliteDriver::class:
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->assertEquals($lines, array (
                    0 => '-- UP MIGRATION --',
                    1 => 'CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,firstname VARCHAR(255) NOT NULL,lastname VARCHAR(255) NOT NULL,email VARCHAR(255) NOT NULL,password VARCHAR(255) NOT NULL,is_active BOOLEAN NOT NULL,created_at DATETIME,last_post_id INTEGER,FOREIGN KEY (last_post_id) REFERENCES post (id) ON DELETE SET NULL ON UPDATE NO ACTION);',
                    2 => 'CREATE UNIQUE INDEX IX_8D93D6492D053F64 ON user (last_post_id);',
                    3 => 'CREATE TABLE post (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,title VARCHAR(255) NOT NULL,content VARCHAR(255) NOT NULL,created_at DATETIME NOT NULL,user_id INTEGER,FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL ON UPDATE NO ACTION);',
                    4 => 'CREATE INDEX IX_5A8A6C8DA76ED395 ON post (user_id);',
                    5 => '-- DOWN MIGRATION --',
                    6 => 'DROP INDEX IX_8D93D6492D053F64;',
                    7 => 'DROP TABLE user;',
                    8 => 'DROP INDEX IX_5A8A6C8DA76ED395;',
                    9 => 'DROP TABLE post;',
                ));
                break;
            case MariaDBDriver::class:
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->assertEquals($lines, array (
                    0 => '-- UP MIGRATION --',
                    1 => 'CREATE TABLE user (id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,firstname VARCHAR(255) NOT NULL,lastname VARCHAR(255) NOT NULL,email VARCHAR(255) NOT NULL,password VARCHAR(255) NOT NULL,is_active TINYINT(1) NOT NULL,created_at DATETIME DEFAULT NULL,last_post_id INT(11) DEFAULT NULL);',
                    2 => 'CREATE UNIQUE INDEX IX_8D93D6492D053F64 ON user (last_post_id);',
                    3 => 'CREATE TABLE post (id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,title VARCHAR(255) NOT NULL,content VARCHAR(255) NOT NULL,created_at DATETIME NOT NULL,user_id INT(11) DEFAULT NULL);',
                    4 => 'CREATE INDEX IX_5A8A6C8DA76ED395 ON post (user_id);',
                    5 => 'ALTER TABLE user ADD CONSTRAINT FK_8D93D6492D053F64 FOREIGN KEY (last_post_id) REFERENCES post(id) ON DELETE SET NULL ON UPDATE NO ACTION;',
                    6 => 'ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL ON UPDATE NO ACTION;',
                    7 => '-- DOWN MIGRATION --',
                    8 => 'ALTER TABLE user DROP FOREIGN KEY FK_8D93D6492D053F64;',
                    9 => 'ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395;',
                    10 => 'DROP INDEX IX_8D93D6492D053F64 ON user;',
                    11 => 'DROP TABLE user;',
                    12 => 'DROP INDEX IX_5A8A6C8DA76ED395 ON post;',
                    13 => 'DROP TABLE post;',
                ));
                break;
            default:
                throw new RuntimeException(sprintf('Driver %s not supported', get_class($driver)));
        }

    }

    private function testExecute(PaperMigration  $paperMigration): void
    {
        $paperMigration->migrate();
        $successList = $paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 1);

        $migrationFile = $paperMigration->diffEntities([UserTest::class]);
        $this->assertNull($migrationFile);
    }

    private function testColumnModification(PaperMigration  $paperMigration): void
    {
        $em = $paperMigration->getEntityManager();
        $driver = $em->getConnection()->getDriver();

        $userColumns = ColumnMapper::getColumns(UserTest::class);
        $userColumns[3] = (new StringColumn(null, 255, true, null, true))->bindProperty('email');
        $userColumns[] = (new IntColumn(null, false, 0))->bindProperty('childs');
        $migrationFile = $paperMigration->diff([
            'user' => [
                'columns' => $userColumns,
                'indexes' => []
            ]
        ]);
        $paperMigration->migrate();
        $successList = $paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 1);
        $this->assertEquals(pathinfo($migrationFile, PATHINFO_FILENAME), $successList[0]);
        switch (get_class($driver)) {
            case SqliteDriver::class:
                $schema = $driver->createDatabaseSchema();
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($schema->supportsDropColumn()) {
                    $this->assertEquals($lines, array (
                        0 => '-- UP MIGRATION --',
                        1 => 'ALTER TABLE user ADD childs INTEGER NOT NULL DEFAULT 0;',
                        2 => 'CREATE UNIQUE INDEX IX_8D93D649E7927C74 ON user (email);',
                        3 => '-- Modify column email is not supported with PhpDevCommunity\\PaperORM\\Schema\\SqliteSchema. Consider creating a new column and migrating the data.;',
                        4 => '-- DOWN MIGRATION --',
                        5 => 'ALTER TABLE user DROP COLUMN childs;',
                        6 => 'DROP INDEX IX_8D93D649E7927C74;',
                    ));
                } else {
                    $this->assertEquals($lines, array (
                        0 => '-- UP MIGRATION --',
                        1 => 'ALTER TABLE user ADD childs INTEGER NOT NULL DEFAULT 0;',
                        2 => 'CREATE UNIQUE INDEX IX_8D93D649E7927C74 ON user (email);',
                        3 => '-- Modify column email is not supported with PhpDevCommunity\\PaperORM\\Schema\\SqliteSchema. Consider creating a new column and migrating the data.;',
                        4 => '-- DOWN MIGRATION --',
                        5 => '-- Drop column childs is not supported with PhpDevCommunity\\PaperORM\\Schema\\SqliteSchema. You might need to manually drop the column.;',
                        6 => 'DROP INDEX IX_8D93D649E7927C74;',
                    ));
                }
                break;
            case MariaDBDriver::class:
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->assertEquals($lines, array (
                    0 => '-- UP MIGRATION --',
                    1 => 'ALTER TABLE user ADD COLUMN childs INT(11) NOT NULL DEFAULT 0;',
                    2 => 'CREATE UNIQUE INDEX IX_8D93D649E7927C74 ON user (email);',
                    3 => 'ALTER TABLE user MODIFY COLUMN email VARCHAR(255) DEFAULT NULL;',
                    4 => '-- DOWN MIGRATION --',
                    5 => 'ALTER TABLE user DROP COLUMN childs;',
                    6 => 'DROP INDEX IX_8D93D649E7927C74 ON user;',
                    7 => 'ALTER TABLE user MODIFY COLUMN email VARCHAR(255) NOT NULL;',
                ));
                break;
            default:
                throw new RuntimeException(sprintf('Driver %s not supported', get_class($driver)));
        }

    }

    private function testFailedMigration(PaperMigration  $paperMigration): void
    {
        $paperMigration->generateMigration();

        $this->expectException(RuntimeException::class, function () use ($paperMigration){
            $paperMigration->migrate();
        });
        $successList = $paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 0);

    }
}
