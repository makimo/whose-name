<?php namespace Domain\WhoseName;

/**
 * A single Web Identity of a certain person.
 * 
 * In the domain, the Identity class describes a set of 
 * related account usernames all belonging to the same person.
 * 
 * See that this identity has no knowledge (and it shouldn't have)
 * of any other details of the person in question.
 * 
 * A real world example can be represented as the following map:
 *  
 *     github: dragonee
 *     jira: michal@makimo.pl
 *     slack: U042M5ZRK
 * 
 */
class Identity {
    protected
        /**
         * A [service => username] map.
         */
        $accounts = [];

    public function __construct(array $accounts) {
        $this->accounts = $accounts;
    }

    public function username(string $service): ?string {
        return isset($this->accounts[$service])
            ? $this->accounts[$service]
            : null;
    }
}