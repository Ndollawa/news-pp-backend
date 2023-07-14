<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Traits\HttpResponses;
use App\Http\Requests\UserRegistrationRequest;
use App\Http\Requests\UserLoginRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;


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

    public function refresh()
    {
        $accessToken = Auth::refresh();
//          $user = Auth::user();
//   // Create JWTs
//   $user = Auth::user(); 
//   $customClaims = [
//       'id' => $user->_id,
//       'email' => $user->email,
//       'name' => $user->name,
//       'userProfile' => $user->profile,
//   ];
  
//   $accessToken = JWTAuth::claims($customClaims)->fromUser($user);
//   // Create secure cookie with new accessToken
$cookie = cookie('jwt', $accessToken, 1440,null,null,true, true,'None'); // Create a secure cookie
        return $this->success(
            ['token' => $accessToken])->cookie($cookie);
    }
}