<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ServiceManagementController extends Controller
{
    /**
     * Get service management data for a specific office.
     */
    public function index(Office $office): JsonResponse
    {
        try {
            $services = Service::where('office_id', $office->id)
                ->orderBy('service_name')
                ->get();

            $services->each(function (Service $service) {
                $this->syncUsageAndLockState($service);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'office' => [
                        'id' => $office->id,
                        'name' => $office->office_name,
                        'acronym' => $office->office_acronym,
                        'display_name' => $office->office_name . ' (' . $office->office_acronym . ')',
                    ],
                    'services' => $services->map(fn (Service $service) => $this->transformService($service->fresh())),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch service management data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch services. Please try again.',
            ], 500);
        }
    }

    /**
     * Create a new service under an office.
     */
    public function store(Request $request, Office $office): JsonResponse
    {
        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:150'],
            'service_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('services', 'service_code')->where(fn ($query) => $query->where('office_id', $office->id)),
            ],
            'service_description' => ['required', 'string'],
            'service_type' => ['required', Rule::in(['External', 'Internal'])],
            'classification' => ['nullable', Rule::in(['Simple', 'Complex', 'Highly_Technical'])],
            'is_free' => ['required', 'boolean'],
            'provides_assistance' => ['required', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            $service = Service::create([
                'office_id' => $office->id,
                'service_name' => $validated['service_name'],
                'service_code' => $validated['service_code'],
                'service_description' => $validated['service_description'],
                'service_type' => $validated['service_type'],
                'classification' => $validated['classification'] ?? 'Simple',
                'is_free' => $validated['is_free'],
                'provides_assistance' => $validated['provides_assistance'],
                'status' => 'active',
                'used_count' => 0,
                'is_locked' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully.',
                'data' => $this->transformService($service),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create service: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to create service. Please try again.',
            ], 500);
        }
    }

    /**
     * Update an existing service.
     */
    public function update(Request $request, Office $office, Service $service): JsonResponse
    {
        if ($service->office_id !== $office->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this office.',
            ], 404);
        }

        $this->syncUsageAndLockState($service);

        if ($service->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'This service is locked because it has been used in at least one survey. Create a new service instead.',
            ], 422);
        }

        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:150'],
            'service_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('services', 'service_code')
                    ->where(fn ($query) => $query->where('office_id', $office->id))
                    ->ignore($service->id),
            ],
            'service_description' => ['required', 'string'],
            'classification' => ['required', Rule::in(['Simple', 'Complex', 'Highly_Technical'])],
        ]);

        try {
            $service->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully.',
                'data' => $this->transformService($service->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update service: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to update service. Please try again.',
            ], 500);
        }
    }

    /**
     * Soft delete a service.
     */
    public function destroy(Office $office, Service $service): JsonResponse
    {
        if ($service->office_id !== $office->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this office.',
            ], 404);
        }

        $this->syncUsageAndLockState($service);

        if ($service->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'This service is locked because it has been used in at least one survey. Create a new service instead.',
            ], 422);
        }

        try {
            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete service: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete service. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle service is_free value.
     */
    public function toggleIsFree(Office $office, Service $service): JsonResponse
    {
        if ($service->office_id !== $office->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this office.',
            ], 404);
        }

        $this->syncUsageAndLockState($service);

        try {
            $service->update(['is_free' => !$service->is_free]);

            return response()->json([
                'success' => true,
                'message' => 'Service fee setting updated successfully.',
                'data' => $this->transformService($service->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle service is_free: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to update service fee setting. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle service status between active and inactive.
     */
    public function toggleStatus(Office $office, Service $service): JsonResponse
    {
        if ($service->office_id !== $office->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this office.',
            ], 404);
        }

        $this->syncUsageAndLockState($service);

        try {
            $service->update(['status' => $service->status === 'active' ? 'inactive' : 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Service status updated successfully.',
                'data' => $this->transformService($service->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle service status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to update service status. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle service provides_assistance value.
     */
    public function toggleProvidesAssistance(Office $office, Service $service): JsonResponse
    {
        if ($service->office_id !== $office->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this office.',
            ], 404);
        }

        $this->syncUsageAndLockState($service);

        try {
            $service->update(['provides_assistance' => !$service->provides_assistance]);

            return response()->json([
                'success' => true,
                'message' => 'Service assistance setting updated successfully.',
                'data' => $this->transformService($service->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle service provides_assistance: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to update service assistance setting. Please try again.',
            ], 500);
        }
    }

    private function syncUsageAndLockState(Service $service): void
    {
        $usedCount = $service->queueTransactions()
            ->whereHas('evaluationResponses')
            ->distinct('queue_transactions.id')
            ->count('queue_transactions.id');

        $shouldLock = $usedCount > 0;

        if ($service->used_count !== $usedCount || (bool) $service->is_locked !== $shouldLock) {
            $service->updateQuietly([
                'used_count' => $usedCount,
                'is_locked' => $shouldLock,
            ]);
        }
    }

    private function transformService(Service $service): array
    {
        return [
            'id' => $service->id,
            'office_id' => $service->office_id,
            'name' => $service->service_name,
            'code' => $service->service_code,
            'description' => $service->service_description,
            'service_type' => $service->service_type,
            'classification' => $service->classification,
            'is_free' => (bool) $service->is_free,
            'is_free_label' => $service->is_free ? 'Yes' : 'No',
            'provides_assistance' => (bool) $service->provides_assistance,
            'provides_assistance_label' => $service->provides_assistance ? 'Yes' : 'No',
            'status' => $service->status,
            'status_label' => $service->status === 'active' ? 'Active' : 'Inactive',
            'used_count' => (int) $service->used_count,
            'is_locked' => (bool) $service->is_locked,
            'lock_status' => $service->is_locked ? 'LOCKED' : 'UNLOCKED',
        ];
    }
}
