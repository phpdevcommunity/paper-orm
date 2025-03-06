<?php

namespace PhpDevCommunity\PaperORM\Proxy;

use DateTimeInterface;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\DateColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;

trait ProxyInitializedTrait
{
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
        return !empty(array_diff_assoc($this->getValues(), $this->__valuesInitialized));
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
        $values = (array)$this;
        $cleanedData = [];
        foreach ($values as $key => $value) {
            $newKey = str_replace(chr(0), '', $key);
            $newKey = str_replace($this->getParentClass(), '', $newKey);
            if (array_key_exists($newKey, $this->__propertiesInitialized)) {
                if ($this->__propertiesInitialized[$newKey]['type'] == DateTimeColumn::class && $value instanceof DateTimeInterface) {
                    $cleanedData[$newKey] = $value->getTimestamp();
                } elseif ($this->__propertiesInitialized[$newKey]['type'] == DateColumn::class && $value instanceof DateTimeInterface) {
                    $cleanedData[$newKey] = $value;
                } elseif ($this->__propertiesInitialized[$newKey]['type'] == JoinColumn::class && $value instanceof EntityInterface) {
                    $cleanedData[$newKey] = $value->getPrimaryKeyValue();
                } else {
                    $cleanedData[$newKey] = $value;
                }
            }
        }
        return $cleanedData;
    }
}
