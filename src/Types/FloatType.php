<?php

namespace PhpDevCommunity\PaperORM\Types;

final class FloatType extends Type
{

    public function convertToDatabase($value): ?string
    {
        return $value === null ? null : floatval($value);
    }

    public function convertToPHP($value): ?string
    {
        return $value === null ? null : floatval($value);
    }
}
