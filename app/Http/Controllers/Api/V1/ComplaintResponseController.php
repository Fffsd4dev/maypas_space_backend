<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationsMail;

use App\Models\{ComplaintResponse, Complaint};

class ComplaintResponseController extends Controller
{
    public function create(Request $request)
    {
        try {
            $landlord = $request->user();
            $estate = app('estateManager');

            $complaint = Complaint::where('id', $request->complaint_id)
                        ->select(['id', 'landlord_agent_id','tenant_id'])
                        ->where('estate_manager_id', $estate->id)
                        ->with('tenant:id,first_name,last_name,email')
                        ->firstOrFail();

            if((int)$landlord->user_type_id != 1 && (int)$landlord->id != (int)$complaint->landlord_agent_id){
                return response()->json(['message' => 'You are not authorized'], 403);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'complaint_id'  => 'required',
                'message'  => 'required|string',
                'attachment'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $filename = time(). $landlord->uuid . '_attachment.' . $request->attachment->extension();
                $request->attachment->storeAs('attachments', $filename, 'public');
                $validated['attachment'] = $filename;
            }

            $validated['landlord_id'] = $landlord->id;
            $validated['estate_manager_id'] = $estate->id;

            $complaintResponse = ComplaintResponse::create($validated);

            $messageContent = [
                'name' => $complaint->tenant->first_name.' '.$complaint->tenant->last_name,
                'body' => 'You just received a response to your complaint',
                'estate_name' => $estate->estate_name,
            ];

            Mail::to($complaint->tenant->email)->queue(
                new NotificationsMail($messageContent)
            );


            return response()->json([
                'message' => 'Complaint response sent',
                'data'    => $complaintResponse,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while sending the complaint response.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function index($slug, $id)
    {
        // fetch all complaints, oldest first
        $complaintResponses = ComplaintResponse::where('complaint_id', $id)->orderBy('created_at', 'asc')->get();

        return response()->json($complaintResponses);
    }

    public function update(Request $request, $slug, $complaint_id, $response_id)
    {
        $landlord = $request->user();
        try {
            $response = ComplaintResponse::select(['id','complaint_id', 'landlord_id', 'message', 'attachment', 'estate_manager_id'])
                        ->where('complaint_id', $complaint_id)
                        ->where('id', $response_id)
                        ->first();

            if((int)$response->landlord_id !== (int)$landlord->id){
                return response()->json(['message' => 'You are not authorized'], 403);
            }

            $validator = Validator::make($request->all(), [
                'message'    => 'sometimes|string',
                'attachment' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $filename = time().'_' . $landlord->uuid . '_attachment.' . $request->attachment->extension();
                $request->attachment->storeAs('attachments', $filename, 'public');
                $validated['attachment'] = $filename;
            }
            
            $response->update($validated);

            return response()->json([
                'message' => 'Complaint response updated successfully',
                'data'    => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while updating the complaint response.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $slug, $id)
    {
        $landlord = $request->user();
        try {
            $response = ComplaintResponse::select(['id', 'landlord_id'])->findOrFail($id);

            if((int)$response->landlord_id !== (int)$landlord->id){
                return response()->json(['message' => 'You are not authorized'], 403);
            }

            $response->delete();

            return response()->json([
                'message' => 'Complaint response deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete complaint response.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
