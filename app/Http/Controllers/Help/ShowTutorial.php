<?php

namespace App\Http\Controllers\Help;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Animated tutorials, one per audience. Each step shows a screen of the site
 * (public/images/tutoriel/{guide}/{step}.webp) and, when relevant, where to
 * tap, in percent of the screen.
 */
class ShowTutorial extends Controller
{
    /**
     * @var array<string, array{access: 'public'|'auth'|'admin', screens: array<string, array{int|float, int|float}|null>}>
     */
    public const GUIDES = [
        'benevoles' => [
            'access' => 'public',
            'screens' => [
                'compte' => [50, 97.5],
                'planning' => [69, 60.4],
                'inscription' => [50, 68.3],
                'mes-creneaux' => null,
                'gateaux' => [27.3, 52.4],
                'je-prevois' => [20.8, 49.9],
                'commande' => [43.6, 49.9],
                'accueil' => null,
            ],
        ],
        'responsables' => [
            'access' => 'auth',
            'screens' => [
                'mes-stands' => [50, 68.7],
                'fiche' => null,
                'creneaux' => [29.1, 14],
                'nouveau-creneau' => [80.3, 80.8],
                'planning' => [69, 68.3],
                'inscrits' => null,
                'impression' => null,
                'stands-particuliers' => [34, 24.1],
            ],
        ],
        'administrateurs' => [
            'access' => 'admin',
            'screens' => [
                'utilisateurs' => [69.7, 15.4],
                'invitation' => [71.4, 90.6],
                'agir' => [29.8, 69.6],
                'stands' => [11.5, 67.6],
                'responsables' => [47, 54],
                'tombola' => [50, 44.2],
                'documents' => [23.6, 74.1],
                'sauvegardes' => [33.8, 32.8],
            ],
        ],
    ];

    public function __invoke(string $guide = 'benevoles'): View
    {
        abort_unless(isset(self::GUIDES[$guide]), 404);

        if (! self::isAccessible($guide)) {
            abort_if(Auth::check(), 403);

            // Guests are asked to sign in first.
            abort(redirect()->guest(route('login')));
        }

        return view('tutorial', [
            'guide' => $guide,
            'screens' => self::GUIDES[$guide]['screens'],
            'guides' => array_values(array_filter(array_keys(self::GUIDES), self::isAccessible(...))),
        ]);
    }

    public static function isAccessible(string $guide): bool
    {
        return match (self::GUIDES[$guide]['access']) {
            'public' => true,
            'auth' => Auth::check(),
            'admin' => Auth::check() && Gate::allows('admin'),
        };
    }
}
