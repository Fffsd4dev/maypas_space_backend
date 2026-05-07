<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentNotificationMail;

use App\Models\{UnsignedDocument,
SignedDocument,
DocumentSigningRequest,
Apartment,
Notification,
LandlordAgent,
RentManager};

class DocumentSigningController extends Controller
{
    public function sign(Request $request)
    {
        $estate = app('estateManager');
        $user = $request->user();

        $request->validate([
            'document_uuid' => 'required|exists:unsigned_documents,uuid',
            'page' => 'required|integer|min:1',
            'signer_name' => 'required|string|max:255',
            'name_x' => 'required|numeric',
            'name_y' => 'required|numeric',
            'signature' => 'required|string',
            'signature_x' => 'required|numeric',
            'signature_y' => 'required|numeric',
        ]);

        $document = UnsignedDocument::where('uuid', $request->document_uuid)
            ->where('estate_manager_id', $estate->id)
            ->firstOrFail();

        if (
            SignedDocument::where('document_id', $document->id)
                ->where('tenant_id', $user->id)
                ->exists()
        ) {
            return response()->json([
                'message' => 'You have already signed this document.'
            ], 409);
        }

        $rentManager = RentManager::where('occupant_id', $user->id)
                        ->where('estate_manager_id', $estate->id)
                        ->with('apartmentUnit:id,apartment_unit_name')
                        ->firstOrFail();

        $landlord = Apartment::select('id', 'landlord_agent_id')
                    ->findOrFail($document->apartment_id);

                    DB::beginTransaction();

        try {
            /** ---------------- TEMP SIGNATURE ---------------- */
            $signatureData = base64_decode(
                preg_replace('/^data:image\/\w+;base64,/', '', $request->signature)
            );

            $tempSignaturePath = storage_path(
                'app/temp/signature_' . uniqid() . '.png'
            );

            if (!is_dir(dirname($tempSignaturePath))) {
                mkdir(dirname($tempSignaturePath), 0755, true);
            }

            file_put_contents($tempSignaturePath, $signatureData);

            /** ---------------- LOAD PDF ---------------- */
            $originalPdfPath = Storage::disk('public')
                ->path('apartment/document/unsigned/' . $document->filename);

            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($originalPdfPath);

            if ($request->page > $pageCount) {
                throw new \Exception('Invalid page number.');
            }

            /** ---------------- SIGN PDF ---------------- */
            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $pdf->importPage($page);
                $pdf->addPage();
                $pdf->useTemplate($templateId);

                if ($page === (int) $request->page) {
                    $pdf->SetFont('Times', '', 12);
                    $pdf->SetXY($request->name_x, $request->name_y);
                    $pdf->Write(0, $request->signer_name);

                    $pdf->Image(
                        $tempSignaturePath,
                        $request->signature_x,
                        $request->signature_y,
                        config('documents.signature_width', 40),
                        config('documents.signature_height', 20)
                    );

                    $pdf->SetFont('Times', 'I', 9);
                    $pdf->SetXY(
                        $request->signature_x,
                        $request->signature_y + 22
                    );
                    $pdf->Write(0, 'Signed on ' . now()->format('Y-m-d H:i'));
                }
            }

            /** ---------------- SAVE PDF ---------------- */
            $filename = 'signed_' . $document->id . '_' . time() . '.pdf';
            $signedPath = 'apartment/document/signed/' . $filename;

            Storage::disk('public')->put(
                $signedPath,
                $pdf->Output('S')
            );

            /** ---------------- DB RECORDS ---------------- */
            SignedDocument::create([
                'document_id' => $document->id,
                'tenant_id' => $user->id,
                'estate_manager_id' => $estate->id,
                'signer_name' => $request->signer_name,
                'filename' => $filename,
                'signed_at' => now(),
                'ip_address' => $request->ip(),
            ]);

            DocumentSigningRequest::where('tenant_id', $user->id)
                ->where('document_id', $document->id)
                ->update(['signed' => 'yes']);
               
            // Notification modification Ended here

            DB::commit();

            // Send email AFTER successful commit

            try {
                $this->notification($user, $landlord, $estate, $document, $rentManager);
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'message' => 'Document signed successfully.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            if (isset($tempSignaturePath) && file_exists($tempSignaturePath)) {
                unlink($tempSignaturePath);
            }

            report($e);

            return response()->json([
                'message' => 'Failed to sign document.',
            ], 500);
        }
    }

    private function notification($user, $landlord, $estate, $document, $rentManager){
         // Saving in notifications table
            $notificationData =[];

            $notificationData['type'] = 'document';
            $notificationData['data'] = ['signer_id'=>$user->id, 'message' => 'Signed document', 'document_name' => $document->name];
            $notificationData['apartment_id'] = $landlord->id;

            if($landlord->landlord_agent_id){
                $notificationData['for'] = $landlord->landlord_agent_id;
            }else{
                $owner = LandlordAgent::where('user_type_id', 1)->where('estate_manager_id', $estate->id)->firstOrFail(); 
                $notificationData['for'] = $owner->id;
            }

            $notificationData['estate_manager_id'] = $estate->id;

            Notification::create($notificationData);

            $messageContent = [
                'estate_name' => $estate->estate_name,
                'signer_name'   => $user->first_name.' '.$user->last_name,
                'apartment_name' => $landlord->name,
                'apartment_unit' => $rentManager->apartmentUnit->apartment_unit_name,
                'document_name' => $document->name,
            ];

            Mail::to($user->email)->queue(
                new DocumentNotificationMail($messageContent)
            );
    }
}
