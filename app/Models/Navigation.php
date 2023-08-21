<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'action' => 'array',
    ];

    /**
     * childs
     *
     * @return void
     */
    public function childs()
    {
        return $this->hasMany(__CLASS__, 'parent_id', 'id');
    }

    /**
     * parent
     *
     * @return void
     */
    public function parent()
    {
        return $this->belongsTo(__CLASS__, 'parent_id', 'id');
    }
}
