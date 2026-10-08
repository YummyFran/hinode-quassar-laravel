<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Member = 'member';
    case Viewer = 'viewer';

    public function canMutateBoard(): bool
    {
        return $this === self::Owner || $this === self::Member;
    }

    public function canManageProject(): bool
    {
        return $this === self::Owner;
    }

    /**
     * @return list<string>
     */
    public static function assignableValues(): array
    {
        return [self::Member->value, self::Viewer->value];
    }
}
