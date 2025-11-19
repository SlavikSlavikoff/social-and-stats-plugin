<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Web;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function index(Request $request)
    {
        $cases = CourtCase::visibleFor($request->user())
            ->with(['judge', 'subject'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search');

                return $query->where(function ($builder) use ($term) {
                    $builder->where('case_number', 'like', '%'.$term.'%')
                        ->orWhereHas('subject', fn ($sq) => $sq->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('socialprofile::court.index', [
            'cases' => $cases,
            'search' => $request->get('search'),
        ]);
    }

    public function judge(Request $request)
    {
        $templates = CourtTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recentCases = CourtCase::query()
            ->visibleFor($request->user())
            ->with(['subject', 'judge'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('socialprofile::court.judge', [
            'templates' => $templates,
            'limits' => config('socialprofile.court.limits'),
            'recentCases' => $recentCases,
        ]);
    }
}
