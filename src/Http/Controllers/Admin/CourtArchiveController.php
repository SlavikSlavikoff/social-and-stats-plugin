<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Illuminate\Http\Request;

class CourtArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = CourtCase::with(['subject', 'judge'])
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($builder) use ($request) {
                $term = $request->string('search');

                return $builder->where(function ($q) use ($term) {
                    $q->where('case_number', 'like', '%'.$term.'%')
                        ->orWhereHas('subject', fn ($sq) => $sq->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('judge', fn ($sq) => $sq->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->orderByDesc('created_at');

        $cases = $query->paginate(20)->withQueryString();

        return view('socialprofile::admin.court.archive', [
            'cases' => $cases,
            'filters' => [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
            ],
        ]);
    }
}
