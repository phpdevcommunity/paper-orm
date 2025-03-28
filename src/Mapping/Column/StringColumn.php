<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use Attribute;
use PhpDevCommunity\PaperORM\Types\StringType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class StringColumn extends Column
{
    public function __construct(
        string $property,
        string $name = null,
        int $length = 255,
        bool   $nullable = false,
        string $defaultValue = null,
        bool $unique = false
    )
    {
        parent::__construct($property, $name, StringType::class, $nullable, $defaultValue, $unique, $length);
    }

    public function getLength(): int
    {
        return intval($this->getFirstArgument());
    }
}
