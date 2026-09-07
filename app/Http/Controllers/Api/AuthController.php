<?php

namespace App\Http\Controllers\Api;

use \App\Http\Resources\Api\UserResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\V1\ApiResponse;
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
            $result = $this->authService->register($request->validated());

            return self::successResponse(
                'Registration successful',
                [
                    'user' =>new UserResource($result['user']),
                    'token' => $result['token'],
                ],
                201
            );
        

    }

    /**
     * Login user
     */
public function login(LoginRequest $request): JsonResponse
{
    $result = $this->authService->login(
        $request->input('login'),
        $request->input('password')
    );

    return self::successResponse(
        'Login successful',
        [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ],
        200
    );
}
    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {

            $this->authService->logout($request->user());

            return self::successResponse("Logout successfull",null);

    }

    /**
     * Forgot password - send OTP
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {

            $this->authService->forgotPassword($request->input('identifier'));

            return self::successResponse("OTP sent successfully . Please check your email",null);
        
    }
    /**
     * Verify OTP
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {

            $isValid = $this->authService->verifyOtp(
                $request->input('identifier'),
                $request->input('otp')
            );
            if (! $isValid) {
                return self::errorResponse("Invalid or expired OTP", null, 400);
            }

            return self::successResponse("OTP verified successfully", nullOrEmptyString());

    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
            $this->authService->resetPassword(
                $request->input('identifier'),
                $request->input('otp'),
                $request->input('password')
            );

            return self::successResponse("Password reset successfully", null);

        
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return self::successResponse(
            "User retrieved successfully",
            [
                'user' => new UserResource($request->user),
            ]
        );
    }

    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
            $this->authService->deleteAccount($request->user());

        return self::successResponse("Account deleted successfully", null);
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {

            $this->authService->changePassword($request->user(),$request->input('password'));

            // Revoke all tokens except the current one (optional - for security)
            // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

            return self::successResponse("Password changed successfully", null);

        
    }
}
