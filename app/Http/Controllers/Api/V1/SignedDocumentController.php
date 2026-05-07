<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SignedDocument;

class SignedDocumentController extends Controller
{
    public function index(Request $request){
        $estate = app('estateManager');

        $tenant = $request->user();

        $documents = SignedDocument::where('tenant_id', $tenant->id)
                        ->where('estate_manager_id', $estate->id)
                        ->select(['id','uuid','filename', 'document_id'])
                        ->with('document:id,name')
                        ->latest()
                        ->paginate(10);

        return response()->json($documents);
    }

    public function show(Request $request, $slug, $uuid){
        $estate = app('estateManager');

        $tenant = $request->user();

        $documents = SignedDocument::where('tenant_id', $tenant->id)
                        ->where('estate_manager_id', $estate->id)
                        ->where('uuid', $uuid)
                        ->select(['id','uuid','filename'])
                        ->latest()
                        ->firstOrFail();

        return response()->json($documents);
    }

    public function landlordIndex(){
        $estate = app('estateManager');

        $documents = SignedDocument::where('estate_manager_id', $estate->id)                       
                        ->select(['id','uuid','filename'])
                        ->latest()
                        ->paginate(10);

        return response()->json($documents);
    }

    public function landlordShow($slug, $uuid){
        $estate = app('estateManager');

        $documents = SignedDocument::where('estate_manager_id', $estate->id)
                        ->where('uuid', $uuid)
                        ->select(['id','uuid','filename'])
                        ->latest()
                        ->firstOrFail();

        return response()->json($documents);
    }
}
