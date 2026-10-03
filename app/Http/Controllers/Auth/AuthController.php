<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
     public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'role'     => 'customer',
        ]);

        $token = JWTAuth::fromUser($user);
        return response()->json(compact('user', 'token'), 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid Credentials'], 401);
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            auth()->logout(); // invalidate this attempt
            return response()->json([
                'success' => false,
                'message' => 'This account is inactive. Please contact support.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ]);

        // return response()->json(compact('token'));
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        // return response()->json(['token' => auth()->refresh()]);
        return response()->json(['token' => JWTAuth::refresh(JWTAuth::getToken())]);
    }

    public function requestRegisterOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $code = (string) random_int(100000, 999999);

        EmailOtp::where('email', $request->email)
            ->where('purpose', 'register')
            ->whereNull('consumed_at')
            ->delete();

        EmailOtp::create([
            'email'      => $request->email,
            'code'       => $code,
            'purpose'    => 'register',
            'payload'    => [
                'name'     => $request->name,
                'password' => bcrypt($request->password),
            ],
            'expires_at' => now()->addMinutes(10),
        ]);

        // Simple mail (Brevo SMTP)
        Mail::raw(
            "Your Kroeung verification code is: {$code}\nIt expires in 10 minutes.",
            function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Your verification code');
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email.',
        ]);
    }

    public function verifyRegisterOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $otp = EmailOtp::where('email', $request->email)
            ->where('purpose', 'register')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$otp || !hash_equals($otp->code, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code.',
            ], 422);
        }

        if (User::where('email', $request->email)->exists()) {
            return response()->json(['success' => false, 'message' => 'Email already registered.'], 422);
        }

        $user = User::create([
            'name'     => $otp->payload['name'],
            'email'    => $request->email,
            'password' => $otp->payload['password'], // already hashed
            'role'     => 'customer',
            'status'   => 'active',
        ]);

        $otp->update(['consumed_at' => now()]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }
}
