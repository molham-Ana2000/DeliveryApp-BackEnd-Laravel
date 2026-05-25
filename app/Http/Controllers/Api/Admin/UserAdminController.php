<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateUserRequest;
use App\Models\User;
use App\Services\Api\Admin\UserAdminService;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function __construct(
        private UserAdminService $userService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            $users = $this->userService->getUsersForAdmin([
                'search' => $request->query('search'),
                'role' => $request->query('role'),
                'per_page' => $request->query('per_page', 10),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully',
                'data' => $users,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(AdminUpdateUserRequest $request, User $user)
    {
        try {
            $updatedUser = $this->userService->updateUserByAdmin(
                $user,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $updatedUser,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['nullable', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                'phone' => ['nullable', 'string', 'max:30'],
                'birthday' => ['nullable', 'date'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $user = $this->userService->createAdminUser($data);

            return response()->json([
                'success' => true,
                'message' => 'Admin user created successfully.',
                'data' => $user,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show(User $user)
    {
        try {
            $user = $this->userService->getUserById($user);

            return response()->json([
                'success' => true,
                'message' => 'User fetched successfully.',
                'data' => $user,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function block(User $user)
    {
        try {
            $this->userService->blockUser($user);

            return response()->json([
                'success' => true,
                'message' => 'User blocked successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to block user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trashed(Request $request)
    {
        try {
            $users = $this->userService->getTrashedUsers([
                'search' => $request->query('search'),
                'role' => $request->query('role'),
                'per_page' => $request->query('per_page', 10),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trashed users fetched successfully.',
                'data' => $users,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trashed users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore(int $userId)
    {
        try {
            $user = $this->userService->restoreUser($userId);

            return response()->json([
                'success' => true,
                'message' => 'User restored successfully.',
                'data' => $user,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forceDelete(int $userId)
    {
        try {
            $this->userService->forceDeleteUser($userId);

            return response()->json([
                'success' => true,
                'message' => 'User permanently deleted successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}