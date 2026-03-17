<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    private const ALLOWED_ROLES = ['SUPERADMIN', 'OFFICE FRONTDESK'];

    /**
     * Get users for the superadmin user management table.
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::with(['role:id,name', 'office:id,office_name,office_acronym'])
                ->orderBy('username')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users->map(fn (User $user) => $this->transformUser($user)),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user management list: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch users. Please try again.',
            ], 500);
        }
    }

    /**
     * Create a new user account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(self::ALLOWED_ROLES)],
            'office_id' => [
                'nullable',
                'integer',
                Rule::exists('offices', 'id')->where(function ($query) {
                    $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
        ]);

        if ($validated['role'] === 'OFFICE FRONTDESK' && empty($validated['office_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => [
                    'office_id' => ['The office field is required when the role is OFFICE FRONTDESK.'],
                ],
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'username' => $validated['username'],
                'password_hash' => Hash::make($validated['password']),
                'role_id' => $this->getRoleId($validated['role']),
                'office_id' => $validated['role'] === 'OFFICE FRONTDESK' ? $validated['office_id'] : null,
                'last_login_at' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => $this->transformUser($user->load(['role:id,name', 'office:id,office_name,office_acronym'])),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to create user. Please try again.',
            ], 500);
        }
    }

    /**
     * Update an existing user account.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(self::ALLOWED_ROLES)],
            'office_id' => [
                'nullable',
                'integer',
                Rule::exists('offices', 'id')->where(function ($query) {
                    $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
        ]);

        if ($validated['role'] === 'OFFICE FRONTDESK' && empty($validated['office_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => [
                    'office_id' => ['The office field is required when the role is OFFICE FRONTDESK.'],
                ],
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payload = [
                'username' => $validated['username'],
                'role_id' => $this->getRoleId($validated['role']),
                'office_id' => $validated['role'] === 'OFFICE FRONTDESK' ? $validated['office_id'] : null,
            ];

            if (!empty($validated['password'])) {
                $payload['password_hash'] = Hash::make($validated['password']);
            }

            $user->update($payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => $this->transformUser($user->load(['role:id,name', 'office:id,office_name,office_acronym'])),
            ]);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to update user. Please try again.',
            ], 500);
        }
    }

    /**
     * Get active offices for the user management office dropdown.
     */
    public function offices(): JsonResponse
    {
        try {
            $offices = Office::active()
                ->orderBy('office_name')
                ->get(['id', 'office_name', 'office_acronym']);

            return response()->json([
                'success' => true,
                'data' => $offices->map(function (Office $office) {
                    return [
                        'id' => $office->id,
                        'name' => $office->office_name,
                        'acronym' => $office->office_acronym,
                        'display_name' => $office->office_name . ' (' . $office->office_acronym . ')',
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user management offices: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch offices. Please try again.',
            ], 500);
        }
    }

    private function getRoleId(string $roleName): int
    {
        $roleId = Role::where('name', $roleName)->value('id');

        if (!$roleId) {
            throw new \InvalidArgumentException("Role {$roleName} is not configured.");
        }

        return $roleId;
    }

    private function transformUser(User $user): array
    {
        $isFrontdesk = $user->role?->name === 'OFFICE FRONTDESK';
        $office = $isFrontdesk && $user->office
            ? [
                'id' => $user->office->id,
                'name' => $user->office->office_name,
                'acronym' => $user->office->office_acronym,
                'display_name' => $user->office->office_name . ' (' . $user->office->office_acronym . ')',
            ]
            : null;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'role' => [
                'id' => $user->role?->id,
                'name' => $user->role?->name,
                'label' => $isFrontdesk ? 'Office Frontdesk' : 'Superadmin',
            ],
            'office_id' => $office['id'] ?? null,
            'office' => $office,
        ];
    }
}