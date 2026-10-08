<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listId' => 'required|uuid|exists:task_lists,id',
            'position' => 'nullable|integer|min:1',
        ];
    }
}
