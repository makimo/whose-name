# Batch query returns 200/207 with a lean, index-correlated result array

**Status:** _accepted_.

## Context

The single lookup endpoint `GET /api/whose-name/query` resolves one `{u, s, q}` triple
and returns `{"username": ...}` with `200` when found or `404` when not. Clients that
need to resolve many identities at once had to issue one request per lookup.

A batch endpoint `POST /api/whose-name/query/batch` accepts a list of queries. Two
design questions do not carry over cleanly from the single endpoint:

- A batch is usually **partial** (some queries match, some do not), so a single `404`
  cannot describe the outcome.
- Clients need to correlate each result back to the query that produced it.

## Decision

- The response is a **lean JSON array** of `{"username": ...}` objects, in the **same
  order** as the incoming `queries`. Item _i_ is the answer to `queries[i]`; a `null`
  username means no match. No input echo — order is the correlation key.
- Status codes:
  - `200 OK` — every query resolved to a username.
  - `207 Multi-Status` — at least one query resolved to `null` (partial or total misses).
  - `422 Unprocessable Entity` — malformed body (validation failure).
- Validation is **strict**: `queries` is required, `1..100` items, and each item must
  provide non-empty `u`, `s` and `q`. Unlike the single endpoint (which defaults missing
  params to an empty string and resolves them to `null`), empty/missing fields are rejected.
- Iteration lives in the domain: `Domain\WhoseName\QueryService::whatAreTheNamesOf`
  loops the existing `whatIsTheNameOf`, keeping the route closure a thin HTTP↔domain map,
  consistent with "the framework is a client of the domain."

## Consequences

- `null` is treated as a legitimate answer, not an HTTP error — so a mixed batch is a
  successful response (`207`) that clients parse per item, rather than a failure.
- The lean array is order-dependent: clients must preserve query order to map results.
  If order-independent correlation is later required, an input echo or client-supplied
  ids can be added without changing the status semantics.
- Reusing the per-request `YamlFileRepository` cache means a batch costs ~one file read
  regardless of size; the `max:100` limit bounds work per request.

## Alternatives

- Always `200` with per-item `null` (no `207`) — simpler, but hides that some queries missed.
- `404` only when every query misses — conflates HTTP "not found" with a valid `null` answer.
- Echo each input triple alongside its username / key results by a client-supplied id —
  order-independent but heavier; rejected in favour of the lean array for now.
- Loop in the route closure instead of the domain — rejected to keep iteration testable
  framework-free and consistent with the domain-first convention.

## Decision date

> 2026-07-06
