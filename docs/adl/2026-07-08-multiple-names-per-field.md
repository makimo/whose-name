# A field may hold multiple names; lookups match any, answers return all

**Status:** _accepted_.

## Context

An identity mapped each service to exactly one name (`slack: U123456`). In
practice one person often uses several names on the same service — most commonly
several email addresses. There was no way to record that, and therefore no way to
resolve an identity from a secondary address.

Two things had to change together:

- **Search** had to find an identity by _any_ of the names a person uses on a
  service, not just a single canonical one.
- **The answer** to "what is their name on service X" had to be able to carry
  more than one name.

## Decision

- A field's YAML value may be either a scalar (one name) or a list (several
  names). The shapes coexist in the same file:

  ```yml
  -
    slack: U234567
    email:
      - other@example.org
      - new@example.org
  ```

- `Domain\WhoseName\QueryService::whatIsTheNameOf` — and the underlying
  `Identity::username` — now return `string|array|null`:
  - a **string** when the asked service holds one name,
  - an **array** of names when it holds several,
  - `null` when the identity or the asked service is unknown.

  `whatAreTheNamesOf` (batch) returns an index-aligned list of those same
  `string|array|null` answers.

- **Lookups match any listed name.** The reverse index built by
  `YamlFileRepository::transformIdentityListToIndex` maps _every_ name under a
  field to its identity (`foreach ((array) $value as $name)`), so a query by any
  one of them resolves the identity. A scalar is treated as a one-element list, so
  single-name fields are unchanged.

- **The HTTP layer is unchanged in shape.** Both the single (`GET
  /api/whose-name/query`) and batch (`POST /api/whose-name/query/batch`) endpoints
  keep the `{"username": ...}` envelope and their existing status semantics
  (`200`/`404`, and `200`/`207` for batch). The `username` value simply widens to
  include a JSON array. `null` remains the sole trigger for a miss, so the status
  logic did not change.

## Consequences

- The API is **backward compatible for single-name data**: existing files,
  queries, and responses behave exactly as before. A response only becomes an
  array when the data for the asked service is itself a list.
- Clients must now accept `username` as either a string or an array of strings.
  This is a widening of the response contract and existing string-only clients
  need updating before they consume multi-name fields.
- Names are indexed per service, so the same string under two services (e.g. an
  address used as both `jira` and `email`) does not collide — it maps each service
  to its identity independently.
- Duplicate names under one service across identities still collide (last write
  wins), exactly as single-name data did; this remains a data-quality concern, not
  something the index resolves.

## Alternatives

- **Keep `?string` and pick one "primary" name** — rejected: it discards the very
  information the change exists to record and makes the choice of primary
  arbitrary.
- **Always return an array** (wrap single names in a one-element list) — cleaner
  types, but a breaking change for every existing client and every existing
  response; rejected in favour of returning what the field holds.
- **A separate `aliases` field instead of a list value** — rejected: it splits one
  concept (the names on a service) across two keys and complicates both lookup and
  answering.

## Decision date

> 2026-07-08
