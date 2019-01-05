<?php
namespace App\Models;

use App\Http\Traits\UniqueUndeletedTrait;
use App\Models\Asset;
use App\Models\SnipeModel;
use App\Models\Traits\Searchable;
use App\Models\User;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;
use DB;

class LocationUsers extends SnipeModel
{
    protected $table = 'location_users';

    public function users()
    {
        return $this->hasMany('\App\Models\User', 'user_id');
    }

    public function locations()
    {
        return $this->hasMany('\App\Models\Location', 'location_id');
    }

    public static function scopeUserLocations($query, $userid){
        return $query->join('location_users', 'location_users.location_id', '=', 'locations.id')
        ->where("location_users.user_id",  $userid);
    }

    public static function scopeUserAssets($query, $userid){
        return $query->join('location_users', 'location_users.location_id', '=', 'assets.location_id')
        ->where("location_users.user_id",  $userid);
    }
    
}