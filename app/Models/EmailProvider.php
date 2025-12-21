<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $identifier
 * @property string $display_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailProvider extends Model
{
    protected $fillable = [
        'name',
        'identifier',
        'display_name',
    ];

    /**
     * Relación con usuarios
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
