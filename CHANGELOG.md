# phpList 4 REST API change log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).


## x.y.z (next release)

### Added
- Run the system test on Travis (#113)
- Add security headers to the default response (#110)
- Whitelist BadRequestHttpException so that messages are not sanitized (#108)

### Changed

### Deprecated

### Removed

### Fixed

## 4.0.0-alpha2

### Added
- REST API endpoint for deleting a session (log out) (#101)
- REST API endpoint for deleting a list (#98)
- REST API endpoint for getting list details (#89)
- System tests for the test and dev environment (#81)
- REST action for getting all subscribers for a list (#83)

### Changed
- Move the PHPUnit configuration file (#99)
- Use the renamed phplist/core package (#97)
- Adopt more of the default Symfony project structure (#92, #93, #94, #95, #102, #106)

### Deprecated

### Removed

### Fixed
- Prevent crashes from sensio/framework-extra-bundle updates (#105)
- Make the exception codes 32-bit safe (#100)
- Always truncate the DB tables after an integration test (#86)

### Security
