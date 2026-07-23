<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $guard = 'admin';

    // NOTE: two_factor_* fields are intentionally NOT fillable — they must
    // only be written by TwoFactorController after the admin proves
    // possession of the secret (see enable()/disable() there).
    //
    // Because $fillable is a non-empty array, Eloquent blocks these keys on
    // ->update()/->create() entirely (not just from request input) — so
    // TwoFactorController writes them with forceFill()->save() instead,
    // which is the intended, deliberate bypass for this trusted code path.
    protected $fillable = ['username', 'password', 'name'];

    protected $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        // 'encrypted*' casts transparently encrypt/decrypt using APP_KEY, so
        // the DB never holds a readable TOTP secret or recovery codes even
        // if the database itself is leaked.
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function hasEnabledTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }
}
