# Changelog

## [2.0.2] - 2026-03-14

### Fixed
- Constructor signature compatibility with DataBase interface
- ODBCQuery pagination now works with MSSQL (TOP/OFFSET FETCH instead of LIMIT)
- ODBCMetaData table quoting works across database engines
- ODBCMetaData filters to TABLE type only (excludes system tables)

### Added
- Uniform test suite (16 tests, 18 assertions)


## [2.0.2] - 2026-03-14

### Added
- Uniform database driver test suite with Docker support
- phpunit.xml configuration
- GitHub Actions CI workflow
- MIT LICENSE file

### Changed
- Removed redundant classmap autoloading (PSR-4 only)
- Added PHP >= 8.1 requirement to composer.json
