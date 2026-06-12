<?php
namespace Modules\Import\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Vocabulary\Models\Term;

class ImportController extends Controller
{
    public function show(Deck $deck): Response
    {
        $this->authorize('update', $deck);

        return Inertia::render('Import::Import/Index', ['deck' => $deck]);
    }

    public function store(Request $request, Deck $deck): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $deck);

        $request->validate([
            'rows' => 'required|array|min:1|max:500',
            'rows.*.term'       => 'required|string|max:500',
            'rows.*.definition' => 'required|string|max:500',
            'rows.*.notes'      => 'nullable|string|max:1000',
        ]);

        $position = $deck->terms()->max('position') ?? 0;
        $created  = [];

        foreach ($request->rows as $row) {
            $term = $deck->terms()->create([
                'term'       => trim($row['term']),
                'definition' => trim($row['definition']),
                'notes'      => trim($row['notes'] ?? ''),
                'position'   => ++$position,
            ]);
            $created[] = ['id' => $term->id, 'term' => $term->term];
        }

        // JSON: frontend enriches each term step-by-step and shows a progress bar
        if ($request->wantsJson()) {
            return response()->json([
                'terms' => $created,
                'count' => count($created),
            ]);
        }

        // Non-JS fallback: enrich first 20 synchronously then redirect
        try {
            $deck->load('sourceLanguage', 'targetLanguage');
            $service  = app(\Modules\Vocabulary\Services\GeminiEnrichmentService::class);
            $termObjs = Term::whereIn('id', array_column($created, 'id'))->get()->keyBy('id');
            $max      = min(count($created), 20);
            for ($i = 0; $i < $max; $i++) {
                $t = $termObjs[$created[$i]['id']] ?? null;
                if ($t) $service->enrich($t, $deck->sourceLanguage?->code, $deck->targetLanguage?->code);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Import enrichment failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('decks.show', $deck)
            ->with('success', count($created) . ' söz import edildi + AI zənginləşdirdi!');
    }
}
