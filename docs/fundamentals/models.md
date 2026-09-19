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

### Many-to-many

```php
class Post extends Model
{
    public function tags(): array
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
    }
}
```

`belongsToMany()` joins the pivot table (`post_tag`) to the related table and returns an array of hydrated related models.

### Eager loading

Each relationship above is a plain method, so accessing it lazily (`$post->tags()`) always re-queries. To resolve relationships up front and cache them on the instance, use `load()`/`with()`:

```php
$post = Post::find(1)->load('tags', 'author');
$post->tags;   // cached result from load(), via magic __get
$post->author;

$posts = Post::with('author'); // loads 'author' onto every row from all()
```

`with()`/`load()` call the named zero-argument relationship methods and cache their results per instance — they don't batch the underlying queries into a single `JOIN`/`WHERE IN`, so still expect one query per model per relation.

## Soft deletes

Opt a model into soft deletes to have `delete()` set a timestamp column instead of removing the row:

```php
class Post extends Model
{
    protected bool $softDeletes = true; // uses 'deleted_at' by default
}

$post->delete();     // sets deleted_at, row stays in the table
$post->trashed();    // true
$post->restore();    // clears deleted_at

Post::find($id);          // excludes soft-deleted rows
Post::withTrashed()->get();  // includes them
Post::onlyTrashed()->get();  // only soft-deleted rows

$post->forceDelete(); // bypasses soft deletes, removes the row
```

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
