<?php

use App\Cookies\OrderableIngredients;
use App\Support\HtmlSanitizer;
use App\Support\Weight;
use App\Support\YouTube;
use Tests\TestCase;

uses(TestCase::class);

describe('orderable ingredients', function () {
    beforeEach(function () {
        config(['marche.orders.ingredients' => ['Poudre d\'amande', 'Noix de coco râpée']]);
        $this->ingredients = new OrderableIngredients;
    });

    test('are keyed by a stable slug', function () {
        expect($this->ingredients->all())->toBe([
            'poudre-damande' => 'Poudre d\'amande',
            'noix-de-coco-rapee' => 'Noix de coco râpée',
        ]);
    });

    test('can be looked up', function () {
        expect($this->ingredients->has('poudre-damande'))->toBeTrue()
            ->and($this->ingredients->has('amandes-entieres'))->toBeFalse()
            ->and($this->ingredients->name('noix-de-coco-rapee'))->toBe('Noix de coco râpée')
            ->and($this->ingredients->name('inconnu'))->toBeNull();
    });
});

test('youtube links are converted to privacy-friendly embed urls', function (?string $url, ?string $expected) {
    expect(YouTube::embedUrl($url))->toBe($expected);
})->with([
    'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'short link' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'other site' => ['https://vimeo.com/123456', null],
    'lookalike host' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', null],
    'empty' => [null, null],
]);

describe('html sanitizer', function () {
    test('keeps simple formatting', function () {
        $html = '<h2>Titre</h2><p>Du <strong>gras</strong> et de l’<em>italique</em>.</p><ul><li>Un</li></ul>';

        expect(HtmlSanitizer::clean($html))->toBe($html);
    });

    test('removes scripts, event handlers and unsafe links', function () {
        $clean = HtmlSanitizer::clean(
            '<p onclick="alert(1)">Texte<script>alert(1)</script></p>'
            .'<a href="javascript:alert(1)">piège</a>'
            .'<a href="https://example.org" target="_blank">lien</a>'
            .'<img src="x" onerror="alert(1)"><iframe src="https://evil.example"></iframe>'
        );

        expect($clean)->not->toContain('script')
            ->not->toContain('onclick')
            ->not->toContain('javascript:')
            ->not->toContain('<img')
            ->not->toContain('iframe')
            ->toContain('<p>Texte</p>')
            ->toContain('href="https://example.org"')
            ->toContain('rel="noopener noreferrer"');
    });

    test('handles empty content', function () {
        expect(HtmlSanitizer::clean(null))->toBe('')
            ->and(HtmlSanitizer::clean('  '))->toBe('');
    });
});

test('weights are formatted in grams or kilos', function (int $grams, string $expected) {
    expect(Weight::format($grams))->toBe($expected);
})->with([
    [0, '0 g'],
    [200, '200 g'],
    [1000, '1 kg'],
    [1250, '1,25 kg'],
    [12500, '12,5 kg'],
]);
