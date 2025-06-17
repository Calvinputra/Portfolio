<?php

namespace App\Imports;

use App\Models\Colour;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ColourImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Colour([
            'name'  => $row['name'],
            'code' => $row['code'],
        ]);
    }
}
