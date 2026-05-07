<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTracker;
use App\Models\SubscriptionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubscriptionCycleController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Public Subscription (Estate Manager)
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
      
        $estateManagerId = app('estateManager')->id;

        return $this->processSubscription($request, $estateManagerId);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Subscription
    |--------------------------------------------------------------------------
    */

    public function subscribe_by_admin(Request $request)
    {
        if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->all(), [
            'estate_manager_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        return $this->processSubscription(
            $request,
            $request->estate_manager_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Core Subscription Processor
    |--------------------------------------------------------------------------
    */

    private function processSubscription(Request $request, int $estateManagerId)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:subscription_models,id',
            'start_date' => 'nullable|date',
            'number_months' => 'required|integer|min:1|max:12',
            'fee' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($validated, $estateManagerId) {

            $existing = SubscriptionTracker::where('estate_manager_id', $estateManagerId)
                ->whereIn('status', ['active', 'suspended'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Active or suspended subscription already exists.'
                ], 409);
            }

            $plan = SubscriptionModel::select('fee', 'discount')
                ->findOrFail($validated['plan_id']);

            $baseAmount = $plan->fee * $validated['number_months'];
            $discountAmount = ($plan->discount / 100) * $baseAmount;
            $expectedAmount = round($baseAmount - $discountAmount, 2);

            if ($validated['fee'] < $expectedAmount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Incomplete payment.',
                    'data' => ['expected_amount' => $expectedAmount]
                ], 422);
            }

            $startDate = $validated['start_date']
                ? Carbon::parse($validated['start_date'])
                : now();

            $endDate = $startDate->copy()->addMonths($validated['number_months']);

            $subscription = SubscriptionTracker::create([
                'plan_id' => $validated['plan_id'],
                'estate_manager_id' => $estateManagerId,
                'created_by_admin_id' => auth()->id(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Subscription created successfully.',
                'data' => ['subscription' => $subscription],
            ], 201);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Suspension Management
    |--------------------------------------------------------------------------
    */

    public function suspend(Request $request, $id)
    {
        if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $subscription = SubscriptionTracker::findOrFail($id);

        if ($subscription->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Only active subscriptions can be suspended.'
            ], 422);
        }

        $subscription->update(['status' => 'suspended']);

        return response()->json([
            'status' => true,
            'message' => 'Subscription suspended successfully.'
        ]);
    }

    public function lift_suspension_by_admin(Request $request)
    {
        if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->all(), [
            'subscription_tracker_id' => 'required|integer|exists:subscription_trackers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subscription = SubscriptionTracker::findOrFail($request->subscription_tracker_id);

        if ($subscription->status !== 'suspended') {
            return response()->json([
                'status' => false,
                'message' => 'Subscription is not suspended.'
            ], 422);
        }

        $subscription->update(['status' => 'active']);

        return response()->json([
            'status' => true,
            'message' => 'Suspension lifted successfully.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Subscription
    |--------------------------------------------------------------------------
    */

    public function cancel(Request $request, $id)
    {
        if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $subscription = SubscriptionTracker::findOrFail($id);

        if ($subscription->status === 'suspended') {
            return response()->json([
                'status' => false,
                'message' => 'Contact super admin to lift suspension first.'
            ], 422);
        }

        $subscription->update(['status' => 'cancelled']);

        return response()->json([
            'status' => true,
            'message' => 'Subscription cancelled successfully.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Current Subscription
    |--------------------------------------------------------------------------
    */

    public function current()
    {
        $estateManagerId = app('estateManager')->id;

        $subscription = SubscriptionTracker::where('estate_manager_id', $estateManagerId)
            ->latest('id')
            ->first();

        if ($subscription &&
            $subscription->status === 'active' &&
            now()->gt($subscription->end_date)) {

            $subscription->update(['status' => 'expired']);
        }

        return response()->json([
            'status' => true,
            'data' => $subscription
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'subscriptions' => SubscriptionTracker::latest('id')->paginate(50)
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function unauthorized()
    {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized access',
            'data' => []
        ], 403);
    }

    public function is_system_admin($request)
    {
        return (int) $request->user()->role_id === 1;
    }
}