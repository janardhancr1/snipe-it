<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Category247;
use App\Http\Transformers\Categories247Transformer;
use App\Http\Transformers\SelectlistTransformer;
use Illuminate\Support\Facades\Storage;

class Categories247Controller extends Controller
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
        $this->authorize('view', Category247::class);
        $allowed_columns = ['id', 'name','category_type', 'category_type','use_default_eula','eula_text', 'require_acceptance','checkin_email', 'assets_count', 'accessories_count', 'consumables_count', 'components_count','licenses_count', 'image'];

        $categories = Category247::select(['id', 'created_at', 'updated_at', 'name', 'category_code', 'erp_category_id', 'category_type','use_default_eula','eula_text', 'require_acceptance','checkin_email','image'])
            ->with('erpcategory')
            ->withCount('assets as assets_count', 'accessories as accessories_count', 'consumables as consumables_count', 'components as components_count','licenses as licenses_count');

        if ($request->filled('search')) {
            $categories = $categories->TextSearch($request->input('search'));
        }

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 50);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'assets_count';
        $categories->orderBy($sort, $order);

        $total = $categories->count();
        $categories = $categories->skip($offset)->take($limit)->get();
        return (new Categories247Transformer)->transformCategories($categories, $total);

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
        $this->authorize('create', Category247::class);
        $Category247 = new Category247;
        $Category247->fill($request->all());

        if ($Category247->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $Category247, trans('admin/categories/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $Category247->getErrors()));

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
        $this->authorize('view', Category247::class);
        $Category247 = Category247::findOrFail($id);
        return (new CategoriesTransformer)->transformCategory($Category247);

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
        $this->authorize('update', Category247::class);
        $Category247 = Category247::findOrFail($id);
        $Category247->fill($request->all());

        if ($Category247->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $Category247, trans('admin/categories/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $Category247->getErrors()));
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
        $this->authorize('delete', Category247::class);
        $Category247 = Category247::withCount('models as models_count', 'accessories as accessories_count','consumables as consumables_count','components as components_count')->findOrFail($id);

        if ($Category247->models_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/categories/message.assoc_items', ['asset_type'=>'model'])));
        } elseif ($Category247->accessories_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/categories/message.assoc_items', ['asset_type'=>'accessory'])));
        } elseif ($Category247->consumables_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/categories/message.assoc_items', ['asset_type'=>'consumable'])));
        } elseif ($Category247->components_count > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null,  trans('admin/categories/message.assoc_items', ['asset_type'=>'component'])));
        }
        $Category247->delete();
        return response()->json(Helper::formatStandardApiResponse('success', null,  trans('admin/categories/message.delete.success')));

    }


    /**
     * Gets a paginated collection for the select2 menus
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0.16]
     * @see \App\Http\Transformers\SelectlistTransformer
     *
     */
    public function selectlist(Request $request, $category_type = 'asset')
    {

        $categories = Category247::select([
            'id',
            'name',
            'image',
        ]);

        if ($request->filled('search')) {
            $categories = $categories->where('name', 'LIKE', '%'.$request->get('search').'%');
        }

        $categories = $categories->where('category_type', $category_type)->orderBy('name', 'ASC')->paginate(50);

        // Loop through and set some custom properties for the transformer to use.
        // This lets us have more flexibility in special cases like assets, where
        // they may not have a ->name value but we want to display something anyway
        foreach ($categories as $Category247) {
            $Category247->use_image = ($Category247->image) ? Storage::disk('public')->url('categories/'.$Category247->image, $Category247->image) : null;
        }

        return (new SelectlistTransformer)->transformSelectlist($categories);

    }

}
