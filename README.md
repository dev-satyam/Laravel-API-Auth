# Laravel API Auth
Pratical step-by-step how to do a RESTful API in Laravel 8 with authentication by email and password using Laravel Sancutum

### Prerequisites
- Apache
- PHP
- Composer
- [Laravel new app created]([https://link](https://laravel.com/docs/8.x/installation#meet-laravel))
  
### Initial notes
The project in this repostory contains all the steps finalized

### Step 1 - Add Laravel Sancutum to composer.json
In the project dir run
```console
composer require laravel/sanctum 
```


### Step 2 - You should publish the Sanctum configuration and migration files using the vendor:publish Artisan command. 

```console 
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" 
```

### Step 3 - Run migrations

```console 
php artisan migrate 
```


### Step 4 - Add HasApiTokens at app/User.php

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

## References
- [Laravel docs](https://laravel.com/docs/8.x) - Laravel Documentation
- [Laravel Sanctum Post](https://laravel.com/docs/8.x/sanctum#introduction) - Create REST API with authentication
