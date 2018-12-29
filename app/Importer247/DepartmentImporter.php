<?php

namespace App\Importer247;

use App\Helpers\Helper;
use App\Models\Statuslabel;

class DepartmentImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        $item_location = $this->findCsvMatch($row, "location");
        $item_department = $this->findCsvMatch($row, "department");
        $this->createDepartment($item_department, $item_location);
    }
}