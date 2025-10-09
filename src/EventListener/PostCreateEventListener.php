<?php

namespace PhpDevCommunity\PaperORM\EventListener;

use PhpDevCommunity\PaperORM\Assigner\AutoIncrementAssigner;
use PhpDevCommunity\PaperORM\Event\PostCreateEvent;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\AutoIncrementColumn;

class PostCreateEventListener
{
    public function __invoke(PostCreateEvent $event)
    {
        $entity = $event->getEntity();
        $em = $event->getEm();

        $autoIncrementAssigner = new AutoIncrementAssigner($em->sequence());
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof AutoIncrementColumn) {
                $autoIncrementAssigner->commit($column);
            }
        }
    }
}
