# phpList 4 REST API

[![Build Status](https://travis-ci.org/phpList/rest-api.svg?branch=master)](https://travis-ci.org/phpList/rest-api)
[![Latest Stable Version](https://poser.pugx.org/phplist/rest-api/v/stable.svg)](https://packagist.org/packages/phpList/rest-api)
[![Total Downloads](https://poser.pugx.org/phplist/rest-api/downloads.svg)](https://packagist.org/packages/phpList/rest-api)
[![Latest Unstable Version](https://poser.pugx.org/phplist/rest-api/v/unstable.svg)](https://packagist.org/packages/phpList/rest-api)
[![License](https://poser.pugx.org/phplist/rest-api/license.svg)](https://packagist.org/packages/phpList/rest-api)


## About phpList

phpList is an open source newsletter manager.


## About this package

This module is the REST API for phpList 4, providing functions for superusers
to manage lists, subscribers and subscriptions via REST calls. It uses
functionality from the `phplist/core` module (the phpList 4 core).
It does not contain any SQL queries, uses functionality from the new core for
DB access.

This module is optional, i.e., it is possible to run phpList 4 without the
REST API.

This new REST API can also be used to provide REST access to an existing
phpList 3 installation. For this, the phpList 3 installation and the phpList 4
installation with the REST API need to share the same database. For security
reasons, the REST APIs from phpList 3 and phpList 4 should not be used for the
same database in parallel, though.


## Installation

Please install this package via Composer from within the
[phpList base distribution](https://github.com/phpList/base-distribution),
which also has more detailed installation instructions in the README.

## Local demo with Postman

You can try out the API using pre-prepared requests and the Postman GUI 
tool. Install Postman as a browser extension or stand-alone app, open the 
[phpList 4 REST API Demo collection](https://documenter.getpostman.com/view/3293511/phplist-4-rest-api-demo/RVftkC9t#4710e871-973d-46fa-94b7-727fdc292cd5)
and click "Run in Postman".


## Contributing to this package

Please read the [contribution guide](.github/CONTRIBUTING.md) on how to
contribute and how to run the unit tests and style checks locally.

### Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold
this code.
