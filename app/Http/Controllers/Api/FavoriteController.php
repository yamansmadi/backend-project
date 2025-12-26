<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Apartment;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // 1. عرض قائمة المفضلات للمستخدم المسجل حالياً
    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['apartment.images']) // جلب بيانات الشقة مع صورها
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    // 2. إضافة أو حذف من المفضلة (Toggle)
    public function toggle(Request $request, $apartmentId)
    {
        $user = $request->user();

        // التأكد أن الشقة موجودة أصلاً
        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json(['message' => 'The apartment not found '], 404);
        }

        // البحث إذا كانت موجودة مسبقاً في المفضلة
        $favorite = Favorite::where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->first();

        if ($favorite) {
            $favorite->delete(); // إذا موجودة نحذفها
            return response()->json(['message' => 'Removed from favorites', 'is_favorite' => false]);
        }

        // إذا غير موجودة نضيفها
        Favorite::create([
            'user_id' => $user->id,
            'apartment_id' => $apartmentId
        ]);

        return response()->json(['message' => 'Added to favorites', 'is_favorite' => true]);
    }
}
