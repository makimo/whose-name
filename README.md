# Whose-name

Given a file of the following structure:

```yml
-
  slack: U123456
  jira: test@example.org
-
  slack: U234567
  jira: other@example.org
```

This service allows answering questions of the following form:

> For one that calls themselves `test@example.org` on `jira`, what is their username on Slack? (Answer: `U123456`).

## Glossary

A set of usernames related to a person is called an **identity**.

## Installation

```
cp .env.example .env
docker run --rm --interactive --tty -v $(pwd):/app composer install
./vendor/bin/sail up
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
./vendor/bin/sail test
```

See:

- [tests/Unit](tests/Unit) and [tests/Feature](tests/Feature) for more information on how to use the codebase
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


