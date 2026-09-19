# 3. Database & Models

## Migration

```bash
php console make:migration create_tasks_table
```

Edit the generated file with the fluent schema builder (see the "Migrations & Models" section of the [top-level README](../../README.md#migrations--models) for the full `Blueprint` API):

```php
use Core\Model\Database\{Migration, Schema, Blueprint};

class CreateTasksTable extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create('tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('title');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('tasks');
    }
}
```

Run it:

```bash
php console migrate
```

## Model

```bash
php console make:model Task
```

```php
use Core\Model\Database\Model;

class Task extends Model
{
    protected array $fillable = ['user_id', 'title', 'completed'];
}
```

`getTable()` derives `tasks` automatically from the class name — see [Models](../fundamentals/models.md) if you need to override that.

## Wire the connection

Once, at bootstrap (`index.php`, alongside where `Router`/`Request`/`Response` are constructed):

```php
use Core\Model\Database\Connection;
use Core\Model\Database\Model;

Model::setConnection((new Connection())->make());
```

## Wire the model into the controller

```php
use App\Model\Task;
use Core\Model\Session;

class TaskController
{
    public function index(Request $request, Response $response): void
    {
        $userId = (new Session())->get('user_id'); // see part 6
        $tasks = Task::query()->where('user_id', $userId)->orderBy('created_at DESC')->get();
        $response->json(['tasks' => array_map(fn (Task $t) => $t->toArray(), $tasks)]);
    }

    public function store(Request $request, Response $response): void
    {
        $task = Task::create([
            'user_id' => (new Session())->get('user_id'),
            'title' => $request->input('title'),
            'completed' => false,
        ]);
        $response->json($task->toArray(), 201);
    }

    public function complete(Request $request, Response $response, string $id): void
    {
        $task = Task::find((int) $id);
        if (!$task) {
            $response->json(['error' => 'Not found'], 404);
            return;
        }
        $task->completed = true;
        $task->save();
        $response->json($task->toArray());
    }

    public function destroy(Request $request, Response $response, string $id): void
    {
        $task = Task::find((int) $id);
        $task?->delete();
        $response->json(['deleted' => (bool) $task]);
    }
}
```

`create()`/`save()`/`delete()` and `where()`/`orderBy()`/`get()` via `Task::query()` are covered in full in [Models](../fundamentals/models.md), including relationships and soft deletes if you extend this later (e.g. a `User hasMany Task`).

Next: [Validation](04-validation.md).
