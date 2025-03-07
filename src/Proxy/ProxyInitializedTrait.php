<?php

namespace PhpDevCommunity\PaperORM\Proxy;

use DateTimeInterface;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\DateColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;

trait ProxyInitializedTrait
{
    /**
     * @var array<string,Column>
     */
    private array $__propertiesInitialized = [];
    private array $__valuesInitialized = [];
    private bool $__initialized = false;

    public function __setInitialized(array $propertiesInitialized)
    {
        $this->__initialized = true;
        $this->__propertiesInitialized = $propertiesInitialized;
        $this->__valuesInitialized = $this->getValues();
    }

    public function __isInitialized(): bool
    {
        return $this->__initialized;
    }

    public function __wasModified(): bool
    {
        if (!$this->__initialized) {
            return false;
        }
        return json_encode($this->getValues()) !== json_encode($this->__valuesInitialized);
    }

    public function __getPropertiesModified() : array
    {
        if (!$this->__initialized) {
            return [];
        }
        return array_keys(array_diff_assoc($this->getValues(), $this->__valuesInitialized));
    }

    public function __destroy() : void
    {
        $this->__initialized = false;
        $this->__propertiesInitialized = [];
        $this->__valuesInitialized = [];
    }

    public function __reset(): void
    {
        $this->__setInitialized($this->__propertiesInitialized);
    }

    private function getParentClass(): string
    {
        return get_parent_class($this);
    }

    private function getValues(): array
    {
        $reflectionProxy = new \ReflectionClass($this);
        $reflection = $reflectionProxy->getParentClass();
        $cleanedData = [];

        foreach ($this->__propertiesInitialized as $key => $column) {
            $property = $reflection->getProperty($key);
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if ($column instanceof DateTimeColumn && $value instanceof DateTimeInterface) {
                $cleanedData[$key] = $value->getTimestamp();
            } elseif ($column instanceof DateColumn && $value instanceof DateTimeInterface) {
                $cleanedData[$key] = $value;
            } elseif ($column instanceof JoinColumn && $value instanceof EntityInterface) {
                $cleanedData[$key] = $value->getPrimaryKeyValue();
            } else {
                $cleanedData[$key] = $value;
            }
            unset($value);
        }
        return $cleanedData;
    }
}
