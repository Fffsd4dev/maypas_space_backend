<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Models\{SignedDocument, 
UnsignedDocument,
DocumentSigningRequest
};

class StreamingController extends Controller
{
    public function view($slug, string $uuid)
    {
        $estate = app('estateManager');
        $user = request()->user();

        $document = UnsignedDocument::where('uuid', $uuid)
            ->where('estate_manager_id', $estate->id)
            ->firstOrFail();

        // Ensure this tenant is allowed to sign this document
        $allowed = DocumentSigningRequest::where('document_id', $document->id)
            ->where('tenant_id', $user->id)
            ->exists();

        if (!$allowed && !$user->user_type_id) {
            abort(403, 'Unauthorized');
        }

        $path = 'apartment/document/unsigned/' . $document->filename;

        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Document file not found');
        }

        return response()->stream(function () use ($path) {
            echo Storage::disk('public')->get($path);
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document.pdf"',
            'Accept-Ranges'       => 'bytes',
        ]);
    }

    public function viewSigned($slug, string $uuid)
    {
        $estate = app('estateManager');
        $user = request()->user();

        $document = SignedDocument::where('uuid', $uuid)
            ->where('estate_manager_id', $estate->id)
            ->firstOrFail();

        // Ensure this tenant is allowed to sign this document
        $allowed = DocumentSigningRequest::where('document_id', $document->id)
            ->where('tenant_id', $user->id)
            ->exists();

        if (! $allowed && !$user->user_type_id) {
            abort(403, 'Unauthorized');
        }

        $path = 'apartment/document/signed/' . $document->filename;

        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Document file not found');
        }

        return response()->stream(function () use ($path) {
            echo Storage::disk('public')->get($path);
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document.pdf"',
            'Accept-Ranges'       => 'bytes',
        ]);
    }
}
