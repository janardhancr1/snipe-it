<?php
namespace App\Models;

use App\Http\Traits\UniqueUndeletedTrait;
use App\Models\SnipeModel;
use App\Models\Traits\Searchable;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * Model for Categories. Categories are a higher-level group
 * than Asset Models, and handle things like whether or not
 * to require acceptance from the user, whether or not to
 * send a EULA to the user, etc.
 *
 * @version    v1.0
 */
class ErpCategory extends SnipeModel
{
    protected $presenter = 'App\Presenters\CategoryPresenter';
    use Presentable;
    use SoftDeletes;
    //protected $dates = ['deleted_at'];
    protected $table = 'erp_categories';
    //protected $hidden = ['user_id','deleted_at'];

    /**
    * ErpCategory validation rules
    */
    public $rules = array(
        'name'   => 'required|min:1|max:255|unique_undeleted',
    );

    /**
    * Whether the model should inject it's identifier to the unique
    * validation rules before attempting validation. If this property
    * is not set in the model it will default to true.
    *
    * @var boolean
    */
    protected $injectUniqueIdentifier = true;
    use ValidatingTrait;
    use UniqueUndeletedTrait;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    use Searchable;
    
    /**
     * The attributes that should be included when searching the model.
     * 
     * @var array
     */
    protected $searchableAttributes = ['name'];

    /**
     * The relations and their attributes that should be included when searching the model.
     * 
     * @var array
     */
    protected $searchableRelations = [];


    /**
     * Establishes the category -> accessories relationship
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v2.0]
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function categories()
    {
        return $this->hasMany('\App\Models\Category247');
    }
}
