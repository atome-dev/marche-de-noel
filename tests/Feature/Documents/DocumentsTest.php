<?php

use App\Livewire\Documents\Index;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(Document::DISK);
});

function storedDocument(string $title = 'Journal du marché'): Document
{
    $path = UploadedFile::fake()->create('journal.pdf', 100, 'application/pdf')->store(Document::DIRECTORY, Document::DISK);

    return Document::factory()->create(['title' => $title, 'path' => $path]);
}

test('guests cannot see nor open the documents', function () {
    $document = storedDocument();

    $this->get(route('documents.index'))->assertRedirect(route('login'));
    $this->get(route('documents.show', $document))->assertRedirect(route('login'));
});

test('every volunteer sees and opens the documents but cannot manage them', function () {
    $document = storedDocument();

    $this->actingAs(User::factory()->create());

    Livewire::test(Index::class)
        ->assertSee('Journal du marché')
        ->assertDontSee(__('documents.form.heading'))
        ->call('delete', $document->id)
        ->assertForbidden();

    Livewire::test(Index::class)->call('add')->assertForbidden();
    Livewire::test(Index::class)->call('moveUp', $document->id)->assertForbidden();

    $this->get(route('documents.show', $document))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename=journal-du-marche.pdf');
});

test('a missing file answers 404', function () {
    $document = Document::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('documents.show', $document))
        ->assertNotFound();
});

test('an administrator uploads a document on the private disk', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->set('title', ' Bon de commande sapins ')
        ->set('file', UploadedFile::fake()->create('bon.pdf', 300, 'application/pdf'))
        ->call('add')
        ->assertHasNoErrors()
        ->assertSet('title', '')
        ->assertSee('Bon de commande sapins');

    $document = Document::sole();

    expect($document->title)->toBe('Bon de commande sapins')
        ->and($document->size)->toBe(300 * 1024)
        ->and($document->sizeLabel())->toBe('300 Ko')
        ->and($document->position)->toBe(1);

    Storage::disk(Document::DISK)->assertExists($document->path);
    Storage::disk('public')->assertMissing($document->path);
});

test('a document needs a title and an allowed file within the size limit', function () {
    config(['marche.documents.max_size_mb' => 1]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)->call('add')->assertHasErrors(['title', 'file']);

    Livewire::test(Index::class)
        ->set('title', 'Script')
        ->set('file', UploadedFile::fake()->create('script.php', 10, 'text/x-php'))
        ->call('add')
        ->assertHasErrors('file');

    Livewire::test(Index::class)
        ->set('title', 'Trop lourd')
        ->set('file', UploadedFile::fake()->create('journal.pdf', 2048, 'application/pdf'))
        ->call('add')
        ->assertHasErrors('file');

    expect(Document::count())->toBe(0);
});

test('an administrator reorders the documents', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$a, $b, $c] = [storedDocument('A'), storedDocument('B'), storedDocument('C')];

    Livewire::test(Index::class)->call('moveUp', $c->id)->call('moveDown', $a->id)->call('moveDown', $a->id)->call('moveDown', $a->id);

    expect(Document::query()->ordered()->pluck('title')->all())->toBe(['C', 'B', 'A']);
});

test('deleting a document deletes its file', function () {
    $this->actingAs(User::factory()->admin()->create());
    $document = storedDocument();

    Livewire::test(Index::class)->call('delete', $document->id);

    expect(Document::count())->toBe(0);
    Storage::disk(Document::DISK)->assertMissing($document->path);
});

test('the purge deletes the documents and their files', function () {
    $document = storedDocument();

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    expect(Document::count())->toBe(0);
    Storage::disk(Document::DISK)->assertMissing($document->path);
});

test('large files are labelled in megabytes', function () {
    expect(Document::factory()->make(['size' => 3_460_301])->sizeLabel())->toBe('3,3 Mo');
});
