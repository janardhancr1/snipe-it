<?php

namespace App\Importer247;

use App\Helpers\Helper;
use App\Models\Statuslabel;

class CategoriesImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        $item_category = $this->findCsvMatch($row, "category");
        $item_category_code = $this->findCsvMatch($row, "categorycode");
        $item_erp_category_code = $this->findCsvMatch($row, "erpcategory");
        $this->createOrFetchCategory($item_category, $item_category_code, $item_erp_category_code);
    }
}