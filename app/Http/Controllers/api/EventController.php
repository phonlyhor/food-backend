<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = Event::all();
        return response()->json([
            'success' => true,
            'data'    => $events,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
     
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'button_text'   => 'nullable|string|max:100',
            'button_link'   => 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'priority'      => 'nullable|integer|min:1',
        ]);

        // Upload Image
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        // Create Event
        $event = Event::create([
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'image'        => $validated['image'] ?? null,
            'start_date'   => $validated['start_date'],
            'end_date'     => $validated['end_date'],
            'button_text'  => $validated['button_text'] ?? null,
            'button_link'  => $validated['button_link'] ?? null,
            'is_active'    => $validated['is_active'] ?? true,
            'priority'     => $validated['priority'] ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data'    => $event,
        ], 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $event,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'start_date'    => 'sometimes|required|date',
            'end_date'      => 'sometimes|required|date|after_or_equal:start_date',
            'button_text'   => 'nullable|string|max:100',
            'button_link'   => 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'priority'      => 'nullable|integer|min:1',
        ]);

        // Upload Image
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        // Update Event
        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data'    => $event,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        // Delete the event
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }
}
