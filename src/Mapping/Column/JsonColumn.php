<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\JsonType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class JsonColumn extends Column
{
    public function __construct(
        string $name = null,
        bool   $nullable = false,
        array $defaultValue = null
    )
    {
        parent::__construct('', $name, JsonType::class, $nullable, $defaultValue);
    }
}
