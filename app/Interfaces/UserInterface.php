<?php

namespace App\Interfaces;
use Illuminate\Support\Facades\Request;
interface UserInterface
{
    public function getAllFileForUser();
    public function rootWithFolderBelongsTo();
    public function childWithFolderBelongsTo();
    public function childRecursive();
}
