<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Card extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'position', 'task_list_id', 'completed'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Card $card) {
            $card->id = $card->id ?? Str::uuid()->toString();
        });

        static::created(function (Card $card) {
            $card->project()?->recalculateTaskCounts();
        });

        static::updated(function (Card $card) {
            if ($card->wasChanged(['completed', 'task_list_id'])) {
                $card->project()?->recalculateTaskCounts();

                if ($card->wasChanged('task_list_id')) {
                    $oldList = TaskList::with('project')->find($card->getOriginal('task_list_id'));
                    $oldList?->project?->recalculateTaskCounts();
                }
            }
        });

        static::deleted(function (Card $card) {
            $card->project()?->recalculateTaskCounts();
        });
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TaskList::class, 'task_list_id');
    }

    public function project(): ?Project
    {
        return $this->list?->project;
    }
}
