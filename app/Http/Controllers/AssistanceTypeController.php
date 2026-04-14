<?php

namespace App\Http\Controllers;

use App\Models\AssistanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AssistanceTypeController extends Controller
{
    /**
     * Get all assistance types for a specific service
     * 
     * @param int $serviceId The service ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByService($serviceId)
    {
        try {
            $assistanceTypes = AssistanceType::where('service_id', $serviceId)
                ->orderBy('assistance_name')
                ->get(['id', 'assistance_name']);

            return response()->json([
                'success' => true,
                'data' => $assistanceTypes
            ]);

        } catch (\Exception $e) {
            Log::error('Get assistance types error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching assistance types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
