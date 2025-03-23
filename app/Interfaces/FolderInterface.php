<?php

namespace App\Interfaces;

interface FolderInterface
{
    public function getRootFolder();
    public function createFolder(array $data);
    public function deleteFolder( int $id);
    public function updateFolder(int $id,array $data);
    public function findById(int $id);

}
