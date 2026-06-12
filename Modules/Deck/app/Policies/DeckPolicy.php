<?php
namespace Modules\Deck\Policies;

use App\Models\User;
use Modules\Deck\Models\Deck;

class DeckPolicy
{
    public function view(User $user, Deck $deck): bool
    {
        return $deck->user_id === $user->id || $deck->is_public;
    }

    public function update(User $user, Deck $deck): bool
    {
        return $deck->user_id === $user->id;
    }

    public function delete(User $user, Deck $deck): bool
    {
        return $deck->user_id === $user->id;
    }
}
