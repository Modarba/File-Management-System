<?php

namespace App\Http\Controllers;
use App\Models\Folder;
use App\Models\User;
use App\Services\UserServices;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\SerializableClosure\Support\SelfReference;

class UserController extends Controller
{
    use ApiResponse;
    public function getAllFileForUser(UserServices $services)
    {
        return $services->getAllFileForUser();
    }
    public function rootBelongsToFolder( UserServices $services)
    {
        return $services->rootWithFolderBelongsTo();
    }
    public function childHasManyFolder(UserServices $services)
    {
        return $services->childHasManyFolder();
    }
    public function deleteFolder(UserServices $services,$id)
    {
        return $services->deleteFolder($id);
    }
    public function addFolder(UserServices $services,Request $request)
    {
        return $services->addFolder($request);
    }
    public function getChildRecursive(UserServices $services)
    {
        return $services->childRecursive();
    }
}
