<?php

namespace PhpDevCommunity\PaperORM;

use PhpDevCommunity\Listener\EventDispatcher;
use PhpDevCommunity\Listener\ListenerProvider;
use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Driver\DriverManager;
use PhpDevCommunity\PaperORM\Event\PreCreateEvent;
use PhpDevCommunity\PaperORM\Event\PreUpdateEvent;
use PhpDevCommunity\PaperORM\EventListener\CreatedAtListener;
use PhpDevCommunity\PaperORM\EventListener\UpdatedAtListener;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Parser\DSNParser;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use PhpDevCommunity\PaperORM\Repository\Repository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;

class EntityManager implements EntityManagerInterface
{
    private PaperConnection $connection;

    private UnitOfWork $unitOfWork;

    /**
     * @var array<string, Repository>
     */
    private array $repositories = [];

    private EntityMemcachedCache $cache;

    private ListenerProviderInterface $listener;
    private EventDispatcherInterface $dispatcher;
    private ?PlatformInterface $platform = null;

    public static function createFromDsn(string $dsn, bool $debug = false, LoggerInterface $logger = null, array $listeners = []): self
    {
        if (empty($dsn)) {
            throw new \LogicException('Cannot create an EntityManager from an empty DSN.');
        }
        $params = DSNParser::parse($dsn);
        $params['extra']['debug'] = $debug;
        if ($logger !== null) {
            $params['extra']['logger'] = $logger;
        }
        $params['extra']['listeners'] = $listeners;
        return new self($params);
    }

    public function __construct(array $config = [])
    {
        if (!isset($config['driver'])) {
            throw new \InvalidArgumentException('Missing "driver" in EntityManager configuration.');
        }

        $this->connection = DriverManager::createConnection($config['driver'], $config);
        $this->unitOfWork = new UnitOfWork();
        $this->cache = new EntityMemcachedCache();
        $this->listener = (new ListenerProvider())
            ->addListener(PreCreateEvent::class, new CreatedAtListener())
            ->addListener(PreUpdateEvent::class, new UpdatedAtListener());

        $listeners = $config['extra']['listeners'] ?? [];
        foreach ((array) $listeners as $event => $listener) {
            foreach ((array) $listener as $l) {
                $this->addEventListener($event, $l);
            }
        }
        $this->dispatcher = new EventDispatcher($this->listener);
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

        $dispatcher = $this->dispatcher;
        if (!isset($this->repositories[$repositoryName])) {
            if (!class_exists($repositoryName)) {
                $repository = new class($entity, $this, $dispatcher) extends Repository
                {
                    private string $entityName;
                    public function __construct($entityName, EntityManager $em, EventDispatcherInterface $dispatcher = null)  {
                        $this->entityName = $entityName;
                        parent::__construct($em, $dispatcher);
                    }

                    public function getEntityName(): string
                    {
                        return $this->entityName;
                    }
                };
            }else {
                $repository = new $repositoryName($this, $dispatcher);
            }
            $this->repositories[$repositoryName] = $repository;
        }

        return  $this->repositories[$repositoryName];
    }


    public function getPlatform(): PlatformInterface
    {
        if ($this->platform === null) {
            $driver = $this->connection->getDriver();
            $this->platform = $driver->createDatabasePlatform($this->getConnection());
        }
        return $this->platform;
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

    public function addEventListener(string $eventType, callable $callable): self
    {
        $this->listener->addListener($eventType, $callable);
        return $this;
    }

}
