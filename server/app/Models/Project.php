<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'total_tasks',
        'completed_tasks',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Project $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function lists(): HasMany
    {
        return $this->hasMany(TaskList::class)->orderBy('created_at');
    }

    public function roleFor(User $user): ?ProjectRole
    {
        $membership = $this->relationLoaded('users')
            ? $this->users->firstWhere('id', $user->id)
            : $this->users()->where('users.id', $user->id)->first();

        if (! $membership?->pivot?->role) {
            return null;
        }

        return ProjectRole::tryFrom($membership->pivot->role);
    }

    public function hasMember(User $user): bool
    {
        return $this->roleFor($user) !== null;
    }

    public function isOwner(User $user): bool
    {
        return $this->roleFor($user) === ProjectRole::Owner;
    }

    public function canMutateBoard(User $user): bool
    {
        return $this->roleFor($user)?->canMutateBoard() ?? false;
    }

    public function recalculateTaskCounts(): void
    {
        $listIds = $this->lists()->pluck('id');

        $this->forceFill([
            'total_tasks' => Card::whereIn('task_list_id', $listIds)->count(),
            'completed_tasks' => Card::whereIn('task_list_id', $listIds)->where('completed', true)->count(),
        ])->saveQuietly();
    }
}
