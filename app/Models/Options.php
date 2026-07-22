<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $option_id
 * @property string $slug
 * @property string $Name
 * @property string $created_at
 * @property string $updated_at
 * @property OptionType $optionType
 * @property CustomerOption[] $customerOptions
 */
class Options extends Model
{
    /**
     * The "type" of the auto-incrementing ID.
     * 
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'option_id', 'slug', 'Name', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function optionType()
    {
        return $this->belongsTo(OptionType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customerOptions()
    {
        return $this->hasMany('App\CustomerOption');
    }
}
