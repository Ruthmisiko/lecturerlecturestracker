<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Reads the first sheet as rows keyed by their (lower-cased, underscored) headings. */
class RowsImport implements ToArray, WithHeadingRow
{
    public function array(array $array)
    {
        // Rows are read by Excel::toArray(); nothing to do here.
    }
}
