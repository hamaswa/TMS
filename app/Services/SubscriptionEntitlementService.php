<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customers;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionEntitlementService
{
    /**
     * Customers that a clothing sale created (counter, stock sell, sales agent, or
     * old sale data). They count toward the plan only once they become regulars.
     */
    public const SALE_BUYER_SOURCES = ['counter_sale', 'sales_agent', 'existing_data'];

    /** A sale-created customer is not checked against the limit when saved: one cash purchase doesn't count. */
    public static function skipsLimitOnCreate(Customers $customer): bool
    {
        return in_array($customer->acquisition_source, self::SALE_BUYER_SOURCES, true);
    }

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
     * customer's phone and walk-in placeholders are not real customers. A buyer
     * a clothing sale created counts only once they are a regular: they bought
     * on udhaar, came back for a second sale, or have a tailoring order.
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

        return $this->countedCustomers($ownerUserId) >= $limit ? $limit : null;
    }

    /** How many of the shop's customers use up its plan's customer limit. */
    public function countedCustomers(int $ownerUserId): int
    {
        // One grouped pass over the shop's sales; ids are text in transactions/orders.
        $sales = DB::table('transactions')
            ->where('userId', (string) $ownerUserId)
            ->where('Order_type', 'Sale')
            ->whereNotNull('customerId')
            ->groupBy('customerId')
            ->selectRaw('customerId AS sale_customer_id, COUNT(*) AS sale_count, MAX(CASE WHEN CAST(remainingBalance AS DECIMAL(12,2)) > 0 THEN 1 ELSE 0 END) AS on_credit');

        return Customers::query()
            ->where('customers.user_id', $ownerUserId)
            ->whereNull('customers.parent_id')
            ->where(fn ($query) => $query->where('customers.is_walk_in', false)->orWhereNull('customers.is_walk_in'))
            ->leftJoinSub($sales, 'customer_sales', fn ($join) => $join->whereRaw('customer_sales.sale_customer_id = CAST(customers.id AS CHAR)'))
            ->where(fn ($query) => $query
                ->whereNull('customers.acquisition_source')
                ->orWhereNotIn('customers.acquisition_source', self::SALE_BUYER_SOURCES)
                ->orWhere('customer_sales.sale_count', '>=', 2)
                ->orWhere('customer_sales.on_credit', 1)
                ->orWhereExists(fn ($orders) => $orders->selectRaw('1')->from('orders')
                    ->whereRaw('orders.customerId = CAST(customers.id AS CHAR)')))
            ->count();
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
