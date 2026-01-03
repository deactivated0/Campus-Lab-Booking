<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $labs = Lab::where('is_active', true)->orderBy('name')->get(['id','code','name','location'])
            ->unique(function($l) { return ($l->code ?? '') . '|' . $l->name; })
            ->values();

        return Inertia::render('Bookings/Calendar', [
            'labs' => $labs,
            'equipment' => Equipment::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id','lab_id','name','category','serial_number']),
            'roles' => $this->userRoleNames($request->user()),
            'currentUserId' => $request->user()?->id,
        ]);
    }

    /**
     * Show the bookings calendar page data.
     *
     * Loads active labs and equipment and passes them to the Inertia `Bookings/Calendar` page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */

    public function events(Request $request)
    {
        $user = $request->user();

        $q = Booking::query()->with(['equipment','lab']);

        if (!$this->userHasAnyRole($user, ['Admin','LabStaff'])) {
            $q->where('user_id', $user->id);
        }

        $from = $request->query('start');
        $to = $request->query('end');

        if ($from && $to) {
            $q->whereBetween('starts_at', [$from, $to]);
        }

        return $q->orderBy('starts_at')->get()->map(function (Booking $b) {
            return [
                'id' => $b->id,
                'title' => $b->equipment?->name ?? $b->title,
                'start' => $b->starts_at->toIso8601String(),
                'end' => $b->ends_at->toIso8601String(),
                'status' => $b->status,
                'lab' => $b->lab?->name,
                'equipment' => $b->equipment?->name,
                'user_id' => $b->user_id,
                'user' => $b->user?->name,
            ];
        });
    }

    /**
     * Return booking events JSON for the calendar between the requested start and end.
     *
     * This endpoint is consumed by FullCalendar on the frontend.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Return equipment IDs that are unavailable for the provided time range (confirmed/checked-out)
     */
    public function availability(Request $request)
    {
        $v = $request->validate([
            'lab_id' => ['required', 'exists:labs,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $starts = $v['starts_at'];
        $ends = $v['ends_at'];
        $labId = $v['lab_id'];

        // Find equipment in the lab that have overlapping confirmed/checked_out bookings
        $unavailableEquipIds = Booking::whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_OUT])
            ->whereNotNull('equipment_id')
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->whereHas('equipment', function ($q) use ($labId) {
                $q->where('lab_id', $labId);
            })
            ->pluck('equipment_id')
            ->unique()
            ->values()
            ->all();

        return response()->json(['unavailable' => $unavailableEquipIds]);
    }

    /**
     * Return equipment IDs that are unavailable for the provided time range.
     *
     * Query inspects overlapping confirmed/checked_out bookings and returns equipment IDs.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */



    public function update(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$this->userHasAnyRole($user, ['Admin','LabStaff']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        $rules = [
            'status' => ['required', 'string', Rule::in([
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_CHECKED_OUT,
                Booking::STATUS_RETURNED,
                Booking::STATUS_CANCELLED,
            ])],
        ];

        $data = $request->validate($rules);

        $booking->update(['status' => $data['status']]);

        // If this is an Inertia navigation/request, return a redirect with a flash
        // message so the Inertia client receives a valid Inertia response instead
        // of a plain JSON payload (which would trigger the "plain JSON" error UI).
        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Booking status updated.');
        }

        // For non-Inertia AJAX/JSON consumers, return JSON
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'booking' => $booking]);
        }

        return redirect()->back()->with('success', 'Booking status updated.');
    }

    /**
     * Update a booking's status.
     *
     * Performs role checks and returns either an Inertia redirect or JSON depending on
     * the incoming request type.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return mixed
     */

    public function store(Request $request)
    {
        // Manual validation so we can return JSON for Inertia/Ajax requests consistently
        $rules = [
            'lab_id' => ['required', 'exists:labs,id'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            // For Inertia requests, throw a ValidationException so the Inertia middleware
            // converts it to a proper Inertia response (instead of returning plain JSON).
            if ($request->header('X-Inertia') || $request->wantsJson()) {
                throw \Illuminate\Validation\ValidationException::withMessages($errors->toArray());
            }
            throw \Illuminate\Validation\ValidationException::withMessages($errors->toArray());
        }

        $data = $validator->validated();

        // Ensure equipment belongs to the selected lab (if an equipment id was provided)
        if (! empty($data['equipment_id'])) {
            $equipment = Equipment::find($data['equipment_id']);
            if (! $equipment || (int) $equipment->lab_id !== (int) $data['lab_id']) {
                $msg = ['equipment_id' => 'Selected equipment does not belong to the chosen lab.'];
                // Throw a ValidationException so Inertia receives a proper Inertia error response
                throw \Illuminate\Validation\ValidationException::withMessages($msg);
            }

            // Prevent overlapping confirmed/checked-out bookings for the same equipment
            $overlap = Booking::where('equipment_id', $data['equipment_id'])
                ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_OUT])
                ->where('starts_at', '<', $data['ends_at'])
                ->where('ends_at', '>', $data['starts_at'])
                ->exists();

            if ($overlap) {
                $msg = ['equipment_id' => 'Selected equipment is already booked for the chosen time range.'];
                throw \Illuminate\Validation\ValidationException::withMessages($msg);
            }
        }

        try {
            logger()->info('Creating booking', ['user' => $request->user()?->id, 'data' => $data]);
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'lab_id' => $data['lab_id'],
                'equipment_id' => $data['equipment_id'] ?? null,
                'title' => $data['title'] ?? 'Booking',
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'notes' => $data['notes'] ?? null,
                'status' => Booking::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to create booking', ['exception' => $e, 'data' => $data]);
            if ($request->header('X-Inertia') || $request->wantsJson()) {
                // Let the framework handle exceptions; throw an HTTP 500 to be converted by the exception handler
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(500, 'Failed to create booking, check server logs.');
            }
            throw $e; // Let the exception bubble so the error is visible and logged
        }

        // For Inertia requests, return a redirect so Inertia receives a valid Inertia response
        if ($request->header('X-Inertia')) {
            return redirect()->route('bookings.show', ['booking' => $booking->id])->with('success', 'Booking request submitted.');
        }

        // For AJAX/JSON consumers, return JSON
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'booking_id' => $booking->id], 201);
        }

        return redirect()->back()->with('success', 'Booking request submitted.');
    }

        /**
         * Create a new booking request.
         *
         * Validates inputs, ensures equipment belongs to the lab, detects overlapping
         * bookings and creates the booking. Returns appropriate Inertia or JSON response.
         *
         * @param \Illuminate\Http\Request $request
         * @return mixed
         */

    public function show(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$this->userHasAnyRole($user, ['Admin','LabStaff']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        $booking->load(['lab','equipment','user']);

        return Inertia::render('Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'title' => $booking->equipment?->name ?? $booking->title,
                'status' => $booking->status,
                'starts_at' => $booking->starts_at->format('M d, Y g:i A'),
                'ends_at' => $booking->ends_at->format('M d, Y g:i A'),
                'lab' => $booking->lab?->name,
                'equipment' => $booking->equipment?->name,
                'canIssueQr' => in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_OUT], true),
            ],
        ]);
    }

    /**
     * Show a single booking details page (Inertia).
     *
     * Performs authorization checks: only Admin/LabStaff or the booking owner may view.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Inertia\Response
     */

    public function cancel(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$this->userHasAnyRole($user, ['Admin','LabStaff']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return redirect()->back()->with('success', 'Booking cancelled.');
    }

    /**
     * Cancel a booking.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */

    public function approve(Request $request, Booking $booking)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) {
            abort(403);
        }

        if ($booking->status !== Booking::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Only pending bookings can be approved.');
        }

        $booking->update([
            'status' => Booking::STATUS_CONFIRMED,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Booking approved.');
    }

    /**
     * Approve a pending booking (Admin/LabStaff only).
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */

    public function issueQr(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$this->userHasAnyRole($user, ['Admin','LabStaff']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        if (!in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_OUT], true)) {
            // For Inertia (X-Inertia) requests, prefer a redirect with a flash message so
            // the Inertia client receives a valid Inertia response instead of plain JSON.
            if ($request->header('X-Inertia')) {
                return redirect()->back()->with('error', 'QR can be issued only for confirmed bookings.');
            }

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'QR can be issued only for confirmed bookings.'], 422);
            }

            return redirect()->back()->with('error', 'QR can be issued only for confirmed bookings.');
        }

        $token = $booking->issueQrToken(15);

        $url = route('kiosk.scan-url', ['token' => $token->token]);

        // If the request is an Inertia navigation, return a redirect with flashed
        // QR so the Inertia response is valid. For XHR/fetch clients, return JSON.
        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('qr', [
                'token' => $token->token,
                'expires_at' => $token->expires_at->toIso8601String(),
                'url' => $url,
            ]);
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'token' => $token->token,
                'url' => $url,
                'expires_at' => $token->expires_at->toIso8601String(),
            ], 201);
        }

        return redirect()->back()->with('qr', [
            'token' => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
            'url' => $url,
        ]);
    }

    /**
     * Issue a time-limited QR token for a booking.
     *
     * Creates a `QRToken` record and returns token info (JSON or flash props for Inertia).
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return mixed
     */

    public function approvals(Request $request)
    {
        if (!$this->userHasAnyRole($request->user(), ['Admin','LabStaff'])) abort(403);

        $pending = Booking::with(['user','lab','equipment'])
            ->where('status', Booking::STATUS_PENDING)
            ->orderBy('starts_at')
            ->get()
            ->map(function (Booking $b) {
                return [
                    'id' => $b->id,
'user' => $b->user?->name,
                    'lab' => $b->lab?->name,
                    'equipment' => $b->equipment?->name ?? '—',
                    'starts_at' => $b->starts_at->format('M d, g:i A'),
                    'ends_at' => $b->ends_at->format('M d, g:i A'),
                    'title' => $b->title,
                ];
            });

        return Inertia::render('Admin/Approvals', [
            'pending' => $pending,
        ]);
    }

    /**
     * Show pending booking approvals (Admin/Staff view).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */

    /**
     * Return latest valid QR for a booking as JSON (used as a client-side fallback
     * when flash props are not available immediately after issuing a QR).
     */
    public function latestQr(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$this->userHasAnyRole($user, ['Admin','LabStaff']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        $token = $booking->qrTokens()->latest()->first();
        if (! $token || ! $token->is_valid) {
            return response()->json(['ok' => false, 'message' => 'No valid token found'], 404);
        }

        return response()->json([
            'ok' => true,
            'token' => $token->token,
            'url' => route('kiosk.scan-url', ['token' => $token->token]),
        ]);
    }

    /**
     * Destroy (delete) a booking.
     * Only the booking owner or Admin/LabStaff may delete.
     */
    public function destroy(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (! $this->userHasAnyRole($user, ['Admin','LabStaff']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        try {
            $booking->delete();
        } catch (\Throwable $e) {
            logger()->error('Failed to delete booking', ['exception' => $e, 'booking' => $booking->id]);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->with('error', 'Failed to delete booking.');
            }
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Failed to delete booking'], 500);
            }
            throw $e;
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('bookings.index')->with('success', 'Booking deleted.');
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('bookings.index')->with('success', 'Booking deleted.');
    }

    /**
     * Return the latest valid QR for the given booking as JSON.
     *
     * This is used by client fallbacks to fetch the QR token after issuing.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */

}
