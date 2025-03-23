<?php

namespace App\Models;

use App\Enums\HttpStatusCode;
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

    use HasFactory,HasRoles;
    protected $fillable= [
        'user_id',
        'parent_id',
        'name',
        'path_save',
        'type'
    ];
    public static function boot()
    {
        parent::boot();
        static::creating(function ($parent_id) {
            if ($parent_id->parent_id) {
                $parent = self::find($parent_id->parent_id);
                if ($parent)
                {
                    $parent->path=$parent->path.'/'.($parent_id->id??'temp');
                }
            }
            else
            {
                $parent_id->path = ($parent_id->id??'temp');
            }
        });
        static::created(function ($parent_id) {
            if ($parent_id->parent_id)
            {
                $parent = self::find($parent_id->parent_id);
                if ($parent) {
                    $parent_id->path = $parent->path . '/' . $parent_id->id;
                }
            } else
            {
                $parent_id->path = $parent_id->id;
            }
            $parent_id->save();
        });
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

}
