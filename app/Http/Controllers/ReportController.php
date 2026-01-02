<?php

namespace App\Http\Controllers;

use App\Exports\UsageLogsExport;
use App\Models\UsageLog;
use App\Models\Lab;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);

        return Inertia::render('Reports/Index', [
            'exportCsvUrl' => route('reports.export.csv'),
            'exportPdfUrl' => route('reports.export.pdf'),
        ]);
    }

    public function data(Request $request)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);

        $from = $request->query('from');
        $to = $request->query('to');

        $q = UsageLog::with(['lab','equipment','user'])->orderBy('checked_in_at','desc');

        if ($from && $to) {
            $q->whereBetween('checked_in_at', [$from, $to]);
        }

        // Aggregations performed by the database for large datasets (faster than in-memory grouping)
        $labCounts = (clone $q)
            ->reorder() // clear ordering inherited from $q to avoid ONLY_FULL_GROUP_BY conflicts
            ->select('lab_id', DB::raw('count(*) as cnt'))
            ->groupBy('lab_id')
            ->pluck('cnt', 'lab_id');

        $equipmentCounts = (clone $q)
            ->reorder() // clear ordering inherited from $q to avoid ONLY_FULL_GROUP_BY conflicts
            ->select('equipment_id', DB::raw('count(*) as cnt'))
            ->groupBy('equipment_id')
            ->pluck('cnt', 'equipment_id');

        $labNames = Lab::whereIn('id', $labCounts->keys()->toArray())->pluck('name', 'id');
        $equipmentNames = Equipment::whereIn('id', $equipmentCounts->keys()->toArray())->pluck('name', 'id');

        $byLab = $labCounts->mapWithKeys(fn($cnt, $labId) => [($labNames[$labId] ?? 'Unknown') => $cnt]);
        $byEquipment = $equipmentCounts->mapWithKeys(fn($cnt, $eqId) => [($equipmentNames[$eqId] ?? 'Unknown') => $cnt]);

        $logs = $q->take(200)->get();

        return response()->json([
            'logs' => $logs->map(function ($l) {
                return [
                    'id' => $l->id,
                    'student' => $l->user?->name,
                    'lab' => ($l->lab?->code ? $l->lab->code . ' — ' : '') . ($l->lab?->name ?? 'Unknown'),
                    'lab_code' => $l->lab?->code,
                    'equipment' => ($l->equipment?->serial_number ? $l->equipment->serial_number . ' — ' : '') . ($l->equipment?->name ?? 'Unknown'),
                    'equipment_code' => $l->equipment?->serial_number,
                    'in' => optional($l->checked_in_at)->format('M d, g:i A'),
                    'out' => $l->checked_out_at ? $l->checked_out_at->format('M d, g:i A') : null,
                ];
            }),
            'byLab' => $byLab,
            'byEquipment' => $byEquipment,
        ]);
    }

    public function exportCsv(Request $request)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);

        // Prefer using the maatwebsite Excel exporter when it's installed and functional.
        // Guard with class/interface checks to avoid fatal errors when the package is absent.
        if (class_exists(\Maatwebsite\Excel\Excel::class) && interface_exists(\Maatwebsite\Excel\Concerns\FromCollection::class)) {
            try {
                return \Maatwebsite\Excel\Facades\Excel::download(new UsageLogsExport(), 'usage_logs.csv');
            } catch (\Throwable $e) {
                logger()->warning('Excel export failed; falling back to manual CSV: ' . $e->getMessage());
            }
        }

        // Fallback: generate CSV manually to avoid depending on maatwebsite package.
        $logs = UsageLog::with(['user','lab','equipment'])
            ->orderByDesc('checked_in_at')
            ->take(2000)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="usage_logs.csv"',
        ];

        $callback = function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student', 'Lab', 'Equipment', 'Checked In At', 'Checked Out At', 'Kiosk']);
            foreach ($logs as $l) {
                fputcsv($out, [
                    $l->user?->name,
                    $l->lab?->name,
                    $l->equipment?->name,
                    optional($l->checked_in_at)->toDateTimeString(),
                    optional($l->checked_out_at)->toDateTimeString(),
                    $l->kiosk_label,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);

        $logs = UsageLog::with(['lab','equipment','user'])->latest()->take(200)->get();

        // Use DomPDF (barryvdh) if it's available; otherwise fall back to an HTML download.
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            try {
                return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.usage_report', [
                    'generatedAt' => now(),
                    'logs' => $logs,
                ])->download('usage_report.pdf');
            } catch (\Throwable $e) {
                logger()->warning('PDF export failed; falling back to HTML download: ' . $e->getMessage());
            }
        }

        // Fallback: render the view HTML and offer it as an .html download
        $html = view('pdf.usage_report', ['generatedAt' => now(), 'logs' => $logs])->render();
        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, 'usage_report.html', ['Content-Type' => 'text/html']);
    }

    public function destroy(Request $request, UsageLog $log)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);

        try {
            $log->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            logger()->error('Failed to delete usage log: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete log'], 500);
        }
    }
}
