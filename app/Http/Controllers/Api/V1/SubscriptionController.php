<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionModel as Subscription;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * List all subscription plans
     */
    public function index()
    {
          if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $subscriptions = Subscription::select([
            'id',
            'name',
            'number_of_staff',
            'number_of_admins',
            'number_of_agents',
            'number_of_apartments',
            'number_of_branches',
            'number_of_locations',
            'fee',
            'discount',
            'created_by_admin_id'
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Store a new subscription plan
     */
    public function store(Request $request)
    {
          if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $adminId = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'number_of_staff' => 'required|integer|min:0',
            'number_of_admins' => 'required|integer|min:0',
            'number_of_agents' => 'required|integer|min:0',
            'number_of_apartments' => 'required|integer|min:0',
            'number_of_branches' => 'required|integer|min:0',
            'number_of_locations' => 'required|integer|min:0',
            'fee' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $name = strtolower(trim($request->name));

        // Check duplicate plan name
        $exists = Subscription::whereRaw('LOWER(name) = ?', [$name])->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan already exists'
            ], 422);
        }

        $subscription = Subscription::create([
            'name' => strtolower($name),
            'number_of_staff' => $request->number_of_staff,
            'number_of_admins' => $request->number_of_admins,
            'number_of_agents' => $request->number_of_agents,
            'number_of_apartments' => $request->number_of_apartments,
            'number_of_branches' => $request->number_of_branches,
            'number_of_locations' => $request->number_of_locations,
            'fee' => $request->fee,
            'discount' => $request->discount ?? 0,
            'created_by_admin_id' => $adminId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan created successfully',
            'data' => $subscription
        ], 201);
    }

    /**
     * Show a specific subscription plan
     */
    public function show($id)
    {
          if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription
        ]);
    }

    /**
     * Update a subscription plan
     */
    public function update(Request $request, $id)
    {
          if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50',
            'number_of_staff' => 'sometimes|integer|min:0',
            'number_of_admins' => 'sometimes|integer|min:0',
            'number_of_agents' => 'sometimes|integer|min:0',
            'number_of_apartments' => 'sometimes|integer|min:0',
            'number_of_branches' => 'sometimes|integer|min:0',
            'number_of_locations' => 'sometimes|integer|min:0',
            'fee' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent duplicate name on update
        if ($request->has('name')) {
            $newName = strtolower(trim($request->name));

            $exists = Subscription::whereRaw('LOWER(name) = ?', [$newName])
                ->where('id', '!=', $subscription->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another subscription plan with this name already exists'
                ], 422);
            }

            $request->merge(['name' => $newName]);
        }

        $subscription->update($request->only([
            'name',
            'number_of_staff',
            'number_of_admins',
            'number_of_agents',
            'number_of_apartments',
            'number_of_branches',
            'number_of_locations',
            'fee',
            'discount'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully',
            'data' => $subscription
        ]);
    }

    /**
     * Delete a subscription plan
     */
    public function destroy($id)
    {
          if (!$this->is_system_admin($request)) {
            return $this->unauthorized();
        }

        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan deleted successfully'
        ]);
    }
    
     public function is_system_admin($request)
    {
        return (int) $request->user()->role_id === 1;
    }
    
    private function unauthorized()
    {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized access',
            'data' => []
        ], 403);
    }

}