<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskListRequest;
use App\Http\Requests\UpdateTaskListRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class TaskListController extends Controller
{
    public function index(string $projectId): JsonResponse
    {
        $project = Project::with(['lists' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($projectId);

        $this->authorize('view', $project);

        return response()->json([
            'success' => true,
            'lists' => $project->lists,
        ]);
    }

    public function store(StoreTaskListRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('mutate', $project);

        $list = $project->lists()->create([
            'title' => $request->title,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'List created successfully',
            'list' => $list,
        ]);
    }

    public function update(UpdateTaskListRequest $request, string $projectId, string $listId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('view', $project);

        $list = $project->lists()->where('id', $listId)->firstOrFail();
        $this->authorize('update', $list);

        $list->title = $request->title;
        $list->save();

        return response()->json([
            'success' => true,
            'message' => 'Task list title updated successfully',
            'list' => $list,
        ]);
    }

    public function destroy(string $projectId, string $listId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('view', $project);

        $list = $project->lists()->where('id', $listId)->firstOrFail();
        $this->authorize('delete', $list);

        $list->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task list deleted successfully',
        ]);
    }
}
