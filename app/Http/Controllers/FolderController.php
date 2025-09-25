<?php
namespace App\Http\Controllers;
use App\Enums\HttpStatusCode;
use App\Models\Folder;
use App\Models\FolderPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use ZipArchive;
class FolderController extends Controller
{
    public function getPath(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $cacheKey = 'folder_path_' . md5($request->path);
        $folders = Redis::get($cacheKey);
        if ($folders === null) {
            $folders = Folder::where('path', 'like', $request->path . '%')
                ->where('user_id', Auth::id())
                ->get(['id', 'name', 'path', 'type', 'size'])
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($folders));
        } else {
            $folders = json_decode($folders, true);
        }
        if (empty($folders)) {
            return $this->errorResponse('No data found', 'error', HttpStatusCode::NOTFOUND->value);
        }
        return $this->successResponse($folders, 'success', HttpStatusCode::SUCCESS->value);
    }
    public function givePermission(Request $request, $userId, $folderId, $permission)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'folder_id' => 'required|exists:folders,id',
            'permission' => 'required|in:read,write,delete',
        ]);
        if (!$this->hasPermission(Auth::id(), $folderId, 'write')) {
            return $this->errorResponse('No permission to grant access', 'error', HttpStatusCode::FORBIDDEN->value);
        }
        $folderIds = Folder::where('path', 'like', "%$folderId%")
            ->orWhere('id', $folderId)
            ->pluck('id')
            ->toArray();
        $existingPermissions = FolderPermission::where('user_id', $userId)
            ->whereIn('folder_id', $folderIds)
            ->where('permission', $permission)
            ->exists();
        if ($existingPermissions) {
            return $this->errorResponse('Permission already exists', 'error', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
        $data = array_map(fn($id) => [
            'user_id' => $userId,
            'folder_id' => $id,
            'permission' => $permission,
            'created_at' => now(),
            'updated_at' => now(),
        ], $folderIds);
        FolderPermission::insert($data);
        Redis::setex("permissions_{$userId}_{$folderId}_{$permission}", 3600, true);
        return $this->successResponse(null, 'Permission granted successfully', HttpStatusCode::SUCCESS->value);
    }
    public function deletePermission(Request $request, $userId, $folderId, $permission)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'folder_id' => 'required|exists:folders,id',
            'permission' => 'required|in:read,write,delete',
        ]);
        if (!$this->hasPermission(Auth::id(), $folderId, 'write')) {
            return $this->errorResponse('No permission to revoke access', 'error', HttpStatusCode::FORBIDDEN->value);
        }
        $folderIds = Folder::where('path', 'like', "%$folderId%")
            ->orWhere('id', $folderId)
            ->pluck('id')
            ->toArray();
        $deleted = FolderPermission::where('user_id', $userId)
            ->whereIn('folder_id', $folderIds)
            ->where('permission', $permission)
            ->delete();
        Redis::del("permissions_{$userId}_{$folderId}_{$permission}");
        return $this->successResponse(null, 'Permission revoked successfully', HttpStatusCode::SUCCESS->value);
    }
    public function updatePermission(Request $request, $folderId, $userId, $permission)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'folder_id' => 'required|exists:folders,id',
            'permission' => 'required|in:read,write,delete',
        ]);
        if (!$this->hasPermission(Auth::id(), $folderId, 'write')) {
            return $this->errorResponse('No permission to update access', 'error', HttpStatusCode::FORBIDDEN->value);
        }
        $folderIds = Folder::where('path', 'like', "%$folderId%")
            ->orWhere('id', $folderId)
            ->pluck('id')
            ->toArray();
        FolderPermission::whereIn('folder_id', $folderIds)
            ->where('user_id', $userId)
            ->update(['permission' => $permission]);
        Redis::setex("permissions_{$userId}_{$folderId}_{$permission}", 3600, true);
        return $this->successResponse(null, 'Permission updated successfully', HttpStatusCode::SUCCESS->value);
    }
    protected function hasPermission($userId, $folderId, $permission)
    {
        if (is_null($folderId)) {
            return Auth::id() == $userId;
        }
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
    public function addFolder(Request $request)
    {
        $request->validate([
            'type' => 'required|in:folder,file',
            'name' => 'required_if:type,folder|string|nullable',
            'file' => 'required_if:type,file|file',
            'parent_id' => 'nullable|exists:folders,id',
        ]);
        $userId = Auth::id();
        if (!$this->hasPermission($userId, $request->parent_id, 'write')) {
            return $this->errorResponse('No permission to add in this folder', 'error', HttpStatusCode::FORBIDDEN->value);
        }
        if ($request->type === 'folder' && $request->parent_id) {
            $parentFolder = Folder::findOrFail($request->parent_id, ['type']);
            if ($parentFolder->type !== 'folder') {
                return $this->errorResponse('Cannot add folder inside a file', 'error', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }
        }
        $folder = null;
        if ($request->type === 'file') {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('uploads', $fileName, 'public');
            $folder = Folder::create([
                'user_id' => $userId,
                'parent_id' => $request->parent_id,
                'name' => $fileName,
                'size' => $file->getSize(),
                'type' => 'file',
            ]);
        } else {
            $folder = Folder::create([
                'user_id' => $userId,
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'type' => 'folder',
            ]);
        }
        if ($request->parent_id) {
            $parentPermissions = FolderPermission::where('folder_id', $request->parent_id)
                ->pluck('permission', 'user_id')
                ->toArray();
            $data = array_map(fn($userId, $permission) => [
                'user_id' => $userId,
                'folder_id' => $folder->id,
                'permission' => $permission,
                'created_at' => now(),
                'updated_at' => now(),
            ], array_keys($parentPermissions), $parentPermissions);
            if (!empty($data)) {
                FolderPermission::insert($data);
            }
        }
        return $this->successResponse($folder, 'Folder created successfully', HttpStatusCode::CREATED->value);
    }
    public function downloadFolder($folderId)
    {
        if (!$this->hasPermission(Auth::id(), $folderId, 'read')) {
            return $this->errorResponse('No permission to download', 'error', HttpStatusCode::FORBIDDEN->value);
        }

        $folder = Folder::findOrFail($folderId, ['id', 'name', 'path', 'type']);
        $tempDir = storage_path('app/temp/' . uniqid());
        mkdir($tempDir, 0777, true);

        $zipFileName = $folder->name . '.zip';
        $zipPath = $tempDir . '/' . $zipFileName;
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->addFolderContentsToZip($folder, $zip);
        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
    private function addFolderContentsToZip(Folder $folder, ZipArchive $zip)
    {
        $cacheKey = 'folder_contents_' . $folder->id;
        $items = Redis::get($cacheKey);

        if ($items === null) {
            $items = Folder::where('path', 'like', $folder->path . '/%')
                ->orWhere('id', $folder->id)
                ->orderBy('path')
                ->get(['id', 'name', 'path', 'type'])
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($items));
        } else {
            $items = json_decode($items, true);
        }

        if ($folder->type === 'folder') {
            $zip->addEmptyDir($folder->name);
        } elseif ($folder->type === 'file') {
            $filePath = storage_path('app/public/uploads/' . $folder->name);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $folder->name);
            }
        }
        foreach ($items as $item) {
            if ($item['id'] === $folder->id) {
                continue;
            }
            $relativePath = str_replace($folder->path . '/', '', $item['path']);
            $zipPath = $folder->name . ($relativePath ? '/' . $relativePath : '');

            if ($item['type'] === 'folder') {
                $zip->addEmptyDir($zipPath);
            } else {
                $filePath = storage_path('app/public/uploads/' . $item['name']);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $zipPath . '/' . $item['name']);
                }
            }
        }
    }
    public function unzipFolder(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        if (!$this->hasPermission(Auth::id(), $request->parent_id, 'write')) {
            return $this->errorResponse('No permission to upload', 'error', HttpStatusCode::FORBIDDEN->value);
        }

        $zipFile = $request->file('file');
        $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
        $zipPath = storage_path('app/temp/' . $zipFile->getClientOriginalName());
        $zipFile->move(storage_path('app/temp/'), $zipFile->getClientOriginalName());

        $extractPath = storage_path('app/uploads/' . $zipFileName);
        mkdir($extractPath, 0777, true);
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            unlink($zipPath);
            return $this->errorResponse('Failed to open ZIP file', 'error', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
        $zip->extractTo($extractPath);
        $zip->close();
        unlink($zipPath);
        $extractedFiles = $this->saveExtractedFiles($extractPath, $zipFileName, Auth::id(), $request->parent_id);
        return $this->successResponse([
            'files' => $extractedFiles,
            'path' => str_replace(storage_path('app/'), '', $extractPath),
        ], 'Files extracted successfully', HttpStatusCode::SUCCESS->value);
    }
    private function saveExtractedFiles($directory, $baseDir, $userId, $parentId = null)
    {
        $files = [];
        $items = scandir($directory);
        $parentFolder = null;
        if ($parentId) {
            $parentFolder = Folder::findOrFail($parentId, ['id', 'path']);
        }
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;
            $relativePath = str_replace(storage_path('app/uploads/' . $baseDir . '/'), '', $fullPath);
            if (is_dir($fullPath)) {
                $folder = Folder::create([
                    'user_id' => $userId,
                    'parent_id' => $parentId,
                    'name' => $item,
                    'type' => 'folder',
                ]);
                $files[$relativePath] = $this->saveExtractedFiles($fullPath, $baseDir, $userId, $folder->id);
            } else {
                $size = filesize($fullPath);
                $folder = Folder::create([
                    'user_id' => $userId,
                    'parent_id' => $parentId,
                    'name' => $item,
                    'size' => $size,
                    'type' => 'file',
                ]);
                $files[] = $relativePath;
            }
        }
        return $files;
    }
    public function deleteFolder($id)
    {
        $folder = Folder::findOrFail($id, ['id', 'user_id']);
        if (!$this->hasPermission(Auth::id(), $id, 'delete')) {
            return $this->errorResponse('No permission to delete', 'error', HttpStatusCode::FORBIDDEN->value);
        }

        $folder->delete();
        return $this->successResponse(null, 'Folder deleted successfully', HttpStatusCode::SUCCESS->value);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'nullable|string',
        ]);

        $folder = Folder::findOrFail($id, ['id', 'user_id', 'parent_id', 'path', 'type']);
        if (!$this->hasPermission(Auth::id(), $id, 'write')) {
            return $this->errorResponse('No permission to update', 'error', HttpStatusCode::FORBIDDEN->value);
        }

        if ($request->parent_id && $folder->id == $request->parent_id) {
            return $this->errorResponse('Cannot move folder to itself', 'error', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }

        if ($request->parent_id && $request->parent_id != $folder->parent_id) {
            $newParent = Folder::find($request->parent_id, ['id', 'path', 'type']);
            if ($newParent && $newParent->type !== 'folder') {
                return $this->errorResponse('Cannot move to a file', 'error', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }

            $oldPath = $folder->path;
            $folder->parent_id = $request->parent_id;
            $folder->path = $newParent ? $newParent->path . '/' . $folder->id : (string) $folder->id;
            $folder->save();
            $descendants = Folder::where('path', 'like', $oldPath . '/%')->get(['id', 'path']);
            foreach ($descendants as $descendant) {
                $descendant->path = str_replace($oldPath, $folder->path, $descendant->path);
                $descendant->saveQuietly();
                Redis::setex("folder_path_{$descendant->id}", 3600, $descendant->path);
            }
        }
        if ($request->has('name')) {
            $folder->name = $request->name;
            $folder->save();
        }

        return $this->successResponse($folder, 'Folder updated successfully', HttpStatusCode::SUCCESS->value);
    }
    public function search(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $cacheKey = 'search_path_' . md5($request->path);
        $folders = Redis::get($cacheKey);
        if ($folders === null) {
            $folders = Folder::where('path', 'like', '%' . $request->path . '%')
                ->where('user_id', Auth::id())
                ->get(['id', 'name', 'path', 'type', 'size'])
                ->toArray();
            Redis::setex($cacheKey, 3600, json_encode($folders));
        } else {
            $folders = json_decode($folders, true);
        }
        return $this->successResponse($folders, 'Search completed successfully', HttpStatusCode::SUCCESS->value);
    }
    public function isAncestor($id)
    {
        $cacheKey = "is_ancestor_{$id}";
        $isAncestor = Redis::get($cacheKey);

        if ($isAncestor === null) {
            $folder = Folder::findOrFail($id, ['parent_id']);
            $isAncestor = !is_null($folder->parent_id);
            Redis::setex($cacheKey, 3600, $isAncestor);
        } else {
            $isAncestor = (bool) $isAncestor;
        }
        return $this->successResponse(['is_ancestor' => $isAncestor], 'Success', HttpStatusCode::SUCCESS->value);
    }

}
