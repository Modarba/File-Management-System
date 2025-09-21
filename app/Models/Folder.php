<?php

namespace App\Models;

use App\Enums\HttpStatusCode;
use App\Observers\FolderObserver;
use App\Services\FolderServices;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
class Folder extends Model
{
    use HasFactory;
    protected $fillable= [
        'size',
        'user_id',
        'parent_id',
        'name',
        'path_save',
        'type'
    ];
    public function Folder()
    {
        return $this->hasMany(Folder::class,'parent_id')->where('type','like','folder');
    }
    public function File()
    {
        return $this->hasMany(Folder::class,'parent_id')->where('type','like','file');
    }
    public function root()
    {
        return $this->belongsTo(Folder::class,'parent_id');
    }
    public function child()
    {
        return $this->hasMany(Folder::class,'parent_id');
    }
    public function childRecursive():HasMany
    {
        return $this->child()->with('childRecursive');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function generatePath(): string
    {
        if (!$this->parent_id) {
            return (string) $this->id;
        }

        $cacheKey = "folder_path_{$this->parent_id}";
        $parentPath = Redis::get($cacheKey);

        if ($parentPath === null) {
            $parent = self::find($this->parent_id, ['path']);
            $parentPath = $parent ? $parent->path : (string) $this->id;
            Redis::setex($cacheKey, 3600, $parentPath);
        }
        return $parentPath . '/' . $this->id;
    }
}
