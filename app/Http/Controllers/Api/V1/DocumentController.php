<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\Document;

class DocumentController extends Controller
{
    public function store(Request $request){
        $landlord = $request->user();

        $estate = app('estateManager');

        if (!$landlord->user_type_id === 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'form_json' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $validated['estate_manager_id'] = $estate->id;
        $validated['landlord_agent_id'] = $landlord->id;

        $doc = Document::create($validated);

        if(!$doc){
            return response()->json(['message' => 'Something went wrong'], 500);
        }

        return response()->json(['message' => 'Document saved successfully', 'data' => $doc], 201);
    }

    public function index(Request $request)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $documents = Document::where('estate_manager_id', $estate->id)
            ->where('landlord_agent_id', $landlord->id)
            ->latest()
            ->get();

        return response()->json(['data' => $documents], 200);
    }

    public function show(Request $request, $id)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        $document = Document::where('id', $id)
            ->where('estate_manager_id', $estate->id)
            ->where('landlord_agent_id', $landlord->id)
            ->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return response()->json(['data' => $document], 200);
    }

    public function update(Request $request, $id)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        $document = Document::where('id', $id)
            ->where('estate_manager_id', $estate->id)
            ->where('landlord_agent_id', $landlord->id)
            ->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'form_json' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $document->update($validated);

        return response()->json(['message' => 'Document updated successfully', 'data' => $document], 200);
    }

    public function destroy(Request $request, $id)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        $document = Document::where('id', $id)
            ->where('estate_manager_id', $estate->id)
            ->where('landlord_agent_id', $landlord->id)
            ->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        // Delete the stored file
        if ($document->file_name && Storage::disk('public')->exists('estate/document/' . $document->file_name)) {
            Storage::disk('public')->delete('estate/document/' . $document->file_name);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully'], 200);
    }
}
