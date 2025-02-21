<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use App\Interfaces\AuthenticationInterface;
use App\Models\User;
use App\Repository\AuthenticationRepository;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;

class AuthenticationServices
{
    protected $authenticationRepository;
    public function __construct(AuthenticationRepository $authentication)
    {
        $this->authenticationRepository = $authentication;
    }
    public function registerForUser($data)
    {
        $register=$this->authenticationRepository->register($data);
        $token=$register->createToken('my-Token')->plainTextToken;
        return ['token'=>$token];
    }
    public function loginForUser($data)
    {
        $user = $this->authenticationRepository->findByEmail($data['email']);
        if (!$user&& !Hash::check('password', $user->password)) {
            return ['error',HttpStatusCode::UNAUTHORIZED->value];
        }
        $token=$user->createToken('my-Token')->plainTextToken;
        return ['token'=>$token];

    }
}
