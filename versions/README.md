# Versions

The PHPFramework version number has 3 digits: *major*, *minor*, and *release attempt*.

New *major* versions include *big new features*.

New *minor* versions include *small features, bugfixes and security fixes*.

The *release attempt* should not be relevant to you. If the release process goes fine, it will be 0. If something breaks during the release, or we release a heavy bug we will withdraw the release and publish a new one with an increased release attempt.

**Example:** 2.3.1 (major: 2, minor: 3, release attempt: 1)

## Backwards compatibility

Starting with version 2.0 the following rules apply:

* A new major release includes breaking changes. That means your code might break if you upgrade the framework. The release notes of each major version include instructions for migrating your code.
* A new minor release is fully compatible to the previous minor release - you should be able to upgrade without troubles (exception: if you were relying on a bug).

## Check for the version

`\AppZap\PHPFramework\Utility\Version`

| method | description |
| ------ | ----------- |
| `minimum($major, $minor)` | Returns true if the current version is `$major.$minor` or newer (higher) |
| `maximum($major, $minor)` | Returns true if the current version is `$major.$minor` or older (lower) |
