<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    // List users with filtering, sorting, and pagination
    public function index(Request $request)
    {
        $query = User::query();

        // Filtering
        if ($request->has('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // Sorting
        if ($request->has('sort_by')) {
            $query->orderBy($request->sort_by, $request->get('order', 'asc'));
        }

        // Pagination
        return JsonResource::collection($query->paginate(10));
    }

    // Store a new user
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
            ]);

            $user = User::create(array_merge(
                $validated,
                ['password' => bcrypt($request->password)]
            ));

            return new JsonResource($user);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Show specific user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return new JsonResource($user);
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|min:6',
        ]);

        if ($request->has('password')) {
            $validated['password'] = bcrypt($request->password);
        }

        $user->update($validated);
        return new JsonResource($user);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}