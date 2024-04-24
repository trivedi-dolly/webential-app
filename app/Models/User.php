<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\hasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Auth;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'profile_picture',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get all of the Friends for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function friendship(): hasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }
    public function friendshipRequestSent(User $user)
    {
        return Friendship::where(function ($query) use ($user) {
            $query->where('user_id', Auth::id())
                ->where('friend_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('friend_id', Auth::id());
        })->exists();
    }

    public function isFriendWith(User $user)
    {
        return $this->friendship()
            ->where(function ($query) use ($user) {
                $query->where('friend_id', $user->id)
                    ->where('user_id', Auth::id());
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('friend_id', Auth::id())
                    ->where('user_id', $user->id);
            })->where('status', 'accepted')->exists();
    }

}
