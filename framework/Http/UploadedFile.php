<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * Uploaded File Handler
 * 
 * Represents a file uploaded via HTTP request.
 */
class UploadedFile
{
    /**
     * Temporary file path
     */
    protected string $path;

    /**
     * Original filename
     */
    protected string $originalName;

    /**
     * MIME type
     */
    protected string $mimeType;

    /**
     * Upload error code
     */
    protected int $error;

    /**
     * File size in bytes
     */
    protected int $size;

    /**
     * Whether file has been moved
     */
    protected bool $moved = false;

    public function __construct(
        string $path,
        string $originalName,
        string $mimeType,
        int $error,
        int $size
    ) {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->error = $error;
        $this->size = $size;
    }

    /**
     * Check if file was uploaded successfully
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->path);
    }

    /**
     * Get temporary file path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get original client filename
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get original file extension
     */
    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Get MIME type
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get guessed MIME type from file contents
     */
    public function guessExtension(): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $this->path);
        finfo_close($finfo);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/json' => 'json',
            'text/plain' => 'txt',
            'text/html' => 'html',
            'text/css' => 'css',
            'application/javascript' => 'js',
        ];

        return $extensions[$mime] ?? null;
    }

    /**
     * Get file size in bytes
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get upload error code
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get upload error message
     */
    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => 'File uploaded successfully',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload',
            default => 'Unknown upload error',
        };
    }

    /**
     * Move file to destination
     */
    public function move(string $directory, ?string $name = null): string
    {
        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        if (!$this->isValid()) {
            throw new \RuntimeException('Cannot move invalid file: ' . $this->getErrorMessage());
        }

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = $name ?? $this->originalName;
        $target = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($this->path, $target)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        $this->moved = true;
        $this->path = $target;

        return $target;
    }

    /**
     * Store file with generated name
     */
    public function store(string $directory, ?string $disk = null): string
    {
        $extension = $this->getClientOriginalExtension();
        $name = bin2hex(random_bytes(20)) . ($extension ? '.' . $extension : '');
        
        return $this->move($directory, $name);
    }

    /**
     * Store file with specific name
     */
    public function storeAs(string $directory, string $name, ?string $disk = null): string
    {
        return $this->move($directory, $name);
    }

    /**
     * Get file contents
     */
    public function getContents(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Check if file has been moved
     */
    public function hasMoved(): bool
    {
        return $this->moved;
    }
}
