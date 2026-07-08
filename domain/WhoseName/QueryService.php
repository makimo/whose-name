<?php namespace Domain\WhoseName;

class QueryService {
    protected
        $repository;

    public function __construct(IdentityQueryRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * Resolve the name(s) an identity uses on another service.
     *
     * @return string|array|null A single name, a list of names when the
     *                           asked service holds several, or null when
     *                           the identity or asked service is unknown.
     */
    public function whatIsTheNameOf(string $username, string $service, string $askedService): string|array|null {
        return $this->repository
            ->findByServiceAndUsername($service, $username)
            ->username($askedService);
    }

    /**
     * Resolve many queries at once.
     *
     * @param array $queries A list of ['username' => , 'service' => , 'askedService' => ] triples.
     *
     * @return array An index-aligned list of string|array|null answers (null where unknown).
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
