<?php

namespace PhpDevCommunity\PaperORM\EventListener;

use PhpDevCommunity\PaperORM\Assigner\TimestampAssigner;
use PhpDevCommunity\PaperORM\Event\Update\PreUpdateEvent;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;

class PreUpdateEventListener
{

    public function __invoke(PreUpdateEvent $event)
    {
        $entity = $event->getEntity();
        $timestampAssigner = new TimestampAssigner();
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof TimestampColumn && $column->isOnUpdated()) {
                $timestampAssigner->assign($entity, $column);
            }
        }
    }
}
