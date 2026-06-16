<?php
namespace Modules\Study\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Progress\Services\SpacedRepetitionService;
use Modules\Study\Models\StudyAnswer;
use Modules\Study\Models\StudySession;

class StudyController extends Controller
{
    public function __construct(private SpacedRepetitionService $srService) {}

    public function show(Request $request, Deck $deck, string $mode = 'flashcard'): Response
    {
        $this->authorize('view', $deck);

        $validModes = ['flashcard', 'learn', 'write', 'match', 'test'];
        $mode = in_array($mode, $validModes) ? $mode : 'flashcard';

        $allTerms = $deck->terms()
            ->with(['primaryImage', 'examples'])
            ->inRandomOrder()
            ->get();

        $wrongTermIds   = [];
        $matchProgress  = null;
        $resumedSession = null;
        $isFresh        = $request->boolean('fresh');

        // ── Resume an incomplete session ──────────────────────────────────
        $resumeId = $request->integer('session');
        if ($resumeId) {
            $resumedSession = StudySession::where('id', $resumeId)
                ->where('user_id', auth()->id())
                ->where('deck_id', $deck->id)
                ->where('mode', $mode)
                ->whereNull('completed_at')
                ->first();

            if ($resumedSession) {
                $answeredIds = $resumedSession->answers()
                    ->pluck('term_id')
                    ->unique()
                    ->toArray();
                $allTerms = $allTerms
                    ->filter(fn ($t) => !in_array($t->id, $answeredIds))
                    ->values();
            }
        }

        // ── Wrong terms first (flashcard / learn / test) ──────────────────
        if (!$resumedSession && !$isFresh && in_array($mode, ['flashcard', 'learn', 'test'])) {
            $lastSession = StudySession::where('user_id', auth()->id())
                ->where('deck_id', $deck->id)
                ->where('mode', $mode)
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->first();

            if ($lastSession && $lastSession->incorrect_count > 0) {
                $wrongTermIds = $lastSession->answers()
                    ->where('is_correct', false)
                    ->pluck('term_id')
                    ->unique()
                    ->intersect($allTerms->pluck('id'))
                    ->values()
                    ->all();
            }

            if (!empty($wrongTermIds)) {
                $wrongSet   = array_flip($wrongTermIds);
                $wrongTerms = $allTerms->filter(fn ($t) => isset($wrongSet[$t->id]));
                $otherTerms = $allTerms->filter(fn ($t) => !isset($wrongSet[$t->id]));
                $allTerms   = $wrongTerms->concat($otherTerms)->values();
            }
        }

        // ── Match: show only unmatched terms ──────────────────────────────
        if ($mode === 'match') {
            $completedIds = StudySession::where('user_id', auth()->id())
                ->where('deck_id', $deck->id)
                ->where('mode', 'match')
                ->whereNotNull('completed_at')
                ->pluck('id');

            $matchedTermIds = StudyAnswer::whereIn('study_session_id', $completedIds)
                ->where('is_correct', true)
                ->distinct()
                ->pluck('term_id')
                ->intersect($allTerms->pluck('id'))
                ->values()
                ->all();

            $totalMatch   = $allTerms->count();
            $matchedCount = count($matchedTermIds);
            $matchReset   = $matchedCount >= $totalMatch && $totalMatch > 0;

            if (!$matchReset) {
                $allTerms = $allTerms->filter(fn ($t) => !in_array($t->id, $matchedTermIds))->values();
            }

            $matchProgress = [
                'matched' => $matchReset ? 0 : $matchedCount,
                'total'   => $totalMatch,
                'reset'   => $matchReset,
            ];
        }

        $terms = $allTerms->map(fn ($t) => [
            'id'             => $t->id,
            'term'           => $t->term,
            'definition'     => $t->definition,
            'pronunciation'  => $t->pronunciation,
            'gender'         => $t->gender,
            'part_of_speech' => $t->part_of_speech,
            'notes'          => $t->notes,
            'image'          => $t->primaryImage?->url,
            'examples'       => $t->examples->map(fn ($e) => [
                'sentence'    => $e->sentence,
                'translation' => $e->translation,
            ]),
        ]);

        // Reuse incomplete session or create a fresh one
        $session = $resumedSession ?? StudySession::create([
            'user_id'     => auth()->id(),
            'deck_id'     => $deck->id,
            'mode'        => $mode,
            'total_cards' => $allTerms->count(),
        ]);

        return Inertia::render('Study::Study/Session', [
            'deck'          => $deck->load('sourceLanguage', 'targetLanguage'),
            'terms'         => $terms,
            'mode'          => $mode,
            'session'       => $session,
            'wrongTermIds'  => $wrongTermIds,
            'matchProgress' => $matchProgress,
            'isResumed'     => $resumedSession !== null,
        ]);
    }

    public function answer(Request $request, StudySession $session): JsonResponse
    {
        $request->validate([
            'term_id'          => 'required|exists:terms,id',
            'is_correct'       => 'required|boolean',
            'response_time_ms' => 'nullable|integer',
            'rating'           => 'nullable|integer|min:0|max:5',
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
