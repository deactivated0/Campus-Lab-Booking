<?php

namespace App\Exports;

use App\Models\UsageLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsageLogsExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return [
            'Student',
            'Lab (code)',
            'Lab',
            'Equipment (code)',
            'Equipment',
            'Checked In At',
            'Checked Out At',
            'Kiosk',
        ];
    }

    /**
     * Return column headings for the export.
     *
     * @return array
     */

    public function collection()
    {
        return UsageLog::with(['user','lab','equipment'])
            ->orderByDesc('checked_in_at')
            ->take(2000)
            ->get()
            ->map(function ($l) {
                return [
                    $l->user?->name,
                    $l->lab?->code,
                    $l->lab?->name,
                    $l->equipment?->serial_number,
                    $l->equipment?->name,
                    optional($l->checked_in_at)->toDateTimeString(),
                    optional($l->checked_out_at)->toDateTimeString(),
                    $l->kiosk_label,
                ];
            });
    }

    /**
     * Return a collection of rows for export.
     *
     * The exporter returns recent usage logs with related user/lab/equipment data.
     * If `maatwebsite/excel` is installed, this class will be consumed by its exporter.
     *
     * @return \Illuminate\Support\Collection
     */
}
