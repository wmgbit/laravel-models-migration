# Laravel models migration
Put Laravel migration definitions inside models, and keep them synchronized with the database

## Installation
```console
composer require wmgbit/laravel-models-migration
```

## Inside the model class
```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class ModelExample extends Model 
{

    function migration(Blueprint $table)
    {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->foreignId('user_id')->constrained();
        $table->unique(['name','user_id']);
        $table->rememberToken();
        $table->timestamps();
    }

}
```

## Command
Run the given command to run the laravel core migration script plus the definitions found in model classes againt the database
```php
php artisan migrate:models
```

## Limits
The command will keep the columns data if their names are the same.
However, there is no way implemented to rename a given column. So changing a column name will be simply a REMOVE plus ADD task for that given column. May be added in future release.


## Execution order
The command detects and handles the dependencies between models based on the definition of the foreign key constraints

## Credit
This package is a fork from [Laravel Automatic Migrations](https://github.com/bastinald/laravel-automatic-migrations/)
