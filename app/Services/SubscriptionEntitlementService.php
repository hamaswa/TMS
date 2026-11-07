<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customers;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubscriptionEntitlementService
{
    public function limit(Business $business, string $column): ?int
    {
        if (! $business->subscriptionIsManaged()) {
            return null;
        }

        $value = $business->currentSubscription()?->getAttribute($column);

        return $value === null ? null : (int) $value;
    }

    public function assertCanAddEmployee(Business $business, ?int $excludingUserId = null): void
    {
        $limit = $this->limit($business, 'max_employees');
        if ($limit === null) {
            return;
        }

        $used = User::query()
            ->where('business_id', $business->id)
            ->where('is_business_owner', false)
            ->where('employee_active', true)
            ->when($excludingUserId, fn ($query) => $query->whereKeyNot($excludingUserId))
            ->count();

        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'employee_limit' => "آپ کے موجودہ پلان میں {$limit} فعال ملازمین کی اجازت ہے۔ مزید ملازم کے لیے پلان اپ گریڈ کریں۔",
            ]);
        }
    }

    /**
     * Customers count per shop: main customers only. Family profiles share a
     * customer's phone and walk-in placeholders are not real customers.
     */
    public function assertCanAddCustomer(int $ownerUserId): void
    {
        if ($limit = $this->customerLimitReached($ownerUserId)) {
            throw ValidationException::withMessages([
                'customer_limit' => self::customerLimitMessage($limit),
            ]);
        }
    }

    /** The shop's customer limit when it is already full, otherwise null. */
    public function customerLimitReached(int $ownerUserId): ?int
    {
        $business = Business::where('owner_user_id', $ownerUserId)->first();
        $limit = $business ? $this->limit($business, 'max_customers') : null;
        if ($limit === null) {
            return null;
        }

        $used = Customers::query()
            ->where('user_id', $ownerUserId)
            ->whereNull('parent_id')
            ->where(fn ($query) => $query->where('is_walk_in', false)->orWhereNull('is_walk_in'))
            ->count();

        return $used >= $limit ? $limit : null;
    }

    public static function customerLimitMessage(int $limit): string
    {
        return "آپ کے موجودہ پلان میں {$limit} گاہکوں کی اجازت ہے۔ مزید گاہک شامل کرنے کے لیے پلان اپ گریڈ کریں۔";
    }

    public function assertCanAddRole(Business $business): void
    {
        $limit = $this->limit($business, 'max_business_roles');
        if ($limit !== null && $business->roles()->count() >= $limit) {
            throw ValidationException::withMessages([
                'role_limit' => "آپ کے موجودہ پلان میں {$limit} کاروباری رولز کی اجازت ہے۔",
            ]);
        }
    }

    public function assertCanAddTailor(Business $business): void
    {
        $limit = $this->limit($business, 'max_tailors');
        if ($limit !== null && Tailor::where('user_id', $business->owner_user_id)->count() >= $limit) {
            throw ValidationException::withMessages([
                'tailor_limit' => "آپ کے موجودہ پلان میں {$limit} درزیوں کی اجازت ہے۔ مزید درزی کے لیے پلان اپ گریڈ کریں۔",
            ]);
        }
    }
}
