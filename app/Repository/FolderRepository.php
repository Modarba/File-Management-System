<?php

namespace App\Repository;

use App\Interfaces\FolderInterface;
use App\Models\Folder;
use Illuminate\Support\Facades\Auth;

class FolderRepository implements FolderInterface
{
    public function createFolder(array $data)
    {
        return Folder::create($data);
    }
    public function getRootFolder($id)
    {
        // TODO: Implement getFolderRoot() method.
        return  Folder::where('user_id',Auth::id())->first();
    }

    public function deleteFolder($id)
    {
        // TODO: Implement deleteFolder() method.
        return Folder::where('user_id',Auth::id())->where('id',$id)->delete();
    }
    public function findById(int $id)
    {
        // TODO: Implement findById() method.
        return Folder::find($id);
    }
    public function updateFolder($id,$data)
    {
            return Folder::where('user_id',Auth::id())->where('id',$id)->update($data);
    }
}
