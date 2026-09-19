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

    public function author(): ?Model
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function tags(): array
    {
        return $this->belongsToMany(Tag::class, 'book_tag', 'book_id', 'tag_id');
    }
}

class Tag extends Model
{
    protected array $fillable = ['name'];
}

class TrashedNote extends Model
{
    protected string $table = 'trashed_notes';
    protected array $fillable = ['body'];
    protected bool $softDeletes = true;
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
        $this->pdo->exec(
            'CREATE TABLE tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT,
             created_at TEXT, updated_at TEXT)'
        );
        $this->pdo->exec(
            'CREATE TABLE book_tag (book_id INTEGER, tag_id INTEGER)'
        );
        $this->pdo->exec(
            'CREATE TABLE trashed_notes (id INTEGER PRIMARY KEY AUTOINCREMENT, body TEXT,
             created_at TEXT, updated_at TEXT, deleted_at TEXT)'
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

    public function testBelongsToManyRelationship(): void
    {
        $book = Book::create(['title' => 'Tagged Book']);
        $fiction = Tag::create(['name' => 'Fiction']);
        $classic = Tag::create(['name' => 'Classic']);
        Tag::create(['name' => 'Unrelated']);

        $this->pdo->exec("INSERT INTO book_tag (book_id, tag_id) VALUES ({$book->id}, {$fiction->id})");
        $this->pdo->exec("INSERT INTO book_tag (book_id, tag_id) VALUES ({$book->id}, {$classic->id})");

        $tags = $book->tags();

        $this->assertCount(2, $tags);
        $this->assertContainsOnlyInstancesOf(Tag::class, $tags);
        $this->assertSame(['Fiction', 'Classic'], array_map(fn (Tag $tag) => $tag->name, $tags));
    }

    public function testLoadEagerLoadsNamedRelation(): void
    {
        $author = Author::create(['name' => 'Loader']);
        $book = Book::create(['title' => 'Loaded', 'author_id' => $author->id]);

        $book->load('author');

        $this->assertSame('Loader', $book->getRelation('author')->name);
        $this->assertSame('Loader', $book->author->name);
    }

    public function testLoadThrowsForUndefinedRelation(): void
    {
        $this->expectException(\RuntimeException::class);

        (new Author())->load('nope');
    }

    public function testWithEagerLoadsRelationForAllModels(): void
    {
        $author = Author::create(['name' => 'Batch']);
        Book::create(['title' => 'One', 'author_id' => $author->id]);
        Book::create(['title' => 'Two', 'author_id' => $author->id]);

        $books = Book::with('author');

        $this->assertCount(2, $books);
        foreach ($books as $book) {
            $this->assertSame('Batch', $book->author->name);
        }
    }

    public function testSoftDeleteHidesRowFromDefaultQueries(): void
    {
        $note = TrashedNote::create(['body' => 'Temp']);
        $id = $note->id;

        $this->assertTrue($note->delete());

        $this->assertNull(TrashedNote::find($id));
        $this->assertTrue($note->trashed());
        $this->assertCount(0, TrashedNote::all());
    }

    public function testSoftDeletedRowIsStillInDatabase(): void
    {
        $note = TrashedNote::create(['body' => 'Kept']);
        $note->delete();

        $stillThere = TrashedNote::withTrashed()->where('id', $note->id)->first();
        $this->assertNotNull($stillThere);
        $this->assertNotNull($stillThere['deleted_at']);
    }

    public function testOnlyTrashedReturnsSoftDeletedRowsOnly(): void
    {
        TrashedNote::create(['body' => 'Alive']);
        $dead = TrashedNote::create(['body' => 'Dead']);
        $dead->delete();

        $trashed = TrashedNote::onlyTrashed()->get();
        $this->assertCount(1, $trashed);
        $this->assertSame('Dead', $trashed[0]['body']);
    }

    public function testRestoreClearsDeletedAt(): void
    {
        $note = TrashedNote::create(['body' => 'Bouncy']);
        $note->delete();
        $this->assertTrue($note->trashed());

        $this->assertTrue($note->restore());
        $this->assertFalse($note->trashed());
        $this->assertNotNull(TrashedNote::find($note->id));
    }

    public function testForceDeleteRemovesRowPermanently(): void
    {
        $note = TrashedNote::create(['body' => 'Gone']);
        $id = $note->id;

        $this->assertTrue($note->forceDelete());

        $this->assertNull(TrashedNote::withTrashed()->where('id', $id)->first());
    }
}
