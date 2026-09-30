<?php
namespace Modules\Study\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Study\Models\StudySession;
use Modules\Vocabulary\Models\Term;

class UniversalStudyController extends Controller
{
    public function decks(): JsonResponse
    {
        $decks = Deck::where('user_id', auth()->id())
            ->withCount('terms')
            ->orderBy('title')
            ->get()
            ->map(fn ($d) => [
                'id'          => $d->id,
                'title'       => $d->title,
                'color'       => $d->color,
                'terms_count' => $d->terms_count,
            ]);

        return response()->json($decks);
    }

    public function start(Request $request)
    {
        $request->validate([
            'deck_ids'   => 'required|array|min:1',
            'deck_ids.*' => 'integer|exists:decks,id',
            'mode'       => 'required|in:learn,test,match',
        ]);

        $deckIds = Deck::whereIn('id', $request->deck_ids)
            ->where('user_id', auth()->id())
            ->pluck('id')
            ->toArray();

        abort_if(empty($deckIds), 403);

        $termCount = Term::whereIn('deck_id', $deckIds)->count();

        $session = StudySession::create([
            'user_id'     => auth()->id(),
            'deck_id'     => null,
            'deck_ids'    => $deckIds,
            'mode'        => $request->mode,
            'total_cards' => $termCount,
        ]);

        return redirect()->route('universal.study.session', $session->id);
    }

    public function session(StudySession $session): Response
    {
        abort_unless($session->user_id === auth()->id(), 403);
        abort_if(empty($session->deck_ids), 400);

        $terms = Term::whereIn('deck_id', $session->deck_ids)
            ->with(['primaryImage', 'examples'])
            ->inRandomOrder()
            ->get()
            ->map(fn ($t) => [
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

        return Inertia::render('Study::Study/UniversalSession', [
            'terms'     => $terms,
            'mode'      => $session->mode,
            'session'   => $session,
            'deckCount' => count($session->deck_ids),
        ]);
    }
}
