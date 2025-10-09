<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\Collector\EntityDirCollector;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Expression\Expr;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\PaperORM\PaperConfiguration;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;
use PhpDevCommunity\PaperORM\Tools\EntityExplorer;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class RegistryTest extends TestCase
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
        foreach (DataBaseHelperTest::drivers() as  $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            $paperMigration = PaperMigration::create($em, 'mig_versions', $this->migrationDir);
            $entityDirCollector = EntityDirCollector::bootstrap();
            $this->assertEquals(1, count($entityDirCollector->all()));
            $entities = EntityExplorer::getEntities($entityDirCollector->all());
            $this->assertEquals(1, count($entities['system']));
            $result = $paperMigration->getSqlDiffFromEntities($entities['system']);
            foreach ($result as $sql) {
                $em->getConnection()->executeStatement($sql);
            }
            $this->test($em);
            $em->getConnection()->close();
        }
    }

    private function test(EntityManager $em): void
    {
        $em->registry()->set('test', 'test');
        $this->assertEquals('test', $em->registry()->get('test'));

        $em->registry()->remove('test');
        $this->assertEquals(null, $em->registry()->get('test'));

        $value = $em->sequence()->peek("test");
        $this->assertEquals(1, $value);

        $em->sequence()->increment("test");
        $value = $em->sequence()->peek("test");
        $this->assertEquals(2, $value);
    }

}
