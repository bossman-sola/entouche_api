<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {}

    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when(
                $request->search,
                fn ($q, $s) =>
                    $q->where(
                        fn ($q) =>
                            $q->where(
                                'name',
                                'like',
                                "%{$s}%"
                            )->orWhere(
                                'email',
                                'like',
                                "%{$s}%"
                            )
                    )
            )
            ->latest()
            ->paginate(
                $request->integer(
                    'per_page',
                    15
                )
            );

        return $this->paginated($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'status' => [
                'sometimes',
                'in:active,inactive',
            ],

            'roles' => [
                'sometimes',
                'array',
            ],

            'roles.*' => [
                'string',
                'exists:roles,name',
            ],
        ]);

        $roles =
            $data['roles'] ?? [];

        unset($data['roles']);

        /*
         * If status was not supplied,
         * create the user as active.
         */
        $data['status'] =
            $data['status'] ?? 'active';

        /*
         * This assumes your User model uses:
         *
         * 'password' => 'hashed'
         *
         * in its casts().
         */
        $user = User::create($data);

        /*
         * Assign roles before notifications so
         * the account is fully configured first.
         */
        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        /*
         * Notify administrators that a new
         * account was created.
         *
         * NotificationService handles:
         * - In-app
         * - Email
         */
        $this->notifications->notifyAdmins(
            'user_created',
            'User Created',
            "A new user account was created for {$user->name} ({$user->email}).",
            [
                'user_id' =>
                    $user->id,
            ]
        );

        /*
         * Notify the new user only if their
         * account is active.
         */
        if ($user->status === 'active') {
            $this->notifications->notifyUser(
                $user,
                'account_created',
                'Your Account Has Been Created',
                "Hello {$user->name}, your account has been created successfully. You can now sign in to the inventory management system.",
                [
                    'user_id' =>
                        $user->id,
                ]
            );
        }

        return $this->created(
            $user->load('roles'),
            'User created'
        );
    }

    public function show(User $user)
    {
        return $this->success(
            $user->load(
                'roles.permissions'
            )
        );
    }

    public function update(
        Request $request,
        User $user
    ) {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'email',
                'unique:users,email,'
                    . $user->id,
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
            ],

            'status' => [
                'sometimes',
                'in:active,inactive',
            ],
        ]);

        if (
            blank(
                $data['password'] ?? null
            )
        ) {
            unset(
                $data['password']
            );
        }

        $user->update($data);

        return $this->success(
            $user->fresh('roles'),
            'User updated'
        );
    }

    public function destroy(User $user)
    {
        /*
         * Keep a copy of the details because
         * the model may be soft-deleted below.
         */
        $userId =
            $user->id;

        $userName =
            $user->name;

        $userEmail =
            $user->email;

        $user->delete();

        /*
         * Notify administrators only.
         *
         * We do not notify the deleted account
         * because it should no longer receive
         * system notifications.
         */
        $this->notifications->notifyAdmins(
            'user_deleted',
            'User Deleted',
            "The user account for {$userName} ({$userEmail}) has been deleted.",
            [
                'user_id' =>
                    $userId,
            ]
        );

        return $this->success(
            null,
            'User deleted'
        );
    }

    public function assignRole(
        Request $request,
        User $user
    ) {
        $data = $request->validate([
            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'string',
                'exists:roles,name',
            ],
        ]);

        $oldRoles =
            $user->roles()
                ->pluck('name')
                ->all();

        $user->syncRoles(
            $data['roles']
        );

        $newRoles =
            $user->fresh()
                ->roles()
                ->pluck('name')
                ->all();

        /*
         * Notify the affected user.
         */
        $this->notifications->notifyUser(
            $user->fresh(),
            'user_role_updated',
            'Your Role Has Been Updated',
            'Your account role or permissions have been updated.',
            [
                'user_id' =>
                    $user->id,

                'old_roles' =>
                    $oldRoles,

                'new_roles' =>
                    $newRoles,
            ]
        );

        return $this->success(
            $user->fresh('roles'),
            'Role assigned'
        );
    }

    public function removeRole(
        Request $request,
        User $user
    ) {
        $data = $request->validate([
            'role' => [
                'required',
                'string',
                'exists:roles,name',
            ],
        ]);

        $removedRole =
            $data['role'];

        $user->removeRole(
            $removedRole
        );

        /*
         * Notify the user after the role
         * has been removed.
         */
        $this->notifications->notifyUser(
            $user->fresh(),
            'user_role_updated',
            'Your Role Has Been Updated',
            "The {$removedRole} role has been removed from your account.",
            [
                'user_id' =>
                    $user->id,

                'removed_role' =>
                    $removedRole,
            ]
        );

        return $this->success(
            $user->fresh('roles'),
            'Role removed'
        );
    }

    public function toggleStatus(User $user)
    {
        $currentStatus =
            $user->status;

        $newStatus =
            $currentStatus === 'active'
                ? 'inactive'
                : 'active';

        /*
         * IMPORTANT:
         *
         * notifyUser() only sends to active users.
         *
         * Therefore, when deactivating an account,
         * notify the user BEFORE changing the
         * status to inactive.
         */
        if ($newStatus === 'inactive') {
            $this->notifications->notifyUser(
                $user,
                'account_deactivated',
                'Account Deactivated',
                'Your account has been deactivated. You may no longer be able to access the inventory management system.',
                [
                    'user_id' =>
                        $user->id,
                ]
            );
        }

        $user->update([
            'status' =>
                $newStatus,
        ]);

        /*
         * When activating the account, the user
         * is active now, so notification can be
         * sent after the update.
         */
        if ($newStatus === 'active') {
            $this->notifications->notifyUser(
                $user->fresh(),
                'account_activated',
                'Account Activated',
                'Your account has been activated. You can now access the inventory management system.',
                [
                    'user_id' =>
                        $user->id,
                ]
            );
        }

        /*
         * Also tell administrators about the
         * status change.
         */
        $this->notifications->notifyAdmins(
            'user_status_updated',
            'User Status Updated',
            "The account for {$user->name} ({$user->email}) has been {$newStatus}.",
            [
                'user_id' =>
                    $user->id,

                'previous_status' =>
                    $currentStatus,

                'new_status' =>
                    $newStatus,
            ]
        );

        return $this->success(
            $user->fresh('roles'),
            'User status updated'
        );
    }
}
