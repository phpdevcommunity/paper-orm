<?php

namespace PhpDevCommunity\PaperORM\State;

final class StateHashTrackerManager
{
    /**
     * @var array<string, StateHashTracker>
     */
    private array $trackers = [];

    /**
     * Register a tracker for a specific key.
     *
     * @param StateHashTracker $tracker The tracker instance to register.
     */
    public function register(StateHashTracker $tracker): void
    {
        $key = spl_object_hash($tracker->getTrackedEntity());
        if ($this->exist($key)) {
            $this->reset($tracker);
            return;
        }

        $this->trackers[$key] = $tracker;
    }


    public function exist(string $key): bool
    {
        return isset($this->trackers[$key]);
    }

    /**
     * Check if the entity associated with the given key has been modified.
     *
     * @param object $entity
     * @return bool True if the entity is modified, false otherwise.
     */
    public function hasStateChanged(object $entity): bool
    {
        $key = spl_object_hash($entity);
        if (!$this->exist($key)) {
            return false;
        }

        return $this->trackers[$key]->hasStateChanged();
    }


    /**
     * Get the tracker associated with the given key.
     *
     * @param object $entity
     * @return StateHashTracker
     */
    public function getTracker(object $entity): StateHashTracker
    {
        $key = spl_object_hash($entity);
        if (!$this->exist($key)) {
            throw new \InvalidArgumentException("No tracker found for the key '$key'.");
        }

        return $this->trackers[$key];
    }


    private function reset(StateHashTracker $tracker): void
    {
        $key = spl_object_hash($tracker->getTrackedEntity());
        if (!$this->exist($key)) {
            throw new \InvalidArgumentException("No tracker found for the key '$key'.");
        }

        unset($this->trackers[$key]);
        $this->trackers[$key] = $tracker;
    }

    public function remove(object $entity): void {
        $key = spl_object_hash($entity);
        if (!$this->exist($key)) {
            return;
        }
        unset($this->trackers[$key]);
    }

    public function clear(): void
    {
        $this->trackers = [];
    }
}

