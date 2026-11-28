<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customer profiles / directory.
     * All authenticated tenant members can view customers.
     */
    public function viewAny(User $user): bool
    {
        return ! empty($user->tenant_id);
    }

    /**
     * Determine whether the user can view the specific customer profile.
     * User and customer must belong to the exact same tenant.
     */
    public function view(User $user, Customer $customer): bool
    {
        if (empty($user->tenant_id) || empty($customer->tenant_id)) {
            return false;
        }

        return (int) $user->tenant_id === (int) $customer->tenant_id;
    }

    /**
     * Determine whether the user can create a customer.
     * Allowed: OWNER, ADMIN, MANAGING_DIRECTOR, SALES, ACCOUNTS.
     */
    public function create(User $user): bool
    {
        if (empty($user->tenant_id)) {
            return false;
        }

        return $user->isAdmin()
            || $user->can('manage-customers')
            || in_array(strtoupper($user->role ?? ''), ['SALES', 'ACCOUNTS'], true);
    }

    /**
     * Determine whether the user can update the customer.
     * Allowed: OWNER, ADMIN, MANAGING_DIRECTOR, SALES, ACCOUNTS within the same tenant.
     */
    public function update(User $user, Customer $customer): bool
    {
        if (empty($user->tenant_id) || empty($customer->tenant_id)) {
            return false;
        }

        if ((int) $user->tenant_id !== (int) $customer->tenant_id) {
            return false;
        }

        return $user->isAdmin()
            || $user->can('manage-customers')
            || in_array(strtoupper($user->role ?? ''), ['SALES', 'ACCOUNTS'], true);
    }

    /**
     * Determine whether the user can delete the customer.
     * Allowed ONLY for OWNER, ADMIN, MANAGING_DIRECTOR within the same tenant.
     * SALES, ACCOUNTS, DISPATCH, VIEWER are strictly forbidden.
     */
    public function delete(User $user, Customer $customer): bool
    {
        if (empty($user->tenant_id) || empty($customer->tenant_id)) {
            return false;
        }

        if ((int) $user->tenant_id !== (int) $customer->tenant_id) {
            return false;
        }

        return $user->isAdmin();
    }
}
