<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use Attribute;
use PhpDevCommunity\PaperORM\Types\DateTimeType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DateTimeColumn extends Column
{

    public function __construct(
        string $property,
        string $name = null,
        bool   $nullable = false
    )
    {
        parent::__construct($property, $name, DateTimeType::class, $nullable);
    }
}
