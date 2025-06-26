<?php

namespace App\Repositories;

use App\Models\LectureAdministered;
use App\Repositories\BaseRepository;

class LectureAdministeredRepository extends BaseRepository
{
    protected $fieldSearchable = [
        
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return LectureAdministered::class;
    }
}
