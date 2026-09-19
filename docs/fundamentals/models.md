# Models

`Core\Model\Database\Model` is a small Active Record base class: each subclass maps to a table, exposes magic-property attribute access, and knows how to save/delete itself.

## Defining a model

```php
use Core\Model\Database\Model;

class User extends Model
{
    protected array $fillable = ['email', 'password'];
}
```

`getTable()` derives the table name automatically from the class name (snake_case, naively pluralized) — override it if your table doesn't follow that convention:

```php
class Person extends Model
{
    protected function getTable(): string
    {
        return 'people';
    }
}
```

## Connecting

All models share one PDO connection, set once at bootstrap:

```php
use Core\Model\Database\Connection;

Model::setConnection((new Connection())->make());
```

## CRUD

```php
$user = User::create(['email' => 'ada@example.com', 'password' => $hash]);

$found = User::find($user->id);   // null if not found
$all = User::all();

$found->email = 'ada.l@example.com'; // magic __set
$found->save();                       // updates, since the record already exists

$found->delete();
```

`fill()` only assigns keys listed in `$fillable`; attributes outside that list are silently ignored, so mass-assigning `$_POST` directly is safe as long as `$fillable` is scoped correctly.

## Relationships

```php
class User extends Model
{
    protected array $fillable = ['email'];

    public function posts(): array
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class Post extends Model
{
    protected array $fillable = ['title', 'user_id'];

    public function author(): ?User
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

`hasMany()` returns an array of hydrated model instances; `belongsTo()` returns a single instance or `null`.

## Querying below the model layer

For anything beyond simple CRUD, drop down to the query builder directly:

```php
$admins = User::query()
    ->where('role', 'admin')
    ->orderBy('created_at DESC')
    ->get();
```

## Scaffolding

```bash
php console make:model User
php console make:migration create_users_table
php console migrate
```

See the "Migrations & Models" section in the [top-level README](../../README.md#migrations--models) for `Blueprint`/`Schema` usage, and [Controllers](controllers.md) for wiring models into request handling.
