<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Location247;
use App\Models\LocationUsers;
use App\Models\User;
use App\Models\Company;
use App\Http\Transformers\Locations247Transformer;
use App\Http\Transformers\UsersTransformer;
use App\Http\Transformers\SelectlistTransformer;
use Illuminate\Support\Facades\Storage;
use Auth;

class LocationUsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', Location247::class);
        $allowed_columns = [
                'id','name','address','address2','city','state','country','zip','created_at',
                'updated_at','manager_id','image',
                'assigned_assets_count','users_count','assets_count','currency'];

        $locations = Location247::with('parent', 'manager', 'childLocations')->select([
            'locations.id',
            'locations.name',
            'locations.address',
            'locations.address2',
            'locations.city',
            'locations.state',
            'locations.zip',
            'locations.country',
            'locations.parent_id',
            'locations.manager_id',
            'locations.created_at',
            'locations.updated_at',
            'locations.image',
            'locations.currency'
        ])->withCount('assignedAssets as assigned_assets_count')
        ->withCount('assets as assets_count')
        ->withCount('managers as users_count');


        if ($request->filled('search')) {
            $locations = $locations->TextSearch($request->input('search'));
        }



        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 50);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';

        switch ($request->input('sort')) {
            case 'parent':
                $locations->OrderParent($order);
                break;
            case 'manager':
                $locations->OrderManager($order);
                break;
            default:
                $locations->orderBy($sort, $order);
                break;
        }


        $total = $locations->count();
        $locations = $locations->skip($offset)->take($limit)->get();
        return (new Locations247Transformer)->transformLocations($locations, $total);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Location247::class);
        $location = new Location;
        $location->fill($request->all());

        if ($location->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new LocationsTransformer)->transformLocation($location), trans('admin/locations/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $location->getErrors()));
    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->authorize('view', Location247::class);
        $location = Location247::with('parent', 'manager', 'childLocations')
            ->select([
                'locations.id',
                'locations.name',
                'locations.address',
                'locations.address2',
                'locations.city',
                'locations.state',
                'locations.zip',
                'locations.country',
                'locations.parent_id',
                'locations.manager_id',
                'locations.created_at',
                'locations.updated_at',
                'locations.image',
                'locations.currency'
            ])
            ->withCount('assignedAssets')
            ->withCount('assets')
            ->withCount('users')->findOrFail($id);
        return (new LocationsTransformer)->transformLocation($location);
    }


    /**
     * Update the specified resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->authorize('update', Location247::class);
        $location = Location247::findOrFail($id);
        $location->fill($request->all());

        if ($location->save()) {
            return response()->json(
                Helper::formatStandardApiResponse(
                    'success',
                    (new LocationsTransformer)->transformLocation($location),
                    trans('admin/locations/message.update.success')
                )
            );
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $location->getErrors()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('delete', Location247::class);
        $location = Location247::findOrFail($id);
        $this->authorize('delete', $location);
        $location->delete();
        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/locations/message.delete.success')));
    }

    /**
     * Gets a paginated collection for the select2 menus
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0.16]
     * @see \App\Http\Transformers\SelectlistTransformer
     *
     */
    public function selectlist(Request $request)
    {

        $locations = Location247::select([
            'locations.id',
            'locations.name',
            'locations.image',
        ]);

        if ($request->filled('search')) {
            $locations = $locations->where('locations.name', 'LIKE', '%'.$request->get('search').'%');
        }

        if(!Auth::user()->isSuperUser())
        {
            $locations = LocationUsers::scopeUserLocations($locations, Auth::user()->id);
        }

        $locations = $locations->orderBy('name', 'ASC')->paginate(50);
        
        // Loop through and set some custom properties for the transformer to use.
        // This lets us have more flexibility in special cases like assets, where
        // they may not have a ->name value but we want to display something anyway
        foreach ($locations as $location) {
            $location->use_text = $location->name;
            $location->use_image = ($location->image) ? Storage::disk('public')->url('locations/'.$location->image, $location->image): null;
        }

        return (new SelectlistTransformer)->transformSelectlist($locations);

    }

    /**
     * Gets a paginated collection for the Location managers
     *
     * @author [Janardhana CR] [<janardhana.cr@247.ai>]
     * @since [v4.0.16]
     * @param  int  $locationid
     * @return \Illuminate\Http\Response
     *
     */
    public function users(Request $request, $locationid)
    {
        $this->authorize('view', User::class);

        $users = User::select([
            'users.activated',
            'users.address',
            'users.avatar',
            'users.city',
            'users.company_id',
            'users.country',
            'users.created_at',
            'users.deleted_at',
            'users.department_id',
            'users.email',
            'users.employee_num',
            'users.first_name',
            'users.id',
            'users.jobtitle',
            'users.last_login',
            'users.last_name',
            'users.location_id',
            'users.manager_id',
            'users.notes',
            'users.permissions',
            'users.phone',
            'users.state',
            'users.two_factor_enrolled',
            'users.updated_at',
            'users.username',
            'users.zip',

        ])->with('manager', 'groups', 'userloc', 'company', 'department','assets','licenses','accessories','consumables')
            ->withCount('assets as assets_count','licenses as licneses_count','accessories as accessories_count','consumables as consumables_count');
        $users = Company::scopeCompanyables($users);


        if ($locationid) {
            $users = $users->join('location_users as lc', 'lc.user_id', '=', 'users.id')
                    ->where('users.location_id', '=', $locationid);
        }

        if ($request->filled('search')) {
            $users = $users->TextSearch($request->input('search'));
        }

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $offset = request('offset', 0);
        $limit = request('limit',  20);

        switch ($request->input('sort')) {
            case 'manager':
                $users = $users->OrderManager($order);
                break;
            case 'location':
                $users = $users->OrderLocation($order);
                break;
            case 'department':
                $users = $users->OrderDepartment($order);
                break;
            default:
                $allowed_columns =
                    [
                        'last_name','first_name','email','jobtitle','username','employee_num',
                        'assets','accessories', 'consumables','licenses','groups','activated','created_at',
                        'two_factor_enrolled','two_factor_optin','last_login', 'assets_count', 'licenses_count',
                        'consumables_count', 'accessories_count', 'phone', 'address', 'city', 'state',
                        'country', 'zip', 'id'
                    ];

                $sort = in_array($request->get('sort'), $allowed_columns) ? $request->get('sort') : 'first_name';
                $users = $users->orderBy($sort, $order);
                break;
        }


        $total = $users->count();
        $users = $users->skip($offset)->take($limit)->get();
        return (new UsersTransformer)->transformUsers($users, $total);



        return (new SelectlistTransformer)->transformSelectlist($locations);

    }

}
