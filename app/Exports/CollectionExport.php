<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class CollectionExport implements FromCollection
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return collect($this->rows);
    }
}
