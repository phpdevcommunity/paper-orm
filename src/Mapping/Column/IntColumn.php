<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\IntType;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class IntColumn extends Column
{

    public function __construct(
        string $property,
        string $name = null,
        bool   $nullable = false,
        ?int $defaultValue = null,
        bool   $unique = false
    )
    {
        parent::__construct($property, $name, IntType::class, $nullable, $defaultValue, $unique);
    }
}
