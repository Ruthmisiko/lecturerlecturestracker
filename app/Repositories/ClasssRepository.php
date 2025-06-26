<?php

namespace App\Repositories;

use App\Models\Classs;
use App\Repositories\BaseRepository;

class ClasssRepository extends BaseRepository
{
    protected $fieldSearchable = [
        
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Classs::class;
    }
}
