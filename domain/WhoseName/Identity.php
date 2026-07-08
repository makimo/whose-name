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
 * A single service may hold more than one name for the same person.
 * In that case the value is a list rather than a scalar:
 *
 *     slack: U042M5ZRK
 *     email:
 *       - michal@makimo.pl
 *       - michal@example.org
 *
 */
class Identity {
    protected
        /**
         * A [service => name(s)] map, where each value is either a
         * single name (string) or a list of names (array of strings).
         */
        $accounts = [];

    public function __construct(array $accounts) {
        $this->accounts = $accounts;
    }

    /**
     * The name(s) held under a service.
     *
     * @return string|array|null A single name, a list of names, or
     *                           null when the service is unknown.
     */
    public function username(string $service): string|array|null {
        return isset($this->accounts[$service])
            ? $this->accounts[$service]
            : null;
    }
}