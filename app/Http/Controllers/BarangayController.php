<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BarangayController extends Controller
{
    /**
     * Get all barangays
     * Used by: Kiosk, Frontdesk (evaluation form)
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $barangays = Barangay::orderBy('barangay_name')
                ->get(['id', 'barangay_name']);

            return response()->json([
                'success' => true,
                'data' => $barangays
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch barangays: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch barangays. Please try again.'
            ], 500);
        }
    }
}