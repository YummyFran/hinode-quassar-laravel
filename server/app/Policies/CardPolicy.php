<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\TaskList;
use App\Models\User;

class CardPolicy
{
    public function view(User $user, Card $card): bool
    {
        return $card->project()?->hasMember($user) ?? false;
    }

    public function create(User $user, TaskList $taskList): bool
    {
        return $taskList->project?->canMutateBoard($user) ?? false;
    }

    public function update(User $user, Card $card): bool
    {
        return $card->project()?->canMutateBoard($user) ?? false;
    }

    public function delete(User $user, Card $card): bool
    {
        return $card->project()?->canMutateBoard($user) ?? false;
    }

    public function move(User $user, Card $card, TaskList $targetList): bool
    {
        $sourceProject = $card->project();
        $targetProject = $targetList->project;

        if (! $sourceProject || ! $targetProject) {
            return false;
        }

        if ($sourceProject->id !== $targetProject->id) {
            return false;
        }

        return $sourceProject->canMutateBoard($user);
    }
}
