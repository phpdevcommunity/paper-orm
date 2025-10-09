<?php

namespace PhpDevCommunity\PaperORM\Assigner;

use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\SlugColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\TokenColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\UuidColumn;
use PhpDevCommunity\PaperORM\Tools\EntityAccessor;
use PhpDevCommunity\PaperORM\Tools\IDBuilder;

final class TokenAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof TokenColumn) {
            throw new \InvalidArgumentException(sprintf(
                'TokenAssigner::assign(): expected instance of %s, got %s.',
                TokenColumn::class,
                get_class($column)
            ));
        }

        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, IDBuilder::generate(sprintf("{TOKEN%s}", $column->getLength())));
    }
}
