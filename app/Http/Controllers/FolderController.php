<?php

namespace App\Http\Controllers;
use App\Enums\HttpStatusCode;
use App\Enums\ItemType;
use App\Events\InheritFolderPermissions;
use App\Models\Folder;
use App\Models\FolderPermission;
use App\Models\User;
use App\Repository\FolderRepository;
use App\Services\FolderServices;
use Dotenv\Store\File\Paths;
use Hamcrest\Core\Set;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
class FolderController extends Controller
{
    protected $folderService;

    public function __construct(FolderServices $services)
    {
        return $this->folderService = $services;
    }

    public function getPath(Request $request)

    {
        $Like = Folder::where('path', 'like', "$request->path%")->get();
        if (!$Like) {
            return $this->errorResponse('No data', 'error', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        } else {
            return $Like;
        }
    }
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $fileName = time() . '/' . $file->getClientOriginalName();
        $upload = $file->store('uploads', 'public');
    }
    public function givePermission($userID, $folderId, $permission)
    {
        $data = [];
        $folder = Folder::query()->find($folderId)->where('path', 'like', "%$folderId%")->orWhere('id',$folderId)->pluck('id')->toArray();
        foreach ($folder as $item) {
            $data[] = [
                'user_id' => $userID,
                'folder_id' => $item,
                'permission' => $permission,
            ];
        }
        $verify = FolderPermission::query()->where('user_id', $userID)->where('permission', $permission)->where('folder_id', $item)->exists();
        if ($verify)
        {
            return $this->errorResponse('', 'permission already exists', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
        FolderPermission::insert($data);
        return $this->successResponse('', 'Successfully given permission', HttpStatusCode::SUCCESS->value);
    }
    public function deletePermission($userID, $folderId, $permission)
    {
        $folder = Folder::where('path', 'like', "%$folderId%")->orWhere('id', $folderId)->pluck('id')->toArray();
        $folderPermission = FolderPermission::where('user_id', $userID)->where('permission', $permission)->whereIn('folder_id', $folder);

        return $folderPermission->delete();
    }
    public function updatePermission($folderID, $userId, $permission)
    {
        $folder = Folder::where('path', 'like', "%$folderID%")->orWhere('id', $folderID)->pluck('id')->toArray();
        $update = FolderPermission::whereIn('folder_id', $folder)->where('user_id', $userId)->update(['permission' => $permission]);
        return $this->successResponse('', 'Successfully updated permission', HttpStatusCode::SUCCESS->value);
    }
    function hasPermission1($userId, $folderId, $permission)
    {
        if (is_null($folderId)) {
            if (Auth::id() == $userId) {
                return true;
            }
        }
        $folder = Folder::find($folderId);
        if ($folder) {
            if ($folder->user_id == $userId) {
                return true;
            }
            return FolderPermission::where('user_id', $userId)
                ->where('folder_id', $folderId)
                ->where('permission', $permission)
                ->exists();
        }
        return false;
    }
    public function addFolder(Request $request)
    {
        $userId = Auth::id();
        $type = $request->type;
        if (!$this->hasPermission1($userId, $request->parent_id, 'write')) {
            return response()->json(['error' => 'You do not have permission to add inside this folder'], 403);
        }
        if (!in_array($type, ['file', 'folder'])) {
            return response()->json(['error' => 'Invalid type'], 400);
        }
        $folder = null;
        if ($type == 'file') {
            $request->validate([
                'file' => 'required|file',
                'type' => 'required',
                'parent_id' => 'nullable|exists:folders,id',
            ]);
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('uploads', $fileName, 'public');
            $folder = Folder::create([
                'user_id' => $userId,
                'parent_id' => $request->parent_id,
                'name' => $fileName,
                'size'=>$file->getSize(),
                'type' => 'file',
            ]);
        }
        if ($type == 'folder') {
            $request->validate([
                'type' => 'required',
                'name' => 'required|string',
                'parent_id' => 'nullable|exists:folders,id',
            ]);
            if ($request->parent_id) {
                $parentFolder = Folder::findOrFail($request->parent_id);
                if ($parentFolder->type != 'folder') {
                    return response()->json(['error' => 'Cannot add folder inside a file'], 400);
                }
            }
            $folder = Folder::create([
                'user_id' => $userId,
                'type' => 'folder',
                'name' => $request->name,
                'parent_id' => $request->parent_id,
            ]);
        }
        if ($request->parent_id) {
            $parentPermissions = FolderPermission::where('folder_id', $request->parent_id)->get();
            $data = [];
            foreach ($parentPermissions as $permission) {
                $data[] = [
                    'user_id' => $permission->user_id,
                    'folder_id' => $folder->id,
                    'permission' => $permission->permission,
                ];
            }
            if (!empty($data)) {
                FolderPermission::insert($data);
            }
        }
        return response()->json(['message' => 'Folder created successfully', 'folder' => $folder], 201);
    }
    public function downloadFolder($folderId)
    {
        $folder = Folder::findOrFail($folderId);
        if (!$this->hasPermission(Auth::id(), $folderId, 'read')) {
            return response()->json(['error' => 'You do not have permission to download this folder'], 403);
        }
        $tempDir = storage_path('app/temp/' . uniqid());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $zipFileName = $folder->name . '.zip';
        $zipPath = $tempDir . '/' . $zipFileName;
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->addFolderContentsToZip($folder, $zip);
        $zip->close();
        return response()->download($zipPath);
    }
    private function addFolderContentsToZip($rootFolder, $zip)
    {
        if ($rootFolder->type === 'folder') {
            $zip->addEmptyDir($rootFolder->name);
        }
        $items = Folder::where('path', 'like', $rootFolder->path . '/%')
            ->orderBy('path')
            ->get();
        foreach ($items as $item) {
            $pathParts = explode('/', $item->path);
            $zipPath = '';
            foreach ($pathParts as $index => $part) {
                $folder = Folder::find($part);
                if ($folder) {
                    $zipPath .= ($zipPath ? '/' : '') . $folder->name;
                }
            }
            if ($item->type == 'folder') {
                $zip->addEmptyDir($zipPath);
            } else if ($item->type == 'file') {
                $filePath = storage_path('app/public/uploads' . $item->name);
                if (file_exists($filePath)) {
                    $parentPath = dirname($zipPath);
                    $finalPath = $parentPath === '.' ? $item->name : $parentPath . '/' . $item->name;
                    $zip->addFile($filePath, $finalPath);
                }
            }
        }
        if ($rootFolder->type === 'file')
        {
            $filePath = storage_path('app/public/uploads' . $rootFolder->name);
            if (file_exists($filePath))
            {
                $zip->addFile($filePath, $rootFolder->name);
            }
        }
    }
    public function unzipFolder(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip'
        ]);
        $zipFile = $request->file('file');
        $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
        $zipPath = storage_path('app/temp/' . $zipFile->getClientOriginalName());
        $zipFile->move(storage_path('app/temp/'), $zipFile->getClientOriginalName());
        $extractPath = storage_path('app/uploads/' . $zipFileName);
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0777, true);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            return response()->json(['error' => 'Failed to open the ZIP file'], 500);
        }
        unlink($zipPath);
        $extractedFiles = $this->getAllExtractedFiles($extractPath, $zipFileName);
        return response()->json([
            'message' => 'Files extracted successfully',
            'files' => $extractedFiles,
            'path' => str_replace(storage_path('app/'), '', $extractPath)
        ]);
    }
    private function getAllExtractedFiles($directory, $baseDir)
    {
        $files = [];
        $items = scandir($directory);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;

            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;
            $relativePath = str_replace(storage_path('app/uploads/') . $baseDir . '/', '', $fullPath);
            if (is_dir($fullPath)) {
                $files[$relativePath] = $this->getAllExtractedFiles($fullPath, $baseDir);
            } else {
                $files[] = $relativePath;
            }
        }
        return $files;
    }
    function hasPermission($userId, $folderId, $permission)
    {
        $folder = Folder::find($folderId);
        if ($folder && $folder->user_id == $userId) {
            return true;
        }
        return FolderPermission::where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('permission', $permission)->exists();
    }
    public function deleteFolder($id){
         $one=Folder::findorfail($id);
         return $one->delete();
    }
//    public function deleteFolder($id)
//    {
//        if (!$this->hasPermission(Auth::id(), $id, 'delete')) {
//            return $this->errorResponse('No data', 'no Permission', HttpStatusCode::FORBIDDEN->value);
//        } else
//            return $this->successResponse($this->folderService->deleteFolder($id), 'success', HttpStatusCode::SUCCESS->value);
//    }
    public function update(Request $request, $id)
    {
        if (!$this->hasPermission(Auth::id(), $id, 'write')) {
            return $this->errorResponse('no data', 'you have no permission', HttpStatusCode::FORBIDDEN->value);
        }
        //old parent
        $update = Folder::find($id);
        $like = Folder::where('id', $id)->where('path', 'like', "$request->parent_id%")->value('path');
        if ($update->id == $request->parent_id) {
            return $this->errorResponse('', ' لا يمكن نقل المجلد لنفسه ', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        } else if ($request->parent_id == $update->parent_id) {
            return $this->errorResponse('', ' لا يمكن نقل المجلد للاب ', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
//       else if (Str::contains($like,(string)$request->parent_id))
//        {
//            return $this->errorResponse('',' لا يمكن نقل المجلد للجد  ',HttpStatusCode::INTERNAL_SERVER_ERROR->value);
//        }
        else if ($update->id != $request->parent_id && $request->parent_id != $update->parent_id) {
            $d = $request->parent_id;
            $newParent = Folder::find($d);
            $oldPath = $update->path;
            $update->parent_id = $d;
            $update->path = $newParent ? $newParent->path . '/' . $update->id : (string)$update->id;
            $update->save();
            if ($oldPath) {
                $desc = Folder::where('path', 'like', '%' . $oldPath . '/%')->get();
                foreach ($desc as $item) {
                    $item->path = str_replace($oldPath, $update->path, $item->path);
                    $item->save();
                }
            }
        }
        return $this->successResponse('', 'Update Successfully', HttpStatusCode::SUCCESS->value);
    }
    public function search(Request $request)
    {
        $request->validate([
            'path' => 'required',
        ]);
        $path = $request->path;
        $folder = DB::table('folders')->where('path', 'like', '%' . $path . '%')->get();
        return $this->successResponse($folder, 'success', HttpStatusCode::SUCCESS->value);
    }
    public function isAnsector($id)
    {
        $parent = Folder::findorfail($id);
        while ($parent->parent_id) {
            return true;
        }
        return false;
    }
    public function updateFolder($id, Request $request)
    {
        if ($request->has('files') && $request->has('name') && $request->has('parent_id')) {
            if ($this->folderService->checkParent($id)) {
                return $this->errorResponse('No data', 'You cant Update Parent', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }
            $data = [
                'name' => $request->name,
                'files' => $this->uploadFile($request),
                'parent_id' => $request->parent_id,
            ];
            return $this->successResponse($this->folderService->updateFolder($id, $data), 'success', HttpStatusCode::SUCCESS->value);
        }
        if ($request->has('name')) {
            $data = [
                'name' => $request->name,
                'files' => $this->folderService->getById($id)->files,
                'parent_id' => $this->folderService->getById($id)->parent_id,
            ];
            return $this->successResponse($this->folderService->updateFolder($id, $data), 'success', HttpStatusCode::SUCCESS->value);
        }
        if ($request->has('files')) {
            $data = [
                'name' => $this->folderService->getById($id)->name,
                'parent_id' => $this->folderService->getById($id)->parent_id,
                'files' => $this->uploadFile($request)
            ];
            return $this->successResponse($this->folderService->updateFolder($id, $data), 'success', HttpStatusCode::SUCCESS->value);
        }
        if ($request->has('parent_id')) {
            if ($this->folderService->checkParent($id)) {
                return $this->errorResponse('No Data', 'You cant update parent', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
            }
            $data = [
                'name' => $this->folderService->getById($id)->name,
                'files' => $this->folderService->getById($id)->files,
                'parent_id' => $request->parent_id,
            ];
            return $this->successResponse($this->folderService->updateFolder($id, $data), 'success', HttpStatusCode::SUCCESS->value);
        }
    }
}
