<?php

namespace PhpDevCommunity\PaperORM\State;


use PhpDevCommunity\PaperORM\Serializer\SerializerToArray;

final class StateHashTracker
{
    private object $entity;
    private array $initialState;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
        $this->initialState = $this->extractState($entity);
    }

    /**
     * Extracts the current state of the entity as an associative array.
     *
     * @param object $entity The entity to extract state from.
     * @return array The serialized state of the entity.
     */
    private function extractState(object $entity): array
    {
        return (new SerializerToArray($entity))->serialize();
    }

    /**
     * Checks if the entity's state has been modified since initialization.
     *
     * @return bool True if the entity's state has changed, false otherwise.
     */
    public function hasStateChanged(): bool
    {
        return $this->initialState !== $this->extractState($this->entity);
    }

    /**
     * Returns the associated entity being tracked.
     *
     * @return object The tracked entity.
     */
    public function getTrackedEntity(): object
    {
        return $this->entity;
    }

    /**
     * Returns the list of properties that have been modified.
     *
     * @return array An array of property names that differ from the initial state.
     */
    public function getModifiedProperties(): array
    {
        $currentState = $this->extractState($this->entity);
        $differences = array_diff_assoc($currentState, $this->initialState);
        return array_keys($differences);
    }
}

