### Installation

```bash
composer require stylemix/laravel-translations
```

### Translation loader

Package registers own translator, which also takes string translations from `storage/app/lang`.
So when translating `trans('messages.hello')` it loads two files (if they exist):
- `resources/lang/en/messages.php`
- `storage/app/lang/en/messages.php`
and merges the arrays returned by these files (the last overrides previous)

### Translation module

Module adds translation interface in admin console. To enable just add the following to `config/concord.php`:

```php
'modules' => [
	Stylemix\Translations\Providers\ModuleServiceProvider::class,
]
```
and run migrations:
```bash
php artisan migrate
```

### Import strings
To import all existing lang string to database run the following command:
```bash
php artisan translations:import
```

### Auto registering strings
To enable auto registering add to `.env`:
```
TRANSLATIONS_AUTO_REGISTERING=true
```
