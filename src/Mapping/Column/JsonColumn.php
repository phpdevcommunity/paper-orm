<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\JsonType;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class JsonColumn extends Column
{
    public function __construct(string $property, string $name = null)
    {
        parent::__construct($property, $name, JsonType::class);
    }
}
