# Whose-name

Given a file of the following structure:

```yml
-
  slack: U123456
  jira: test@example.org
-
  slack: U234567
  jira: other@example.org
  email:
    - other@example.org
    - new@example.org
```

This service answers questions of the following form:

> For one that calls themselves `test@example.org` on `jira`, what is their username on Slack? (Answer: `U123456`).

A field may hold **more than one name** for the same person — write it as a
list, as `email` above. Then:

- A lookup matches **any** of the listed names. Asking about the one who is
  `other@example.org` _or_ `new@example.org` on `email` finds the same identity.
- Asking _for_ a multi-name field returns the whole list. The Slack user
  `U234567`'s `email` resolves to `["other@example.org", "new@example.org"]`.

## Glossary

A set of usernames related to a person is called an **identity**.

A field maps a service to one **name**, or to a list of names when a person
uses several on that service.

## Installation

Run:

```
cp .env.example .env
docker run --rm --interactive --tty -v $(pwd):/app composer:2.1 install
```

> Note: `composer:2.1` pins the composer version according to this repo's `composer.lock` packages.

To install vendor dependencies. Then, to boot the containers, use:

```
./vendor/bin/sail up
```

While keeping the containers up (booted by `sail up` command), in the separate terminal window run:

```
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

Note down the token given to you.

For convenience, you can setup a [Bash Alias](https://laravel.com/docs/9.x/sail#configuring-a-bash-alias) for the Sail command.

## Usage

```
curl 'http://localhost/api/whose-name/query?u=test@example.org&s=jira&q=slack' \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <YOURTOKEN>"
{"username":"U123456"}
```

Note: the `Accept` header is important for all requests.

The `username` field is a **string** when the asked service holds one name, an
**array of strings** when it holds several, and `null` (with a `404`) when there
is no match:

```
curl 'http://localhost/api/whose-name/query?u=U234567&s=slack&q=email' \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <YOURTOKEN>"
{"username":["other@example.org","new@example.org"]}
```

See the [whose-name-client](https://github.com/makimo/whose-name-client) repository for a client of this API.

### Batch query

To resolve many identities in a single request, `POST` a list of queries to the
`/api/whose-name/query/batch` endpoint. Each query is a `{"u","s","q"}` triple with
the same meaning as the single query endpoint (`u` = known username, `s` = its service,
`q` = the service you ask about).

```
curl -X POST 'http://localhost/api/whose-name/query/batch' \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer <YOURTOKEN>" \
    -d '{"queries":[
          {"u":"test@example.org","s":"jira","q":"slack"},
          {"u":"other@example.org","s":"jira","q":"slack"},
          {"u":"unknown","s":"unknown","q":"unknown"}
        ]}'
[{"username":"U123456"},{"username":"U234567"},{"username":null}]
```

The response is an array of `{"username": ...}` results in the **same order** as the
queries. As with the single endpoint, each `username` is a string, an array of
strings (when the asked service holds several names), or `null` when no match was
found. The endpoint returns:

- `200 OK` when every query resolved to a username,
- `207 Multi-Status` when at least one query returned `null`,
- `422 Unprocessable Entity` when the request body is malformed (each query must
  provide non-empty `u`, `s` and `q`; a batch may contain between 1 and 100 queries).

### Changing the Yaml file

By default, the project uses the `tests/whosename.yml` file. The file contains two users and is not suited for more extensive work or running a working copy of the API.

If you'd like to change the path, modify the following entry in your `.env` file.

```
WHOSENAME_YAML=tests/whosename.yml
```

Because of Docker containers, the file must be located inside the repository.

### Creating users and requesting tokens

For your convenience, the `db:seed` command created a user for you with the following credentials:

```
email: test@makimo.pl
password: test
```

If you need to create another user, use the `user:create` Artisan command:

```
./vendor/bin/sail artisan user:create someuser someone@example.org
```

The command will ask for a password for this user.

To request a token for a given user, you can use the `/api/request-token` API endpoint:

```
curl -X POST 'http://localhost/api/request-token' \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"test@makimo.pl","password":"test","title":"test@my-laptop","abilities":["whose-name"]}'
```

Where:

- `email` and `password` are valid user credentials
- `title` is a required token title. It should describe, where the token will be used (e.g. `"test@my-laptop"`)
- `abilities` is an array of abilities the token can access. For now, only the `whose-name` ability is available.

## Code

### Conventions

This repository follows the following conventions:

- [Tests](tests) communicate purpose of the code
- Domain separation into [domain](domain) logic, [infrastructure](infrastructure) and [application](routes/api.php) code
  - Framework is a client of the domain, not the owner
- [Architecture Decision Ledger](docs/adl) to communicate design decisions

### Structure

To get to know what does the code do, run the test suite:

```
./vendor/bin/sail test --group usage,behavior
```

To see all tests, including edge case tests, run the complete test suite:

```
./vendor/bin/sail test
```

See:

- [tests](tests) for more information on how to use the codebase
- [domain/WhoseName](domain/WhoseName) for domain objects. Specifically look for the Identity class.
- [infrastructure/WhoseName](infrastructure/WhoseName) for a Yaml file persistence implementation
- [routes/api.php](routes/api.php) for endpoint definitions
- [docs/adl](docs/adl) for design decisions

### Framework

[Laravel](https://laravel.com/) is a web application framework with expressive, elegant syntax, written in PHP.

Laravel has a thorough [documentation](https://laravel.com/docs) and video tutorial library, making it a breeze to get started with the framework.

## Production

A deploy workflow has been set up on the `main` branch for continuous deployment.

See [.github/workflows/deploy.yml](.github/workflows/deploy.yml) for more information.

The service is available under the [whosename.makimo.pl](https://whosename.makimo.pl) address.

### Administration and management

To manage users on production, you need access to access `sites@lambdadelta.pl` via SSH.

To add new users use the `./artisan user:create` command. See [Creating users and requesting tokens](#creating-users-and-requesting-tokens) section for more information.

To change or remove users, use `./artisan tinker` and play with `App\Models\User` Eloquent Model, e.g.

```php
$user = App\Models\User::where('email', 'someone@example.org')->find();
$user->delete();
```

### Working with data

The [whose-name-data](https://github.com/makimo/whose-name-data) handles all data for this service. See it's README for more information.
