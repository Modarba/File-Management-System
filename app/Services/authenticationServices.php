<?php

namespace App\Services;

use App\Http\Requests\userLoginRequest;
use App\Http\Requests\userRegisterRequest;
use App\Interface1\authenticationInterface;

class authenticationServices
{

    protected $authenticationRepository;
    public function __construct(authenticationInterface $authentication)
    {
        $this->authenticationRepository = $authentication;
    }
    public function registerForUser(userRegisterRequest $request)
    {
        return $this->authenticationRepository->register($request);
    }
    public function loginForUser(userLoginRequest $request)
    {
        return $this->authenticationRepository->login($request);
    }
}
