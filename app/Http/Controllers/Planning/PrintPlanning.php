<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Stand;
use App\Planning\PlanningGrid;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Printable planning: the whole grid, or the detailed sheet of one stand.
 */
class PrintPlanning extends Controller
{
    public function __invoke(Request $request): View
    {
        $day = in_array($request->query('day'), config('marche.dates'), true) ? $request->query('day') : null;

        $stands = Stand::query()
            ->inPlanning()
            ->ordered()
            ->when($request->query('stand'), fn ($query, $standId) => $query->whereKey($standId))
            ->with([
                'managers',
                'schoolClasses',
                'slots' => fn ($slots) => $slots->where('capacity', '>', 0),
                'slots.registrations.user',
                'slots.reservedClasses',
            ])
            ->get();

        abort_if($request->filled('stand') && $stands->isEmpty(), 404);

        $stand = $request->filled('stand') ? $stands->first() : null;

        return view('planning.print', [
            'grid' => new PlanningGrid($stands, $day),
            'stand' => $stand,
            'day' => $day,
            // Phones are only printed for the managers of the stand and admins.
            'showContacts' => $stand !== null && Gate::allows('update', $stand),
        ]);
    }
}
