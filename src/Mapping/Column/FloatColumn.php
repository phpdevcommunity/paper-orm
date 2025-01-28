<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\FloatType;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class FloatColumn extends Column
{

    public function __construct(string $property, string $name = null)
    {
        parent::__construct($property, $name, FloatType::class);
    }
}
