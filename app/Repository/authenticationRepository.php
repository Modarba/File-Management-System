<?php

namespace App\Repository;

use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use App\Interface1\authenticationInterface;
use App\Models\Folder;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class authenticationRepository implements authenticationInterface
{
    use ApiResponse;
    public function register(userRegisterRequest $request)
    {
        // TODO: Implement register() method.
        $reqister=User::create(
            [
                'name'=>$request->name,
                'email'=>$request->email,
                'password'=>Hash::make($request->password),
                ]
        );
        $token=$reqister->createToken('my-Token')->plainTextToken;
        return  $this->successResponse([$reqister,$token],201);
    }
    public function login(userLoginRequest $request)
    {
        $user = User::where('email',$request->email)->first();
        if(!$user || !Hash::check($request->password,$user->password)){
            return  $this->errorResponse('','Invalid',401);
        }
        $token = $user->createToken($user->name.'-AuthToken')->plainTextToken;
        return $this->successResponse($token,'Welcome',201);
    }

}
