<?php

namespace App\Presenters;

use App\Helpers\Helper;

/**
 * Class LocationPresenter
 * @package App\Presenters
 */
class LocationUsersPresenter extends Presenter
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
            ],
            [
                "field" => "name",
                "searchable" => true,
                "sortable" => true,
                "title" => trans('admin/locations/table.name'),
                "visible" => true,
                "formatter" => "locationusersLinkFormatter"
            ],
            [
                "field" => "users_count",
                "searchable" => false,
                "sortable" => true,
                "switchable" => true,
                "title" =>  trans('general247.manager'),
                "visible" => true,
            ],

            [
                "field" => "created_at",
                "searchable" => true,
                "sortable" => true,
                "switchable" => true,
                "title" => trans('general.created_at'),
                "visible" => false,
                'formatter' => 'dateDisplayFormatter'
            ],

            [
                "field" => "actions",
                "searchable" => false,
                "sortable" => false,
                "switchable" => false,
                "title" => trans('table.actions'),
                "visible" => true,
                "formatter" => "locationsActionsFormatter",
            ]
        ];

        return json_encode($layout);
    }



    /**
     * Link to this locations name
     * @return string
     */
    public function nameUrl()
    {
        return (string)link_to_route('locationusers.show', $this->name, $this->id);
    }

    /**
     * Getter for Polymorphism.
     * @return mixed
     */
    public function name()
    {
        return $this->model->name;
    }

    /**
     * Url to view this item.
     * @return string
     */
    public function viewUrl()
    {
        return route('locationusers.show', $this->id);
    }

    public function glyph()
    {
        return '<i class="fa fa-map-marker"></i>';
    }
    
    public function fullName() {
        return $this->name;
    }
}
