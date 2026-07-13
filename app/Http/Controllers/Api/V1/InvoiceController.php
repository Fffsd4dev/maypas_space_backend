<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\InvoiceModel;
use App\Models\TaxModel;
use App\Models\User;
use App\Models\Spot;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use App\Mail\RefundEmail;
use App\Models\SpacePaymentModel;
use App\Models\PaymentListing;
use App\Models\TimeZoneModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    // CREATE
   public function create(array $data, $spot_id)
{
    $validator = Validator::make($data, [
        'user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric',
        'book_spot_id' => 'required|numeric',
        'booked_by_user_id' => 'required|numeric',
        'tenant_id' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return ['error' => $validator->errors()];
    }

    $validated = $validator->validated();
    $validated['invoice_ref'] = InvoiceModel::generateInvoiceRef();

    if (!isset($validated['status'])) {
        $validated['status'] = 'pending';
    }
    $validated['created_at'] = now();
    $validated['updated_at'] = now();

    $invoice = InvoiceModel::insert($validated);

    if (!$invoice) {
        return ['error' => 'Invoice creation failed'];
    }

    return [
        'message' => 'Invoice created successfully',
        'invoice_ref' => $invoice->invoice_ref,
        'invoice' => $invoice,
        'success' => true,
    ];
}


  
public function index(Request $request, $slug)
{
$tenant_id  =$request->user()->tenant_id;
   
$invoices = InvoiceModel::with([
    'bookSpot:id,spot_id,user_id,start_time,invoice_ref,fee',
    'bookSpot.spot:id,location_id',
    'user:id,first_name,last_name',
    'spacePayment:invoice_ref,amount,payment_status,created_at'
])
->where('tenant_id', $tenant_id)
->whereNotNull('invoice_ref')
->select('id','book_spot_id', 'user_id', 'invoice_ref', 'tenant_id')
->get();



    if ($invoices->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No invoices found'], 404);
    }
    

    //Add location_id directly from eager-loaded relation (no extra queries)
   $invoices->transform(function ($invoice) {
    $invoice->location_id = $invoice->bookSpot->spot->location_id ?? 'n/a';
    return $invoice;
});

    return response()->json([
        'success' => true,
        'invoices' => $invoices,
    ]);
}

public function show(Request $request, $slug, $id)
{
    
    $tenant_id = $request->user()->tenant_id;
    $tenant = Tenant::where('id', $tenant_id)->first();

    if (!$tenant) {
        return response()->json(['message' => 'Tenant not found'], 404);
    }

    $invoice = InvoiceModel::with([
        'bookSpot:id,spot_id,user_id,start_time,invoice_ref,fee,chosen_days,expiry_day',
        'user:id,first_name,last_name',
        'bookSpot.spot:id,space_id,location_id,floor_id'
    ])->where('tenant_id', $tenant_id)->find($id);

    if (!$invoice) {
        return response()->json(['error' => 'Invoice not found'], 404);
    }

    $bookSpot = optional($invoice->bookSpot);

    // Fetch space/location info (your original query with corrected joins)
    $space_info = Spot::where('spots.id', $bookSpot->spot_id)->select(
        'spots.id as spot_id',
        'spots.book_status',
        'spots.space_id',
        'spots.location_id',
        'spots.floor_id',
        'spots.tenant_id',
        'spaces.id as space_id',
        'floors.name as floor_name',
        'locations.id as location_id',
        'locations.name as location_name'
    )
    ->join('spaces', 'spaces.id', '=', 'spots.space_id')
    ->join('locations', 'locations.id', '=', 'spots.location_id')  // corrected: use spots.location_id
    ->join('categories', 'categories.id', '=', 'spaces.space_category_id')
    ->join('floors', 'floors.id', '=', 'spaces.floor_id')  // assuming floor relation is on space
    ->first();

    if (!$space_info) {
        return response()->json(['error' => 'Space information not found'], 404);
    }

    // Get display timezone for this location
    $locationId = $space_info->location_id;
    $displayTz  = $this->getLocationTimezone($locationId);

    // Load bank details
    $bank = $tenant->bankAccounts
        ->where('tenant_id', $tenant->id)
        ->where('location_id', $locationId)
        ->first();
$payment_listing = [];

    $paymentListings = PaymentListing::where('tenant_id', $tenant->id)
    ->where('book_spot_id', $bookSpot->id)
    ->get([
        'id',
        'payment_name',
        'fee',
        'space_name',
        'space_fee',
        'space_category',
        'booking_type',
        'payment_status',
    ]);

$payment_listing = $paymentListings
    ->map(fn ($entry) => [
        'name'            => $entry->payment_name,
        'fee'             => $entry->fee,
        'payment_list_id' => $entry->id,
       'payment_status' => $entry->payment_status === 'Refunded'
    ? 'Refunded'
    : '',
    ])
    ->all();

$spaceFee = $paymentListings->firstWhere('payment_name', 'Space Fee');

if ($spaceFee) {
    $space_info->space_fee      = $spaceFee->space_fee;
    $space_info->space_name     = $spaceFee->space_name;
    $space_info->space_category = $spaceFee->space_category;
    $space_info->booking_type   = $spaceFee->booking_type;
}
    
    // ───────────────────────────────────────────────────────────────
    // Apply timezone corrections (in-place, no structure change)
    // ───────────────────────────────────────────────────────────────

    // 1. Direct booking fields
    if ($bookSpot->start_time) {
        $bookSpot->start_time = Carbon::parse($bookSpot->start_time)
            ->setTimezone($displayTz)
            ->toDateTimeString();
    }

 if ($bookSpot->expiry_day) {
    $bookSpot->expiry_day = Carbon::parse($bookSpot->expiry_day, 'UTC')
        ->setTimezone($displayTz)  // convert to Africa/Lagos
        ->addHour()               // STEP UP +1 hour
        ->toDateTimeString();     // format as string
}

    // 2. chosen_days (JSON field) — decode, convert, re-encode
    $chosenDaysRaw = json_decode($bookSpot->chosen_days ?? '[]', true);

    $chosenDaysConverted = collect($chosenDaysRaw)
        ->map(function ($day) use ($displayTz) {
            if (isset($day['start_time'])) {
                $day['start_time'] = Carbon::parse($day['start_time'])
                    ->setTimezone($displayTz)
                    ->toDateTimeString();
            }
            if (isset($day['end_time'])) {
                $day['end_time'] = Carbon::parse($day['end_time'])
                    ->setTimezone($displayTz)
                    ->toDateTimeString();
            }
            return $day;
        })->toArray();

    $bookSpot->chosen_days = json_encode($chosenDaysConverted);

    // 3. Generate schedule with correct timezone
    $expiryDayCarbon = $bookSpot->expiry_day
        ? Carbon::parse($bookSpot->expiry_day) // already converted above
        : null;

    $schedule = [];
    if (!empty($chosenDaysConverted) && $expiryDayCarbon) {
        $schedule = $this->generateSchedule($chosenDaysConverted, $expiryDayCarbon, $displayTz);
    }

    // Attach schedule to invoice object (your original pattern)
    $invoice->schedule = $schedule;

    // ───────────────────────────────────────────────────────────────
    // Return EXACT same structure as your original code
    // ───────────────────────────────────────────────────────────────
    return response()->json([
        'invoice'     => $invoice,
        'bank'        => $bank,
        'space_info'  => $space_info,
        'charges'     => $payment_listing,  // your naming
    ]);
}

    // UPDATE
    public function update($id, array $data)
    {
        $invoice = InvoiceModel::find($id);

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $validator = Validator::make($data, [
            'user_id' => 'sometimes|required|exists:users,id',
            'invoice_ref' => 'sometimes|required|exists:book_spots,invoice_ref',
            'amount' => 'sometimes|required|numeric',
            'book_spot_id' => 'sometimes|required|numeric|exists:book_spots,id',
            'booked_user_id' => 'sometimes|required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $invoice->update($validator->validated());

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice,
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        $invoice = InvoiceModel::find($id);

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    // CLOSE INVOICE
    public function closeInvoice(Request $request, $slug)
    {
        //close only your nvoice
        $tenant_id = $request->user()->tenant_id;

        $validator = Validator::make($request->all(), [
            'invoice_ref' => 'required|exists:space_payment_models,invoice_ref',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $ref = $request->invoice_ref;

        $payment = SpacePaymentModel::where('invoice_ref', $ref)->where('tenant_id', $tenant_id)->first();
        $invoice_model = InvoiceModel::where('invoice_ref', $ref)->where('tenant_id', $tenant_id)->first();


        if (!$payment) {
            return response()->json(['error' => 'Payment not found for given invoice_ref'], 404);
        }

        $payment->update(['payment_status' => 'completed']);
        $invoice_model->update(['status'=>'paid']);
        PaymentListing::where('book_spot_id',$invoice_model['book_spot_id'])->update(['payment_completed'=>true]);

        return response()->json([
            'message' => 'Invoice closed successfully',
            'data'=>'',
        ], 200);
    }
    
    private function generateSchedule(array $chosenDays, Carbon $expiryDate, string $displayTz): array
{
    $schedule = [];
    foreach ($chosenDays as $day) {
        $weekday = strtolower($day['day'] ?? '');

        // Convert UTC Carbon instances to local timezone for display
        $startCarbon = Carbon::parse($day['start_time'] ?? now())->setTimezone($displayTz);
        $endCarbon   = Carbon::parse($day['end_time']   ?? now())->setTimezone($displayTz);

        $startTime = $startCarbon->format('H:i:s');
        $endTime   = $endCarbon->format('H:i:s');

        $current = $startCarbon->copy();

        while ($current->lte($expiryDate)) {
            $schedule[] = [
                'day'        => $weekday,
                'date'       => $current->toDateString(),
                'start_time' => $current->format('Y-m-d H:i:s'),
                'end_time'   => $current->copy()->setTimeFromTimeString($endTime)->format('Y-m-d H:i:s'),
            ];
            $current->addWeek();
        }
    }

    usort($schedule, fn($a, $b) => strtotime($a['start_time']) <=> strtotime($b['start_time']));
    return $schedule;
}

    
    public function cancelInvoice($book_spot_id, $slug)
    {
        $tenant = Tenant::where('slug', $slug)->first();
        $data = InvoiceModel::where('book_spot_id', $book_spot_id)->where('tenant_id', $tenant->id)->first();
        $space_payment_model = SpacePaymentModel::where('invoice_ref',$data['invoice_ref'])->where('tenant_id', $tenant->id)->update(['payment_status'=>'cancelled']);

        if (!$data) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }
        $data->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Invoice cancelled successfully']);
    }
private function getTenantFromSpot($spotId)
{
    return Spot::with([
        'space:id,space_name,space_category_id',
        'space.category:id,category',
        'floor:id,name',
        'location:id,name,address',
    ])->find($spotId); 
}


private function offsetToTimezone(string $offset): string
{
    // Normalize input, ensure it is in ±HH:MM format
    $offset = trim($offset);
    if (!preg_match('/^[+-]\d{2}:\d{2}$/', $offset)) {
        return 'UTC';
    }

    // Predefined mapping of offsets to IANA timezones
    $offsetMap = [
        '-12:00' => 'Etc/GMT+12',
        '-11:00' => 'Etc/GMT+11',
        '-10:00' => 'Etc/GMT+10',
        '-09:00' => 'Etc/GMT+9',
        '-08:00' => 'Etc/GMT+8',
        '-07:00' => 'Etc/GMT+7',
        '-06:00' => 'Etc/GMT+6',
        '-05:00' => 'America/New_York',   // EST/EDT
        '-04:00' => 'America/Halifax',    // AST/ADT
        '-03:00' => 'America/Argentina/Buenos_Aires',
        '-02:00' => 'Etc/GMT+2',
        '-01:00' => 'Etc/GMT+1',
        '+00:00' => 'UTC',
        '+01:00' => 'Africa/Lagos',       // WAT
        '+02:00' => 'Africa/Cairo',       // EET
        '+03:00' => 'Africa/Nairobi',     // EAT
        '+03:30' => 'Asia/Tehran',
        '+04:00' => 'Asia/Dubai',
        '+04:30' => 'Asia/Kabul',
        '+05:00' => 'Asia/Karachi',
        '+05:30' => 'Asia/Kolkata',
        '+05:45' => 'Asia/Kathmandu',
        '+06:00' => 'Asia/Dhaka',
        '+06:30' => 'Asia/Yangon',
        '+07:00' => 'Asia/Bangkok',
        '+08:00' => 'Asia/Singapore',
        '+09:00' => 'Asia/Tokyo',
        '+09:30' => 'Australia/Darwin',
        '+10:00' => 'Australia/Sydney',
        '+11:00' => 'Pacific/Guadalcanal',
        '+12:00' => 'Pacific/Auckland',
        '+13:00' => 'Pacific/Tongatapu',
        '+14:00' => 'Pacific/Kiritimati',
    ];

    if (isset($offsetMap[$offset])) {
        return $offsetMap[$offset];
    }

    // Fallback: Try to find closest timezone by offset using Carbon
    foreach (timezone_identifiers_list() as $tz) {
        $now = Carbon::now($tz);
        $tzOffset = $now->format('P'); // ±HH:MM
        if ($tzOffset === $offset) {
            return $tz;
        }
    }

    return 'UTC'; // ultimate fallback
}
private function getLocationTimezone(int $locationId): string
{
    $tzRecord = TimeZoneModel::where('location_id', $locationId)
        ->value('utc_time_zone');

    if (!$tzRecord) {
        return 'UTC';
    }

    return $this->offsetToTimezone($tzRecord);
}
public function refundInvoice(Request $request, $slug)
{
    $user = $request->user();
    $tenantId = $user->tenant_id;

    // Only tenant owner can refund
    if ((int) $user->user_type_id !== 1) {
        return response()->json([
            'error' => 'Unauthorized'
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'invoice_ref' => 'required|exists:invoices,invoice_ref',
        'payment_data' => 'required|array|min:1',
        'payment_data.*.payment_list_id' => 'required|integer|exists:payment_listings,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors()
        ], 422);
    }

    $invoice = InvoiceModel::where('invoice_ref', $request->invoice_ref)
        ->where('tenant_id', $tenantId)
        ->where('status', 'paid')
        ->join('tenants', 'invoices.tenant_id', '=', 'tenants.id')
        ->select(
            'invoices.id',
            'invoices.user_id',
            'invoices.invoice_ref',
            'invoices.amount',
            'invoices.book_spot_id',
            'tenants.company_name'
        )
        ->first();

    if (!$invoice) {
        return response()->json([
            'error' => 'Invoice not found.'
        ], 404);
    }

    $invoice_user = User::select('first_name', 'last_name', 'email')
        ->find($invoice->user_id);

    if (!$invoice_user) {
        return response()->json([
            'error' => 'Invoice owner not found.'
        ], 404);
    }

    $paymentListingIds = collect($request->payment_data)
        ->pluck('payment_list_id')
        ->unique()
        ->values();

    $payments = PaymentListing::whereIn('id', $paymentListingIds)
        ->where('tenant_id', $tenantId)
        ->where('book_spot_id', $invoice->book_spot_id)
        ->get();

    if ($payments->count() !== $paymentListingIds->count()) {
        return response()->json([
            'error' => 'One or more payment listings are invalid.'
        ], 422);
    }

    $alreadyRefunded = $payments
        ->where('payment_status', 'refunded')
        ->pluck('id');

    if ($alreadyRefunded->isNotEmpty()) {
        return response()->json([
            'error' => 'Some selected payment items have already been refunded.',
            'payment_listing_ids' => $alreadyRefunded->values(),
        ], 422);
    }

    $refundAmount = (float) $payments->sum('fee');

    $payment = $payments->first();

    $refundInvoice = null;

    DB::transaction(function () use (
        $invoice,
        $paymentListingIds,
        $refundAmount,
        $tenantId,
        $user,
        &$refundInvoice
    ) {

        $newAmount = max(0, (float) $invoice->amount - $refundAmount);

        $invoice->update([
            'amount' => $newAmount,
            'status' => $newAmount == 0 ? 'refunded' : 'paid',
        ]);

        PaymentListing::whereIn('id', $paymentListingIds)
            ->update([
                'payment_status' => 'refunded',
                'updated_at' => now(),
            ]);

        $refundInvoice = InvoiceModel::create([
            'user_id' => $invoice->user_id,
            'amount' => $refundAmount,
            'book_spot_id' => $invoice->book_spot_id,
            'booked_by_user_id' => $user->id,
            'tenant_id' => $tenantId,
            'invoice_ref' => InvoiceModel::generateInvoiceRef(),
            'status' => 'refunded',
        ]);
    });

    $invoice_data = array_merge(
        $refundInvoice->toArray(),
        [
            'customer_name' => $invoice_user->first_name . ' ' . $invoice_user->last_name,
            'company_name' => $invoice->company_name,
            'space_name' => $payment->space_name,
            'space_fee' => $payment->space_fee,
            'space_category' => $payment->space_category,
            'booking_type' => $payment->booking_type,
            'refund_amount' => $refundAmount,
            'refunded_items' => $payments->values()->toArray(),
        ]
    );

    Mail::to($invoice_user->email)->send(new RefundEmail($invoice_data));

    return response()->json([
        'success' => true,
        'message' => 'Invoice refunded successfully.',
        'refund_amount' => $refundAmount,
        'invoice_amount' => $invoice->fresh()->amount,
        'refund_invoice_ref' => $refundInvoice->invoice_ref,
    ], 200);
}


public function modifyUpdate()
{
    // Fetch invoice information
    $invoices = InvoiceModel::whereNotNull('invoices.invoice_ref')
        ->join('book_spots', 'invoices.book_spot_id', '=', 'book_spots.id')
        ->join('spots', 'book_spots.spot_id', '=', 'spots.id')
        ->join('spaces', 'spots.space_id', '=', 'spaces.id')
        ->join('categories', 'spaces.space_category_id', '=', 'categories.id')
        ->select([
            'invoices.invoice_ref',
            'invoices.book_spot_id',
            'invoices.user_id',
            'invoices.status as invoice_status',
            'invoices.amount as invoice_amount',
            'invoices.tenant_id',
            'book_spots.fee',
            'book_spots.chosen_days',
            'book_spots.expiry_day',
            'spots.location_id',
            'spaces.space_name',
            'spaces.space_fee',
            'categories.category as space_category',
            'categories.booking_type',
        ])
        ->get();

    if ($invoices->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No invoices found.',
        ]);
    }

    // Index invoices by book_spot_id for fast lookup
    $invoiceMap = $invoices->keyBy('book_spot_id');

    // Calculate total fees already charged per booking
    $charges = PaymentListing::whereIn('book_spot_id', $invoiceMap->keys())
        ->selectRaw('book_spot_id, SUM(fee) as total_fee')
        ->groupBy('book_spot_id')
        ->get();

    $now = now();
    $paymentData = [];
    

    foreach ($charges as $charge) {

        $invoice = $invoiceMap->get($charge->book_spot_id);

        if (!$invoice) {
            continue;
        }

        $spaceFee = (float) $invoice->invoice_amount - (float) $charge->total_fee;
        $invoice_status = $invoice->invoice_status;
        

        // Skip if there's nothing to insert
        if ($spaceFee <= 0) {
            continue;
        }

        $paymentData[] = [
            'payment_name'       => 'Space Fee',
            'book_spot_id'       => $invoice->book_spot_id,
            'fee'                => $spaceFee,
            'payment_completed' => (int) ($invoice_status === 'paid'),
            'payment_by_user_id' => $invoice->tenant_id,
            'space_name'         => $invoice->space_name,
            'space_fee'          => $invoice->space_fee,
            'space_category'     => $invoice->space_category,
            'tenant_id'          => $invoice->tenant_id,
            'booking_type'         => $invoice->booking_type,
            'payment_status'=>$invoice_status,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];
    }

    // Uncomment when you're ready to save
     PaymentListing::insert($paymentData);

    return response()->json([
        'success' => true,
        'records_to_insert' => count($paymentData),
        'data' => $paymentData, // Remove this once you've verified the output
    ]);
}
public function getInvoicebyRef(Request $request)
{
    $validator = Validator::make($request->all(), [
        'invoice_ref' => ['required', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 422);
    }

    $tenantId = $request->user()->tenant_id;

    $invoice = InvoiceModel::with([
            'bookSpot:id,user_id,spot_id,fee,chosen_days,expiry_day',
            'bookSpot.user:id,first_name,last_name,email',
            'bookSpot.paymentlisting:id,book_spot_id,payment_name,fee,payment_by_user_id,payment_status,payment_completed'
        ])
        ->select([
            'id',
            'invoice_ref',
            'book_spot_id',
            'tenant_id',
            'user_id',
            'amount',
            'status',
            'created_at',
        ])
        ->where('tenant_id', $tenantId)
        ->where('invoice_ref', $request->invoice_ref)
        ->first();

    if (!$invoice) {
        return response()->json([
            'success' => false,
            'message' => 'Invoice not found.',
        ], 404);
    }
if ($invoice->bookSpot) {
    foreach ($invoice->bookSpot->paymentlisting as $payment) {
        $payment->payment_status = $invoice->status;
        $payment->payment_completed = $invoice->status === 'paid';
    }
}
    
    return response()->json([
        'success' => true,
        'message' => 'Invoice data retrieved successfully.',
        'data' => $invoice,
    ], 200);
}
}