<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ImportsTransformer;
use App\Models\Import;
use App\Models\Asset;
use App\Models\CustomFieldset;
use App\Models\CustomField;


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

    public function getcustomfile(){
        //print_r($_REQUEST);
        if(isset($_REQUEST["filedsets"]) && count($_REQUEST["filedsets"]) > 0)
        {
            $fields = array();
            foreach($_REQUEST["filedsets"] as $setId){
                $customFields = CustomFieldset::with('fields')
                        ->where('id', '=', $setId)->first();
                if ($customFields) {
                    foreach ($customFields->fields as $customField) {
                        $field =  CustomField::find($customField->id);
                        if($field){
                            if(!in_array($field->name, $fields)){
                                array_push($fields, $field->name);
                            }
                        }
                    }
                }
            }
            //print_r($fields);
            $fieldsString = "";
            foreach($fields as $field){
                $fieldsString .= $field . ",";
            }
            rtrim($fieldsString, ",");
            $path = resource_path('/templates/customtemplate.csv');
            \file_put_contents(resource_path('/templates/template.csv'), file_get_contents($path) . "," . $fieldsString);
            return response()->download(resource_path('/templates/template.csv'));
        }
        $fieldsets = CustomFieldset::with("fields", "models")->get();
        return view('importer247/template')->with("custom_fieldsets", $fieldsets);
    }
}
