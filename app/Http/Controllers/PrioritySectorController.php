<?php

namespace App\Http\Controllers;

use App\Models\PrioritySector;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PrioritySectorController extends Controller
{
    /**
     * Get all priority sectors
     * Used by: Kiosk, Frontdesk, Superadmin
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $sectors = PrioritySector::orderBy('sector_name')
                ->get(['id', 'sector_name']);

            return response()->json([
                'success' => true,
                'data' => $sectors
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch priority sectors: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch priority sectors. Please try again.'
            ], 500);
        }
    }
}