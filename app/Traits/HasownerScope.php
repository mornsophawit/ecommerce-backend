<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Add `use HasOwnerScope;` to a controller to accept an optional ?owner=
 * query param on top of whatever scoping it already does.
 *
 * SECURITY: the param is taken from the request, so it must never be
 * trusted blindly — otherwise a store_admin could pass ?owner=1 and see
 * another user's data. resolveOwnerId() only allows:
 *   - a user asking for their own id (always allowed), or
 *   - a super_admin asking for anyone's id (inspection/admin view)
 * Anything else is rejected with a 403 before the controller ever builds
 * a query around it.
 */
trait HasOwnerScope
{
    /**
     * @return int|null  null when no ?owner= was passed — the caller keeps
     *                    its existing default behavior in that case.
     */
    protected function resolveOwnerId(Request $request): ?int
    {
        if (!$request->filled('owner')) {
            return null;
        }

        /** @var User $authUser */
        $authUser = auth()->user();
        $ownerId = (int) $request->input('owner');

        if ($ownerId !== $authUser->id && !$authUser->isSuperAdmin()) {
            abort(response()->json([
                'success' => false,
                'message' => 'You can only filter by your own owner id.',
            ], 403));
        }

        return $ownerId;
    }

    /** Fetch the target user once scoping needs to know THEIR role (e.g. cashier vs store_admin). */
    protected function resolveOwnerUser(int $ownerId): ?User
    {
        return User::find($ownerId);
    }
}