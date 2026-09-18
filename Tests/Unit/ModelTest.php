<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Model;
use PDO;
use PHPUnit\Framework\TestCase;

class Author extends Model
{
    protected array $fillable = ['name'];
}

class Book extends Model
{
    protected array $fillable = ['title', 'author_id'];
}

final class ModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE authors (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT,
             created_at TEXT, updated_at TEXT)'
        );
        $this->pdo->exec(
            'CREATE TABLE books (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, author_id INTEGER,
             created_at TEXT, updated_at TEXT)'
        );
        Model::setConnection($this->pdo);
    }

    public function testTableNameInference(): void
    {
        $this->assertSame('authors', (new Author())->getTable());
        $this->assertSame('books', (new Book())->getTable());
    }

    public function testCreatePersistsAndAssignsId(): void
    {
        $author = Author::create(['name' => 'Ada']);

        $this->assertTrue($author->exists());
        $this->assertSame(1, $author->id);
        $this->assertSame('Ada', $author->name);
    }

    public function testFind(): void
    {
        $created = Author::create(['name' => 'Grace']);

        $found = Author::find($created->id);
        $this->assertNotNull($found);
        $this->assertSame('Grace', $found->name);
    }

    public function testFindReturnsNullWhenMissing(): void
    {
        $this->assertNull(Author::find(999));
    }

    public function testAll(): void
    {
        Author::create(['name' => 'A']);
        Author::create(['name' => 'B']);

        $all = Author::all();
        $this->assertCount(2, $all);
        $this->assertContainsOnlyInstancesOf(Author::class, $all);
    }

    public function testUpdateViaSave(): void
    {
        $author = Author::create(['name' => 'Original']);
        $author->name = 'Updated';
        $author->save();

        $this->assertSame('Updated', Author::find($author->id)->name);
        $this->assertCount(1, Author::all());
    }

    public function testDelete(): void
    {
        $author = Author::create(['name' => 'Temp']);
        $id = $author->id;

        $this->assertTrue($author->delete());
        $this->assertNull(Author::find($id));
        $this->assertFalse($author->exists());
    }

    public function testTimestampsSetOnCreate(): void
    {
        $author = Author::create(['name' => 'Stamped']);

        $this->assertNotNull($author->created_at);
        $this->assertNotNull($author->updated_at);
    }

    public function testMassAssignmentRespectsFillable(): void
    {
        $author = new Author(['name' => 'Legit', 'is_admin' => true]);

        $this->assertSame('Legit', $author->name);
        $this->assertNull($author->is_admin);
    }

    public function testToArray(): void
    {
        $author = new Author(['name' => 'Ada']);
        $this->assertSame(['name' => 'Ada'], $author->toArray());
    }

    public function testHasManyRelationship(): void
    {
        $author = Author::create(['name' => 'Prolific']);
        Book::create(['title' => 'First', 'author_id' => $author->id]);
        Book::create(['title' => 'Second', 'author_id' => $author->id]);
        Book::create(['title' => 'Other', 'author_id' => 999]);

        $books = $author->hasMany(Book::class, 'author_id');

        $this->assertCount(2, $books);
        $this->assertContainsOnlyInstancesOf(Book::class, $books);
    }

    public function testBelongsToRelationship(): void
    {
        $author = Author::create(['name' => 'Writer']);
        $book = Book::create(['title' => 'A Book', 'author_id' => $author->id]);

        $owner = $book->belongsTo(Author::class, 'author_id');

        $this->assertNotNull($owner);
        $this->assertSame('Writer', $owner->name);
    }
}
