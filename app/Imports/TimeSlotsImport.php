<?php

namespace App\Imports;

use App\Models\TimeSlot;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;

class TimeSlotsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        $start = trim(strtoupper($row[1])); // normalize
        $start = str_replace(' ', '', $start); // remove spaces before AM/PM

        $startTime = Carbon::createFromTime(0, 0)
            ->addDays((float)$start)
            ->format('H:i:s');

        $end = trim(strtoupper($row[2])); // normalize
        $end = str_replace(' ', '', $end); // remove spaces before AM/PM
        $endtTime = Carbon::createFromTime(0, 0)
            ->addDays((float)$end)
            ->format('H:i:s');

        return new TimeSlot([
            'day'     => $row[0],
            'start'    => $startTime,
            'end' => $endtTime,
        ]);
    }
}
