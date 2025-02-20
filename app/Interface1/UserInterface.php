<?php

namespace App\Interface1;
use Illuminate\Support\Facades\Request;
interface UserInterface
{
    public function getAllFileForUser();
    public function addFolder(\Illuminate\Http\Request $request);
    public function deleteFolder($id);
    public function rootWithFolderBelongsTo();
    public function childWithFolderBelongsTo();
    public function childRecursive();
}
