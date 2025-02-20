<?php

namespace App\Repository;
use App\Interface1\UserInterface;
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
    $folder=Folder::where('user_id',Auth::id())->with('child')->get();
    return $this->successResponse([$folder,'All File'],201);
}
public function addFolder(\Illuminate\Http\Request $request)
{
    $folder=Folder::where('user_id',Auth::id())->first();
    $file=$request->file('files');
    $file1=time().'_'.$file->getClientOriginalExtension();
    if (!$folder)
    {
        $request->validate([
            'name'=>'required',
           'files'=>'required',
        ]);
        if ($request->has('parent_id'))
        {
            return  $this->errorResponse(['You cant add parent_id'],401);
        }
        //root
        $root=Folder::create(
            [
                'name'=>$request->name,
                'user_id'=>Auth::id(),
                'files'=>$file1,
            ]
        );
         return $this->successResponse([$root,'Successfully'],201);
    }
    else{
        $request->validate([
           'parent_id'=>'required|integer|exists:folders,id',
            'name'=>'required',
            'files'=>'required',
        ]);
        //sub
        $folder1=Folder::create([
                'name'=>$request->name,
                'user_id'=>Auth::id(),
                'files'=>$file1,
                'parent_id'=>$request->parent_id
            ]
        );
        return $this->successResponse([$folder1,'Successfully'],201);
    }

}
public function rootWithFolderBelongsTo()
{
    $folder=Folder::with('root')->where('user_id',Auth::id())->get();
    return $this->successResponse(['data'=>$folder,'Success'],201);
}
public function deleteFolder($id)
{
    // TODO: Implement DeleteFolder() method.
    $folder=Folder::where('id',$id)->delete();
    return $this->successResponse(['','Successfully'],201);
}
public function childRecursive()
{
    // TODO: Implement childRecursive() method.
    $folder=Folder::with('childRecursive')->where('user_id',Auth::id())->get();
    return $this->successResponse([$folder,'childRecursive'],201);
}

public function childWithFolderBelongsTo()
{
    $child=Folder::with('child')->where('user_id',Auth::id())->get();
    return $this->successResponse(['data'=>$child,'Success'],201);
}
}
