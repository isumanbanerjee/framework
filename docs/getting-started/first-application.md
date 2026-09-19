# Your First Application

This walks through building a small "notes" API on top of the framework, tying together routing, a model, and a migration.

## 1. Define a route

```php
// index.php
use Core\Model\{Request, Response, Router};

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

$router->get('/notes', function ($req, $res) {
    $res->json(['notes' => Note::all()]);
});

$router->resolve();
```

## 2. Create a migration

```bash
php console make:migration create_notes_table
```

Edit the generated file under `Configuration/database/migrations/` (or wherever your project stores migrations):

```php
use Core\Model\Database\{Migration, Schema, Blueprint};

class CreateNotesTable extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('notes');
    }
}
```

Run it:

```bash
php console migrate
```

## 3. Create a model

```bash
php console make:model Note
```

```php
use Core\Model\Database\Model;

class Note extends Model
{
    protected array $fillable = ['title', 'body'];
}
```

Wire up the connection once, at bootstrap:

```php
use Core\Model\Database\Connection;

Model::setConnection((new Connection())->make());
```

## 4. Add the remaining routes

```php
$router->post('/notes', function ($req, $res) {
    $note = Note::create([
        'title' => $req->input('title'),
        'body' => $req->input('body'),
    ]);
    $res->json($note->toArray(), 201);
});

$router->get('/notes/{id}', function ($req, $res, $id) {
    $note = Note::find((int) $id);
    $note ? $res->json($note->toArray()) : $res->json(['error' => 'Not found'], 404);
});
```

Or skip the manual wiring entirely and use a resource route with `Core\Controller\ResourceController`:

```php
$router->resource('notes', NoteController::class);
```

See [Routing](../fundamentals/routing.md), [Models](../fundamentals/models.md), and [Controllers](../fundamentals/controllers.md) for the full picture.
