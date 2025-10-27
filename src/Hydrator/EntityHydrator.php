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
use PhpDevCommunity\PaperORM\Schema\SchemaInterface;
use ReflectionClass;

final class EntityHydrator extends AbstractEntityHydrator
{
    private EntityMemcachedCache $cache;
    private SchemaInterface $schema;

    public function __construct(SchemaInterface $schema, EntityMemcachedCache $cache)
    {
        $this->cache = $cache;
        $this->schema = $schema;
    }

    protected function instantiate(string $class, array $data): object
    {
        $primaryKey = ColumnMapper::getPrimaryKeyColumnName($class);

        $object = $this->cache->get($class, $data[$primaryKey]) ?: ProxyFactory::create($class);

        $this->cache->set($class, $data[$primaryKey], $object);

        return $object;
    }

    protected function getSchema(): SchemaInterface
    {
        return $this->schema;
    }
}

