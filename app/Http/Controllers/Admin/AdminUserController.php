<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request, UserService $users): JsonResponse|View
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'in:admin,customer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $users = $users->paginate($filters);

        if (! $request->expectsJson()) {
            return view('admin.users.index', compact('users', 'filters'));
        }

        return response()->json(['data' => $users]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function show(User $user): JsonResponse|View
    {
        $user->loadCount('orders');

        if (! request()->expectsJson()) {
            return view('admin.users.show', compact('user'));
        }

        return response()->json(['data' => $user]);
    }

    public function store(StoreAdminUserRequest $request, UserService $users): JsonResponse|RedirectResponse
    {
        $user = $users->create($request->validated());

        if (! $request->expectsJson()) {
            return redirect()->route('admin.users.show', $user)->with('success', 'User created.');
        }

        return response()->json([
            'message' => 'User created.',
            'data' => $user,
        ], 201);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(
        UpdateAdminUserRequest $request,
        User $user,
        UserService $users
    ): JsonResponse|RedirectResponse {
        $updatedUser = $users->update($user, $request->validated());

        if (! $request->expectsJson()) {
            return redirect()->route('admin.users.show', $updatedUser)->with('success', 'User updated.');
        }

        return response()->json([
            'message' => 'User updated.',
            'data' => $updatedUser,
        ]);
    }

    public function destroy(
        Request $request,
        User $user,
        UserService $users
    ): JsonResponse|RedirectResponse {
        if ($request->user()?->is($user)) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $users->delete($user);

        if (! $request->expectsJson()) {
            return redirect()->route('admin.users.index')->with('success', 'User deleted.');
        }

        return response()->json(['message' => 'User deleted.']);
    }
}
