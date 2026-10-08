<?php

namespace App\Policies;

use App\Models\TaskList;
use App\Models\User;

class TaskListPolicy
{
    public function view(User $user, TaskList $taskList): bool
    {
        return $taskList->project?->hasMember($user) ?? false;
    }

    public function update(User $user, TaskList $taskList): bool
    {
        return $taskList->project?->canMutateBoard($user) ?? false;
    }

    public function delete(User $user, TaskList $taskList): bool
    {
        return $taskList->project?->canMutateBoard($user) ?? false;
    }
}
