<?php

namespace PhpDevCommunity\PaperORM\Types;

final class IntType extends Type
{

    public function convertToDatabase($value): ?int
    {
        return $value === null ? null : (int)$value;
    }

    public function convertToPHP($value): ?int
    {
        return $value === null ? null : (int)$value;
    }
}
