A Gitea Storage filesystem for [Flysystem](https://flysystem.thephpleague.com/docs/).

[![Latest Version](https://img.shields.io/packagist/v/infernalmedia/flysystem-gitea-storage.svg?style=flat-square)](https://packagist.org/packages/infernalmedia/flysystem-gitea-storage)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/infernalmedia/flysystem-gitea-storage.svg?style=flat-square)](https://packagist.org/packages/infernalmedia/flysystem-gitea-storage)

This package contains a Flysystem adapter for Gitea. Under the hood, Gitea's [API](https://docs.gitea.com/development/api-usage) v1 is used.

This is a fork from the marvelous [RoyVoetman/flysystem-gitlab-storage](https://github.com/RoyVoetman/flysystem-gitlab-storage) package which has been adapted to work with Gitea's API.

## Installation

```bash
composer require infernalmedia/flysystem-gitea-storage
```

## Usage
```php
// Create a Gitea Client to talk with the API
$client = new Client('username', 'repository', 'branch', 'base-url', 'personal-access-token');
   
// Create the Adapter that implements Flysystems AdapterInterface
$adapter = new GiteaAdapter(
    // Gitea API Client
    $client,
    // Optional path prefix
    'path/prefix',
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);

// see http://flysystem.thephpleague.com/api/ for full list of available functionality
```
### Username

This is the username or the organization name under which repositories are stored.

### Repository

Name of the repository.

### Base URL
This will be the URL where you host your gitea server (e.g. https://gitea.com)

### Access token (required for private projects)
Gitea supports server side API authentication with Personal Access tokens

Personal Access Token can be created from the Settings page of your user account.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Contributions are **welcome** and will be fully **credited**. We accept contributions via Pull Requests on [Github](https://github.com/infernalmedia/flysystem-gitea-storage).

### Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).
- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Create feature branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
