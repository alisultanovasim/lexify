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

        // One row per deck+mode — the most recent session for each combination
        $recentSessions = StudySession::where('user_id', $userId)
            ->with('deck')
            ->withCount('answers')
            ->where(fn ($q) => $q->whereNotNull('completed_at')->orWhereHas('answers'))
            ->latest()
            ->get()
            ->unique(fn ($s) => $s->deck_id . '_' . $s->mode)
            ->values()
            ->take(20)
            ->map(fn ($s) => [
                'id'          => $s->id,
                'deck_id'     => $s->deck_id,
                'deck_title'  => $s->deck?->title ?? '—',
                'mode'        => $s->mode,
                'total'       => $s->total_cards,
                'correct'     => $s->correct_count,
                'answered'    => $s->answers_count,
                'score'       => $s->total_cards > 0 ? round(($s->correct_count / $s->total_cards) * 100) : 0,
                'completed'   => !is_null($s->completed_at),
                'completed_at'=> $s->completed_at?->diffForHumans() ?? $s->updated_at->diffForHumans(),
            ]);

        // Incomplete sessions: started (has ≥1 answer) but never completed
        $incompleteSessions = StudySession::where('user_id', $userId)
            ->whereNull('completed_at')
            ->whereHas('answers')
            ->with('deck')
            ->withCount('answers')
            ->latest()
            ->get()
            ->unique('deck_id')        // one per deck (most recent)
            ->values()
            ->take(5)
            ->map(fn ($s) => [
                'id'           => $s->id,
                'deck_id'      => $s->deck_id,
                'deck_title'   => $s->deck?->title ?? '—',
                'mode'         => $s->mode,
                'answered'     => $s->answers_count,
                'total'        => $s->total_cards,
                'percent'      => $s->total_cards > 0 ? round($s->answers_count / $s->total_cards * 100) : 0,
                'started_at'   => $s->created_at->diffForHumans(),
            ])
            ->values();

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
            'dueToday', 'recentSessions', 'deckProgress', 'incompleteSessions'
        ));
    }
}
