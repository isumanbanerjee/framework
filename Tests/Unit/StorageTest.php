<?php

namespace Tests\Unit;

use Core\Model\Storage;
use PHPUnit\Framework\TestCase;

/**
 * Storage System Tests
 */
class StorageTest extends TestCase
{
    private Storage $storage;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/test_storage_' . uniqid();
        mkdir($this->testDir, 0755, true);
        
        $this->storage = new Storage('local', [
            'root' => $this->testDir,
            'public_url' => '/storage'
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
        parent::tearDown();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) return;
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($path);
    }

    public function testStorageCanPutFile(): void
    {
        $result = $this->storage->put('test.txt', 'Hello World');
        
        $this->assertTrue($result);
        $this->assertTrue($this->storage->exists('test.txt'));
    }

    public function testStorageCanGetFile(): void
    {
        $this->storage->put('test.txt', 'Hello World');
        
        $contents = $this->storage->get('test.txt');
        
        $this->assertEquals('Hello World', $contents);
    }

    public function testStorageExists(): void
    {
        $this->assertFalse($this->storage->exists('nonexistent.txt'));
        
        $this->storage->put('exists.txt', 'content');
        
        $this->assertTrue($this->storage->exists('exists.txt'));
    }

    public function testStorageCanDeleteFile(): void
    {
        $this->storage->put('delete.txt', 'content');
        $this->assertTrue($this->storage->exists('delete.txt'));
        
        $result = $this->storage->delete('delete.txt');
        
        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists('delete.txt'));
    }

    public function testStorageCanDeleteMultipleFiles(): void
    {
        $this->storage->put('file1.txt', 'content1');
        $this->storage->put('file2.txt', 'content2');
        
        $result = $this->storage->delete(['file1.txt', 'file2.txt']);
        
        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists('file1.txt'));
        $this->assertFalse($this->storage->exists('file2.txt'));
    }

    public function testStorageCanCopyFile(): void
    {
        $this->storage->put('original.txt', 'content');
        
        $result = $this->storage->copy('original.txt', 'copy.txt');
        
        $this->assertTrue($result);
        $this->assertTrue($this->storage->exists('original.txt'));
        $this->assertTrue($this->storage->exists('copy.txt'));
        $this->assertEquals('content', $this->storage->get('copy.txt'));
    }

    public function testStorageCanMoveFile(): void
    {
        $this->storage->put('old.txt', 'content');
        
        $result = $this->storage->move('old.txt', 'new.txt');
        
        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists('old.txt'));
        $this->assertTrue($this->storage->exists('new.txt'));
    }

    public function testStorageCanGetSize(): void
    {
        $this->storage->put('sized.txt', '12345');
        
        $size = $this->storage->size('sized.txt');
        
        $this->assertEquals(5, $size);
    }

    public function testStorageCanGetLastModified(): void
    {
        $this->storage->put('modified.txt', 'content');
        
        $time = $this->storage->lastModified('modified.txt');
        
        $this->assertIsInt($time);
        $this->assertGreaterThan(0, $time);
    }

    public function testStorageCanGetMimeType(): void
    {
        $this->storage->put('test.txt', 'content');
        
        $mime = $this->storage->mimeType('test.txt');
        
        $this->assertIsString($mime);
    }

    public function testStorageCanGenerateUrl(): void
    {
        $url = $this->storage->url('images/photo.jpg');
        
        $this->assertEquals('/storage/images/photo.jpg', $url);
    }

    public function testStorageCanMakeDirectory(): void
    {
        $result = $this->storage->makeDirectory('testdir');
        
        $this->assertTrue($result);
        $this->assertTrue(is_dir($this->testDir . '/testdir'));
    }

    public function testStorageCanDeleteDirectory(): void
    {
        $this->storage->makeDirectory('testdir');
        $this->storage->put('testdir/file.txt', 'content');
        
        $result = $this->storage->deleteDirectory('testdir');
        
        $this->assertTrue($result);
        $this->assertFalse(is_dir($this->testDir . '/testdir'));
    }

    public function testStorageCanListFiles(): void
    {
        $this->storage->put('file1.txt', 'content');
        $this->storage->put('file2.txt', 'content');
        
        $files = $this->storage->files();
        
        $this->assertIsArray($files);
        $this->assertGreaterThanOrEqual(2, count($files));
    }

    public function testStorageCanSetAllowedExtensions(): void
    {
        $storage = $this->storage->setAllowedExtensions(['txt', 'pdf']);
        
        $this->assertInstanceOf(Storage::class, $storage);
    }

    public function testStorageCanSetMaxFileSize(): void
    {
        $storage = $this->storage->setMaxFileSize(1024 * 1024);
        
        $this->assertInstanceOf(Storage::class, $storage);
    }

    public function testStorageTemporaryUrl(): void
    {
        $url = $this->storage->temporaryUrl('private/file.pdf', 3600);
        
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);
    }
}

