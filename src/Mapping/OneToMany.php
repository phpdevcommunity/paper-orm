<?php

namespace PhpDevCommunity\PaperORM\Mapping;

use PhpDevCommunity\PaperORM\Collection\ObjectStorage;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class OneToMany
{
    private string $property;
    private string $targetEntity;
    private ?string $mappedBy;
    private array $criteria;
    private ObjectStorage $storage;
    final public function __construct(string $property, string $targetEntity, string $mappedBy = null, array $criteria = [])
    {
        $this->property = $property;
        $this->targetEntity = $targetEntity;
        $this->mappedBy = $mappedBy;
        $this->criteria = $criteria;
        $this->storage = new ObjectStorage();
    }

    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    public function getMappedBy(): ?string
    {
        return $this->mappedBy;
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

    public function getName(): string
    {
        return $this->getProperty();
    }
    public function getProperty(): string
    {
        return $this->property;
    }
}
