<?php

namespace App\Imports;

use App\Models\Shape;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ShapeImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Shape([
            'name'  => $row['name'],
            'code' => $row['code'],
        ]);
    }
}
