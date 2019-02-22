<?php

namespace App\Http\Controllers\Api;

use App\Http\Transformers\LicensesTransformer;
use App\Models\License;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Transformers\UsersTransformer;
use App\Models\Company;
use App\Models\User;
use App\Helpers\Helper;
use App\Http\Requests\SaveUserRequest;
use App\Models\Asset;
use App\Http\Transformers\AssetsTransformer;
use App\Http\Transformers\SelectlistTransformer;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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


        if (($request->filled('deleted')) && ($request->input('deleted')=='true')) {
            $users = $users->onlyTrashed();
        }

        if ($request->filled('company_id')) {
            $users = $users->where('users.company_id', '=', $request->input('company_id'));
        }

        if ($request->filled('location_id')) {
            $users = $users->where('users.location_id', '=', $request->input('location_id'));
        }

        if ($request->filled('group_id')) {
            $users = $users->ByGroup($request->get('group_id'));
        }

        if ($request->filled('department_id')) {
            $users = $users->where('users.department_id','=',$request->input('department_id'));
        }

        if ($request->filled('search')) {
            $users = $users->TextSearch($request->input('search'));
        }

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $offset = (($users) && (request('offset') > $users->count())) ? 0 : request('offset', 0);
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

        $users = User::select(
            [
                'users.id',
                'users.username',
                'users.employee_num',
                'users.first_name',
                'users.last_name',
                'users.gravatar',
                'users.avatar',
                'users.email',
            ]
            )->where('show_in_list', '=', '1');

        $users = Company::scopeCompanyables($users);

        if ($request->filled('search')) {
            $users = $users->where('first_name', 'LIKE', '%'.$request->get('search').'%')
                ->orWhere('last_name', 'LIKE', '%'.$request->get('search').'%')
                ->orWhere('username', 'LIKE', '%'.$request->get('search').'%')
                ->orWhere('employee_num', 'LIKE', '%'.$request->get('search').'%');
        }

        $users = $users->orderBy('last_name', 'asc')->orderBy('first_name', 'asc');
        $users = $users->paginate(50);

        foreach ($users as $user) {
            $name_str = '';
            if ($user->last_name!='') {
                $name_str .= e($user->last_name).', ';
            }
            $name_str .= e($user->first_name);

            if ($user->username!='') {
                $name_str .= ' ('.e($user->username).')';
            }

            if ($user->employee_num!='') {
                $name_str .= ' - #'.e($user->employee_num);
            }

            $user->use_text = $name_str;
            $user->use_image = ($user->present()->gravatar) ? $user->present()->gravatar : null;
        }

        return (new SelectlistTransformer)->transformSelectlist($users);

    }



    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SaveUserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = new User;
        $user->fill($request->all());

        $tmp_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 20);
        $user->password = bcrypt($request->get('password', $tmp_pass));


        if ($user->save()) {
            if ($request->has('groups')) {
                $user->groups()->sync($request->input('groups'));
            } else {
                $user->groups()->sync(array());
            }
            
            return response()->json(Helper::formatStandardApiResponse('success', (new UsersTransformer)->transformUser($user), trans('admin/users/message.success.create')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $user->getErrors()));
    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->authorize('view', User::class);
        $user = User::findOrFail($id);
        return (new UsersTransformer)->transformUser($user);
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
    public function update(SaveUserRequest $request, $id)
    {
        $this->authorize('update', User::class);

        $user = User::findOrFail($id);

        // This is a janky hack to prevent people from changing admin demo user data on the public demo.
        // The $ids 1 and 2 are special since they are seeded as superadmins in the demo seeder.
        // Thanks, jerks. You are why we can't have nice things. - snipe

        if ((($id == 1) || ($id == 2)) && (config('app.lock_passwords'))) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'Permission denied. You cannot update user information via API on the demo.'));
        }


        $user->fill($request->all());

        if ($user->id == $request->input('manager_id')) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'You cannot be your own manager'));
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // Update the location of any assets checked out to this user
        Asset::where('assigned_type', User::class)
            ->where('assigned_to', $user->id)->update(['location_id' => $request->input('location_id', null)]);

        if ($user->save()) {
            if ($request->filled('groups')) {
                $user->groups()->sync($request->input('groups'));
            }
            return response()->json(Helper::formatStandardApiResponse('success', (new UsersTransformer)->transformUser($user), trans('admin/users/message.success.update')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $user->getErrors()));
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
        $this->authorize('delete', User::class);
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);


        if ($user->assets()->count() > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/users/message.error.delete_has_assets')));
        }

        // Remove the user's avatar if they have one
        if (Storage::disk('public')->exists('avatars/'.$user->avatar)) {
            try  {
                Storage::disk('public')->delete('avatars/'.$user->avatar);
            } catch (\Exception $e) {
                \Log::debug($e);
            }
        }

        if ($user->delete()) {
            return response()->json(Helper::formatStandardApiResponse('success', null,  trans('admin/users/message.success.delete')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/users/message.error.delete')));
    }

    /**
     * Return JSON containing a list of assets assigned to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     * @param $userId
     * @return string JSON
     */
    public function assets($id)
    {
        $this->authorize('view', User::class);
        $this->authorize('view', Asset::class);
        $assets = Asset::where('assigned_to', '=', $id)->where('assigned_type', '=', User::class)->with('model')->get();
        return (new AssetsTransformer)->transformAssets($assets, $assets->count());
    }

    /**
     * Return JSON containing a list of licenses assigned to a user.
     *
     * @author [N. Mathar] [<snipe@snipe.net>]
     * @since [v5.0]
     * @param $userId
     * @return string JSON
     */
    public function licenses($id)
    {
        $this->authorize('view', User::class);
        $this->authorize('view', License::class);
        $user = User::where('id', $id)->withTrashed()->first();
        $licenses = $user->licenses()->get();
        return (new LicensesTransformer())->transformLicenses($licenses, $licenses->count());
    }

    /**
     * Reset the user's two-factor status
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     * @param $userId
     * @return string JSON
     */
    public function postTwoFactorReset(Request $request)
    {

        $this->authorize('update', User::class);

        if ($request->filled('id')) {
            try {
                $user = User::find($request->get('id'));
                $user->two_factor_secret = null;
                $user->two_factor_enrolled = 0;
                $user->save();
                return response()->json(['message' => trans('admin/settings/general.two_factor_reset_success')], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => trans('admin/settings/general.two_factor_reset_error')], 500);
            }
        }
        return response()->json(['message' => 'No ID provided'], 500);

    }

    /**
     * Get info on the current user.
     *
     * @author [Juan Font] [<juanfontalonso@gmail.com>]
     * @since [v4.4.2]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCurrentUserInfo(Request $request)
    {
        return response()->json($request->user());
    }
}
