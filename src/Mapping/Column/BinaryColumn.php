<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use Attribute;
use PhpDevCommunity\PaperORM\Types\BinaryType;
use PhpDevCommunity\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class BinaryColumn extends Column
{
    public function __construct(
        string $name = null,
        bool   $nullable = false
    )
    {
        parent::__construct('', $name, BinaryType::class, $nullable);
    }
}
