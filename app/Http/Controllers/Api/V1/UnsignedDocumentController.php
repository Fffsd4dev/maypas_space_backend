<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\{Apartment,
    DocumentSigningRequest,
    UnsignedDocument};

class UnsignedDocumentController extends Controller
{
    public function store(Request $request)
    {
        $estate = app('estateManager');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png',
            'apartment_uuid' => 'required|string|exists:apartments,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $apartment = Apartment::where('uuid', $validated['apartment_uuid'])
            ->select('id')
            ->firstOrFail();

        $file = $request->file('document');
        $extension = $file->extension();

        $validated['type'] = $extension === 'pdf' ? 'pdf' : 'image';
        $validated['filename'] = Str::uuid() . '.' . $extension;
        $validated['apartment_id'] = $apartment->id;
        $validated['estate_manager_id'] = $estate->id;

        DB::beginTransaction();

        try {
            $file->storeAs(
                'apartment/document/unsigned',
                $validated['filename'],
                'public'
            );

            $document = UnsignedDocument::create($validated);

            DB::commit();

            return response()->json([
                'message' => 'Document uploaded successfully',
                'data' => $document
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to upload document'
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $estate = app('estateManager');

        $documents = UnsignedDocument::where('estate_manager_id', $estate->id)
            ->when($request->apartment_uuid, function ($query) use ($request) {
                $query->whereHas('apartment', function ($q) use ($request) {
                    $q->where('uuid', $request->apartment_uuid);
                });
            })
            ->latest()
            ->paginate(10);

        return response()->json($documents);
    }

    // public function index(Request $request)
    // {
    //     $estate = app('estateManager');

    //     $user = $request->user();

    //     $documents = DocumentSigningRequest::where('tenant_id',$user->id)
    //                     ->where('estate_manager_id', $estate->id)
    //                     ->where('signed', 'no')
    //                     ->with('document')
    //                     ->paginate(10);

    //     return response()->json($documents);
    // }

    public function show($slug, string $uuid)
    {
        $estate = app('estateManager');

        $document = UnsignedDocument::where('uuid', $uuid)
            ->where('estate_manager_id', $estate->id)
            ->firstOrFail();

        return response()->json($document);
    }

    public function update(Request $request, string $uuid)
    {
        $estate = app('estateManager');

        $document = UnsignedDocument::where('uuid', $uuid)
            ->where('estate_manager_id', $estate->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'document' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            // If replacing file
            if ($request->hasFile('document')) {
                // Delete old file
                Storage::disk('public')->delete(
                    'apartment/document/unsigned/' . $document->filename
                );

                $file = $request->file('document');
                $extension = $file->extension();

                $validated['type'] = $extension === 'pdf' ? 'pdf' : 'image';
                $validated['filename'] = Str::uuid() . '.' . $extension;

                $file->storeAs(
                    'apartment/document/unsigned',
                    $validated['filename'],
                    'public'
                );
            }

            $document->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Document updated successfully',
                'data' => $document->fresh()
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update document'
            ], 500);
        }
    }

    public function destroy($slug, string $uuid)
    {
        $estate = app('estateManager');

        $document = UnsignedDocument::where('uuid', $uuid)
            ->where('estate_manager_id', $estate->id)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            Storage::disk('public')->delete(
                'apartment/document/unsigned/' . $document->filename
            );

            $document->delete();

            DB::commit();

            return response()->json([
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete document'
            ], 500);
        }
    }

}
