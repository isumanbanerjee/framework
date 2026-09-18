<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\UploadedFile;
use PHPUnit\Framework\TestCase;

final class UploadedFileTest extends TestCase
{
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function makeTempSource(string $contents = 'data'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $contents);
        $this->tmpFiles[] = $path;

        return $path;
    }

    public function testFromArrayPopulatesMetadata(): void
    {
        $file = UploadedFile::fromArray([
            'name' => 'avatar.PNG',
            'type' => 'image/png',
            'size' => 2048,
            'tmp_name' => '/tmp/php123',
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertSame('avatar.PNG', $file->getClientOriginalName());
        $this->assertSame('image/png', $file->getMimeType());
        $this->assertSame(2048, $file->getSize());
        $this->assertSame('png', $file->extension());
        $this->assertTrue($file->isValid());
    }

    public function testInvalidWhenUploadError(): void
    {
        $file = UploadedFile::fromArray([
            'name' => 'x.txt',
            'type' => 'text/plain',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ]);

        $this->assertFalse($file->isValid());
        $this->assertFalse($file->validate([]));
    }

    public function testValidateMaxSize(): void
    {
        $file = new UploadedFile('big.jpg', 'image/jpeg', 5000, '/tmp/x');

        $this->assertFalse($file->validate(['max' => 4096]));
        $this->assertTrue($file->validate(['max' => 8192]));
    }

    public function testValidateExtensions(): void
    {
        $file = new UploadedFile('doc.pdf', 'application/pdf', 100, '/tmp/x');

        $this->assertTrue($file->validate(['extensions' => ['pdf', 'doc']]));
        $this->assertFalse($file->validate(['extensions' => ['png', 'jpg']]));
    }

    public function testHashNamePreservesExtension(): void
    {
        $file = new UploadedFile('photo.jpeg', 'image/jpeg', 1, '/tmp/x');
        $hash = $file->hashName();

        $this->assertStringEndsWith('.jpeg', $hash);
        $this->assertNotSame('photo.jpeg', $hash);
    }

    public function testStoreMovesFileWithGivenName(): void
    {
        $source = $this->makeTempSource('hello');
        $file = new UploadedFile('note.txt', 'text/plain', 5, $source);

        $dir = sys_get_temp_dir() . '/uploads_' . uniqid();
        $stored = $file->store($dir, 'saved.txt');
        $this->tmpFiles[] = $stored;

        $this->assertFileExists($stored);
        $this->assertSame('hello', file_get_contents($stored));
        $this->assertStringEndsWith('/saved.txt', $stored);

        // cleanup dir
        @rmdir($dir);
    }
}
