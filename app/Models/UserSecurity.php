<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSecurity extends Model
{
    protected $fillable = [
        'user_id',
        'two_factor_enabled',
        'two_factor_secret',
        'recovery_codes',
        'password_changed_at',
    ];

    protected $casts = [
        'two_factor_enabled'  => 'boolean',
        'recovery_codes'      => 'array',
        'password_changed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
