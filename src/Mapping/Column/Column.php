<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\Type;
use PhpDevCommunity\PaperORM\Types\TypeFactory;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
class Column
{
    private string $property;
    private ?string $name;
    private string $type;
    private bool $unique;
    private bool $nullable;

     public function __construct(
          string $property,
          ?string $name = null,
          string $type = 'string',
          bool $unique = false,
           bool $nullable = false
     )
    {
        $this->property = $property;
        $this->name = $name;
        $this->type = $type;
        $this->unique = $unique;
        $this->nullable = $nullable;
    }

    final public function __toString(): string
    {
        return $this->getProperty();
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    final public function getName(): ?string
    {
        return $this->name ?: $this->getProperty();
    }


    public function getType(): string
    {
        return $this->type;
    }

    final public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Converts a value to its corresponding database representation.
     *
     * @param mixed $value The value to be converted.
     * @return mixed The converted value.
     * @throws \ReflectionException
     */
    final function convertToDatabase($value)
    {
        $type = $this->getType();
        if (is_subclass_of($type, Type::class)) {
            $value = TypeFactory::create($type)->convertToDatabase($value);
        }
        return $value;
    }

    /**
     * Converts a value to its corresponding PHP representation.
     *
     * @param mixed $value The value to be converted.
     * @return mixed The converted PHP value.
     * @throws \ReflectionException
     */
    final function convertToPHP($value)
    {
        $type = $this->getType();
        if (is_subclass_of($type, Type::class)) {
            $value = TypeFactory::create($type)->convertToPHP($value);
        }
        return $value;
    }
}
