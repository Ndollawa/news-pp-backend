<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\v1\Profile;
use App\Traits\v1\HttpResponses;
use App\Http\Requests\v1\UserRegistrationRequest;
use App\Http\Requests\v1\UserLoginRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;


class AuthController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['login','register']]);
       
    
    // }
        use HttpResponses;

    public function register(UserRegistrationRequest $request)
    {
        $request->validated($request->all());
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->save();
        
        // Create a new profile for the user
 
        $profile = new Profile([
            'roles' => [1003]
        ]);
        $user->profile()->save($profile);
        // Create JWTs
        $customClaims = [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'profile' => $user->profile
            ]
        ];

        $accessToken = JWTAuth::claims($customClaims)->fromUser($user);
        // Create secure cookie with new accessToken
            
        $cookie = cookie('jwt', $accessToken, 1440,null,null,true, true,'None'); // Create a secure cookie
        return $this->success([
                // 'user' => $user,
                'authorisation' => [
                    'token' => $accessToken,
                    'type' => 'bearer',
                ]
                ],
                'Registration successful.',
                201)->cookie($cookie);

    }

public function login(UserLoginRequest $request)
    {
        $request->validated($request->all());
           // Check for user in the DB
        
           if ( !$token = Auth::attempt($request->only('email', 'password'))) {
              return $this->error(null,'Invalid credentials',401);
           }
     
            // Create JWTs
            $user = Auth::user(); 
            $customClaims = [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'profile' => $user->profile
                ]
            ];

            $accessToken = JWTAuth::claims($customClaims)->fromUser($user);
            // Create secure cookie with new accessToken
                
            $cookie = cookie('jwt', $accessToken, 1440,null,null,true, true,'None'); // Create a secure cookie
            return $this->success([
                    //   'user' => $user,
                    'authorisation' => [
                        'token' => $accessToken,
                        'type' => 'bearer',
                    ]
                    ],
                    'Login successful.',
                    201)->cookie($cookie);
                }

    public function logout()
    {
        Auth::logout();
        return $this->success('','Successfully logged out');
    }

    public function refresh(Request $request)
    {

        $jwtToken = $request->cookie('jwt');
        return($jwtToken);
                if ($jwtToken) {
                    try {
                        $user = JWTAuth::parseToken()->authenticate();
        
                        if ($user) {
                            // Set the authenticated user in the Laravel Auth facade
                            Auth::setUser($user);
                        }
                    } catch (\Exception $e) {
                        // Handle token validation errors
                        return response()->json(['message' => 'Unauthorized'], 401);
                    }
        $accessToken = Auth::refresh();
                }
//   // Create secure cookie with new accessToken
$cookie = cookie('jwt', $accessToken, 1440,null,null,true, true,'None'); // Create a secure cookie
        return $this->success(
            ['token' => $accessToken])->cookie($cookie);
    }
}