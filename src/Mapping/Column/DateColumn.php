<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\DateType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateColumn extends Column
{

    public function __construct(
        string $name = null,
        bool $nullable = false
    )
    {
        parent::__construct('', $name, DateType::class, $nullable);
    }
}
