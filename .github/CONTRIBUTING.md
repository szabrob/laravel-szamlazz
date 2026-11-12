# Contribution Guide

Thank you for considering contributing to Laravel Szamlazz! Please review the following guidelines before submitting a pull request.

For significant changes, please open an issue first so we can discuss the approach.

## Process

1. Fork the project
2. Create a new branch
3. Code, test, commit, and push
4. Open a pull request detailing your changes

## Guidelines

- Ensure the coding style passes by running `composer lint`.
- Send a coherent commit history, making sure each commit in your pull request is meaningful.
- You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
- Please remember that we follow [SemVer](http://semver.org/).

## Setup

Clone your fork, then install the dev dependencies:

```bash
composer install
```

## Workbench test console

The workbench ships a browser UI that calls every `SzamlaAgent` method and shows the
result DTO, the sent request XML (credentials masked) and the raw Számla Agent response.

```bash
cp workbench/.env.example workbench/.env   # set SZAMLAZZ_API_KEY (use a test account)
composer clear                              # re-copy workbench/.env into the Testbench skeleton
composer serve
```

Then open `http://127.0.0.1:8000/szamlazz`. An Agent key can also be entered on the page;
it is kept only in the session. Every call hits the configured `SZAMLAZZ_BASE_URL`, so
real documents are created on the account.

## Lint

Lint your code:

```bash
composer lint
```

## Tests

Run all tests:

```bash
composer test
```
