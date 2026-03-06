<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    // /**
    //  * Maximum login attempts per minute
    //  */
    // protected $maxAttempts = 10;

    /**
     * User Login
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // Rate limiting
        $key = 'login-attempts:' . $request->ip();
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user
        $user = User::with(['role', 'office'])
            ->where('username', $request->username)
            ->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // Check if this is the only front desk for the office (business rule enforcement)
        if ($user->isFrontdesk()) {
            $existingActiveSession = User::where('office_id', $user->office_id)
                ->where('id', '!=', $user->id)
                ->whereHas('tokens', function ($query) {
                    $query->where('last_used_at', '>', now()->subHours(1));
                })
                ->exists();

            if ($existingActiveSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another front desk user is already logged in for this office.'
                ], 403);
            }
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token', [
            $user->isSuperadmin() ? 'superadmin' : 'frontdesk'
        ])->plainTextToken;

        // Prepare response
        $responseData = [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role->name,
                    'office' => $user->office ? [
                        'id' => $user->office->id,
                        'name' => $user->office->office_name,
                        'acronym' => $user->office->office_acronym,
                    ] : null,
                ],
                'token' => $token,
            ]
        ];

        return response()->json($responseData);
    }

    /**
     * User Logout
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'office']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role->name,
                'office' => $user->office ? [
                    'id' => $user->office->id,
                    'name' => $user->office->office_name,
                    'acronym' => $user->office->office_acronym,
                ] : null,
                'last_login' => $user->last_login_at,
            ]
        ]);
    }

    /**
     * Verify token validity
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'data' => [
                'user' => $request->user()->username,
                'role' => $request->user()->role->name,
            ]
        ]);
    }
}
