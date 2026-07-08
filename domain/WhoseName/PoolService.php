<?php namespace Domain\WhoseName;

class PoolService {
    protected
        $pools,
        $queryService;

    public function __construct(PoolQueryRepository $pools, QueryService $queryService) {
        $this->pools = $pools;
        $this->queryService = $queryService;
    }

    /**
     * Resolve each pool member's name(s) on a given service.
     *
     * Returns one response per member, index-aligned with the pool's names
     * and mirroring QueryService::whatAreTheNamesOf, so callers can map the
     * results one-to-one (e.g. onto a batch HTTP response with 207
     * semantics). Each response is string|array|null, exactly as
     * QueryService::whatIsTheNameOf returns it. When the asked service is the
     * pool's own field, the pool's names are returned verbatim.
     *
     * @return array A list of string|array|null answers, one per member.
     */
    public function whatAreTheNamesOf(string $poolName, string $askedService): array {
        $pool = $this->pools->findByName($poolName);

        if ($pool->getField() === $askedService) {
            return $pool->getNames();
        }

        return array_map(
            fn ($name) => $this->queryService->whatIsTheNameOf(
                $name,
                $pool->getField(),
                $askedService
            ),
            $pool->getNames()
        );
    }

    /**
     * The flat list of distinct names present in a pool on a given service.
     *
     * Flattens whatAreTheNamesOf: a member resolving to several names is
     * spread in, a member resolving to none (null) is dropped, and any name
     * reached through more than one member appears only once. Order follows
     * first occurrence.
     *
     * @return array A flat list of unique names.
     */
    public function whoseNamesAreThere(string $poolName, string $askedService): array {
        return array_values(array_unique(
            array_merge([], ...array_map(
                fn ($response) => (array) $response,
                $this->whatAreTheNamesOf($poolName, $askedService)
            ))
        ));
    }
}
