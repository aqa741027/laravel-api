<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CloudFrontService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //    public function index()
    //    {
    //        $users = Cache::remember('users:index', 60, function () {
    //            return User::all();
    //        });
    //
    //        return response()->json($users);
    //    }

    //	public function index()
    //	{
    //	    $cacheKey = 'users:index';
    //	
    //	    if (Cache::has($cacheKey)) {
    //	        $users = Cache::get($cacheKey);
    //	        $cacheStatus = 'HIT';
    //	    } else {
    //	        $users = User::all();
    //	        Cache::put($cacheKey, $users, 60);
    //	        $cacheStatus = 'MISS';
    //	    }
    //	
    //	    return response()
    //	        ->json($users)
    //	        ->header('X-Laravel-Cache', $cacheStatus)
    //		->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60');
    //	}
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $cacheKey = "users:index:page:{$page}";

        if (Cache::has($cacheKey)) {
            $users = Cache::get($cacheKey);
            $cacheStatus = 'HIT';
        } else {
            $users = User::latest()->paginate(10);
            Cache::put($cacheKey, $users, 60);
            $cacheStatus = 'MISS';
        }

        return response()->json([
            'deploy_version' => 'v1.0.4',
            'deploy_time' => now()->toDateTimeString(),
            'server' => gethostname(),
            'cache_status' => $cacheStatus,
            'data' => $users,
        ])
            ->header('X-Laravel-Cache', $cacheStatus)
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60');
    }


    //    public function show($id)
    //    {
    //        $user = Cache::remember("users:show:{$id}", 60, function () use ($id) {
    //            return User::find($id);
    //        });
    //
    //        if (!$user) {
    //            return response()->json([
    //                'message' => 'User not found'
    //            ], 404);
    //        }
    //
    //        return response()->json($user);
    //    }

    public function show($id)
    {
        $cacheKey = "users:show:{$id}";

        if (Cache::has($cacheKey)) {
            $user = Cache::get($cacheKey);
            $cacheStatus = 'HIT';
        } else {
            $user = User::find($id);
            Cache::put($cacheKey, $user, 60);
            $cacheStatus = 'MISS';
        }

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()
            ->json($user)
            ->header('X-Laravel-Cache', $cacheStatus)
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Cache::forget('users:index');

        app(CloudFrontService::class)->invalidate([
            '/api/users'
        ]);
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        Cache::forget('users:index');
        Cache::forget("users:show:{$id}");

        app(CloudFrontService::class)->invalidate([
            '/api/users',
            "/api/users/{$id}"
        ]);
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        Cache::forget('users:index');
        Cache::forget("users:show:{$id}");

        app(CloudFrontService::class)->invalidate([
            '/api/users*',
            "/api/users/{$id}"
        ]);
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
