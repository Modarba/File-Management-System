<?php

namespace App\Http\Controllers;
use App\Enums\HttpStatusCode;
use App\Models\Employee;
use App\Models\Folder;
use App\Models\User;
use App\Services\UserServices;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\SerializableClosure\Support\SelfReference;

class UserController extends Controller
{
    protected $userServices;
    public function __construct(UserServices $userServices)
    {
        $this->userServices=$userServices;
    }
    public function getAllFileForUser()
    {
        return $this->successResponse($this->userServices->getAllFileForUser(),'success',HttpStatusCode::SUCCESS->value);
    }
    public function rootBelongsToFolder()
    {
        return $this->successResponse($this->userServices->rootWithFolderBelongsTo(),'success',HttpStatusCode::SUCCESS->value);
    }
    public function childHasManyFolder()
    {
        return $this->successResponse($this->userServices->childHasManyFolder(),'success',HttpStatusCode::SUCCESS->value);
    }
    public function getChildRecursive()
    {
        return $this->successResponse($this->userServices->childRecursive(),'success',HttpStatusCode::SUCCESS->value);
    }
}
