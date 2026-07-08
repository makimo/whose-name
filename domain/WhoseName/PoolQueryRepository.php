<?php namespace Domain\WhoseName;

interface PoolQueryRepository {
    /**
     * Fetch a Pool given its name.
     *
     * @param string $name A pool name (its lookup key).
     *
     * @return Pool (an empty one if no match was found).
     */
    public function findByName(string $name): Pool;
}
