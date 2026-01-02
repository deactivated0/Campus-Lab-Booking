<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Usage Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Campus Lab Booking — Usage Report</h1>
    <div class="meta">Generated at: {{ $generatedAt }}</div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Lab</th>
            <th>Equipment</th>
            <th>Checked In</th>
            <th>Checked Out</th>
            <th>Kiosk</th>
        </tr>
        </thead>
        <tbody>
        @foreach($logs as $i => $log)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ optional($log->user)->name }}</td>
                <td>{{ optional($log->lab)->name }}</td>
                <td>{{ optional($log->equipment)->name }}</td>
                <td>{{ optional($log->checked_in_at)->toDateTimeString() }}</td>
                <td>{{ optional($log->checked_out_at)->toDateTimeString() }}</td>
                <td>{{ $log->kiosk_label }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
