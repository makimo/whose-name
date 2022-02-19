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
}
