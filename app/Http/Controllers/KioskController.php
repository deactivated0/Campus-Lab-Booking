<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\QRToken;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KioskController extends Controller
{
    public function page(Request $request)
    {
        // Kiosk can be protected by basic auth in real deployment; for demo we keep it open.
        return Inertia::render('Kiosk', [
            'scanPostUrl' => route('kiosk.scan'),
        ]);
    }

    /**
     * Render the kiosk page.
     *
     * This returns an Inertia page that the tablet/phone scanner UI will use.
     * The `scanPostUrl` prop contains the API endpoint that the client will
     * POST scanned QR tokens to.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */

    public function scan(Request $request)
    {
        $data = $request->validate([
            'token_or_url' => ['required','string','max:500'],
            'kiosk_label' => ['nullable','string','max:80'],
        ]);

        $raw = trim($data['token_or_url']);
        $token = $this->extractToken($raw);

        logger()->info('Kiosk scan attempt', ['raw' => $data['token_or_url'], 'token' => $token, 'kiosk_label' => $data['kiosk_label'] ?? null]);

        $qr = QRToken::whereRaw('LOWER(`token`) = ?', [strtolower($token)])->with('booking')->first();
        if (!$qr) {
            logger()->info('Kiosk scan failed: invalid token', ['token' => $token]);
            return response()->json(['ok' => false, 'message' => 'Invalid token.'], 422);
        }

        if (!$qr->is_valid) {
            logger()->info('Kiosk scan failed: token not valid', ['token' => $token, 'used_at' => $qr->used_at, 'expires_at' => $qr->expires_at]);
            return response()->json(['ok' => false, 'message' => 'Token expired or already used.'], 422);
        }

        $booking = $qr->booking;
        if (!$booking || !in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_CHECKED_OUT], true)) {
            return response()->json(['ok' => false, 'message' => 'Booking not eligible for check-in/out.'], 422);
        }

        $booking->load(['equipment','lab','user']);

        // Open log?
        $openLog = UsageLog::where('booking_id', $booking->id)
            ->whereNull('checked_out_at')
            ->latest()
            ->first();

        if (!$openLog) {
            // check-in / check-out equipment (start usage)
            $log = UsageLog::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'lab_id' => $booking->lab_id,
                'equipment_id' => $booking->equipment_id,
                'checked_in_at' => now(),
                'kiosk_label' => $data['kiosk_label'] ?? 'Tablet Kiosk',
                'meta' => [
                    'source' => 'qr',
                ],
            ]);

            $booking->update(['status' => Booking::STATUS_CHECKED_OUT]);
            $qr->update(['used_at' => now()]);

            return response()->json([
                'ok' => true,
                'action' => 'check_in',
                'message' => 'Checked out successfully.',
                'summary' => $this->summary($booking),
            ]);
        }

        // check-out / return equipment (end usage)
        $openLog->update(['checked_out_at' => now()]);
        $booking->update(['status' => Booking::STATUS_RETURNED]);
        $qr->update(['used_at' => now()]);

        return response()->json([
            'ok' => true,
            'action' => 'check_out',
            'message' => 'Returned successfully.',
            'summary' => $this->summary($booking),
        ]);
    }

    /**
     * Process a scanned token or URL from a kiosk.
     *
     * Validates input, finds the matching `QRToken`, checks validity and
     * then either creates a `UsageLog` (check-in) or closes an open `UsageLog`
     * (check-out) depending on whether the booking is already checked out.
     * Responses are JSON for kiosk clients.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function scanUrl(string $token)
    {
        // Useful if QR encodes a URL; scanner might send full URL.
        return response('OK: token=' . $token, 200);
    }

    /**
     * Endpoint used when QR encodes a full URL.
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */

    private function extractToken(string $raw): string
    {
        // If raw is a URL with ?token=..., parse it.
        if (str_contains($raw, 'token=')) {
            $parts = parse_url($raw);
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $q);
                if (!empty($q['token'])) return (string) $q['token'];
            }
        }

        // As a fallback, try to extract a UUID token from arbitrary input (useful when scanners
        // include extra text or the user pastes a URL or prefix/suffix text).
        if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}/', $raw, $m)) {
            return $m[0];
        }

        return $raw;
    }

    /**
     * Extract a token string from arbitrary input that may be a URL or raw token.
     *
     * Tries to parse `?token=` from a URL, then falls back to UUID regex, then
     * returns the original raw string.
     *
     * @param  string  $raw
     * @return string
     */

    private function summary(Booking $b): array
    {
        return [
            'student' => $b->user?->name,
            'equipment' => $b->equipment?->name ?? '—',
            'lab' => $b->lab?->name ?? '—',
            'window' => $b->starts_at->format('M d, g:i A') . ' → ' . $b->ends_at->format('M d, g:i A'),
        ];
    }

    /**
     * Return a compact booking summary used in kiosk responses.
     *
     * @param  \App\Models\Booking  $b
     * @return array
     */
}
