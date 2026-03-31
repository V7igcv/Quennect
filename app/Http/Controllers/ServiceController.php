<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    /**
     * Get EXTERNAL services for a specific office
     * Used by: Kiosk
     * 
     * @param int $officeId
     * @return JsonResponse
     */
    public function getByOffice(int $officeId): JsonResponse
    {
        try {
            $services = Service::where('office_id', $officeId)
                ->where('service_type', 'External')  // ✅ service_type ang column
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
                        'office_id' => $service->office_id,
                        'service_type' => $service->service_type  // ✅ isama sa response
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
     * Get INTERNAL services for a specific office
     * Used by: Internal Transactions Module (future)
     * 
     * @param int $officeId
     * @return JsonResponse
     */
    public function getInternalServices(int $officeId): JsonResponse
    {
        try {
            $services = Service::where('office_id', $officeId)
                ->where('service_type', 'Internal')  // ✅ internal services
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
                        'office_id' => $service->office_id,
                        'service_type' => $service->service_type
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch internal services: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch internal services. Please try again.'
            ], 500);
        }
    }

    /**
     * Get ALL services for a specific office
     * Used by: Superadmin (for management)
     * 
     * @param int $officeId
     * @return JsonResponse
     */
    public function getAllServices(int $officeId): JsonResponse
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
                        'office_id' => $service->office_id,
                        'service_type' => $service->service_type
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch all services: ' . $e->getMessage());
            
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
                    'service_type' => $service->service_type,
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