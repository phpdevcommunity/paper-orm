<?php

namespace PhpDevCommunity\PaperORM\Mapping;

use PhpDevCommunity\PaperORM\Collection\ObjectStorage;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class OneToMany
{
    private string $property;
    private string $targetEntity;
    private array $criteria;
    private ObjectStorage $storage;
    final public function __construct(string $property, string $targetEntity, array $criteria = [])
    {
        $this->property = $property;
        $this->targetEntity = $targetEntity;
        $this->criteria = $criteria;
        $this->storage = new ObjectStorage();
    }

    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getDefaultValue():ObjectStorage
    {
        return clone $this->storage;
    }
    public function getType(): string
    {
        return '\\'.ltrim(get_class($this->getDefaultValue()), '\\');
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
