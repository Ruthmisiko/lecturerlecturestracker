<?php

namespace App\Http\Controllers;


use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Display a listing of the properties.
     */
    public function index()
{
    try {
        $properties = Property::all();
        return response()->json($properties);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    /**
     * Store the form for creating a new property.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'price' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $property = Property::create($validated);
        return redirect()->route('properties.index')->with('success', 'Property added successfully!');
    }

    /**
     * Show the form for editing a property.
     */
    public function edit($id)
    {
        $property = Property::findOrFail($id);
        return view('properties.edit', compact('property')); // Show the edit form
    }

    /**
     * Update the specified property.
     */
    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'price' => 'sometimes|integer',
            'description' => 'nullable|string',
        ]);

        $property->update($validated);
        return redirect()->route('properties.index')->with('success', 'Property updated successfully!');
    }

    /**
     * Delete the specified property.
     */
    public function destroy($id)
    {
        $property = Property::findOrFail($id);
        $property->delete();
        return redirect()->route('properties.index')->with('success', 'Property deleted successfully!');
    }
}
