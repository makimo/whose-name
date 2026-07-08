<?php namespace Domain\WhoseName;

/**
 * A named list of names sharing a single field (service).
 *
 * A Pool groups people together under a key (its name) and records,
 * for one field, the names that belong to the pool:
 *
 *     name: Everyone
 *     field: email
 *     names:
 *       - michal@makimo.pl
 *       - alice@makimo.pl
 *
 * The name is the lookup key; the Pool itself only carries the field
 * its names belong to and the names themselves.
 */
class Pool {
    protected
        $field,
        $names;

    public function __construct(string $field, array $names) {
        $this->field = $field;
        $this->names = $names;
    }

    public function getField(): string {
        return $this->field;
    }

    public function getNames(): array {
        return $this->names;
    }
}
