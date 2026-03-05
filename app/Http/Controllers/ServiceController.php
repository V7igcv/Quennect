<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    /**
     * Get services for a specific office
     * Used by: Kiosk, Frontdesk, Superadmin
     * 
     * @param int $officeId
     * @return JsonResponse
     */
    public function getByOffice(int $officeId): JsonResponse
    {
        try {
            $services = Service::where('office_id', $officeId)
                ->orderBy('service_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->service_name,
                        'code' => $service->service_code,
                        'display_name' => $service->display_name,
                        'description' => $service->service_description,
                        'office_id' => $service->office_id
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch services: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch services. Please try again.'
            ], 500);
        }
    }

    /**
     * Get single service details
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = Service::with('office')->find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $service->id,
                    'name' => $service->service_name,
                    'code' => $service->service_code,
                    'display_name' => $service->display_name,
                    'description' => $service->service_description,
                    'office' => [
                        'id' => $service->office->id,
                        'name' => $service->office->office_name,
                        'acronym' => $service->office->office_acronym
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch service details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch service details. Please try again.'
            ], 500);
        }
    }
}