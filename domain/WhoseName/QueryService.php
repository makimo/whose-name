<?php namespace Domain\WhoseName;

class QueryService {
    protected
        $repository;

    public function __construct(IdentityQueryRepository $repository) {
        $this->repository = $repository;
    }

    public function whatIsTheNameOf(string $username, string $service, string $askedService): ?string {
        return $this->repository
            ->findByServiceAndUsername($service, $username)
            ->username($askedService);
    }

    /**
     * Resolve many queries at once.
     *
     * @param array $queries A list of ['username' => , 'service' => , 'askedService' => ] triples.
     *
     * @return array An index-aligned list of ?string usernames (null where unknown).
     */
    public function whatAreTheNamesOf(array $queries): array {
        return array_map(
            fn ($query) => $this->whatIsTheNameOf(
                $query['username'],
                $query['service'],
                $query['askedService']
            ),
            $queries
        );
    }
}
