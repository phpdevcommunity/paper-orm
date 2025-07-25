<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use Attribute;
use PhpDevCommunity\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class TextColumn extends Column
{
    public function __construct(
        string $name = null,
        bool   $nullable = false,
        string $defaultValue = null
    )
    {
        parent::__construct('', $name, StringType::class, $nullable, $defaultValue);
    }
}
