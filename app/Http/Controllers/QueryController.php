<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatusCode;
use App\Jobs\GenerateZipArchive;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Laravel\Prompts\select;

class QueryController extends Controller
{
    //بباث معين شوفي فولدرات متكررة اسمائها
    public function nameOfFolder(Request $request)
    {
        $folder = Folder::
        where('path', 'like', "%$request->path%")
            ->groupBy('name')
            ->select('name', DB::raw('count(*) as count'))
            ->havingRaw('count(*) > 1')->get();
        return $this->successResponse($folder, 'success', HttpStatusCode::SUCCESS->value);
    }
    //خلي اول واحد وحذيف الباقي
    public function deleteOfFolder(Request $request)
    {
        DB::table('folders as f1')
            ->leftJoin(DB::raw('(SELECT MIN(id) as min_id, name FROM folders GROUP BY name) as f2'), function ($join) {
                $join->on('f1.name', '=', 'f2.name')
                    ->whereColumn('f1.id', '>', 'f2.min_id');
            })
            ->whereNotNull('f2.min_id')
            ->where('path', 'like', "%$request->path%")
            ->delete();
    }
    // الاسماء الغير مكرره
    public function nameNotFound(Request $request)
    {
        $folder = Folder::
        where('path', 'like', "%$request->path%")
            ->groupBy('name')
            ->select('name', DB::raw('count(*) as count'))
            ->havingRaw('count(*) = 1')->get();
        return $folder;
    }
    // تحميل بالخلفيه
    public function downloadQueue(Request $request)
    {
        $folderId = $request->input('folderId');
        $userId = Auth::id();
        $folder = Folder::find($folderId);
        if (!$folder) {
            return $this->errorResponse('', '', HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
        GenerateZipArchive::dispatch($folder->path, $userId);
    }
    //يلي ما عندن ولا مجلد وعكسا يلي عندن مجلد واحد على الأقل
    public function userNoFolder()
    {
        $user=User::query()->
            doesntHave('folder')->get();
        return response()->json($user, HttpStatusCode::SUCCESS->value);
    }
    public function userHasAtLeastOneFolder()
    {
        $user=User::query()->
        has('folder')->get();
        return response()->json($user, HttpStatusCode::SUCCESS->value);
    }
    public function userNoFile()
    {
        $user=User::query()
            ->doesntHave('file')->get();
        return $user;
    }
    public function userAtLeastFile()
    {
    $user=User::query()->has('file')->get();
    return $user;
    }
    public function folderNoFile()
    {
    $folder=Folder::query()->doesntHave('file')->get();
    return $this->successResponse($folder,'success', HttpStatusCode::SUCCESS->value);
    }
    public function betweenSize(Request $request)
    {
        $folder=Folder::query()
            ->whereBetween('size', [$request->from,$request->to])->get();
        return $folder;

    }
}

