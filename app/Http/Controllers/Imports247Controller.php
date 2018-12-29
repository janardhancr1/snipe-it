<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ImportsTransformer;
use App\Models\Import;
use App\Models\Asset;


class Imports247Controller extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('create', Asset::class);
        $imports = (new ImportsTransformer)->transformImports(Import::latest()->get());
        return view('importer247/import')->with('imports', $imports);
    }

    public function getfile(){
        $path = storage_path('/private_uploads/imports/importerror.csv');
        return response()->download($path);
    }

    public function getlocationfile(){
        $path = resource_path('/templates/location.csv');
        return response()->download($path);
    }

    public function getdepartmentfile(){
        $path = resource_path('/templates/department.csv');
        return response()->download($path);
    }

    public function getmanufacturersfile(){
        $path = resource_path('/templates/manufacturer.csv');
        return response()->download($path);
    }

    public function getcategoryfile(){
        $path = resource_path('/templates/category.csv');
        return response()->download($path);
    }

    public function getmodelfile(){
        $path = resource_path('/templates/assetmodel.csv');
        return response()->download($path);
    }

    public function getassetfile(){
        $path = resource_path('/templates/assetdata.csv');
        return response()->download($path);
    }
}
