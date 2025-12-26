<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ApartmentSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'city' => 'nullable|string|max:100',
                'governorate' => 'nullable|string|max:100',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'has_wifi' => 'nullable|boolean',
                'bedrooms' => 'nullable|integer|min:1',
                'start_date' => 'nullable|date|after_or_equal:today',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            $query = Apartment::query()->where('status', 'active')->with(['mainImage', 'owner']);

            if ($request->filled('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            if ($request->filled('governorate')) {
                $query->where('governorate', $request->governorate);
            }

            if ($request->filled('min_price')) {
                $query->where('price_per_night', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('price_per_night', '<=', $request->max_price);
            }

            if ($request->filled('has_wifi')) {
                $query->where('has_wifi', $request->boolean('has_wifi'));
            }
            if ($request->has('bedrooms') && $request->bedrooms != null) {
                $query->where('bedrooms', '=', $request->bedrooms);
            }

            if ($request->filled(['start_date', 'end_date'])) {
                $query->whereDoesntHave('bookings', function ($q) use ($request) {
                    $q->where('status', 'approved') // الشقة مشغولة فقط إذا كان الحجز مقبولاً
                        ->where(function ($b) use ($request) {
                            $b->where(function ($inner) use ($request) {
                                $inner->whereBetween('start_date', [$request->start_date, $request->end_date])
                                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                                    ->orWhere(function ($deep) use ($request) {
                                        $deep->where('start_date', '<=', $request->start_date)
                                            ->where('end_date', '>=', $request->end_date);
                                    });
                            });
                        });
                });
            }

            $perPage = $request->per_page ?? 20;
            $apartments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Apartment found ' . $apartments->total(),
                'data' => $apartments->items(),
                'pagination' => [
                    'total' => $apartments->total(),
                    'current_page' => $apartments->currentPage(),
                    'last_page' => $apartments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while searching',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
