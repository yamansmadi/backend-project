<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    // Show dashboard with pending users only
    public function dashboard()
    {
        $pendingUsers = User::where('status', 'pending')->get();
        return view('admin.dashboard', compact('pendingUsers'));
    }

    // Approve user
    public function approveUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();

        return redirect()->back()->with('success', 'User approved');
    }

    // Reject user
    public function rejectUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'blocked'; // or delete if you prefer
        $user->save();

        return redirect()->back()->with('success', 'User rejected');
    }
}
