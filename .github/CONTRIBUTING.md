# Contributing to this project

:+1::tada: Many thanks for taking the time to contribute! :tada::+1:

When you contribute, please take the following things into account:


## Contributor Code of Conduct

Please note that this project is released with a
[Contributor Code of Conduct](../CODE_OF_CONDUCT.md). By participating in this
project, you agree to abide by its terms.


## Reporting an issue

We have provided an issue template that will help you create helpful tickets.


## Do not report an issue if …

* … you are asking how to use some feature. Please use
  [the phpList community](https://www.phplist.org/users/) for this purpose.
* … your issue is about a security vulnerability. Please
  [contact us directly](mailto:info@phplist.com) to report security issues.


## Avoid duplicated issues

Before you report an issue, please search through the existing issues here on
GitHub to see if your issue is already reported or fixed to make sure you are
not reporting a duplicated issue.

Also please make sure you have the latest version of this package and check if
the issue still exists.


# Contribute code, bug fixes or documentation (pull requests)

Third-party contributions are essential for keeping the project great.

We want to keep it as easy as possible to contribute changes that get things
working in your environment.

There are a few guidelines that we need contributors to follow so that we can
have a chance of keeping on top of things:

1. Make sure you have a [GitHub account](https://github.com/join).
2. [Fork this Git repository](https://guides.github.com/activities/forking/).
3. Clone your forked repository and install the development dependencies doing
   a `composer install`.
4. Add a local remote "upstream" so you will be able to
   [synchronize your fork with the original repository](https://help.github.com/articles/syncing-a-fork/).
5. Create a local branch for your changes.
6. Add unit tests for your changes (if your changes are code-related).
   These tests should fail without your changes.
7. Add your changes. Your added unit tests now should pass, and no other tests
   should be broken. Check that your changes follow the
   [coding style](#coding-style).
8. Add a changelog entry.
9. [Commit](#git-commits) and push your changes.
10. [Create a pull request](https://help.github.com/articles/about-pull-requests/)
    for your changes. Check that the [Github actions](https://github.com/phpList/rest-api/actions/workflows/ci.yml) build is green. (If it is not, fix the
    problems listed by [Github actions](https://github.com/phpList/rest-api/actions/workflows/ci.yml).)
    We have provided a template for pull requests as well.
11. [Request a review](https://help.github.com/articles/about-pull-request-reviews/).
11. Together with your reviewer, polish your changes until they are ready to be
    merged.

## API Documenting with `OPENAPI`

Controllers are annotated with the [`OpenAPI`](https://swagger.io/docs/specification/about/) specification using the `PHPDoc` implementation from [zircote/swagger-php](https://github.com/zircote/swagger-php). 

If you add or modify existing annotations, you should run `composer openapi-generate` to have the updated openapi decription of the API.

### Notes
- `composer openapi-generate` produces `openapi.json` in `docs/`
-  The generated `docs/openapi.json` is excluded in commits. _See .gitignore_
-  The only reason you should generate `openapi.json` is for debugging and testing.

### Debugging `openapi.json` description.

To ensure builds pass and new annotations are deployed, do validate `openapi.json` by copy-pasting it's content in `https://validator.swagger.io/`

In addition you can also use the [openapi-checker](github.com/phplist/openapi-checker) to validate you file as follows;

- `npm install -g openapi-checker`
- `openapi-checker docs/openapi.json`

### More on `composer openapi-generate` 

`composer openapi-generate` basically runs `vendor/bin/openapi -o docs/openapi.json --format json src` defined in the scripts section of `composer.json` which as mentioned above generates `openapi.json` description file in the `docs/` directory.

### Swagger UI

[Swagger UI](https://github.com/swagger-api/swagger-ui) is used to visualize generated api description and is visible at [phplist.github.io/restapi-docs](https://phplist.github.io/restapi-docs) after a successful CI build.

You might also achieve local visualization by cloning [phplist/restapi-docs](https://github.com/phpList/restapi-docs) and temporally changing the `url` property of `SwaggerUIBundle` to point to your generated file.


## Unit-test your changes

Please cover all changes with automatic tests and make sure that your code does
not break any existing tests. We will only merge pull request that include full
code coverage of the fixed bugs and the new features.

### Running the unit tests

To run the existing unit tests, run this command:

```bash
vendor/bin/phpunit tests/Unit/
```

### Running the integration tests

For being able to run the integration tests, you will need a local MySQL
database and a user with access permissions to that database.

After you have created the database and the user, please import the database
schema once. Assuming that your database is named `phplist_test`, the user is
named `phplist`, and the password is `batterystaple`, the command looks like
this:

```bash
mysql -u phplist_test --password=batterystaple phplist_test < vendor/phplist/core/resources/Database/Schema.sql
```

When running the integration tests, you will need to specify the database name
and access credentials on the command line (in the same line):

```bash
PHPLIST_DATABASE_NAME=phplist_test PHPLIST_DATABASE_USER=phplist PHPLIST_DATABASE_PASSWORD=batterystaple vendor/bin/phpunit -c config/PHPUnit/phpunit.xml tests/Integration/
```


## Coding Style

Please make your code clean, well-readable and easy to understand.

Please use the same coding style ([PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md))
as the rest of the code. Indentation for all files is four spaces.

We will only merge pull requests that follow the project's coding style.

Please check your code with the provided PHP_CodeSniffer standard:

```bash
vendor/bin/phpcs --standard=vendor/phplist/core/config/PhpCodeSniffer/ src/ tests/
```

Please also check the code structure using PHPMD:

```bash
vendor/bin/phpmd src/ text vendor/phplist/core/config/PHPMD/rules.xml
```

And also please run the static code analysis:

```bash
vendor/bin/phpstan analyse -l 5 src/ tests/
```

You can also run all code style checks using one long line from a bash shell:

```bash
find src/ tests/ -name '*.php' -print0 | xargs -0 -n 1 -P 4 php -l && vendor/bin/phpstan analyse -l 5 src/ tests/ && vendor/bin/phpmd src/ text vendor/phplist/core/config/PHPMD/rules.xml && vendor/bin/phpcs --standard=vendor/phplist/core/config/PhpCodeSniffer/ src/ tests/
```

This will execute all tests except for the unit tests and the integration
tests.
