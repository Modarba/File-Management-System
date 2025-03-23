<?php

namespace App\Http\Middleware;

use App\Models\FolderPermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class checkFolderPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$permission): Response
    {
        $user_id=Auth::id();
        $permission=FolderPermission::where('user_id',Auth::id())->where('permission',$permission)->value('permission');
        $permissionsMap = [
            'read' => ['read'],
            'write' => ['read', 'write'],
            'delete' => ['read', 'write', 'delete'],
            'full_access' => ['read', 'write', 'delete', 'full_access'],
        ];

        if (!in_array($permission, $permissionsMap[$permission] ?? [])) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        return $next($request);
    }
}
