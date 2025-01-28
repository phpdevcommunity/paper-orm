<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\IntegerType;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class PrimaryKeyColumn extends Column
{
    public function __construct(string $property, string $name = null, string $type = IntegerType::class)
    {
        parent::__construct($property, $name, $type);
    }
}
