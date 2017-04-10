## laravel-require
---

This Laravel 5 package provides a `require:package` artisan command, which first installs a package via composer, then attempts to automatically register its service provider.  This makes it even easier to install a Laravel package!

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
#### Example Usage

```
$ php artisan require:package laracasts/flash
```
This installs and registers the `flash` package from laracasts.

```
$ php artisan require:package laracasts/flash --scan
``` 
This will scan the contents of PHP files in the package in order to locate the Service Providers.  Use this method if `laravel-require` can't find the service provider for a package.

---
#### License

This package is open-source software, released under the [MIT license](LICENSE).

