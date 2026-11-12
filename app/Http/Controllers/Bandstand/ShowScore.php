<?php

namespace App\Http\Controllers\Bandstand;

use App\Http\Controllers\Controller;
use App\Models\Score;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a score to signed-in users: files are never publicly reachable.
 */
class ShowScore extends Controller
{
    public function __invoke(Score $score): StreamedResponse
    {
        $disk = Storage::disk(Score::DISK);

        abort_unless($disk->exists($score->path), 404);

        // Inline: phones open a PDF directly instead of saving it.
        return $disk->response($score->path, $score->downloadName(), [], 'inline');
    }
}
