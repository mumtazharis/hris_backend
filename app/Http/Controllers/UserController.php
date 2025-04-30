<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    // List users with filtering, sorting, and pagination
    public function index()
    {
        $query = User::query();

        // Pagination
        return JsonResource::collection($query->paginate(10));
    }

}