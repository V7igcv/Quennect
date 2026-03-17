<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OfficeManagementController extends Controller
{
    /**
     * Get offices for superadmin Office Management cards.
     */
    public function index(): JsonResponse
    {
        try {
            $offices = Office::query()
                ->orderBy('office_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $offices->map(fn (Office $office) => $this->transformOffice($office)),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch office management list: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch offices. Please try again.',
            ], 500);
        }
    }

    /**
     * Create a new office for Office Management.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:offices,office_name'],
            'acronym' => ['required', 'string', 'max:20', 'unique:offices,office_acronym'],
            'description' => ['required', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $logoPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            $office = Office::create([
                'office_name' => $validated['name'],
                'office_acronym' => $validated['acronym'],
                'office_description' => $validated['description'],
                'logo' => $logoPath,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Office created successfully.',
                'data' => $this->transformOffice($office),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            Log::error('Failed to create office: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to create office. Please try again.',
            ], 500);
        }
    }

    /**
     * Update an existing office for Office Management.
     */
    public function update(Request $request, Office $office): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('offices', 'office_name')->ignore($office->id)],
            'acronym' => ['required', 'string', 'max:20', Rule::unique('offices', 'office_acronym')->ignore($office->id)],
            'description' => ['required', 'string'],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $newLogoPath = null;
        $oldLogoPath = $office->logo;

        try {
            DB::beginTransaction();

            if ($request->hasFile('logo')) {
                $newLogoPath = $request->file('logo')->store('logos', 'public');
            }

            $office->update([
                'office_name' => $validated['name'],
                'office_acronym' => $validated['acronym'],
                'office_description' => $validated['description'],
                'is_active' => $validated['status'] === 'Active',
                'logo' => $newLogoPath ?? $office->logo,
            ]);

            DB::commit();

            if ($newLogoPath && $oldLogoPath && $oldLogoPath !== $newLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Office updated successfully.',
                'data' => $this->transformOffice($office->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            Log::error('Failed to update office: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to update office. Please try again.',
            ], 500);
        }
    }

    /**
     * Soft delete an office for Office Management.
     */
    public function destroy(Office $office): JsonResponse
    {
        try {
            $office->delete();

            return response()->json([
                'success' => true,
                'message' => 'Office deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete office: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete office. Please try again.',
            ], 500);
        }
    }

    private function transformOffice(Office $office): array
    {
        return [
            'id' => $office->id,
            'name' => $office->office_name,
            'acronym' => $office->office_acronym,
            'description' => $office->office_description,
            'is_active' => (bool) $office->is_active,
            'status' => $office->is_active ? 'Active' : 'Inactive',
            'logo' => $office->logo ? asset('storage/' . $office->logo) : null,
        ];
    }
}
