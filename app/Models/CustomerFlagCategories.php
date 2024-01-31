<?php

namespace App\Models;

use App\Models\Interfaces\CustomerFlagCategoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerFlagCategories extends Model implements CustomerFlagCategoryInterface
{

    /**
     * Table name.
     *
     * @var array
     */
    public $table = 'customer_flag_categories';
    use SoftDeletes;

    public const ACTIVE = 1;
    public const INACTIVE = 0;

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public $timestamps = true;

    /**
     * ORM Functions
     **/

    public function FlagMataData($Filter = [])
    {
        $data = $this->hasMany(FlagCategoryMetaData::class, 'category_ref_id', 'id');
        $arrg_type = gettype($Filter);

        if (count($Filter) > 0) {

            $data->whereIn('type', $Filter);
        } else if ($arrg_type == 'integer' || $arrg_type == 'double' || $arrg_type == 'string' || $arrg_type == 'boolean') {

            $data->where('type', $Filter);
        }

        return $data;
    }




    public function getParent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function FlagCategoryValues()
    {
        return $this->hasOne(CustomerFlagCategoryValues::class, 'category_ref_id', 'id');
    }

    /**
     * Disable Function
     **/
    public function disable(): void
    {
        $this->is_enable = 0;
        $this->save();
    }

    /**
     * Enable Function
     **/
    public function enable(): void
    {
        $this->is_enable = 1;
        $this->save();
    }

    /**
     * Attribute Function For Disable And Enable
     **/
    public function getStatusTextFormattedAttribute(): string
    {
        return (int)$this->attributes['is_enable'] === 1 ?
            '<a href="' . route('customer-service.isDisable', $this->attributes['id']) . '"><span class="label label-success">Enable</span></a>' :
            '<a href="' . route('customer-service.isEnable', $this->attributes['id']) . '"><span class="label label-warning">Disable</span></a>';
    }

    /**
     * Scope For Parent Data
     **/
    public function scopeIsParent($query)
    {
        return $query->where('parent_id', '==', 0);
    }

    /**
     * Function For Child Data
     **/
    public function getChilds()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

}
