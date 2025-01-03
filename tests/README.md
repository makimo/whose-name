# Tests

The tests present here describe how to use the application and mark its validity.

There are three directories containing tests:

- `Domain` for domain logic;
- `Infrastructure` for infrastructure code;
- `Application` for testing whole application (e.g. endpoints).

**Note**: The tests in the `Domain` directory do not bootstrap the Laravel framework, which means that framework services like caching, database, user authentication are not present here.

This is done to maintain separation between domain logic and the Laravel framework. Ideally no code in the application domain should require any framework services.

If you are familiar with Laravel framework, domain tests are comparable to `Unit` tests and `Infrastructure`/`Application` tests are comparable to `Feature` tests. This behavior is set in the `Pest.php` initialization file.

## Running tests

```
./vendor/bin/sail test
```

## Adding tests

To create a test, create an empty `.php` file inside one of the three directories.

### Naming tests

XXX TODO

### Grouping tests

When writing tests, categorize them into one of the following three categories:

- `usage`: Show examples of how a user can use the interface.
- `edge`: Define edge cases of an interface (e.g. errors, exceptions, non-standard usage).
- `behavior`: Explain additional behaviour that the code exhibits outside.

Use the provided `gest` function for that (defined in `Pest.php` file).

```php
gest('usage', 'Given ID, status of something can be changed', function() {
    // ...
});
```

Tests can be then restricted to a specific group(s) for comprehension purposes

```
./vendor/bin/sail --group usage,behavior
```