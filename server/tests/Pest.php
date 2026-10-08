<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Tests\TestCase;

function actingAsApi(User $user): TestCase
{
    return test()->actingAs($user, 'sanctum');
}

function createProjectWithRole(User $user, ProjectRole $role = ProjectRole::Owner): Project
{
    $project = Project::factory()->create();
    $project->users()->attach($user->id, ['role' => $role->value]);

    return $project;
}
