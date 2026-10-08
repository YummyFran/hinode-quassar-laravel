<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'task_list_id' => TaskList::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'position' => 1,
            'completed' => false,
        ];
    }
}
