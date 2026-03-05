<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OfficeController extends Controller
{
    /**
     * Get all active offices
     * Used by: Kiosk, Superadmin
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $offices = Office::active()
                ->orderBy('office_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $offices->map(function ($office) {
                    return [
                        'id' => $office->id,
                        'name' => $office->office_name,
                        'acronym' => $office->office_acronym,
                        'display_name' => $office->office_name . ' (' . $office->office_acronym . ')',
                        'description' => $office->office_description,
                        'is_active' => $office->is_active,
                        'logo' => $office->logo ? asset('storage/' . $office->logo) : null
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch offices: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch offices. Please try again.'
            ], 500);
        }
    }

    /**
     * Get single office details
     * Used by: Kiosk, Frontdesk, Superadmin
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $office = Office::with(['services' => function($query) {
                $query->orderBy('service_name');
            }])->find($id);

            if (!$office) {
                return response()->json([
                    'success' => false,
                    'message' => 'Office not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $office->id,
                    'name' => $office->office_name,
                    'acronym' => $office->office_acronym,
                    'display_name' => $office->display_name,
                    'description' => $office->office_description,
                    'is_active' => $office->is_active,
                    'logo' => $office->logo ? asset('storage/' . $office->logo) : null,
                    'services' => $office->services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->service_name,
                            'code' => $service->service_code,
                            'display_name' => $service->display_name
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch office details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch office details. Please try again.'
            ], 500);
        }
    }
}