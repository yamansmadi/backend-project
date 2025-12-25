<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\ApartmentImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApartmentController extends Controller
{
    //  هدول بشكل مبدأي

    public function show($id)
    {
        $apartment = Apartment::with('images')->find($id);

        if (!$apartment) {
            return response()->json([
                'message' => 'Apartment not found'
            ], 404);
        }

        return response()->json([
            'apartment' => $apartment
        ]);
    }

    public function index(Request $request)
    {
        $query = Apartment::with('images');

        // فلترة حسب المدينة إذا وجدت في الطلب
        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // فلترة حسب المحافظة
        if ($request->has('governorate')) {
            $query->where('governorate', $request->governorate);
        }

        $query->where('status', 'active');

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Account not approved'
            ], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_per_night' => 'required|numeric',
            'bedrooms' => 'required|integer',
            'governorate' => 'required|string',
            'city' => 'required|string',
            'address' => 'nullable|string',
            'max_guests' => 'nullable|integer',

            // images are now optional
            'images' => 'nullable|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'main_image' => 'nullable|integer' // index of main image
        ]);

        DB::beginTransaction();

        try {
            // create apartment
            $apartment = Apartment::create([
                'owner_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'price_per_night' => $data['price_per_night'],
                'bedrooms' => $data['bedrooms'],
                'governorate' => $data['governorate'],
                'city' => $data['city'],
                'address' => $data['address'] ?? null,
                'max_guests' => $data['max_guests'] ?? 2,
            ]);

            // store images if any
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('apartments', 'public');

                    $apartment->images()->create([
                        'image_path' => $path,
                        'is_main' => isset($data['main_image'])
                            ? $data['main_image'] == $index
                            : $index === 0 // first image default
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Apartment created successfully',
                'apartment' => $apartment->load('images')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create apartment',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $user = $request->user();

        $apartment = Apartment::where('id', $id)
            ->where('owner_id', $user->id)
            ->first();

        if (!$apartment) {
            return response()->json([
                'message' => 'Apartment not found or unauthorized'
            ], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price_per_night' => 'sometimes|numeric',
            'bedrooms' => 'sometimes|integer',
            'governorate' => 'sometimes|string',
            'city' => 'sometimes|string',
            'address' => 'sometimes|nullable|string',
            'max_guests' => 'sometimes|integer',
            'has_wifi' => 'sometimes|boolean',
            'status' => 'sometimes|in:pending,active,reserved'
        ]);

        $apartment->update($data);

        return response()->json([
            'message' => 'Apartment updated successfully',
            'apartment' => $apartment->load('images')
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // التحقق من أن المستخدم هو أدمن
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only admins can delete apartments.'
            ], 403);
        }

        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        // حذف الصور من التخزين الفيزيائي
        foreach ($apartment->images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $apartment->delete();

        return response()->json(['message' => 'Apartment deleted successfully by Admin']);
    }

    public function myApartments(Request $request)
    {
        $user = $request->user();

        // جلب الشقق التابعة لهذا المالك فقط مع صورها
        $apartments = Apartment::with('images')
            ->where('owner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $apartments->count(),
            'data' => $apartments
        ]);
    }
}
