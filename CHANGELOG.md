# phpList 4 REST API change log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).


## x.y.z

### Added
- REST API endpoint for deleting a list (#98)
- REST API endpoint for getting list details (#89)
- System tests for the test and dev environment (#81)
- REST action for getting all subscribers for a list (#83)

### Changed
- Move the PHPUnit configuration file (#99)
- Use the renamed phplist/core package (#97)
- Adopt more of the default Symfony project structure (#92, #93, #94, #95)

### Deprecated

### Removed

### Fixed
- Always truncate the DB tables after an integration test (#86)

### Security
