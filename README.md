## laravel-require
---

This Laravel 5 package provides a `require:package` artisan command, which first installs a package via composer, then attempts to automatically register its service provider(s) and facades.  

This makes it even easier to install a Laravel package!

*Written and tested with Laravel 5.4.*

---
#### Installation

You may install this package using composer:
	`composer require patinthehat/laravel-require`
	
Once it's installed, you must add the service provider to the 'providers' section in your `config/app.php` file:

```php
LaravelRequire\LaravelRequireServiceProvider::class,
```

You will now have a `require:package {package-name}` command available in artisan. It will attempt to automatically register the service provider for a package after installation, and will let you know if it is unable to do so.  If this happens, you will have to register the package manually.

---
#### Requirements

In order for `laravel-require` to work properly, you must either have `composer.phar` in your project's base directory or have the `composer` command available in your environment's PATH variable.

---
#### Example Usage

```
$ php artisan require:package laracasts/flash
```
This installs and registers the `flash` package from laracasts.

```
$ php artisan require:package laracasts/flash --register-only
``` 
This will skip running the `composer require` command entirely, and only attempt to register the package's Service Providers and Facades.

---
#### How it works

`laravel-require` first creates a list of files in the package that might contain a Service Provider or Facade.  It first attempts to locate Service Providers/Facades through matching filenames.  If this fails, the contents of the files are scanned to locate the Service Providers and Facades.

---
#### License

This package is open-source software, released under the [MIT license](LICENSE).

