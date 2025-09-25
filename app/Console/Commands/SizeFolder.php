<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\FolderPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
class SizeFolder extends Command
{
    protected $signature = 'folder:size {folder : Folder ID or path}';
    protected $description = 'Update the size of a folder and its descendants based on file sizes';
    public function handle()
    {
        $folderInput = $this->argument('folder');
        $folder = is_numeric($folderInput)
            ? Folder::find($folderInput, ['id', 'path', 'type', 'user_id'])
            : Folder::where('path', $folderInput)->where('user_id', auth()->id())->first(['id', 'path', 'type', 'user_id']);

        if (!$folder) {
            $this->error('Folder not found or unauthorized');
            return 1;
        }
        if (!$this->hasPermission(auth()->id(), $folder->id, 'write')) {
            $this->error('No permission to update this folder');
            return 1;
        }
        if ($folder->type !== 'folder') {
            $this->error('Cannot update size for a file');
            return 1;
        }
        $cacheKey = "folder_size_{$folder->id}";
        $cachedSize = Redis::get($cacheKey);
        if ($cachedSize !== null) {
            $folder->update(['size' => $cachedSize]);
            $this->info("Folder size updated from cache: {$cachedSize} bytes");
            return 0;
        }
        $size = Folder::where('type', 'file')
            ->where('path', 'like', $folder->path . '/%')
            ->sum('size');
        $folder->update(['size' => $size]);
        Redis::setex($cacheKey, 3600, $size);
        $this->updateAncestorSizes($folder->parent_id);
        $this->info("Folder size updated: {$size} bytes");
        return 0;
    }
    protected function updateAncestorSizes($parentId)
    {
        if (!$parentId) {
            return;
        }

        $parent = Folder::find($parentId, ['id', 'path', 'type']);
        if (!$parent || $parent->type !== 'folder') {
            return;
        }
        $cacheKey = "folder_size_{$parent->id}";
        $cachedSize = Redis::get($cacheKey);
        if ($cachedSize !== null) {
            $parent->update(['size' => $cachedSize]);
            return;
        }
        $size = Folder::where('type', 'file')
            ->where('path', 'like', $parent->path . '/%')
            ->sum('size');
        $parent->update(['size' => $size]);
        Redis::setex($cacheKey, 3600, $size);
        $this->updateAncestorSizes($parent->parent_id);
    }
    protected function hasPermission($userId, $folderId, $permission)
    {
        $cacheKey = "permissions_{$userId}_{$folderId}_{$permission}";
        $cachedPermission = Redis::get($cacheKey);
        if ($cachedPermission !== null) {
            return (bool) $cachedPermission;
        }
        $folder = Folder::find($folderId, ['user_id']);
        if ($folder && $folder->user_id == $userId) {
            Redis::setex($cacheKey, 3600, true);
            return true;
        }
        $hasPermission = FolderPermission::where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('permission', $permission)
            ->exists();
        Redis::setex($cacheKey, 3600, $hasPermission);
        return $hasPermission;
    }
}
