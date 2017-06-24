# phpList 4 REST API

[![Build Status](https://travis-ci.org/phpList/rest-api.svg?branch=master)](https://travis-ci.org/phpList/rest-api)
[![Latest Stable Version](https://poser.pugx.org/phplist/rest-api/v/stable.svg)](https://packagist.org/packages/phpList/rest-api)
[![Total Downloads](https://poser.pugx.org/phplist/rest-api/downloads.svg)](https://packagist.org/packages/phpList/rest-api)
[![Latest Unstable Version](https://poser.pugx.org/phplist/rest-api/v/unstable.svg)](https://packagist.org/packages/phpList/rest-api)
[![License](https://poser.pugx.org/phplist/rest-api/license.svg)](https://packagist.org/packages/phpList/rest-api)


## About phpList

phpList is an open source newsletter manager.


## About this package

This module will be the REST API for phpList 4. It will use functionality from
the `phplist/phplist4-core` module (the phpList 4 core). It will not contain any SQL
queries, but use functionality from the new core for DB access.

This module is optional, i.e., it will be possible to run phpList 4 without the
REST API.

This new REST API can also be used to provide REST access to an existing
phpList 3 installation. For this, the phpList 3 installation and the phpList 4
installation with the REST API need to share the same database.


## Installation

Please install this package via Composer from within the
[phpList base distribution](https://github.com/phpList/base-distribution),
which also has more detailed installation instructions in the README.


## Contributing to this package

Please read the [contribution guide](.github/CONTRIBUTING.md) on how to
contribute and how to run the unit tests and style checks locally.

### Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold
this code.
