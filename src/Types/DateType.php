<?php

namespace PhpDevCommunity\PaperORM\Types;

final class DateType extends Type
{

    public function convertToDatabase($value, string $format = 'Y-m-d'): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        throw new \LogicException('Could not convert PHP value "' . $value . '" to ' . self::class);
    }

    public function convertToPHP($value, string $format = 'Y-m-d'): ?\DateTimeInterface
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }

        $date = \DateTime::createFromFormat($format,$value);
        if (!$date instanceof \DateTimeInterface) {
            throw new \LogicException('Could not convert database value "' . $value . '" to ' . self::class);
        }

        return $date;
    }

}
