# 4. Validation

Reject bad input before it reaches the model. `Core\Model\Validation` takes an input array and a rules array:

```php
use Core\Model\Validation;

class TaskController
{
    public function store(Request $request, Response $response): void
    {
        $validation = new Validation($database); // needs a Database instance for `unique` rules

        $rules = ['title' => 'required|min:2|max:200'];

        if (!$validation->make($request->all(), $rules)) {
            $response->json(['errors' => $validation->errors()], 422);
            return;
        }

        $task = Task::create([
            'user_id' => (new Session())->get('user_id'),
            'title' => $request->input('title'),
            'completed' => false,
        ]);
        $response->json($task->toArray(), 201);
    }
}
```

`make()` returns `true`/`false`; `errors()` returns the full error set, `firstError('title')` returns just one field's first message if you only need to display one at a time. Available rules — `required`, `email`, `min`, `max`, `numeric`, `alpha`, `alphanumeric`, `match`, `unique`, `url` — are listed under [Validation in the top-level README](../../README.md#validation).

Construct `Validation` the same way `AuthController` does, with a real `Database` instance:

```php
use Core\Model\Database\Database;
use Core\Model\Logger;

$validation = new Validation(new Database(new Logger('logs/app.log')));
```

Next: [Views](05-views.md).
