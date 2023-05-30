# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add support for RAR 5.0 archive format (https://www.rarlab.com/technote.htm#filehead)
- Add changelog

### Changed

- Change RarEntry::$packedSize data type from float to int
- Change RarEntry::$unpackedSize data type from string to int
- Upgrade dependencies

### Removed

- Remove support for 32-bit platforms

## [0.2.0] - 2021-01-06
