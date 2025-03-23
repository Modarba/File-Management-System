<?php

namespace App\Listeners;

use App\Events\FolderCreated;
use App\Models\FolderPermission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AssignParentPermissions
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }
    /**
     * Handle the event.
     */
    public function handle(FolderCreated $event)
    {
        $folder = $event->folder;
        if ($folder->parent_id) {
            $parentPermissions = FolderPermission::where('folder_id', $folder->parent_id)->get();
            foreach ($parentPermissions as $permission) {
                FolderPermission::create([
                    'user_id' => $permission->user_id,
                    'folder_id' => $folder->id,
                    'permission' => $permission->permission,
                ]);
            }
        }
    }
}
