<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\DecimalType;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class DecimalColumn extends Column
{

    private ?int $precision;
    private ?int $scale;

    public function __construct(
        string                $property,
        string                $name = null,
         ?int $precision = null,
         ?int $scale = null
    )
    {
        parent::__construct($property, $name, DecimalType::class);
        $this->precision = $precision;
        $this->scale = $scale;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }
}
