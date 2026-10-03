<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared helpers that restrict admin-panel listings to the store_admin
 * "tenant" the authenticated user belongs to:
 * - super_admin: unrestricted (sees everything).
 * - store_admin: only rows they personally created.
 * - cashier: only rows created by the store_admin who owns their assigned branch.
 * - any other role: left untouched by this trait (handle separately).
 */
trait ScopesToOwner
{
    /**
     * Scope a query by its own `created_by` column (e.g. Stores, Products, Users).
     */
    protected function scopeToStoreOwner(Builder $query, ?User $user, string $column = 'created_by'): Builder
    {
        if ($user && ($user->isStoreAdmin() || $user->isCashier())) {
            $query->where($column, $user->storeOwnerId() ?? 0);
        }

        return $query;
    }

    /**
     * Scope a query through a relation path whose target carries the
     * `created_by` store ownership (e.g. 'store' on Branch, 'branch.store'
     * on Order, 'order.branch.store' on Refund).
     */
    protected function scopeToStoreOwnerVia(Builder $query, ?User $user, string $relation): Builder
    {
        if ($user && ($user->isStoreAdmin() || $user->isCashier())) {
            $ownerId = $user->storeOwnerId() ?? 0;
            $query->whereHas($relation, function (Builder $q) use ($ownerId) {
                $q->where('created_by', $ownerId);
            });
        }

        return $query;
    }
}
