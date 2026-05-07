<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\{Charge, ApartmentUnit,Landlord};
use App\Models\User;
use App\Models\PaymentInfo;
use App\Mail\InvoiceMail;
use App\Models\BrandModel;
use App\Models\RentCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InvoiceController extends Controller
{
    //
    public function index()
    {
        return response()->json(['message' => 'Invoice index']);
    }
    
    
    public function createInvoice(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | 1. Validate Request
    |--------------------------------------------------------------------------
    */
    $validated = $request->validate([
        'user_uuid' => 'required|string|exists:users,uuid',
        'apartment_unit_uuid' => 'required|string|exists:apartment_units,uuid',
        'fee_name' => 'required|array|min:1',
        'fee_name.*' => 'required|string',
        'fee_amount' => 'required|array|min:1',
        'fee_amount.*' => 'required|numeric|min:0',
    ]);

    /*
    |--------------------------------------------------------------------------
    | 2. Ensure fee_name and fee_amount align
    |--------------------------------------------------------------------------
    */
    if (count($validated['fee_name']) !== count($validated['fee_amount'])) {
        return response()->json([
            'message' => 'Fee names and fee amounts must have the same count'
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Calculate Total Amount
    |--------------------------------------------------------------------------
    */
    $totalAmount = array_sum($validated['fee_amount']);

    /*
    |--------------------------------------------------------------------------
    | 4. Fetch User
    |--------------------------------------------------------------------------
    */
    $user = User::where('uuid', $validated['user_uuid'])
        ->select('id', 'first_name', 'last_name', 'middle_name', 'email')
        ->first();

    /*
    |--------------------------------------------------------------------------
    | 5. Fetch Apartment Unit
    |--------------------------------------------------------------------------
    */
    $apartmentUnit = ApartmentUnit::with([
        'apartment:id,name,location,category_id,landlord_id',
        'apartment.category:id,name,description,uuid'
    ])
    ->where('uuid', $validated['apartment_unit_uuid'])
    ->select('id', 'uuid', 'apartment_id', 'apartment_unit_name')
    ->first();

    /*
    |--------------------------------------------------------------------------
    | 6. Optional Brand & Bank Data
    |--------------------------------------------------------------------------
    */
    $brand_data = BrandModel::where(
        'estate_manager_id',
        $request->user()->estate_manager_id ?? null
    )
    ->select('name', 'addresses', 'phones', 'social_links', 'logo')
    ->first();

    $bank_data = null;
    if ($apartmentUnit->apartment->landlord_id) {
        $bank_data = Landlord::where(
            'id',
            $apartmentUnit->apartment->landlord_id
        )
        ->select('bank_name', 'bank_account_number')
        ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Database Transaction (VERY IMPORTANT)
    |--------------------------------------------------------------------------
    */
    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 8. Create Invoice
        |--------------------------------------------------------------------------
        */
        $invoice = Invoice::create([
            'amount' => $totalAmount,
            'user_id' => $user->id,
            'apartment_unit_id' => $apartmentUnit->id,
            'rent_cycle_id' => null,
            'status' => 'pending',
            'active' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 9. Build Payment Info Records
        |--------------------------------------------------------------------------
        */
        $paymentData = [];

        foreach ($validated['fee_name'] as $index => $feeName) {
            $paymentData[] = [
                'invoice_id' => $invoice->id,
                'payment_name' => $feeName,
                'payment_fee' => $validated['fee_amount'][$index],
                'rent_managers_id' => null,
                'rent_cycle_id' => null,
                'user_id' => $user->id,
                'apartment_unit_id' => $apartmentUnit->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        PaymentInfo::insert($paymentData);

        /*
        |--------------------------------------------------------------------------
        | 10. Commit Transaction
        |--------------------------------------------------------------------------
        */
        DB::commit();

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'message' => 'Failed to create invoice',
            'error' => $e->getMessage()
        ], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | 11. Send Invoice Email
    |--------------------------------------------------------------------------
    */
    $invoice_data = [
        'invoice_ref' => $invoice->uuid,
        'user' => $user,
        'apartment_unit' => $apartmentUnit,
        'payment_info' => $paymentData,
        'bank_data' => $bank_data,
        'brand_data' => $brand_data,
    ];

    \Mail::to($user->email)->send(new InvoiceMail($invoice_data));

    /*
    |--------------------------------------------------------------------------
    | 12. Final Response
    |--------------------------------------------------------------------------
    */
    return response()->json([
        'message' => 'Invoice created successfully',
        'invoice_id' => $invoice->id,
        'invoice_ref' => $invoice->uuid,
        'total_amount' => $totalAmount,
        'payment_count' => count($paymentData),
    ], 201);
}


    public function generateInvoice($request)
    {
        //for invoice creation on rent account creation
         $user_id  = User::where('uuid', $request->user_uuid)->value('id');
        if(!$user_id){
            return response()->json(['message' => 'User not found'], 404);
        }
        $apartment_data  = ApartmentUnit::where('uuid', $request->apartment_unit_uuid)
        ->select('apartment_units.id as apartment_unit_id',
        'apartment_units.name as apartment_unit_name',
        'apartments.id as apartments_id', 'apartments.name as apartment_name','apartments.location as apartment_location',
        'apartments.location as apartments_location','country_code')->first()->toArray();
        if(!$apartment_data){
            return response()->json(['message' => 'Apartment Unit not found'], 404);
        }
        $apartment_data['charges'] = Charge::where('apartment_unit_id', $apartment_data['apartment_unit_id'])
        ->select('charge_type', 'fee_type', 'value')->get()->toArray();
        
       
        $invoice = Invoice::create([
            'amount' => $amount,
            'user_id' => $user_id,
            'apartment_unit_id' => $apartment_unit_id,
            'rent_cycle_id' => $rent_cycle_id,
            'invoice_uuid' => \Illuminate\Support\Str::uuid(),
        ]);

        return $invoice;
    }

 public function getInvoices(Request $request)
{
    $estateManagerId = (int) $request->user()->estate_manager_id;
    $request->validate([
        'from_date' => 'nullable|date',
        'to_date'   => 'nullable|date',
        'type'      => 'required|in:pending,cancelled,completed,all',
    ]);

    $query = Invoice::join('users', 'invoices.user_id', '=', 'users.id')
        ->join('apartment_units', 'invoices.apartment_unit_id', '=', 'apartment_units.id')
        ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
        ->select(
            'invoices.uuid as invoice_uuid','invoices.amount as invoice_amount','invoices.id',
            'invoices.status as invoice_status','invoices.created_at as invoice_created_at',
            'invoices.amount as invoice_amount',
            'invoices.rent_cycle_id',
            'users.first_name as first_name',
            'users.last_name as last_name',
            'users.email as user_email',
            'apartment_units.apartment_unit_name',
            'apartments.name as apartment_name',
            'apartments.location as apartment_location'
        )->where('users.estate_manager_id', $estateManagerId);

    // Apply type filter only if not "all"
    if ($request->type !== 'all') {
        $query->where('invoices.status', $request->type);
    }

    // Apply date range filters if provided
    if ($request->filled('from_date')) {
        $query->whereDate('invoices.created_at', '>=', $request->from_date);
    }

    if ($request->filled('to_date')) {
        $query->whereDate('invoices.created_at', '<=', $request->to_date);
    }

    // Paginate results
    $invoices = $query->orderByDesc('invoices.created_at')->paginate(50);
    $cycles = RentCycle::pluck('fee', 'id');  // id => fee mapping


$update = [];

foreach ($invoices as $invoice) {

    if ($invoice->rent_cycle_id && isset($cycles[$invoice->rent_cycle_id])) {

        $amount = number_format($cycles[$invoice->rent_cycle_id], 2, '.', '');

        $update[] = Invoice::where('id', $invoice->id)
            ->update([
                'amount' => $amount
            ]);

        $invoice->invoice_amount = $amount;
        unset($invoice->id);
    }
}



    return response()->json([
        'data' => $invoices,
        'message' => 'Invoices retrieved successfully',
    ], 200);
}

public function getSingleInvoice(Request $request, $slug, $uuid)
{
    $uuid = strip_tags($uuid);
    $invoice = Invoice::where('uuid', $uuid)
        ->with(['user', 'apartmentUnit.apartment', 'paymentInfos'])
        ->first();
    if (!$invoice) {
        return response()->json(['message' => 'Invoice not found'], 404);
    }

    return response()->json([
        'data' => $invoice,
        'message' => 'Invoice retrieved successfully',
    ], 200);

}
public function updateInvoiceStatus(Request $request, string $slug, string $invoice_uuid)
    {
        $request->validate([
            'status' => 'required|in:cancelled,completed,pending',
        ]);

        $invoice = Invoice::where('uuid', $invoice_uuid)->first();

        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $invoice->status = $request->status;
        $invoice->save();

        return response()->json([
            'message' => 'Invoice status updated successfully',
        ], 200);
    }

public static function generateRentInvoice(object $rentManager, object $charges, int $rent_cycle_id,$brand_data)
{
    DB::beginTransaction();

    try {
        $total = 0;
        $rent_base_fee = $rentManager->rent_value ?? 0;
        $bank_data = null;
        

        if ($rentManager->apartment_unit->apartment->landlord_id) {
            $bank_data = Landlord::where('id', $rentManager->apartment_unit->apartment->landlord_id)
                ->select('bank_name', 'bank_account_number')
                ->first();
        }

        //Calculate all charges
        foreach ($charges as $charge) {
            if ($charge->fee_type === 'fixed') {
                $total += floatval($charge->value);
            } else {
                $total += floatval($rent_base_fee) * (floatval($charge->value) / 100);
            }
        }

        //Prepend base rent
        $charge = new \stdClass();
        $charge->charge_type = 'rent';
        $charge->fee_type = 'fixed';
        $charge->value = $rent_base_fee;
        $charges->prepend($charge);

        $grand_total = $total + $rent_base_fee;
        

        //Create invoice
        $invoice = Invoice::create([
            'amount' => $grand_total,
            'user_id' => $rentManager->occupant_id,
            'apartment_unit_id' => $rentManager->apartment_unit_id,
            'rent_cycle_id' => $rent_cycle_id,
            'status' => 'pending',
            'active' => 1,
        ]);

        //Build payment data
        $paymentData = [];
        foreach ($charges as $fee) {
            $paymentData[] = [
                'invoice_id' => $invoice->id,
                'payment_name' => $fee->name,
                'payment_fee' => $fee->value,
                'rent_managers_id' => $rentManager->id,
                'rent_cycle_id' => $rent_cycle_id,
                'user_id' => $rentManager->user->id,
                'apartment_unit_id' => $rentManager->apartment_unit_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        PaymentInfo::insert($paymentData);

        $invoice_data = [
            'invoice_ref' => $invoice->uuid,
            'user' => $rentManager->user,
            'apartment_unit' => $rentManager->apartment_unit,
            'payment_info' => $paymentData,
            'bank_data' => $bank_data,
            'brand_data' => $brand_data??[],
        ];

        //Send email AFTER records inserted
        Mail::to($rentManager->user->email)->send(new InvoiceMail($invoice_data));

        DB::commit(); // Commit success
        return true;

    } catch (\Throwable $e) {
        DB::rollBack(); // Rollback if any error occurs

        Log::error("Invoice generation failed", [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return false; //Or throw $e if you want
    }
}
}
