# Laravel Package Maker

Changes between versions will be tracked in the [CHANGELOG](CHANGELOG.md).

## Installation

```bash
composer require ambersive/packagemaker --dev
```

#### Optional: Publish the config

```bash
php artisan vendor:publish --tag=packagemaker
```

## Usage


### Command

```bash
 php artisan make:package ambersive/demo 
```

This command will create a scaffold for your next laravel project. Addtional options are:

***--force***

Will force the execution. Otherwise the command will not execute if a package with the same name exists.

***--composer***

Will add the package to the composer.json file of the laravel installation. It will also execute a "**compose require PACKAGE_NAME**".

## Feedback

Please feel free to give us feedback or any improvement suggestions.

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to Manuel Pirker-Ihl via [manuel.pirker-ihl@ambersive.com](mailto:manuel.pirker-ihl@ambersive.com). All security vulnerabilities will be promptly addressed.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
