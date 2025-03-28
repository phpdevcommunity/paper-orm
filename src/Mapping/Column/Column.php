<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Mapping\Index;
use PhpDevCommunity\PaperORM\Types\StringType;
use PhpDevCommunity\PaperORM\Types\Type;
use PhpDevCommunity\PaperORM\Types\TypeFactory;

abstract class Column
{
    private string $property;
    private ?string $name;
    private string $type;
    private bool $unique;
    private bool $nullable;
    private $defaultValue;
    private ?string $firstArgument;
    private ?string $secondArgument;
    private ?Index $index = null;

     public function __construct(
          string $property,
          ?string $name = null,
          string $type = StringType::class,
          bool $nullable = false,
          $defaultValue = null,
          bool $unique = false,
         ?string $firstArgument = null,
         ?string $secondArgument = null
     )
    {
        $this->property = $property;
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->unique = $unique;
        $this->nullable = $nullable;
        $this->firstArgument = $firstArgument;
        $this->secondArgument = $secondArgument;

        if ($this instanceof JoinColumn || $unique === true) {
            $this->index = new Index([$this->getName()], $unique);
        }
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

    final public function getFirstArgument(): ?string
    {
        return $this->firstArgument;
    }

    final public function getSecondArgument(): ?string
    {
        return $this->secondArgument;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getDefaultValueToDatabase()
    {
        return $this->convertToDatabase($this->getDefaultValue());
    }

    public function getIndex(): ?Index
    {
        return $this->index;
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
