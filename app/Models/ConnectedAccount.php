<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $email_provider_id
 * @property string $provider_user_id
 * @property string $email
 * @property string $name
 * @property string|null $avatar
 * @property string $token
 * @property string|null $refresh_token
 * @property string|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmailProvider $provider
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereEmailProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereProviderUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConnectedAccount whereUserId($value)
 * @mixin \Eloquent
 */
class ConnectedAccount extends Model
{
    protected $fillable = [
        'user_id',
        'email_provider_id',
        'provider_user_id',
        'name',
        'email',
        'avatar',
        'token',
        'refresh_token',
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(EmailProvider::class, 'email_provider_id');
    }
}