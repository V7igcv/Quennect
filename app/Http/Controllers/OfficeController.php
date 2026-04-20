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
            $query = Office::active()
                ->orderBy('office_name');

            if (request()->boolean('has_internal_services')) {
                $query->whereHas('services', function ($q) {
                    $q->where('service_type', 'Internal');
                });
            }

            $offices = $query->get();

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
                        'logo' => $office->logo ? asset('storage/' . $office->logo) : null,
                        'map_image' => $this->getMapImageUrl($office->map_image) // CHANGED - simplified
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
                    'map_image' => $this->getMapImageUrl($office->map_image), // CHANGED - simplified
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

    /**
     * Helper function to get correct map image URL
     * 
     * @param string|null $path
     * @return string|null
     */
    private function getMapImageUrl(?string $path): ?string
    {
        // If no path, return null
        if (!$path) {
            return null;
        }
        
        // If it's already a full URL starting with http
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        
        // If it already starts with /storage/, return as is (no need to add another storage)
        if (str_starts_with($path, '/storage/')) {
            return asset($path);
        }
        
        // If it starts with storage/ (no leading slash), add slash
        if (str_starts_with($path, 'storage/')) {
            return asset('/' . $path);
        }
        
        // If it starts with maps/ or any other folder, add /storage/
        if (str_starts_with($path, 'maps/')) {
            return asset('storage/' . $path);
        }
        
        // Default: assume it's a filename in maps folder
        return asset('storage/maps/' . $path);
    }
}