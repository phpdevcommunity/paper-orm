<?php

namespace PhpDevCommunity\PaperORM\Assigner;

use DateTimeImmutable;
use InvalidArgumentException;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;
use PhpDevCommunity\PaperORM\Tools\EntityAccessor;

final class TimestampAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof TimestampColumn) {
            throw new InvalidArgumentException(sprintf(
                'TimestampAssigner::assign(): expected instance of %s, got %s.',
                TimestampColumn::class,
                get_class($column)
            ));
        }

        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, new DateTimeImmutable('now'));
    }
}
