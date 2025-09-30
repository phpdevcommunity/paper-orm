<?php

namespace PhpDevCommunity\PaperORM\EventListener;

use PhpDevCommunity\PaperORM\Event\PreCreateEvent;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;

class CreatedAtListener
{
    public function __invoke(PreCreateEvent $event)
    {
        $entity = $event->getEntity();

        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof TimestampColumn && $column->isOnCreated()) {
                $property = $column->getProperty();
                $method   = "set" . ucfirst($property);
                if (method_exists($entity, $method)) {
                    $entity->$method(new \DateTimeImmutable('now'));
                } elseif (array_key_exists($property, get_object_vars($entity))) {
                    $entity->$property = new \DateTimeImmutable('now'); // OK car public
                } else {
                    throw new \LogicException(sprintf(
                        'Cannot set created-at timestamp: expected setter "%s()" or a public property "%s" in entity "%s".',
                        $method,
                        $property,
                        get_class($entity)
                    ));
                }
            }
        }
    }
}
