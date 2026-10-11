<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a document to signed-in users: files are never publicly reachable.
 */
class ShowDocument extends Controller
{
    public function __invoke(Document $document): StreamedResponse
    {
        $disk = Storage::disk(Document::DISK);

        abort_unless($disk->exists($document->path), 404);

        // Inline: phones open a PDF directly instead of saving it.
        return $disk->response($document->path, $document->downloadName(), [], 'inline');
    }
}
