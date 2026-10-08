<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\AddProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        return response()->json([
            'success' => true,
            'projects' => Project::with(['users' => function ($query) {
                $query->select('users.id', 'name')
                    ->withPivot('role', 'created_at', 'updated_at');
            }])
                ->whereHas('users', fn ($q) => $q->where('users.id', Auth::id()))
                ->orderBy('updated_at', 'desc')
                ->get(),
        ], 200);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'total_tasks' => 0,
            'completed_tasks' => 0,
        ]);

        $project->users()->attach(Auth::id(), ['role' => ProjectRole::Owner->value]);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'project' => $project,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with([
            'users' => function ($query) {
                $query->select('users.id', 'name')
                    ->withPivot('role', 'created_at', 'updated_at');
            },
            'lists.cards',
        ])->findOrFail($id);

        $this->authorize('view', $project);

        return response()->json([
            'success' => true,
            'message' => 'Details for project '.$id,
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        $project->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'project' => $project,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
        ]);
    }

    public function addMember(AddProjectMemberRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('manageMembers', $project);

        $user = User::where('email', $request->email)->firstOrFail();
        $role = ProjectRole::tryFrom($request->input('role', ProjectRole::Member->value))
            ?? ProjectRole::Member;

        if ($role === ProjectRole::Owner) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign owner role through this endpoint.',
            ], 422);
        }

        if ($project->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already a member of this project.',
            ], 409);
        }

        $project->users()->attach($user->id, ['role' => $role->value]);

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully.',
            'user' => $user,
            'role' => $role->value,
        ]);
    }

    public function removeMember(string $projectId, string $userId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('manageMembers', $project);

        if ($userId == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove yourself as a member.',
            ], 400);
        }

        $project->users()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully.',
        ]);
    }
}
