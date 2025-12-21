<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Apartment;

class BookingController extends Controller
{
    // LIST USER BOOKINGS
    public function index(Request $request)
    {
        return Booking::where('tenant_id', $request->user()->id)->get();
    }

    // CREATE
    public function store(Request $request)
    {
        $data = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $apartment = Apartment::findOrFail($data['apartment_id']);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $totalNights = $start->diffInDays($end);

        $totalPrice = $totalNights * $apartment->price_per_night;

        return Booking::create([
            'tenant_id' => $request->user()->id,
            'apartment_id' => $apartment->id,
            'start_date' => $start,
            'end_date' => $end,
            'total_nights' => $totalNights,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }


    // SHOW
    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        return $booking;
    }

    // UPDATE
    public function update(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update after approval'
            ], 403);
        }

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $totalNights = $start->diffInDays($end);
        $totalPrice = $totalNights * $booking->apartment->price_per_night;

        $booking->update([
            'start_date' => $start,
            'end_date' => $end,
            'total_nights' => $totalNights,
            'total_price' => $totalPrice,
        ]);

        return $booking;
    }


    // DELETE
    public function destroy(Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete after approval'
            ], 403);
        }

        $booking->delete();

        return response()->noContent();
    }

    private function authorizeBooking(Booking $booking)
    {
        if ($booking->tenant_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
    }
}
