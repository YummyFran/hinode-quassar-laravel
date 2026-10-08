<?php

use App\Enums\ProjectRole;
use App\Models\Card;
use App\Models\Project;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('signup sets an HttpOnly auth cookie', function () {
    $response = $this->postJson('/api/signup', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonMissingPath('token')
        ->assertCookie('auth_token');

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === 'auth_token');

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue();
});

test('login sets an HttpOnly auth cookie and does not return a token', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonMissingPath('token')
        ->assertCookie('auth_token');

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === 'auth_token');

    expect($cookie->isHttpOnly())->toBeTrue();
});

test('non-members cannot read or mutate another projects lists and cards', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $project = createProjectWithRole($owner, ProjectRole::Owner);
    $list = TaskList::factory()->create(['project_id' => $project->id]);
    $card = Card::factory()->create(['task_list_id' => $list->id]);

    actingAsApi($outsider)->getJson("/api/project/{$project->id}")->assertForbidden();
    actingAsApi($outsider)->getJson("/api/project/{$project->id}/list")->assertForbidden();
    actingAsApi($outsider)->postJson("/api/project/{$project->id}/list", ['title' => 'Hacked'])->assertForbidden();
    actingAsApi($outsider)->getJson("/api/list/{$list->id}/card")->assertForbidden();
    actingAsApi($outsider)->postJson("/api/list/{$list->id}/card", ['title' => 'Hacked'])->assertForbidden();
    actingAsApi($outsider)->putJson("/api/cards/{$card->id}", ['title' => 'Hacked'])->assertForbidden();
    actingAsApi($outsider)->deleteJson("/api/cards/{$card->id}")->assertForbidden();
});

test('viewers can read but cannot mutate board or manage project', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = createProjectWithRole($owner, ProjectRole::Owner);
    $project->users()->attach($viewer->id, ['role' => ProjectRole::Viewer->value]);
    $list = TaskList::factory()->create(['project_id' => $project->id]);
    $card = Card::factory()->create(['task_list_id' => $list->id]);
    $otherList = TaskList::factory()->create(['project_id' => $project->id]);

    actingAsApi($viewer)->getJson("/api/project/{$project->id}")->assertOk();
    actingAsApi($viewer)->getJson("/api/project/{$project->id}/list")->assertOk();
    actingAsApi($viewer)->getJson("/api/list/{$list->id}/card")->assertOk();

    actingAsApi($viewer)->postJson("/api/project/{$project->id}/list", ['title' => 'Nope'])->assertForbidden();
    actingAsApi($viewer)->putJson("/api/project/{$project->id}/list/{$list->id}", ['title' => 'Nope'])->assertForbidden();
    actingAsApi($viewer)->deleteJson("/api/project/{$project->id}/list/{$list->id}")->assertForbidden();
    actingAsApi($viewer)->postJson("/api/list/{$list->id}/card", ['title' => 'Nope'])->assertForbidden();
    actingAsApi($viewer)->putJson("/api/cards/{$card->id}", ['title' => 'Nope'])->assertForbidden();
    actingAsApi($viewer)->putJson("/api/cards/{$card->id}/move", ['listId' => $otherList->id])->assertForbidden();
    actingAsApi($viewer)->deleteJson("/api/cards/{$card->id}")->assertForbidden();
    actingAsApi($viewer)->putJson("/api/project/{$project->id}", [
        'title' => 'Nope',
        'description' => 'Nope',
    ])->assertForbidden();
    actingAsApi($viewer)->deleteJson("/api/project/{$project->id}")->assertForbidden();
    actingAsApi($viewer)->postJson("/api/project/{$project->id}/member", [
        'email' => User::factory()->create()->email,
    ])->assertForbidden();
});

test('members can mutate board but cannot manage project', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = createProjectWithRole($owner, ProjectRole::Owner);
    $project->users()->attach($member->id, ['role' => ProjectRole::Member->value]);
    $list = TaskList::factory()->create(['project_id' => $project->id]);
    $card = Card::factory()->create(['task_list_id' => $list->id]);
    $otherList = TaskList::factory()->create(['project_id' => $project->id]);

    actingAsApi($member)->postJson("/api/list/{$list->id}/card", [
        'title' => 'Member card',
        'description' => 'Created by member',
    ])->assertOk()->assertJsonPath('success', true);

    actingAsApi($member)->putJson("/api/cards/{$card->id}/move", [
        'listId' => $otherList->id,
    ])->assertOk();

    actingAsApi($member)->putJson("/api/project/{$project->id}", [
        'title' => 'Nope',
        'description' => 'Nope',
    ])->assertForbidden();

    actingAsApi($member)->postJson("/api/project/{$project->id}/member", [
        'email' => User::factory()->create()->email,
        'role' => 'viewer',
    ])->assertForbidden();
});

test('owners can manage members including assigning viewer role', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'viewer@example.com']);
    $project = createProjectWithRole($owner, ProjectRole::Owner);

    actingAsApi($owner)->postJson("/api/project/{$project->id}/member", [
        'email' => 'viewer@example.com',
        'role' => 'viewer',
    ])->assertOk()
        ->assertJsonPath('role', 'viewer');

    expect($project->fresh()->roleFor($invitee))->toBe(ProjectRole::Viewer);
});

test('cards cannot be moved across projects', function () {
    $owner = User::factory()->create();
    $projectA = createProjectWithRole($owner, ProjectRole::Owner);
    $projectB = createProjectWithRole($owner, ProjectRole::Owner);
    $listA = TaskList::factory()->create(['project_id' => $projectA->id]);
    $listB = TaskList::factory()->create(['project_id' => $projectB->id]);
    $card = Card::factory()->create(['task_list_id' => $listA->id]);

    actingAsApi($owner)->putJson("/api/cards/{$card->id}/move", [
        'listId' => $listB->id,
    ])->assertForbidden();
});

test('task counters update on create complete and delete', function () {
    $owner = User::factory()->create();
    $project = createProjectWithRole($owner, ProjectRole::Owner);
    $list = TaskList::factory()->create(['project_id' => $project->id]);

    $create = actingAsApi($owner)->postJson("/api/list/{$list->id}/card", [
        'title' => 'Count me',
        'description' => 'Counter card',
    ])->assertOk();

    $cardId = $create->json('card.id');
    expect($project->fresh()->total_tasks)->toBe(1)
        ->and($project->fresh()->completed_tasks)->toBe(0);

    actingAsApi($owner)->putJson("/api/cards/{$cardId}", [
        'title' => 'Count me',
        'description' => 'Counter card',
        'completed' => true,
    ])->assertOk();

    expect($project->fresh()->completed_tasks)->toBe(1);

    actingAsApi($owner)->deleteJson("/api/cards/{$cardId}")->assertOk();

    expect($project->fresh()->total_tasks)->toBe(0)
        ->and($project->fresh()->completed_tasks)->toBe(0);
});

test('password is hashed once on signup', function () {
    $this->postJson('/api/signup', [
        'name' => 'Hash Check',
        'email' => 'hash@example.com',
        'password' => 'password123',
    ])->assertOk();

    $user = User::where('email', 'hash@example.com')->first();

    expect(Hash::check('password123', $user->password))->toBeTrue();
});
