<?php

namespace PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Driver\DriverManager;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Repository\Repository;

class EntityManager implements EntityManagerInterface
{
    private PaperConnection $connection;

    private UnitOfWork $unitOfWork;

    /**
     * @var array<string, Repository>
     */
    private array $repositories = [];

    private EntityMemcachedCache $cache;

    public function __construct(array $config = [])
    {
        $driver = $config['driver'];
        $this->connection = DriverManager::getConnection($driver, $config);
        $this->unitOfWork = new UnitOfWork();
        $this->cache = new EntityMemcachedCache();
    }

    public function persist(object $entity): void
    {
        $this->unitOfWork->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->unitOfWork->remove($entity);
    }

    public function flush(): void
    {
        foreach ($this->unitOfWork->getEntityInsertions() as &$entity) {
            $repository = $this->getRepository(get_class($entity));
            $repository->insert($entity);
            $this->unitOfWork->unsetEntity($entity);
        }

        foreach ($this->unitOfWork->getEntityUpdates() as $entityToUpdate) {
            $repository = $this->getRepository(get_class($entityToUpdate));
            $repository->update($entityToUpdate);
            $this->unitOfWork->unsetEntity($entityToUpdate);
        }

        foreach ($this->unitOfWork->getEntityDeletions() as $entityToDelete) {
            $repository = $this->getRepository(get_class($entityToDelete));
            $repository->delete($entityToDelete);
            $this->unitOfWork->unsetEntity($entityToDelete);
        }

        $this->unitOfWork->clear();
    }

    public function getRepository(string $entity): Repository
    {
        $repositoryName = EntityMapper::getRepositoryName($entity);
        if ($repositoryName === null) {
            $repositoryName = 'ProxyRepository'.$entity;
        }
        if (!isset($this->repositories[$repositoryName])) {
            if (!class_exists($repositoryName)) {
                $repository = new class($entity, $this) extends Repository
                {
                    private string $entityName;
                    public function __construct($entityName, EntityManager $em)  {
                        $this->entityName = $entityName;
                        parent::__construct($em);
                    }

                    public function getEntityName(): string
                    {
                        return $this->entityName;
                    }
                };
            }else {
                $repository = new $repositoryName($this);
            }
            $this->repositories[$repositoryName] = $repository;
        }

        return  $this->repositories[$repositoryName];
    }


    public function createDatabasePlatform(): PlatformInterface
    {
        $driver = $this->connection->getDriver();
        return $driver->createDatabasePlatform($this->getConnection());
    }


    public function getConnection(): PaperConnection
    {
        return $this->connection;
    }

    public function getCache(): EntityMemcachedCache
    {
        return $this->cache;
    }

    public function clear(): void
    {
        $this->getCache()->clear();
    }

}
