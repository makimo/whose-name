# Batch query echoes each query and returns the answer under `a`

**Status:** _accepted_. Supersedes the response-shape half of
[2026-07-06-batch-query-status-and-shape.md](2026-07-06-batch-query-status-and-shape.md).

## Context

The batch endpoint `POST /api/whose-name/query/batch` returned a **lean array** of
`{"username": ...}` objects, correlated to the input purely by order. The original
ADL called this out as a deliberate, reversible trade: _"an input echo or
client-supplied ids can be added without changing the status semantics"_.

Order-only correlation is fragile for clients: a result carries no record of which
query produced it, so any reordering, filtering, or logging of individual results
loses that link, and a single result is not self-describing.

## Decision

Each result item **echoes its query and carries the answer**:

```json
{"u": "test@example.org", "s": "jira", "q": "slack", "a": "U123456"}
```

- `u`, `s`, `q` are the query's fields, verbatim.
- `a` is the answer — a string, an array of strings (when the asked service holds
  several names), or `null` when no match was found. It replaces the old
  `username` key.
- The response stays an array in the **same order** as the queries.

Everything else is unchanged from the superseded ADL: `200` when every query
resolved, `207` when at least one `a` is `null`, `422` on a malformed body
(strict `1..100` items, each with non-empty `u`, `s`, `q`). Iteration still lives
in `QueryService::whatAreTheNamesOf`; the route closure zips the validated queries
with their answers.

The single endpoint `GET /query` is untouched — it still returns
`{"username": ...}`.

## Consequences

- Each result is **self-describing**: clients can correlate by content, not just
  position, and safely reorder, filter, or log individual items.
- The payload is heavier (four keys per item, echoing input the client already
  sent). Acceptable for a bounded batch (`max:100`).
- **Breaking change** for clients: the per-item key changed from `username` to
  `a`, and the shape gained `u`/`s`/`q`. Consumers of the batch endpoint must
  migrate. The single endpoint's `{"username": ...}` is intentionally left as-is,
  so the two endpoints now differ in their result key.
- The pool per-member endpoint (`GET /pool`) adopts the **same** echoed shape,
  mapping each member to a query: `u` = member name, `s` = the pool's field,
  `q` = the asked service, `a` = the answer. The route reads the pool (for its
  members and field) and zips it with the answers from
  `PoolService::whatAreTheNamesOf`, which is left returning a plain list so
  `whoseNamesAreThere` and the flattened `/pool/names` endpoint are unaffected.

## Alternatives

- **Keep the lean array, add a client-supplied `id`** — order-independent with a
  smaller payload, but pushes correlation bookkeeping onto the client and needs a
  new input field; rejected in favour of echoing the query the client already has.
- **Echo input but keep `username` for the answer** — rejected: `a` is short and
  neutral, and renaming makes the shape change unmistakable at the call site.

## Decision date

> 2026-07-08
