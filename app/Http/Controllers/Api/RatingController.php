<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RatingController extends Controller
{

    // 📄 List user ratings
    public function index()
    {
        return Rating::where('tenant_id', auth()->id())
            ->with('apartment')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $booking = Booking::with('rating')->findOrFail($data['booking_id']);

        // 🔐 Booking ownership
        if ($booking->tenant_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ⏳ Date check
        if (Carbon::today()->lt(Carbon::parse($booking->start_date))) {
            return response()->json([
                'message' => 'You can rate only after your stay starts'
            ], 403);
        }

        if ($booking->status !== 'approved') {
            return response()->json([
                'message' => 'You cannot rate unless your booking is approved'
            ], 403);
        }

        // 🚫 Already rated
        if ($booking->rating) {
            return response()->json([
                'message' => 'You already rated this booking'
            ], 409);
        }

        $rating = Rating::create([
            'tenant_id' => auth()->id(),
            'apartment_id' => $booking->apartment_id,
            'booking_id' => $booking->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json($rating, 201);
    }

    // ✏️ Update rating
    public function update(Request $request, Rating $rating)
    {
        if ($rating->tenant_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $rating->update($data);

        return $rating;
    }

    // ❌ Delete rating
    public function destroy(Rating $rating)
    {
        if ($rating->tenant_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rating->delete();

        return response()->json(['message' => 'Rating deleted']);
    }

}
