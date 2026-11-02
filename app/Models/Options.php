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
    protected $fillable = ['user_id', 'measurement_template_id', 'option_id', 'slug', 'Name', 'is_default', 'created_at', 'updated_at'];

    protected $casts = ['is_default' => 'boolean'];

    public static function ownerUsesOwnChoices(int $ownerId): bool
    {
        return User::whereKey($ownerId)->value('option_source') === User::OPTION_SOURCE_OWN;
    }

    /**
     * The default list also shows what the shop added; the own list hides the seeded choices.
     */
    public function scopeInOwnerSource($query, int $ownerId)
    {
        return self::ownerUsesOwnChoices($ownerId) ? $query->where('is_default', false) : $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function optionType()
    {
        return $this->belongsTo(OptionType::class, 'option_id');
    }

    public function measurementTemplate()
    {
        return $this->belongsTo(MeasurementTemplate::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customerOptions()
    {
        return $this->hasMany('App\CustomerOption');
    }
}
