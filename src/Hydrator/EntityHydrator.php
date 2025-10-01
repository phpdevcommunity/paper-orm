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

        $object = $this->cache->get($class, $data[$primaryKey])
            ?: $this->createProxy($class);

        $this->cache->set($class, $data[$primaryKey], $object);

        return $object;
    }

    private function createProxy(string $class): object
    {
        $sanitized = str_replace('\\', '_', $class);
        $proxyClass = 'Proxy_' . $sanitized;

        if (!class_exists($proxyClass)) {
            eval("
                class $proxyClass extends \\$class implements \\PhpDevCommunity\\PaperORM\\Proxy\\ProxyInterface {
                    use \\PhpDevCommunity\\PaperORM\\Proxy\\ProxyInitializedTrait;
                }
            ");
        }

        return new $proxyClass();
    }
}

