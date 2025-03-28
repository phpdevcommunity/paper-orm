<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use Attribute;
use PhpDevCommunity\PaperORM\Types\DecimalType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DecimalColumn extends Column
{

    public function __construct(
        string $property,
        string $name = null,
        bool   $nullable = false,
        string $defaultValue = null,
        int    $precision = 10,
        int    $scale = 2,
        bool   $unique = false
    )
    {
        parent::__construct($property, $name, DecimalType::class, $nullable, $defaultValue, $unique, $precision, $scale);
    }

    public function getPrecision(): ?int
    {
        return $this->getFirstArgument() ? intval($this->getFirstArgument()) : null;
    }

    public function getScale(): ?int
    {
        return $this->getSecondArgument() ? intval($this->getSecondArgument()) : null;
    }
}
