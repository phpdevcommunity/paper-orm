<?php

namespace PhpDevCommunity\PaperORM\Assigner;

use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\SlugColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\UuidColumn;
use PhpDevCommunity\PaperORM\Tools\EntityAccessor;
use PhpDevCommunity\PaperORM\Tools\IDBuilder;

final class UuidAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof UuidColumn) {
            throw new \InvalidArgumentException(sprintf(
                'UuidAssigner::assign(): expected instance of %s, got %s.',
                UuidColumn::class,
                get_class($column)
            ));
        }

        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, IDBuilder::generate('{UUID}'));
    }
}
