<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Notification::where('user_id', $user->id);

        // Filter by read status if provided
        if ($request->has('read')) {
            $isRead = $request->read === 'true' ? 1 : 0;
            $query->where('is_read', $isRead);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Order by latest first
        $notifications = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'total' => $notifications->total(),
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'related_url' => 'nullable|string|max:255',
        ]);

        // Add default values for notifiable fields
        $data = $request->all();
        $data['notifiable_type'] = 'App\Models\User';
        $data['notifiable_id'] = $data['user_id'];

        $notification = Notification::create($data);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'is_read' => 'boolean',
            'title' => 'string|max:255',
            'message' => 'string',
        ]);

        $notification->update($request->only(['is_read', 'title', 'message']));

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $notification = $notification->markAsRead(); // Assign the returned value

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = Notification::where('user_id', $user->id)->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count
            ],
            'message' => 'Unread notifications count retrieved successfully'
        ]);
    }
}