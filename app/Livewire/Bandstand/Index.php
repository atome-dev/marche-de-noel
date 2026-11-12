<?php

namespace App\Livewire\Bandstand;

use App\Models\Score;
use App\Support\UploadLimit;
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
 * Bandstand: everyone reads the presentation and opens the scores, the
 * managers of a bandstand stand add, rename, reorder and delete them.
 *
 * @property-read Collection<int, Score> $scores
 * @property-read bool $canManage
 * @property-read UploadLimit $uploadLimit
 */
class Index extends Component
{
    use WithFileUploads;

    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public string $title = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public ?int $renamingId = null;

    public string $renamingTitle = '';

    public function add(): void
    {
        $this->authorize('manage-bandstand');

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:'.implode(',', self::EXTENSIONS), 'max:'.(config('marche.documents.max_size_mb') * 1024)],
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $validated['file'];

        Score::create([
            'title' => trim($validated['title']),
            'path' => $file->store(Score::DIRECTORY, Score::DISK),
            'size' => $file->getSize(),
            'position' => (int) Score::max('position') + 1,
        ]);

        $this->reset('title', 'file');
        unset($this->scores);

        Flux::toast(variant: 'success', text: __('bandstand.toasts.added'));
    }

    public function startRenaming(int $scoreId): void
    {
        $this->authorize('manage-bandstand');

        $this->renamingTitle = Score::findOrFail($scoreId)->title;
        $this->renamingId = $scoreId;
        $this->resetValidation('renamingTitle');
    }

    public function rename(): void
    {
        $this->authorize('manage-bandstand');

        $validated = $this->validate([
            'renamingTitle' => ['required', 'string', 'max:150'],
        ], attributes: ['renamingTitle' => __('bandstand.form.title')]);

        Score::findOrFail($this->renamingId)->update(['title' => trim($validated['renamingTitle'])]);

        $this->cancelRenaming();
        unset($this->scores);

        Flux::toast(variant: 'success', text: __('bandstand.toasts.renamed'));
    }

    public function cancelRenaming(): void
    {
        $this->reset('renamingId', 'renamingTitle');
        $this->resetValidation('renamingTitle');
    }

    public function delete(int $scoreId): void
    {
        $this->authorize('manage-bandstand');

        $score = Score::findOrFail($scoreId);

        Storage::disk(Score::DISK)->delete($score->path);
        $score->delete();

        if ($this->renamingId === $scoreId) {
            $this->cancelRenaming();
        }

        unset($this->scores);
    }

    public function moveUp(int $scoreId): void
    {
        $this->move($scoreId, -1);
    }

    public function moveDown(int $scoreId): void
    {
        $this->move($scoreId, 1);
    }

    /**
     * @return Collection<int, Score>
     */
    #[Computed]
    public function scores(): Collection
    {
        return Score::query()->ordered()->get();
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('manage-bandstand');
    }

    #[Computed]
    public function uploadLimit(): UploadLimit
    {
        return app(UploadLimit::class);
    }

    public function render(): View
    {
        return view('livewire.bandstand.index')
            ->title(__('bandstand.title'));
    }

    private function move(int $scoreId, int $offset): void
    {
        $this->authorize('manage-bandstand');

        DB::transaction(function () use ($scoreId, $offset): void {
            $ids = Score::query()->ordered()->pluck('id')->all();
            $from = array_search($scoreId, $ids, true);

            if (! is_int($from) || ! isset($ids[$from + $offset])) {
                return;
            }

            $to = $from + $offset;

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $index => $id) {
                Score::whereKey($id)->update(['position' => $index + 1]);
            }
        });

        unset($this->scores);
    }
}
