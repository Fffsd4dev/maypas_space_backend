<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\CheckRentExpiration;

class Test extends Controller
{
    //
    public function testRentExpirationJob(Request $request)
    {
        CheckRentExpiration::dispatch();

        return response()->json(['message' => 'CheckRentExpiration job dispatched successfully.']);
    }
}
