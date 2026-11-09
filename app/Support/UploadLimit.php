<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Real maximum size of an uploaded document: DOCUMENTS_MAX_SIZE_MB, unless
 * the PHP settings of the server (upload_max_filesize, post_max_size) allow
 * less. PHP then rejects the file before the application sees it.
 */
class UploadLimit
{
    private const MEGABYTE = 1024 * 1024;

    public function configuredMegabytes(): int
    {
        return (int) config('marche.documents.max_size_mb');
    }

    /**
     * Smallest of upload_max_filesize and post_max_size, in bytes.
     */
    public function serverBytes(): int
    {
        return (int) UploadedFile::getMaxFilesize();
    }

    public function serverMegabytes(): int
    {
        return max(1, intdiv($this->serverBytes(), self::MEGABYTE));
    }

    public function isReducedByServer(): bool
    {
        return $this->serverBytes() < $this->configuredMegabytes() * self::MEGABYTE;
    }

    /**
     * Size announced in the form, in megabytes.
     */
    public function megabytes(): int
    {
        return $this->isReducedByServer() ? $this->serverMegabytes() : $this->configuredMegabytes();
    }
}
