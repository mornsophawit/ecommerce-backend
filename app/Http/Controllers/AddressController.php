<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;

class AddressController extends Controller
{
     public function index()
    {
        $addresses = Address::where('user_id', auth()->id())->get();
        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'contact' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $address = Address::create([
            'user_id' => auth()->id(),
            'address' => $request->address,
            'contact' => $request->contact,
            'full_name' => $request->full_name,
            'email' => $request->email,
        ]);

        return response()->json(['message' => 'Address created successfully', 'data' => $address], 201);
    }

    public function show($id)
    {
        $address = Address::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($address);
    }

    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'address' => 'required|string',
            'contact' => 'required|string|max:255',
        ]);

        $address->update([
            'address' => $request->address,
            'contact' => $request->contact,
        ]);

        return response()->json(['message' => 'Address updated successfully']);
    }

    public function destroy($id)
    {
        $address = Address::where('user_id', auth()->id())->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }
}
