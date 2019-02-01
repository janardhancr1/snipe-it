<?php

namespace App\Presenters;

use App\Helpers\Helper;

/**
 * Class CategoryPresenter
 * @package App\Presenters
 */
class Category247Presenter extends Presenter
{

    /**
     * Json Column Layout for bootstrap table
     * @return string
     */
    public static function dataTableLayout()
    {
        $layout = [
            [
                "field" => "id",
                "searchable" => false,
                "sortable" => true,
                "switchable" => true,
                "title" => trans('general.id'),
                "visible" => false
            ],[
                "field" => "name",
                "searchable" => true,
                "sortable" => true,
                "title" => trans('general.name'),
                "visible" => true,
                "formatter" => 'categoriesLinkFormatter',
            ],[
                "field" => "category_code",
                "searchable" => true,
                "sortable" => true,
                "title" => trans('general247.code'),
                "visible" => true,
            ],[
                "field" => "erpcategory.name",
                "searchable" => true,
                "sortable" => true,
                "title" => trans('general247.erpcategory'),
                "visible" => true,
            ],[
                "field" => "image",
                "searchable" => false,
                "sortable" => true,
                "title" => trans('general.image'),
                "visible" => true,
                "formatter" => 'imageFormatter',
            ],[
                "field" => "category_type",
                "searchable" => true,
                "sortable" => true,
                "title" => trans('general.type'),
                "visible" => true
            ], [
                "field" => "assets_count",
                "searchable" => false,
                "sortable" => true,
                "title" => trans('general.assets'),
                "visible" => true
            ], [
                "field" => "accessories_count",
                "searchable" => false,
                "sortable" => true,
                "title" => trans('general.accessories'),
                "visible" => true
            ], [
                "field" => "consumables_count",
                "searchable" => false,
                "sortable" => true,
                "title" => trans('general.consumables'),
                "visible" => true
            ], [
                "field" => "licenses_count",
                "searchable" => false,
                "sortable" => true,
                "title" => trans('general.licenses'),
                "visible" => true
            ], [
                "field" => "eula",
                "searchable" => false,
                "sortable" => false,
                "title" => trans('admin/categories/table.eula_text'),
                "visible" => false,
                "formatter" => 'trueFalseFormatter',
            ],  [
                "field" => "require_acceptance",
                "searchable" => false,
                "sortable" => true,
                "title" => trans('admin/categories/table.require_acceptance'),
                "visible" => true,
                "formatter" => 'trueFalseFormatter',
            ],
           [
                "field" => "actions",
                "searchable" => false,
                "sortable" => false,
                "switchable" => false,
                "title" => trans('table.actions'),
                "visible" => true,
                "formatter" => "categories247ActionsFormatter",
            ]
        ];

        return json_encode($layout);
    }


    /**
     * Link to this categories name
     * @return string
     */
    public function nameUrl()
    {
        return (string) link_to_route('categories.show', $this->name, $this->id);
    }

    /**
     * Url to view this item.
     * @return string
     */
    public function viewUrl()
    {
        return route('categories.show', $this->id);
    }
}
