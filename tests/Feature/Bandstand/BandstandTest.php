<?php

use App\Enums\StandType;
use App\Livewire\Bandstand\Index;
use App\Models\Score;
use App\Models\Stand;
use App\Models\User;
use App\Support\UploadLimit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(Score::DISK);
});

function storedScore(string $title = 'Douce nuit'): Score
{
    $path = UploadedFile::fake()->create('partition.pdf', 100, 'application/pdf')->store(Score::DIRECTORY, Score::DISK);

    return Score::factory()->create(['title' => $title, 'path' => $path]);
}

function bandstandManager(): User
{
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Bandstand)->create()->managers()->attach($manager);

    return $manager;
}

test('guests cannot see the bandstand nor open a score', function () {
    $score = storedScore();

    $this->get(route('bandstand.index'))->assertRedirect(route('login'));
    $this->get(route('bandstand.scores.show', $score))->assertRedirect(route('login'));
});

test('every volunteer reads the presentation and opens the scores but cannot manage them', function () {
    $score = storedScore();

    $this->actingAs(User::factory()->create());

    $this->get(route('bandstand.index'))
        ->assertOk()
        ->assertSee(__('bandstand.title'))
        ->assertSee('Douce nuit')
        ->assertDontSee(__('bandstand.form.heading'));

    Livewire::test(Index::class)->call('add')->assertForbidden();
    Livewire::test(Index::class)->call('delete', $score->id)->assertForbidden();
    Livewire::test(Index::class)->call('moveUp', $score->id)->assertForbidden();
    Livewire::test(Index::class)->call('startRenaming', $score->id)->assertForbidden();
    Livewire::test(Index::class)->set('renamingId', $score->id)->set('renamingTitle', 'Piraté')->call('rename')->assertForbidden();

    expect($score->fresh()->title)->toBe('Douce nuit');

    $this->get(route('bandstand.scores.show', $score))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename=douce-nuit.pdf');
});

test('a missing file answers 404', function () {
    $score = Score::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('bandstand.scores.show', $score))
        ->assertNotFound();
});

test('managers of a bandstand stand and administrators manage the scores', function () {
    $otherManager = User::factory()->create();
    Stand::factory()->ofType(StandType::Concert)->create()->managers()->attach($otherManager);

    expect(Gate::forUser(bandstandManager())->allows('manage-bandstand'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('manage-bandstand'))->toBeTrue()
        ->and(Gate::forUser($otherManager)->allows('manage-bandstand'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->create())->allows('manage-bandstand'))->toBeFalse();
});

test('the reference bandstand stand becomes a bandstand stand', function () {
    $stand = Stand::factory()->ofType(StandType::Generic)->create(['name' => 'Kiosque à musique']);
    $other = Stand::factory()->ofType(StandType::Generic)->create(['name' => 'Musique ambulante']);

    (require database_path('migrations/2026_11_12_000002_set_bandstand_stand_type.php'))->up();

    expect($stand->fresh()->type)->toBe(StandType::Bandstand)
        ->and($other->fresh()->type)->toBe(StandType::Generic)
        ->and(DB::table('stands')->where('type', 'bandstand')->count())->toBe(1);
});

test('a manager uploads a score on the private disk', function () {
    $this->actingAs(bandstandManager());

    Livewire::test(Index::class)
        ->assertSee(__('bandstand.form.heading'))
        ->set('title', ' Vive le vent ')
        ->set('file', UploadedFile::fake()->create('vent.pdf', 300, 'application/pdf'))
        ->call('add')
        ->assertHasNoErrors()
        ->assertSet('title', '')
        ->assertSee('Vive le vent');

    $score = Score::sole();

    expect($score->title)->toBe('Vive le vent')
        ->and($score->size)->toBe(300 * 1024)
        ->and($score->position)->toBe(1);

    Storage::disk(Score::DISK)->assertExists($score->path);
    Storage::disk('public')->assertMissing($score->path);
});

test('a score needs a title and a pdf or an image within the size limit', function () {
    config(['marche.documents.max_size_mb' => 1]);

    $this->actingAs(bandstandManager());

    Livewire::test(Index::class)->call('add')->assertHasErrors(['title', 'file']);

    Livewire::test(Index::class)
        ->set('title', 'Tableur')
        ->set('file', UploadedFile::fake()->create('partition.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))
        ->call('add')
        ->assertHasErrors('file');

    Livewire::test(Index::class)
        ->set('title', 'Trop lourd')
        ->set('file', UploadedFile::fake()->create('partition.pdf', 2048, 'application/pdf'))
        ->call('add')
        ->assertHasErrors('file');

    expect(Score::count())->toBe(0);
});

test('a manager renames a score without touching its file', function () {
    $this->actingAs(bandstandManager());
    $score = storedScore();
    $path = $score->path;

    Livewire::test(Index::class)
        ->call('startRenaming', $score->id)
        ->assertSet('renamingId', $score->id)
        ->assertSet('renamingTitle', 'Douce nuit')
        ->set('renamingTitle', ' Douce nuit (flûte) ')
        ->call('rename')
        ->assertHasNoErrors()
        ->assertSet('renamingId', null)
        ->assertSee('Douce nuit (flûte)');

    expect($score->fresh()->title)->toBe('Douce nuit (flûte)')
        ->and($score->fresh()->path)->toBe($path);
});

test('a score cannot be renamed to an empty title', function () {
    $this->actingAs(bandstandManager());
    $score = storedScore();

    Livewire::test(Index::class)
        ->call('startRenaming', $score->id)
        ->set('renamingTitle', '   ')
        ->call('rename')
        ->assertHasErrors('renamingTitle')
        ->assertSet('renamingId', $score->id);

    expect($score->fresh()->title)->toBe('Douce nuit');
});

test('cancelling a renaming keeps the title', function () {
    $this->actingAs(bandstandManager());
    $score = storedScore();

    Livewire::test(Index::class)
        ->call('startRenaming', $score->id)
        ->set('renamingTitle', 'Autre titre')
        ->call('cancelRenaming')
        ->assertSet('renamingId', null)
        ->assertSet('renamingTitle', '');

    expect($score->fresh()->title)->toBe('Douce nuit');
});

test('a manager reorders the scores', function () {
    $this->actingAs(bandstandManager());
    [$a, $b, $c] = [storedScore('A'), storedScore('B'), storedScore('C')];

    Livewire::test(Index::class)->call('moveUp', $c->id)->call('moveDown', $a->id)->call('moveDown', $a->id)->call('moveDown', $a->id);

    expect(Score::query()->ordered()->pluck('title')->all())->toBe(['C', 'B', 'A']);
});

test('deleting a score deletes its file', function () {
    $this->actingAs(bandstandManager());
    $score = storedScore();

    Livewire::test(Index::class)->call('delete', $score->id);

    expect(Score::count())->toBe(0);
    Storage::disk(Score::DISK)->assertMissing($score->path);
});

test('the purge deletes the scores and their files', function () {
    $score = storedScore();

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    expect(Score::count())->toBe(0);
    Storage::disk(Score::DISK)->assertMissing($score->path);
});

test('the form announces the server limit to managers when PHP allows less', function () {
    config(['marche.documents.max_size_mb' => 20]);
    $this->partialMock(UploadLimit::class, fn ($mock) => $mock->shouldReceive('serverBytes')->andReturn(2 * 1024 * 1024));
    $this->actingAs(bandstandManager());

    Livewire::test(Index::class)->assertSee(__('bandstand.form.file_help', ['max' => 2]));
});

test('the menu and the dashboard link to the bandstand once a score is online', function () {
    storedScore();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('navigation.bandstand'))
        ->assertSee(__('dashboard.modules.bandstand.text'))
        ->assertSee(route('bandstand.index'));
});

test('volunteers see no link to the bandstand while it has no score', function () {
    $otherManager = User::factory()->create();
    Stand::factory()->ofType(StandType::Concert)->create()->managers()->attach($otherManager);

    foreach ([User::factory()->create(), $otherManager] as $user) {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('navigation.bandstand'))
            ->assertDontSee(route('bandstand.index'));
    }
});

test('managers and administrators see the link to the bandstand before the first score', function (Closure $user) {
    $this->actingAs($user())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.modules.bandstand.text'))
        ->assertSee(route('bandstand.index'));
})->with([
    'bandstand manager' => [fn () => bandstandManager()],
    'administrator' => [fn () => User::factory()->admin()->create()],
]);
