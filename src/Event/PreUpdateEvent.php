<?php

namespace PhpDevCommunity\PaperORM\Event;

use PhpDevCommunity\Listener\Event;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;

class PreUpdateEvent extends Event
{

    private EntityInterface $entity;

    /**
     * PreCreateEvent constructor.
     *
     * @param EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
    }


    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }

}
