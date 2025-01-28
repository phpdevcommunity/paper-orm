<?php

namespace PhpDevCommunity\PaperORM\Types;

final class DecimalType extends Type
{

    public function convertToDatabase($value): ?string
    {
        return $value === null ? null : (string)$value;
    }

    public function convertToPHP($value): ?string
    {
        return $value === null ? null : (string)$value;
    }
}
