<?php

/**
 * Uploaded File Wrapper
 *
 * Wraps a single entry from the $_FILES superglobal, exposing metadata,
 * validation, and storage helpers.
 *
 * PHP version 8.1
 *
 * @category  HTTP
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

/**
 * UploadedFile Class
 *
 * Example:
 * ```php
 * $file = UploadedFile::fromArray($_FILES['avatar']);
 * if ($file->isValid() && $file->extension() === 'png') {
 *     $path = $file->store('uploads/avatars');
 * }
 * ```
 *
 * @category  HTTP
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class UploadedFile
{
    private string $originalName;
    private string $mimeType;
    private int $size;
    private string $tmpPath;
    private int $error;

    /**
     * @param string $originalName Client-provided file name.
     * @param string $mimeType     Client-provided MIME type.
     * @param int    $size         File size in bytes.
     * @param string $tmpPath      Temporary upload path.
     * @param int    $error        PHP upload error code (UPLOAD_ERR_*).
     */
    public function __construct(
        string $originalName,
        string $mimeType,
        int $size,
        string $tmpPath,
        int $error = UPLOAD_ERR_OK
    ) {
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->tmpPath = $tmpPath;
        $this->error = $error;
    }

    /**
     * Build an instance from a $_FILES entry.
     *
     * @param array<string,mixed> $file One entry of the $_FILES array.
     *
     * @return self
     */
    public static function fromArray(array $file): self
    {
        return new self(
            (string) ($file['name'] ?? ''),
            (string) ($file['type'] ?? ''),
            (int) ($file['size'] ?? 0),
            (string) ($file['tmp_name'] ?? ''),
            (int) ($file['error'] ?? UPLOAD_ERR_OK)
        );
    }

    /**
     * @return string Original client file name.
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * @return string Client MIME type.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return int Size in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return int Upload error code.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Lowercased file extension derived from the original name.
     *
     * @return string
     */
    public function extension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    /**
     * Whether the upload completed without error.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Validate the file against simple rules.
     *
     * Supported rules:
     * - max: maximum size in bytes
     * - extensions: array of allowed lowercase extensions
     *
     * @param array{max?:int,extensions?:array<int,string>} $rules Validation rules.
     *
     * @return bool
     */
    public function validate(array $rules): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (isset($rules['max']) && $this->size > $rules['max']) {
            return false;
        }

        if (isset($rules['extensions']) && !in_array($this->extension(), $rules['extensions'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Generate a random file name preserving the original extension.
     *
     * @return string
     */
    public function hashName(): string
    {
        $extension = $this->extension();

        return bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
    }

    /**
     * Store the file in a directory under a generated name.
     *
     * @param string      $directory Target directory.
     * @param string|null $name      Optional explicit file name.
     *
     * @return string The path the file was stored at.
     */
    public function store(string $directory, ?string $name = null): string
    {
        $directory = rtrim($directory, '/\\');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name ??= $this->hashName();
        $destination = $directory . '/' . $name;

        $this->moveTo($destination);

        return $destination;
    }

    /**
     * Move the temporary file to a destination.
     *
     * Uses move_uploaded_file() for real uploads and falls back to rename()
     * for non-HTTP contexts (e.g. tests), where the temp file is not an
     * actual PHP upload.
     *
     * @param string $destination Target path.
     *
     * @return void
     */
    private function moveTo(string $destination): void
    {
        if (is_uploaded_file($this->tmpPath)) {
            move_uploaded_file($this->tmpPath, $destination);

            return;
        }

        rename($this->tmpPath, $destination);
    }
}
