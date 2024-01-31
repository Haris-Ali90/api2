<?php

namespace App\Models;

use App\Models\Interfaces\SiteSettingInterface;
use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\CategoryInterface;

class SiteSetting extends Model implements SiteSettingInterface
{
    protected $guarded = [];
}
