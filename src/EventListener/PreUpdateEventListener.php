<?php

namespace PhpDevCommunity\PaperORM\EventListener;

use DateTimeImmutable;
use PhpDevCommunity\PaperORM\Event\PreUpdateEvent;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;
use PhpDevCommunity\PaperORM\Tools\EntityAccessor;

class PreUpdateEventListener
{

    public function __invoke(PreUpdateEvent $event)
    {
        $entity = $event->getEntity();
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof TimestampColumn && $column->isOnUpdated()) {
                $property = $column->getProperty();
                EntityAccessor::setValue($entity, $property, new DateTimeImmutable('now'));
            }
        }
    }
}
