<?php

namespace PhpDevCommunity\PaperORM\Hydrator;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\PaperORM\Cache\EntityMemcachedCache;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use PhpDevCommunity\PaperORM\Proxy\ProxyFactory;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;
use ReflectionClass;

final class EntityHydrator extends AbstractEntityHydrator
{
    private EntityMemcachedCache $cache;

    public function __construct(EntityMemcachedCache $cache)
    {
        $this->cache = $cache;
    }

    protected function instantiate(string $class, array $data): object
    {
        $primaryKey = ColumnMapper::getPrimaryKeyColumnName($class);

        $object = $this->cache->get($class, $data[$primaryKey]) ?: ProxyFactory::create($class);

        $this->cache->set($class, $data[$primaryKey], $object);

        return $object;
    }
}

