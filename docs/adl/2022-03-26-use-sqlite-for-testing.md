# Use SQLite in-memory database for testing

**Status:** _accepted_.

## Context

It has been found out that each test run generates a few new `App\Models\User` objects that accumulate with thime to the database a developer might be working on.

Using `RefreshesDatabase` trait in the tests, on the other hand, removes all data, including those created by the developer by hand.

## Decision

Instead of MySQL, a SQLite in-memory database will be used for testing.

## Consequences

If any future tests depend on anything database-specific, that might require reverting back to MySQL.

## Alternatives

- Add another MySQL Database that can be deleted upon test completion safely without hindering development
- Add another Docker MySQL service for testing

## Decision date

> 2022-03-26