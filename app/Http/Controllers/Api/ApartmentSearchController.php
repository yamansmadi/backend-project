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
            ]);

            $query = Apartment::query()->with(['mainImage', 'owner']);

            if ($request->filled('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            if ($request->filled('governorate')) {
                $query->where('governorate', 'like', '%' . $request->governorate . '%');
            }

            if ($request->filled('min_price') && $request->filled('max_price')) {
                $query->whereBetween('price_per_night', [$request->min_price, $request->max_price]);
            } elseif ($request->filled('min_price')) {
                $query->where('price_per_night', '>=', $request->min_price);
            } elseif ($request->filled('max_price')) {
                $query->where('price_per_night', '<=', $request->max_price);
            }

            if ($request->filled('check_in') && $request->filled('check_out')) {
                $query->whereDoesntHave('bookings', function ($q) use ($request) {
                    $q->where(function ($booking) use ($request) {
                        $booking->whereBetween('check_in', [$request->check_in, $request->check_out])
                            ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                            ->orWhere(function ($b) use ($request) {
                                $b->where('check_in', '<=', $request->check_in)
                                    ->where('check_out', '>=', $request->check_out);
                            });
                    })
                        ->whereIn('status', ['approved', 'pending']);
                });
            }
            if ($request->filled('has_wifi')) {
                $query->where('has_wifi', $request->boolean('has_wifi'));
            }
            if ($request->filled('bedrooms')) {
                $query->where('bedrooms', '>=', $request->bedrooms);
            }



            $perPage = $request->per_page ?? 20; //قسم النتائج، كل صفحة فيها 20 شقة (إلا إذا طلب غير ذلك)
            $apartments = $query->paginate($perPage);

            $stats = [
                'total_results' => $apartments->total(),
                'current_page' => $apartments->currentPage(),
                'last_page' => $apartments->lastPage(),
                'per_page' => $apartments->perPage(),
                'has_more_pages' => $apartments->hasMorePages(),
            ];


            return response()->json([
                'success' => true,
                'message' => 'Apartment Found ' . $apartments->total(),
                'stats' => $stats,
                'data' => $apartments->items(),
                'filters_applied' => [
                    'city' => $request->city,
                    'governorate' => $request->governorate,
                    'min_price' => $request->min_price,
                    'max_price' => $request->max_price,
                    'check_in' => $request->check_in,
                    'check_out' => $request->check_out,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Input',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during search',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getCities()
    {
        $cities = Apartment::query()
            ->select('city', 'governorate', DB::raw('COUNT(*) as apartments_count'))
            ->groupBy('city', 'governorate')
            ->orderBy('city')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    public function getPriceRange()
    {
        $minPrice = Apartment::active()->min('price_per_night');
        $maxPrice = Apartment::max('price_per_night');

        return response()->json([
            'success' => true,
            'data' => [
                'min_price' => (float) $minPrice,
                'max_price' => (float) $maxPrice
            ]
        ]);
    }
}
