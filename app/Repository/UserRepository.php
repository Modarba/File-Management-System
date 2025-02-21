<?php

namespace App\Repository;
use App\Interfaces\UserInterface;
use App\Models\Folder;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class UserRepository implements UserInterface
{
use ApiResponse;
public function getAllFileForUser()
{
    // TODO: Implement GetAllFileForUser() method.
     return Folder::where('user_id',Auth::id())->with('child')->get();
}
public function rootWithFolderBelongsTo()
{
    return Folder::with('root')->where('user_id',Auth::id())->get();
}
public function childRecursive()
{
    // TODO: Implement childRecursive() method.
     return Folder::with('childRecursive')->where('user_id',Auth::id())->get();
}
public function childWithFolderBelongsTo()
{
     return Folder::with('child')->where('user_id',Auth::id())->get();

}
}
