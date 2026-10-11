<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Yearly documents: everyone reads them, administrators upload them.
 *
 * @property-read Collection<int, Document> $documents
 * @property-read bool $canManage
 */
class Index extends Component
{
    use WithFileUploads;

    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'jpg', 'jpeg', 'png'];

    public string $title = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function add(): void
    {
        $this->authorize('admin');

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:'.implode(',', self::EXTENSIONS), 'max:'.(config('marche.documents.max_size_mb') * 1024)],
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $validated['file'];

        Document::create([
            'title' => trim($validated['title']),
            'path' => $file->store(Document::DIRECTORY, Document::DISK),
            'size' => $file->getSize(),
            'position' => (int) Document::max('position') + 1,
        ]);

        $this->reset('title', 'file');
        unset($this->documents);

        Flux::toast(variant: 'success', text: __('documents.toasts.added'));
    }

    public function delete(int $documentId): void
    {
        $this->authorize('admin');

        $document = Document::findOrFail($documentId);

        Storage::disk(Document::DISK)->delete($document->path);
        $document->delete();

        unset($this->documents);
    }

    public function moveUp(int $documentId): void
    {
        $this->move($documentId, -1);
    }

    public function moveDown(int $documentId): void
    {
        $this->move($documentId, 1);
    }

    /**
     * @return Collection<int, Document>
     */
    #[Computed]
    public function documents(): Collection
    {
        return Document::query()->ordered()->get();
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('admin');
    }

    public function render(): View
    {
        return view('livewire.documents.index')
            ->title(__('documents.title'));
    }

    private function move(int $documentId, int $offset): void
    {
        $this->authorize('admin');

        DB::transaction(function () use ($documentId, $offset): void {
            $ids = Document::query()->ordered()->pluck('id')->all();
            $from = array_search($documentId, $ids, true);

            if (! is_int($from) || ! isset($ids[$from + $offset])) {
                return;
            }

            $to = $from + $offset;

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $index => $id) {
                Document::whereKey($id)->update(['position' => $index + 1]);
            }
        });

        unset($this->documents);
    }
}
