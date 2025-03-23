<?php

namespace App\Listeners;

use App\Events\InheritFolderPermissions;
use App\Models\Folder;
use App\Models\FolderPermission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ApplyInheritedPermissions
{
    /**
     * Create the event listener.
     */

    /**
     * Handle the event.
     */
    public function handle(InheritFolderPermissions $event)
    {
        $parentFolder = Folder::find($event->folderId);
        if (!$parentFolder) {
            return;
        }
        $folderIds = Folder::where('path', 'like', "{$parentFolder->path}/%")
            ->orWhere('id', $event->folderId)
            ->pluck('id')
            ->toArray();
        $parentPermissions = FolderPermission::where('folder_id', $event->folderId)->get();
        if ($parentPermissions->isEmpty())
        {
            return;
        }
        $data = [];
        foreach ($folderIds as $folderId) {
            foreach ($parentPermissions as $permission) {
                $exists = FolderPermission::where('user_id', $permission->user_id)
                    ->where('folder_id', $folderId)
                    ->where('permission', $permission->permission)
                    ->exists();
                if (!$exists) {
                    $data[] = [
                        'user_id' => $permission->user_id,
                        'folder_id' => $folderId,
                        'permission' => $permission->permission,
                    ];
                }
            }
        }
        if (!empty($data)) {
            FolderPermission::insert($data);
        }
    }
}
