<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $staff = Staff::all();

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'gen' => 'required|string|size:1|in:M,F',
            'dob' => 'required|date',
            'position' => 'required|string|max:50',
            'salary' => 'required|numeric|min:0',
            'stopwork' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:10248',
            'status' => 'required|in:active,inactive'
        ]);

        // handle image
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $request->file('photo')->store('staffs', 'public');
        }

        Staff::create([
            'full_name' => $request->full_name,
            'gen' => $request->gen,
            'dob' => $request->dob,
            'position' => $request->position,
            'salary' => $request->salary,
            'stopwork' => $request->stopwork ? true : false,
            'photo' => $photoUrl,
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Insert successfully!',
            // 'data' => $staff
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $staff = Staff::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'gen' => 'required|string|size:1|in:M,F',
            'dob' => 'required|date',
            'position' => 'required|string|max:50',
            'salary' => 'required|numeric|min:0',
            'stopwork' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:10248',
            'status' => 'required|in:active,inactive'
        ]);

        // Handle image
        $photoUrl = $staff->photo; // Keep existing photo by default
        
        if ($request->hasFile('photo')) {
            // New photo uploaded - delete old one if exists
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $photoUrl = $request->file('photo')->store('staffs', 'public');
        } else if ($request->has('delete_photo') && $request->delete_photo) {
            // Delete photo flag set - remove existing photo
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $photoUrl = null;
        }
        // If no photo field and no delete_photo flag, keep existing photo

        $staff->update([
            'full_name' => $request->full_name,
            'gen' => $request->gen,
            'dob' => $request->dob,
            'position' => $request->position,
            'salary' => $request->salary,
            'stopwork' => $request->stopwork ? true : false,
            'photo' => $photoUrl,
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Update successfully!',
            'data' => $staff
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $staff = Staff::findOrFail($id);

        if ($staff->photo) {
            Storage::disk('public')->delete($staff->photo);
        }

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delete successfully!'
        ]);
    }
}
