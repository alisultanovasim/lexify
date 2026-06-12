<?php
namespace Modules\Progress\Services;

use Carbon\Carbon;
use Modules\Progress\Models\UserTermProgress;

class SpacedRepetitionService
{
    public function update(int $userId, int $termId, int $rating): UserTermProgress
    {
        $progress = UserTermProgress::firstOrCreate(
            ['user_id' => $userId, 'term_id' => $termId],
            ['ease_factor' => 2.5, 'interval' => 1, 'repetitions' => 0]
        );

        if ($rating < 3) {
            $progress->repetitions = 0;
            $progress->interval    = 1;
        } else {
            $progress->repetitions++;
            $progress->interval = match ($progress->repetitions) {
                1       => 1,
                2       => 6,
                default => (int) round($progress->interval * $progress->ease_factor),
            };
        }

        $progress->ease_factor = max(1.3, $progress->ease_factor + 0.1 - (5 - $rating) * (0.08 + (5 - $rating) * 0.02));
        $progress->last_reviewed_at = now();
        $progress->next_review_at   = Carbon::now()->addDays($progress->interval);
        $progress->save();

        return $progress;
    }

    public function getDueTerms(int $userId, int $deckId): \Illuminate\Support\Collection
    {
        return UserTermProgress::where('user_id', $userId)
            ->where('next_review_at', '<=', now())
            ->whereHas('term', fn ($q) => $q->where('deck_id', $deckId))
            ->with('term.primaryImage')
            ->get()
            ->pluck('term');
    }
}
