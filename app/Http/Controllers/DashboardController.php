<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $activeNow = Booking::with(['equipment'])
            ->where('user_id', $user->id)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_OUT])
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        $upNext = Booking::with(['equipment'])
            ->where('user_id', $user->id)
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();

        $recent = UsageLog::with(['equipment', 'lab'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'status' => $log->checked_out_at ? 'Returned' : 'Checked Out',
                    'item' => $log->equipment?->name ?? '—',
                    'lab' => $log->lab?->name ?? '—',
                    'due' => optional($log->booking?->ends_at)->format('M d, g:i A') ?? '—',
                    'when' => $log->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Dashboard', [
            'cards' => [
                'activeNow' => $activeNow ? [
                    'title' => $activeNow->equipment?->name ?? $activeNow->title,
                    'due' => $activeNow->ends_at->format('M d, g:i A'),
                    'status' => $activeNow->status === Booking::STATUS_CHECKED_OUT ? 'Checked Out' : 'Confirmed',
                ] : null,
                'upNext' => $upNext ? [
                    'title' => $upNext->equipment?->name ?? $upNext->title,
                    'due' => $upNext->starts_at->format('M d, g:i A'),
                    'status' => $upNext->status === Booking::STATUS_PENDING ? 'Pending' : 'Confirmed',
                ] : null,
                'account' => [
                    'status' => 'Good Standing',
                ],
            ],
            'recentActivity' => $recent,
            'can' => [
                'approve' => $this->userHasAnyRole($request->user(), ['Admin','LabStaff']),
                'admin' => $this->userHasRole($request->user(), 'Admin'),
            ],
        ]);
    }

    /**
     * Dashboard page data provider.
     *
     * Collects active/up-next bookings and recent usage logs for the authenticated user
     * and returns them as Inertia props for the `Dashboard` page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
}
