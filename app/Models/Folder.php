<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;
    protected $fillable= [
        'user_id',
        'parent_id',
        'files',
        'name',
    ];
    //Root belongs to Folder
    public function root()
    {
        return $this->belongsTo(Folder::class,'parent_id');
    }
    // child has many Folder
    public function child()
    {
        return $this->hasMany(Folder::class,'parent_id');
    }
    //Child Recursive
    public function childRecursive():HasMany
    {
        return $this->child()->with('childRecursive');
    }
    //Folder Belongs to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
