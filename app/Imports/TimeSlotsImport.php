<?php

namespace App\Imports;

use App\Models\TimeSlot;
use Maatwebsite\Excel\Concerns\ToModel;

class TimeSlotsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new TimeSlot([
            'day'     => $row[0],
            'start'    => $row[1],
            'end' => $row[2],
        ]);
    }
}
