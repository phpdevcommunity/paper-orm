<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\FloatType;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class FloatColumn extends Column
{

    public function __construct(
        string $property,
        string $name = null,
        bool   $nullable = false,
        ?float $defaultValue = null,
        bool   $unique = false
    )
    {
        parent::__construct($property, $name, FloatType::class, $nullable, $defaultValue, $unique);
    }
}
