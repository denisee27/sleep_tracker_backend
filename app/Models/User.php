<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

class User extends BaseModel implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];

    /**
     * appends
     *
     * @var array
     */
    protected $appends = [
        'initials',
        'is_superadmin',
        'wa_number'
    ];

    /**
     * getInitialsAttribute
     *
     * @return void
     */
    public function getInitialsAttribute()
    {
        preg_match_all('/(?<=\b)\w/iu', $this->name, $matches);
        return mb_strtoupper(implode('', array_slice($matches[0], 0, 3)));
    }

    /**
     * getIsSuperadminAttribute
     *
     * @return void
     */
    public function getIsSuperadminAttribute()
    {
        $access = (array) ($this->job_position->role->access ?? []);
        return count($access) && $access[0] == '*';
    }

    /**
     * getPhoneNumberAttribute
     *
     * @return void
     */
    public function getWaNumberAttribute()
    {
        if (!$this->phone) {
            return null;
        }
        $phone = str_replace('-', '', $this->phone);
        $phone = str_replace(' ', '', $phone);
        if (Str::startsWith($phone, '+62') || Str::startsWith($phone, '62')) {
            return str_replace('+', '', $phone);
        } elseif (Str::startsWith($phone, '08')) {
            return '62' . substr($phone, 1, 20);
        } elseif (Str::startsWith($phone, '02')) {
            return '62' . substr($phone, 1, 20);
        } else {
            return '62' . str_replace('+', '', $phone);
        }
    }

    /**
     * role
     *
     * @return mixed
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * superior
     *
     * @return mixed
     */
    public function superior()
    {
        return $this->belongsTo(User::class,'parent_id');
    }

    /**
     * notifications
     *
     * @return void
     */
    public function notifications()
    {
        return $this->hasMany(UserNotification::class, 'user_id', 'id');
    }

    /**
     * getJWTIdentifier
     *
     * @return void
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
