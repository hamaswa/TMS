<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tailor;
use App\Models\Tailorsalary;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\MeasurementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class QaDemoSeeder extends Seeder
{
    private const PASSWORD = 'Demo@2026';

    public function run(): void
    {
        DB::transaction(function () {
            [$combined, $combinedBusiness] = $this->owner(
                'Ahmed Khan', 'ahmed.khan@tms.test', 'خان ٹیلرز اینڈ فیبرکس',
                true, true, '03001234567', 'مین بازار، راولپنڈی'
            );
            [$tailoring] = $this->owner(
                'Bilal Ahmed', 'bilal.ahmed@tms.test', 'بلال ٹیلرز',
                true, false, '03011234567', 'صدر بازار، پشاور'
            );
            [$clothing] = $this->owner(
                'Usman Ali', 'usman.ali@tms.test', 'علی فیبرکس',
                false, true, '03021234567', 'کچہری بازار، فیصل آباد'
            );

            $this->employees($combined, $combinedBusiness);
            $this->tailoringData($combined, true);
            $this->clothingData($combined, true);
            $this->tailoringData($tailoring, false);
            $this->clothingData($clothing, false);
        });
    }

    private function owner(
        string $name,
        string $email,
        string $businessName,
        bool $tailoring,
        bool $clothing,
        string $phone,
        string $address,
    ): array {
        $owner = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'password' => Hash::make(self::PASSWORD),
            'tailoring_access' => $tailoring,
            'clothing_access' => $clothing,
            'is_business_owner' => true,
            'preferred_workspace' => $tailoring && ! $clothing ? 'tailoring' : ($clothing && ! $tailoring ? 'shop' : null),
        ]);
        $owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));

        $business = Business::create([
            'name' => $businessName,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => $tailoring,
            'clothing_enabled' => $clothing,
            'password_expiry_days' => 90,
            'password_policy_updated_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        DB::table('settings')->insert([
            'user_id' => $owner->id,
            'name' => $businessName,
            'note' => 'معیاری کپڑا، نفیس سلائی اور قابلِ اعتماد خدمت',
            'address' => $address,
            'logo' => 'images/logo.png',
            'status' => 1,
            'contact_no' => $phone,
            'contact' => $phone,
            'shop_slug' => Str::slug($businessName).'-'.$owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$owner, $business];
    }

    private function employees(User $owner, Business $business): void
    {
        $roles = [
            ['name' => 'آرڈر منیجر', 'permissions' => [
                BusinessRole::TAILORING_ACCESS, BusinessRole::TAILORING_CUSTOMERS,
                BusinessRole::TAILORING_ORDERS, BusinessRole::TAILORING_WORKSHOP,
                BusinessRole::CUSTOMER_BALANCES,
            ]],
            ['name' => 'سیلز پرسن', 'permissions' => [
                BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES,
                BusinessRole::CUSTOMER_BALANCES,
            ]],
            ['name' => 'اکاؤنٹنٹ', 'permissions' => [
                BusinessRole::FINANCE_VIEW, BusinessRole::CUSTOMER_BALANCES,
                BusinessRole::EXPENSES_MANAGE,
            ]],
        ];
        $people = [
            ['Sana Khan', 'sana.orders', 'sana.khan@tms.test', '03035550101', 'آرڈر منیجر', 'tailoring'],
            ['Farhan Ali', 'farhan.sales', 'farhan.ali@tms.test', '03035550102', 'سیلز پرسن', 'shop'],
            ['Ayesha Malik', 'ayesha.accounts', 'ayesha.malik@tms.test', '03035550103', 'اکاؤنٹنٹ', null],
        ];

        $employeeRole = Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']);
        foreach ($roles as $index => $data) {
            $role = BusinessRole::create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'permissions' => $data['permissions'],
            ]);
            [$name, $username, $email, $phone, $jobTitle, $workspace] = $people[$index];
            $employee = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'address' => 'راولپنڈی، پنجاب',
                'password' => Hash::make(self::PASSWORD),
                'business_id' => $business->id,
                'business_role_id' => $role->id,
                'job_title' => $jobTitle,
                'preferred_workspace' => $workspace,
                'employee_active' => true,
                'must_change_password' => false,
                'password_changed_at' => now(),
                'is_business_owner' => false,
                'tailoring_access' => false,
                'clothing_access' => false,
            ]);
            $employee->assignRole($employeeRole);
        }
    }

    private function tailoringData(User $owner, bool $combined): void
    {
        $measurementService = app(MeasurementService::class);
        $collar = MeasurementField::create([
            'user_id' => $owner->id,
            'label' => 'کالر کی اونچائی',
            'key' => 'collar_height_'.$owner->id,
            'field_type' => 'number',
            'unit' => 'inch',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $fit = MeasurementField::create([
            'user_id' => $owner->id,
            'label' => 'فٹنگ کی پسند',
            'key' => 'fit_preference_'.$owner->id,
            'field_type' => 'select',
            'unit' => 'none',
            'options' => ['نارمل', 'سلم فٹ', 'کشادہ'],
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'مردانہ شلوار قمیض',
            'description' => 'روزمرہ اور تقریبات کے مکمل سوٹ کی پیمائش',
            'system_fields' => ['length', 'arms', 'teraa', 'senaChorai', 'damanchorai', 'shalwar', 'pancha', 'shoulder'],
            'custom_field_ids' => [$collar->id, $fit->id],
            'is_default' => true,
            'is_active' => true,
        ]);

        $customerNames = $combined
            ? [['Muhammad Aslam', '03007771111', 'شام کے وقت رابطہ کریں'], ['Tariq Mehmood', '03007772222', 'سادہ ڈیزائن پسند ہے']]
            : [['Naveed Iqbal', '03008881111', 'عید سے پہلے ڈیلیوری درکار ہے'], ['Hamza Saeed', '03008882222', 'کالر قدرے کھلا رکھیں']];
        $customers = [];
        foreach ($customerNames as $index => [$name, $phone, $note]) {
            $customer = Customers::create([
                'name' => $name,
                'phone_number1' => $phone,
                'user_id' => $owner->id,
                'measurement_template_id' => $template->id,
                'length' => 41 + $index,
                'arms' => 24 + ($index * 0.5),
                'teraa' => 18 + ($index * 0.5),
                'senaChorai' => 22 + $index,
                'damanchorai' => 24 + $index,
                'shalwar' => 40 + $index,
                'pancha' => 8 + ($index * 0.5),
                'shoulder' => 18 + ($index * 0.5),
                'note' => $note,
                'mobile_pin' => Hash::make('123456'),
                'pin_changed_at' => now(),
            ]);
            $measurementService->syncCustomer($customer, collect([$collar, $fit]), [
                $collar->id => 1.5 + ($index * 0.25),
                $fit->id => $index === 0 ? 'نارمل' : 'سلم فٹ',
            ]);
            $measurementService->recordHistory($customer, $owner->id, $template, $owner->id, 'customer_created');
            $customers[] = $customer;
        }

        $tailor = Tailor::create([
            'name' => $combined ? 'Rashid Mahmood' : 'Imran Shah',
            'phone_number1' => $combined ? '03009990101' : '03009990202',
            'email' => $combined ? 'rashid.tailor@tms.test' : 'imran.tailor@tms.test',
            'password' => Hash::make(self::PASSWORD),
            'user_id' => $owner->id,
        ]);
        $rate = Tailorsalary::create([
            'tailor_id' => $tailor->id,
            'type' => 'مردانہ سوٹ',
            'price' => $combined ? 900 : 850,
        ]);

        foreach ($customers as $index => $customer) {
            $total = $combined ? 3500 + ($index * 500) : 3000 + ($index * 400);
            $received = $index === 0 ? 2000 : 1500;
            $order = Order::create([
                'sub_customer' => (string) $customer->id,
                'customerId' => (string) $customer->id,
                'measurement_template_id' => $template->id,
                'suitQuantity' => 1,
                'totalPayment' => $total,
                'designPrice' => $total,
                'tailorId' => $tailor->id,
                'rateId' => $rate->id,
                'suitNum' => json_encode(['سوٹ نمبر '.($index + 1)], JSON_UNESCAPED_UNICODE),
                'design' => $index === 0 ? 'سادہ کالر، گول دامن' : 'بین کالر، سائیڈ جیب',
                'returnDate' => now()->addDays(7 + ($index * 3))->toDateString(),
                'userId' => $owner->id,
                'remarks' => $customer->note,
                'tailor_price' => $rate->price,
                'rack_no' => 'R-'.($index + 1),
                'status' => $index === 0 ? 'stitching' : 'ready',
                'status_changed_at' => now(),
                'started_at' => now()->subDays(2),
                'ready_at' => $index === 1 ? now() : null,
                'tailor_payment_status' => 'unpaid',
            ]);
            $measurementService->snapshotOrder($order, $customer, $template);
            Transaction::create([
                'remainingBalance' => $total - $received,
                'recivedPayment' => $received,
                'customerId' => $customer->id,
                'userId' => $owner->id,
                'orderId' => $order->id,
                'tailorId' => $tailor->id,
                'comment' => 'آرڈر بکنگ پر پیشگی رقم',
                'Order_type' => 'Tailor',
            ]);
        }
    }

    private function clothingData(User $owner, bool $combined): void
    {
        $brand = ClothBrand::create([
            'name' => $combined ? 'Gul Ahmed' : 'Khaadi Fabrics',
            'brand_slug' => $combined ? 'gul-ahmed' : 'khaadi-fabrics',
            'user_id' => $owner->id,
        ]);
        $type = ClothType::create(['name' => $combined ? 'کپاس' : 'واش اینڈ وئیر', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'price' => $combined ? 850 : 780,
            'sale_price' => $combined ? 1150 : 1050,
            'user_id' => $owner->id,
        ]);
        $color = ClothColor::create([
            'cloth_id' => $cloth->id,
            'color' => $combined ? 'سفید' : 'آسمانی',
            'length' => 0,
            'average_unit_cost' => 0,
            'user_id' => $owner->id,
        ]);
        $supplier = Supplier::create([
            'user_id' => $owner->id,
            'name' => $combined ? 'المدینہ ٹیکسٹائل' : 'فیصل ٹیکسٹائل ملز',
            'contact_person' => $combined ? 'Zubair Hassan' : 'Saad Raza',
            'phone' => $combined ? '03004440101' : '03004440202',
            'email' => $combined ? 'zubair@textile.test' : 'saad@textile.test',
            'address' => $combined ? 'راجہ بازار، راولپنڈی' : 'جھنگ روڈ، فیصل آباد',
            'opening_balance' => 0,
            'active' => true,
        ]);
        $quantity = $combined ? 60 : 45;
        $unitCost = $combined ? 850 : 780;
        $purchase = Purchase::create([
            'user_id' => $owner->id,
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PO-'.now()->format('Ymd').'-'.$owner->id,
            'purchase_date' => now()->subDays(5)->toDateString(),
            'status' => 'received',
            'total_amount' => $quantity * $unitCost,
            'paid_amount' => $combined ? 30000 : 20000,
            'balance_amount' => ($quantity * $unitCost) - ($combined ? 30000 : 20000),
            'reference' => 'INV-'.$owner->id.'-1001',
            'note' => 'معیاری کپڑے کی نئی کھیپ',
            'received_at' => now()->subDays(4),
        ]);
        $purchase->items()->create([
            'cloth_id' => $cloth->id,
            'cloth_color_id' => $color->id,
            'color' => $color->color,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => $quantity * $unitCost,
            'received_quantity' => $quantity,
        ]);
        app(InventoryService::class)->receive(
            $color, $quantity, $unitCost, 'purchase_receipt', $purchase, $purchase->purchase_number
        );
        SupplierPayment::create([
            'user_id' => $owner->id,
            'supplier_id' => $supplier->id,
            'purchase_id' => $purchase->id,
            'payment_date' => now()->subDays(4)->toDateString(),
            'amount' => $purchase->paid_amount,
            'reference' => 'BANK-'.$owner->id.'-01',
            'note' => 'بینک کے ذریعے جزوی ادائیگی',
        ]);

        $customer = Customers::firstOrCreate(
            ['user_id' => $owner->id, 'phone_number1' => $combined ? '03007771111' : '03006661111'],
            [
                'name' => $combined ? 'Muhammad Aslam' : 'Junaid Anwar',
                'note' => 'مستقل گاہک',
                'mobile_pin' => Hash::make('123456'),
                'pin_changed_at' => now(),
            ]
        );
        $sale = Sale::create([
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);
        $sale->detail()->create([
            'product_name' => $brand->name.' '.$type->name.' - '.$color->color,
            'quantity' => 4,
            'price' => $cloth->sale_price,
        ]);
        $saleTotal = 4 * (float) $cloth->sale_price;
        $received = $combined ? 2500 : 3000;
        Transaction::create([
            'remainingBalance' => $saleTotal - $received,
            'recivedPayment' => $received,
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'sale_id' => $sale->id,
            'comment' => 'کاؤنٹر فروخت کی جزوی ادائیگی',
            'Order_type' => 'Sale',
        ]);
    }
}
