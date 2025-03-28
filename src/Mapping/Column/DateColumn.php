<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\DateType;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class DateColumn extends Column
{

    public function __construct(
        string $property,
        string $name = null,
        bool $nullable = false
    )
    {
        parent::__construct($property, $name, DateType::class, $nullable);
    }
}
