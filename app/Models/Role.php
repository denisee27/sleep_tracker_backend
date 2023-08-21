<?php

namespace App\Models;

class Role extends BaseModel
{
    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'access' => 'object',
        'dashboard' => 'object',
    ];

    /**
     * hidden
     *
     * @var array
     */
    protected $hidden = [
        'dashboard'
    ];

    /**
     * appends
     *
     * @var array
     */
    protected $appends = [
        'dashboard_access'
    ];

    /**
     * getDashboardAttribute
     *
     * @return void
     */
    public function getDashboardAccessAttribute()
    {
        return (count($this->access ?? []) ? ($this->access[0] == '*' ? ['finance' => true, 'project' => true, 'warehouse' => true] : ($this->dashboard ?? json_decode('{}'))) : json_decode('{}'));
    }

    /**
     * users
     *
     * @return void
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
