<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasUuids;

    /**
     * incrementing
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * keyType
     *
     * @var string
     */
    public $keyType = 'string';

    /**
     * dateFormat
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * creator
     *
     * @return void
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * updater
     *
     * @return void
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
