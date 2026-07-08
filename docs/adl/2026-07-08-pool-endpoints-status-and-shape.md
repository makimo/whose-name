# Pool endpoints: a per-member view and a flattened view

**Status:** _accepted_.

## Context

A pool is a named list of names sharing one field (see
`Domain\WhoseName\Pool`). `PoolService` exposes two ways to read it:

- `whatAreTheNamesOf($pool, $service)` — one answer per member, index-aligned
  with the pool's names (`string|array|null` each), mirroring
  `QueryService::whatAreTheNamesOf`.
- `whoseNamesAreThere($pool, $service)` — those answers flattened into a single
  distinct list of names.

Both needed HTTP exposure. The single (`GET /query`) and batch
(`POST /query/batch`) endpoints already set two precedents that pull in
different directions: the single GET is **lenient** (missing params default to
`''`, an unresolved query is `404` with `{"username":null}`), while the batch
POST is **strict** (`422` on a malformed body, `207` on partial results).

## Decision

Two `GET` endpoints under the `whose-name` group, both taking `p` (pool name)
and `q` (asked service):

- `GET /pool` returns the **per-member** view: a JSON array of `{"username": ...}`
  in pool order, one per member — the same envelope and order-as-correlation-key
  contract as the batch endpoint. Status:
  - `200 OK` when every member resolved,
  - `207 Multi-Status` when at least one member resolved to `null`,
  - `404 Not Found` (body `[]`) when the pool is unknown or empty.
- `GET /pool/names` returns the **flattened** view: `{"names": [...]}`, a distinct
  flat list. Status `200 OK`, or `404 Not Found` (body `{"names": []}`) when empty.

Both endpoints are **lenient** like the single GET: `p` and `q` default to `''`
and simply resolve to an empty result (→ `404`) rather than `422`. Strict
`422`-style validation is reserved for the body-carrying batch endpoint.

The route closures stay thin HTTP↔domain maps: `/pool` maps each answer to
`{"username": ...}` and picks `200`/`207` from the presence of `null` (reusing
the batch endpoint's `in_array(null, …, true)` check); `/pool/names` returns the
service's flat list as-is. All iteration, flattening, de-duplication and the
same-field shortcut live in `PoolService`.

## Consequences

- `/pool` is a drop-in parallel of the batch endpoint — a client that already
  handles `{"username": string|array|null}` items and `200`/`207` needs no new
  parsing; only the request shape (a pool name instead of an explicit query list)
  differs.
- `404` on an empty result conflates "unknown pool" with "known pool, no members
  resolved". This matches the single endpoint's "nothing found → 404" and keeps
  `null`/`[]` a valid, non-error answer inside a `207`; distinguishing the two
  cases would need an extra signal and is deferred until a client needs it.
- `/pool/names` drops `null`s and duplicates, so it cannot be correlated back to
  members — that is the explicit trade of the flattened view. Use `/pool` when
  per-member correlation or `207` semantics matter.
- Leniency means a typo in `p` or `q` yields `404`, not `422`. Consistent with the
  single GET, at the cost of not flagging malformed input.

## Alternatives

- **One endpoint with a `flat=true` flag** — rejected: two response shapes
  (`[{...}]` vs `{"names":[…]}`) and two status tables behind one URL is harder to
  document and cache than two intent-named routes.
- **`POST /pool/batch`** for many pools at once — deferred; no need yet, and it
  can be added later exactly like `query/batch` without disturbing these routes.
- **Strict `422` validation of `p`/`q`** — rejected for parity with the single
  GET; can be tightened later if clients prefer explicit rejection.
- **`200` with `[]` for an unknown pool** — rejected: hides misses behind a
  success, unlike the rest of the API.

## Decision date

> 2026-07-08
