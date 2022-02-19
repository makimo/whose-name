<?php namespace Domain\WhoseName;

interface IdentityQueryRepository {
    /**
     * Fetch Identity given a service name and username.
     * 
     * @param string $service A service name.
     * @param string $username A username used within the service.
     * 
     * @return Identity (an empty one if no match was found).
     */
    public function findByServiceAndUsername(string $service, string $username): Identity;
}