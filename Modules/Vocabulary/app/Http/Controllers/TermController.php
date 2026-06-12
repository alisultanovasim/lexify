<?php
namespace Modules\Vocabulary\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Vocabulary\Models\Term;
use Modules\Vocabulary\Models\TermExample;
use Modules\Vocabulary\Services\GeminiEnrichmentService;

class TermController extends Controller
{
    public function create(Deck $deck): Response
    {
        $this->authorize('update', $deck);

        return Inertia::render('Vocabulary::Terms/Create', [
            'deck' => $deck->load('sourceLanguage', 'targetLanguage'),
        ]);
    }

    public function store(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('update', $deck);

        $data = $request->validate([
            'term'         => 'required|string|max:500',
            'definition'   => 'required|string|max:500',
            'pronunciation' => 'nullable|string|max:200',
            'part_of_speech' => 'nullable|in:noun,verb,adjective,adverb,phrase,other',
            'gender'       => 'nullable|in:der,die,das',
            'plural_form'  => 'nullable|string|max:200',
            'level'        => 'nullable|in:A1,A2,B1,B2,C1,C2',
            'notes'        => 'nullable|string|max:1000',
            'examples'     => 'nullable|array',
            'examples.*.sentence'    => 'required|string|max:500',
            'examples.*.translation' => 'nullable|string|max:500',
        ]);

        $position = $deck->terms()->max('position') + 1;
        $term = $deck->terms()->create(array_merge($data, ['position' => $position]));

        if (!empty($data['examples'])) {
            foreach ($data['examples'] as $example) {
                $term->examples()->create($example);
            }
        }

        return back()->with('success', 'Söz əlavə edildi!');
    }

    public function update(Request $request, Term $term): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $term->deck);

        $data = $request->validate([
            'term'         => 'required|string|max:500',
            'definition'   => 'required|string|max:500',
            'pronunciation' => 'nullable|string|max:200',
            'part_of_speech' => 'nullable|in:noun,verb,adjective,adverb,phrase,other',
            'gender'       => 'nullable|in:der,die,das',
            'plural_form'  => 'nullable|string|max:200',
            'level'        => 'nullable|in:A1,A2,B1,B2,C1,C2',
            'notes'        => 'nullable|string|max:1000',
            'examples'     => 'nullable|array',
            'examples.*.id'          => 'nullable|exists:term_examples,id',
            'examples.*.sentence'    => 'required|string|max:500',
            'examples.*.translation' => 'nullable|string|max:500',
        ]);

        $term->update($data);

        if (isset($data['examples'])) {
            $keepIds = collect($data['examples'])->pluck('id')->filter()->all();
            $term->examples()->whereNotIn('id', $keepIds)->delete();

            foreach ($data['examples'] as $ex) {
                if (!empty($ex['id'])) {
                    TermExample::find($ex['id'])?->update($ex);
                } else {
                    $term->examples()->create($ex);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['term' => $term->load('examples', 'primaryImage')]);
        }

        return back()->with('success', 'Söz yeniləndi!');
    }

    public function destroy(Term $term): RedirectResponse
    {
        $this->authorize('update', $term->deck);
        $term->delete();

        return back()->with('success', 'Söz silindi!');
    }

    public function reorder(Request $request, Deck $deck): JsonResponse
    {
        $this->authorize('update', $deck);
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->order as $position => $termId) {
            $deck->terms()->where('id', $termId)->update(['position' => $position]);
        }

        return response()->json(['message' => 'Sıra yeniləndi']);
    }

    public function enrich(Request $request, Term $term): JsonResponse
    {
        $this->authorize('update', $term->deck);

        $service = app(GeminiEnrichmentService::class);
        $term->load('deck.sourceLanguage', 'deck.targetLanguage');

        $result = $service->enrich($term, $term->deck->sourceLanguage?->code, $term->deck->targetLanguage?->code);
        $term->load('examples', 'primaryImage');

        return response()->json([
            'status' => $result,
            'success' => $result === 'ok',
            'term'   => $term,
        ]);
    }

    public function enrichLast(Request $request, Deck $deck): JsonResponse
    {
        $this->authorize('update', $deck);

        // MUST use Term model directly — deck->terms() has orderBy('position')
        // which overrides latest(), causing the WRONG term to be returned
        $term = Term::where('deck_id', $deck->id)->orderByDesc('id')->first();
        if (!$term) {
            return response()->json(['status' => 'error', 'success' => false], 404);
        }

        $deck->load('sourceLanguage', 'targetLanguage');
        $service = app(GeminiEnrichmentService::class);

        $result = $service->enrich($term, $deck->sourceLanguage?->code, $deck->targetLanguage?->code);
        $term->load('examples', 'primaryImage');

        return response()->json([
            'status' => $result,
            'success' => $result === 'ok',
            'term'   => $term,
        ]);
    }

    public function lastTerm(Deck $deck): JsonResponse
    {
        $this->authorize('update', $deck);
        $term = Term::where('deck_id', $deck->id)->orderByDesc('id')->first();
        if (!$term) {
            return response()->json(['term' => null], 404);
        }
        return response()->json(['term' => ['id' => $term->id, 'term' => $term->term, 'definition' => $term->definition]]);
    }
}
