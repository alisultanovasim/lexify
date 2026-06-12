<?php
namespace Modules\Progress\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Deck\Models\Deck;
use Modules\Progress\Models\UserTermProgress;
use Modules\Study\Models\StudySession;

class ProgressController extends Controller
{
    public function index(): Response
    {
        $userId = auth()->id();

        $totalDecks = Deck::where('user_id', $userId)->count();
        $totalTerms = \Modules\Vocabulary\Models\Term::whereHas('deck', fn ($q) => $q->where('user_id', $userId))->count();
        $studiedTerms = UserTermProgress::where('user_id', $userId)->count();

        $dueToday = UserTermProgress::where('user_id', $userId)
            ->where('next_review_at', '<=', now())
            ->with('term.deck')
            ->get()
            ->map(fn ($p) => [
                'term_id'      => $p->term_id,
                'term'         => $p->term->term,
                'definition'   => $p->term->definition,
                'deck_id'      => $p->term->deck_id,
                'deck_title'   => $p->term->deck->title,
                'interval'     => $p->interval,
                'repetitions'  => $p->repetitions,
            ]);

        $recentSessions = StudySession::where('user_id', $userId)
            ->with('deck')
            ->whereNotNull('completed_at')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'deck_title'  => $s->deck->title,
                'mode'        => $s->mode,
                'total'       => $s->total_cards,
                'correct'     => $s->correct_count,
                'score'       => $s->total_cards > 0 ? round(($s->correct_count / $s->total_cards) * 100) : 0,
                'completed_at'=> $s->completed_at?->diffForHumans(),
            ]);

        $deckProgress = Deck::where('user_id', $userId)
            ->withCount('terms')
            ->get()
            ->map(fn ($d) => [
                'id'           => $d->id,
                'title'        => $d->title,
                'color'        => $d->color,
                'terms_count'  => $d->terms_count,
                'studied_count'=> UserTermProgress::where('user_id', $userId)
                    ->whereHas('term', fn ($q) => $q->where('deck_id', $d->id))
                    ->count(),
            ]);

        return Inertia::render('Progress::Progress/Index', compact(
            'totalDecks', 'totalTerms', 'studiedTerms',
            'dueToday', 'recentSessions', 'deckProgress'
        ));
    }
}
