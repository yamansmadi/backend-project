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
        $user = $request->user();
        $type = $request->query('type'); // past, current, pending, rejected , cancelled

        $query = Booking::with('apartment')->where('tenant_id', $user->id);

        switch ($type) {
            case 'current':
                // الحجوزات المقبولة والتي تاريخ انتهائها اليوم أو في المستقبل
                $query->where('status', 'approved')
                    ->where('end_date', '>=', now()->toDateString());
                break;
            case 'past':
                // الحجوزات المقبولة والتي انتهت تاريخياً
                $query->where('status', 'approved')
                    ->where('end_date', '<', now()->toDateString());
                break;
            case 'pending':
                $query->where('status', 'pending');
                break;
            case 'rejected':
                // الطلبات التي رفضها المالك أو ألغاها المستأجر
                // ملاحظة: دمجنا rejected و cancelled هنا لتسهيل العرض للمستخدم
                $query->whereIn('status', ['rejected', 'cancelled']);
                break;
            default:
                // إذا لم يتم تحديد نوع، نعرض كل شيء مرتباً من الأحدث للأقدم
                break;
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $bookings->count(),
            'data' => $bookings
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $data = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $exists = Booking::where('apartment_id', $data['apartment_id'])
            ->where('status', 'approved')
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date']);
                    });
            })->exists();

        if ($exists) {
            return response()->json(['message' => 'The apartment is already reserved for these dates :)'], 422);
        }

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
        if (auth('sanctum')->id() !== $booking->tenant_id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }
        // 2. السماح بالتعديل فقط إذا كان الحجز pending أو approved
        // ومنعه إذا كان rejected
        if ($booking->status === 'rejected') {
            return response()->json(['message' => 'Cannot update a rejected booking.'], 403);
        }
        $booking->load('apartment'); // ضروري جداً لحساب السعر الجديد

        $this->authorizeBooking($booking);

        $data = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        // منطق التحقق من التعارض (مع استثناء الحجز الحالي نفسه)
        $exists = Booking::where('apartment_id', $booking->apartment_id)
            ->where('status', 'approved')
            ->where('id', '!=', $booking->id) // ضروري جداً لكي لا يرى نفسه كتعارض
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date']);
                    });
            })->exists();

        if ($exists) {
            return response()->json(['message' => 'The apartment is already reserved for these dates.'], 422);
        }

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $totalNights = $start->diffInDays($end);
        $totalPrice = $totalNights * $booking->apartment->price_per_night;

        $booking->update([
            'start_date' => $start,
            'end_date' => $end,
            'total_nights' => $totalNights,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Booking updated. Waiting for owner approval on the new dates.', 'data' => $booking]);
    }
    // DELETE
    public function destroy(Request $request, $id)
    {
        // التأكد من أن المستخدم هو أدمن
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Only Admin can delete booking records.'], 403);
        }

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking record deleted permanently by Admin.']);
    }

    private function authorizeBooking(Booking $booking)
    {
        if ($booking->tenant_id !== auth('sanctum')->id()) {
            abort(403, 'Unauthorized');
        }
    }

    public function approve(Booking $booking)
    {
        $booking->load('apartment');
        if (!$booking->apartment) {
            return response()->json(['message' => 'Apartment data not found for this booking.'], 404);
        }
        // 1. التأكد أن المستخدم الحالي هو صاحب الشقة
        if (auth('sanctum')->id() !== (int) $booking->apartment->owner_id) {
            return response()->json(['message' => 'Unauthorized action , You are not the owner of this apartment.'], 403);
        }

        // 2. التحقق مرة أداة قبل الموافقة من عدم وجود حجز آخر "مقبول" في نفس الفترة
        $conflict = Booking::where('apartment_id', $booking->apartment_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($booking) {
                $q->whereBetween('start_date', [$booking->start_date, $booking->end_date])
                    ->orWhereBetween('end_date', [$booking->start_date, $booking->end_date]);
            })->exists();

        if ($conflict) {
            return response()->json(['message' => 'Not possible, there is another confirmed booking during this period.'], 422);
        }

        // 3. الموافقة على الحجز (طلب أساسي)
        $booking->update(['status' => 'approved']);

        // 4. (اختياري احترافي) رفض جميع الحجوزات الأخرى "المعلقة" التي تتداخل مع هذا الحجز
        Booking::where('apartment_id', $booking->apartment_id)
            ->where('status', 'pending')
            ->where('id', '!=', $booking->id)
            ->where(function ($q) use ($booking) {
                $q->whereBetween('start_date', [$booking->start_date, $booking->end_date])
                    ->orWhereBetween('end_date', [$booking->start_date, $booking->end_date]);
            })->update(['status' => 'rejected']);

        return response()->json(['message' => 'The booking was successfully approved']);
    }

    public function ownerBookings(Request $request)
    {
        // هنا نستخدم whereHas للتأكد أن صاحب الشقة المرتبطة بالحجز هو المستخدم الحالي
        $bookings = Booking::whereHas('apartment', function ($query) {
            $query->where('owner_id', auth('sanctum')->id());
        })
            ->with(['apartment', 'tenant'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function cancel(Booking $booking) //هاد للالغاء ممن قبل المستأجر
    {
        // التأكد أن الذي يحاول الإلغاء هو المستأجر صاحب الحجز
        if (auth('sanctum')->id() !== $booking->tenant_id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }
        // منع الإلغاء إذا بدأ الحجز أو انتهى
        if (now()->startOfDay()->gte($booking->start_date)) {
            return response()->json([
                'message' => 'Cannot cancel a booking that has already started or passed.'
            ], 422);
        }

        // لا يمكن إلغاء حجز منتهي أو ملغي سابقاً
        if (in_array($booking->status, ['rejected', 'cancelled'])) {
            return response()->json(['message' => 'Booking is already cancelled or rejected.'], 422);
        }

        $booking->update(['status' => 'cancelled']); // أو يمكنك إضافة حالة 'cancelled' لقاعدة البيانات

        return response()->json(['message' => 'Your booking has been cancelled successfully.']);
    }

    public function reject(Booking $booking) //هاد للالغاء من قبل المالك
    {
        $booking->load('apartment');

        // التأكد أن المستخدم هو صاحب الشقة
        if (auth('sanctum')->id() !== $booking->apartment->owner_id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // المالك يرفض الطلبات المعلقة فقط
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'You can only reject pending requests.'], 422);
        }

        $booking->update(['status' => 'rejected']);

        return response()->json(['message' => 'Booking request has been rejected.']);
    }
}
