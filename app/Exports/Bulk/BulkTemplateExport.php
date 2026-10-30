<?php

namespace App\Exports\Bulk;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Template workbook: the sheet to fill in, then an Instructions sheet. */
class BulkTemplateExport implements WithMultipleSheets
{
    public function __construct(private TemplateSheet $template, private InstructionSheet $instructions) {}

    public function sheets(): array
    {
        return [$this->template, $this->instructions];
    }
}
