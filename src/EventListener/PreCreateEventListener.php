<?php

namespace PhpDevCommunity\PaperORM\EventListener;

use PhpDevCommunity\PaperORM\Assigner\AutoIncrementAssigner;
use PhpDevCommunity\PaperORM\Assigner\SlugAssigner;
use PhpDevCommunity\PaperORM\Assigner\TimestampAssigner;
use PhpDevCommunity\PaperORM\Assigner\TokenAssigner;
use PhpDevCommunity\PaperORM\Assigner\UuidAssigner;
use PhpDevCommunity\PaperORM\Event\PreCreateEvent;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\AutoIncrementColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\SlugColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\TokenColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\UuidColumn;

class PreCreateEventListener
{
    public function __invoke(PreCreateEvent $event)
    {
        $entity = $event->getEntity();
        $em = $event->getEm();

        $autoIncrementAssigner = new AutoIncrementAssigner($em->sequence());
        $slugAssigner = new SlugAssigner();
        $timestampAssigner = new TimestampAssigner();
        $uuidAssigner = new UuidAssigner();
        $tokenAssigner = new TokenAssigner();
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof TimestampColumn && $column->isOnCreated()) {
                $timestampAssigner->assign($entity, $column);
            } elseif ($column instanceof SlugColumn) {
                $slugAssigner->assign($entity, $column);
            } elseif ($column instanceof AutoIncrementColumn) {
                $autoIncrementAssigner->assign($entity, $column);
            }elseif ($column instanceof UuidColumn) {
                $uuidAssigner->assign($entity, $column);
            }elseif ($column instanceof TokenColumn) {
                $tokenAssigner->assign($entity, $column);
            }
        }
    }
}
