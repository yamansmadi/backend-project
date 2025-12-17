<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    //  هدول بشكل مبدأي
    public function index()
    {
        $apartment = Apartment::all();
        return response()->json($apartment);
    }
    public function store(Request $request)
    {
        $apartment = Apartment::create($request->all());
        return response()->json($apartment);
    }
    public function update(Request $request, Apartment $apartment)
    {
        $apartment->update($request->all());
        return response()->json($apartment);
    }
    public function destroy(Apartment $apartment)
    {
        $apartment->delete();
        return response()->json($apartment);
    }
}
