<?php
namespace Modules\Study\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Progress\Services\SpacedRepetitionService;
use Modules\Study\Models\StudySession;

class StudyController extends Controller
{
    public function __construct(private SpacedRepetitionService $srService) {}

    public function show(Deck $deck, string $mode = 'flashcard'): Response
    {
        $this->authorize('view', $deck);

        $validModes = ['flashcard', 'learn', 'write', 'match', 'test'];
        $mode = in_array($mode, $validModes) ? $mode : 'flashcard';

        $terms = $deck->terms()
            ->with(['primaryImage', 'examples'])
            ->inRandomOrder()
            ->get()
            ->map(fn ($t) => [
                'id'            => $t->id,
                'term'          => $t->term,
                'definition'    => $t->definition,
                'pronunciation' => $t->pronunciation,
                'gender'        => $t->gender,
                'part_of_speech' => $t->part_of_speech,
                'notes'         => $t->notes,
                'image'         => $t->primaryImage?->url,
                'examples'      => $t->examples->map(fn ($e) => [
                    'sentence'    => $e->sentence,
                    'translation' => $e->translation,
                ]),
            ]);

        $session = StudySession::create([
            'user_id'     => auth()->id(),
            'deck_id'     => $deck->id,
            'mode'        => $mode,
            'total_cards' => $terms->count(),
        ]);

        return Inertia::render('Study::Study/Session', [
            'deck'    => $deck->load('sourceLanguage', 'targetLanguage'),
            'terms'   => $terms,
            'mode'    => $mode,
            'session' => $session,
        ]);
    }

    public function answer(Request $request, StudySession $session): JsonResponse
    {
        $request->validate([
            'term_id'         => 'required|exists:terms,id',
            'is_correct'      => 'required|boolean',
            'response_time_ms' => 'nullable|integer',
            'rating'          => 'nullable|integer|min:0|max:5',
        ]);

        $session->answers()->create([
            'term_id'          => $request->term_id,
            'is_correct'       => $request->is_correct,
            'response_time_ms' => $request->response_time_ms,
        ]);

        if ($request->is_correct) {
            $session->increment('correct_count');
        } else {
            $session->increment('incorrect_count');
        }

        $rating = $request->rating ?? ($request->is_correct ? 4 : 1);
        $this->srService->update(auth()->id(), $request->term_id, $rating);

        return response()->json(['ok' => true]);
    }

    public function complete(StudySession $session): JsonResponse
    {
        $session->update(['completed_at' => now()]);

        return response()->json([
            'total'     => $session->total_cards,
            'correct'   => $session->correct_count,
            'incorrect' => $session->incorrect_count,
            'score'     => $session->total_cards > 0
                ? round(($session->correct_count / $session->total_cards) * 100)
                : 0,
        ]);
    }
}
