<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use Attribute;
use PhpDevCommunity\PaperORM\Types\BoolType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class BoolColumn extends Column
{
    public function __construct(
        string  $property,
        ?string $name = null,
        bool    $nullable = false,
        ?bool $defaultValue = null
    )
    {
        parent::__construct($property, $name, BoolType::class, $nullable, $defaultValue);
    }
}
