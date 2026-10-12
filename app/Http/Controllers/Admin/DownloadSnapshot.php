<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Snapshots\SnapshotStore;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lets an administrator keep a copy of a snapshot outside the server.
 */
class DownloadSnapshot extends Controller
{
    public function __invoke(string $name, SnapshotStore $snapshots): StreamedResponse
    {
        $path = $snapshots->find($name);

        abort_if($path === null, 404);

        return Storage::disk(SnapshotStore::DISK)->download($path, basename($path));
    }
}
