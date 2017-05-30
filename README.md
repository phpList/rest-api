# phpList 4 REST API

This module will be the REST API for phpList 4. It will use functionality from
the `phplist/phplist4-core` module (the phpList 4 core). It will not contain any SQL
queries, but use functionality from the new core for DB access.

This module is optional, i.e., it will be possible to run phpList 4 without the
REST API.

This new REST API can also be used to provide REST access to an existing
phpList 3 installation. For this, the phpList 3 installation and the phpList 4
installation with the REST API need to share the same database.
