<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveCardRequest;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\Card;
use App\Models\TaskList;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    public function index(string $listId): JsonResponse
    {
        $list = TaskList::with('project')->findOrFail($listId);
        $this->authorize('view', $list);

        return response()->json([
            'success' => true,
            'cards' => $list->cards()->orderBy('position')->get(),
        ]);
    }

    public function store(StoreCardRequest $request, string $listId): JsonResponse
    {
        $list = TaskList::with('project')->findOrFail($listId);
        $this->authorize('create', [Card::class, $list]);

        $card = $list->cards()->create([
            'title' => $request->title,
            'description' => $request->description,
            'position' => $list->cards()->count() + 1,
            'completed' => false,
        ]);

        return response()->json([
            'success' => true,
            'card' => $card,
        ]);
    }

    public function moveCard(MoveCardRequest $request, string $cardId): JsonResponse
    {
        $card = Card::with('list.project')->findOrFail($cardId);
        $newList = TaskList::with('project')->findOrFail($request->listId);

        $this->authorize('move', [$card, $newList]);

        $card->task_list_id = $request->listId;

        if ($request->filled('position')) {
            $card->position = $request->position;
        } else {
            $maxPosition = Card::where('task_list_id', $request->listId)->max('position');
            $card->position = $maxPosition ? $maxPosition + 1 : 1;
        }

        $card->save();

        return response()->json([
            'success' => true,
            'message' => 'Card moved successfully',
            'card' => $card,
        ]);
    }

    public function update(UpdateCardRequest $request, string $cardId): JsonResponse
    {
        $card = Card::with('list.project')->findOrFail($cardId);
        $this->authorize('update', $card);

        $card->update($request->only(['title', 'description', 'completed']));

        return response()->json([
            'success' => true,
            'message' => 'Card updated successfully',
            'card' => $card->fresh(),
        ]);
    }

    public function destroy(string $cardId): JsonResponse
    {
        $card = Card::with('list.project')->findOrFail($cardId);
        $this->authorize('delete', $card);

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Card deleted successfully',
        ]);
    }
}
