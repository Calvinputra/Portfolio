<?php

namespace App\Imports;

use App\Models\Classification;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClassificationImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Classification([
            'categories_id'  => $row['categories_id'],
            'name' => $row['name'],
            'code' => $row['code'],
        ]);
    }
}
