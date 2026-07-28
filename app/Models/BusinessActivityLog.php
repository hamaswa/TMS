<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'actor_user_id',
        'method',
        'route_name',
        'path',
        'route_parameters',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'route_parameters' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actionLabel(): string
    {
        return match ($this->method) {
            'POST' => 'نیا ریکارڈ یا کارروائی',
            'PUT', 'PATCH' => 'ریکارڈ میں تبدیلی',
            'DELETE' => 'ریکارڈ حذف کیا',
            default => 'کارروائی',
        };
    }

    public function actionDescription(): string
    {
        $route = (string) $this->route_name;

        $description = match ($route) {
            'admin.team.employees.store' => 'نیا ملازم شامل کیا',
            'admin.team.employees.update' => 'ملازم کی تفصیلات یا رسائی تبدیل کی',
            'admin.team.employees.password' => 'ملازم کے لیے عارضی پاس ورڈ جاری کیا',
            'admin.team.password-policy.update' => 'ملازمین کی پاس ورڈ میعاد پالیسی تبدیل کی',
            'admin.team.roles.store' => 'نیا ملازم رول بنایا',
            'admin.team.roles.update' => 'ملازم رول کی اجازتیں تبدیل کیں',
            'admin.team.roles.destroy' => 'ملازم رول حذف کیا',
            'employee.password.update' => 'اپنا پاس ورڈ تبدیل کیا',
            'admin.suppliers.store' => 'نیا سپلائر شامل کیا',
            'admin.suppliers.update' => 'سپلائر کی تفصیلات تبدیل کیں',
            'admin.suppliers.destroy' => 'سپلائر حذف کیا',
            'admin.suppliers.payment' => 'سپلائر کی عمومی ادائیگی درج کی',
            'admin.purchases.store' => 'نئی خریداری درج کی',
            'admin.purchases.receive' => 'خریداری کا مال وصول کیا',
            'admin.purchases.cancel' => 'خریداری منسوخ کی',
            'admin.purchases.payment' => 'خریداری کی ادائیگی درج کی',
            'admin.purchases.return' => 'خریداری کی واپسی درج کی',
            'admin.inventory-ledger.adjust' => 'اسٹاک کی مقدار درست کی',
            'admin.stock.store' => 'نئی فروخت درج کی',
            'admin.sale.destroy' => 'فروخت منسوخ کر کے کھاتہ واپس کیا',
            'admin.tailor-jobs.status' => 'سلائی کام کی حالت تبدیل کی',
            'admin.tailor-jobs.payment' => 'درزی کی ادائیگی درج کی',
            'admin.tailor-jobs.retry' => 'سلائی کام دوبارہ شروع کیا',
            'admin.order.status' => 'آرڈر کی حالت تبدیل کی',
            'admin.order.notify' => 'آرڈر کی اطلاع بھیجی',
            'admin.RackNo' => 'آرڈر کا ریک نمبر درج کیا',
            'admin.DirectPayment' => 'گاہک کی براہ راست ادائیگی درج کی',
            'admin.sale-direct-payment' => 'فروخت کی براہ راست ادائیگی درج کی',
            'admin.storefront.orders.payment-verification' => 'آن لائن آرڈر کی دستی ادائیگی کی تصدیق کی',
            'admin.storefront.inquiries.payment-verification' => 'ٹیلرنگ درخواست کی دستی ادائیگی کی تصدیق کی',
            default => null,
        };

        if ($description !== null) {
            return $description;
        }

        $area = $this->areaLabel();

        return match ($this->method) {
            'POST' => $area.' میں نیا ریکارڈ یا کارروائی کی',
            'PUT', 'PATCH' => $area.' میں تبدیلی کی',
            'DELETE' => $area.' سے ریکارڈ حذف کیا',
            default => $area.' میں کارروائی کی',
        };
    }

    public function areaLabel(): string
    {
        $route = (string) $this->route_name;

        return match (true) {
            str_contains($route, 'employee.password') => 'اکاؤنٹ سیکیورٹی',
            str_contains($route, 'team.') => 'ملازمین اور رولز',
            str_contains($route, 'tailor-jobs'), str_contains($route, 'order.status') => 'ورکشاپ کام',
            str_contains($route, 'Tailor'), str_contains($route, 'tailor-') => 'درزی',
            str_contains($route, 'Customers'), str_contains($route, 'customers') => 'گاہک',
            str_contains($route, 'order') => 'ٹیلرنگ آرڈر',
            str_contains($route, 'purchases') => 'خریداری',
            str_contains($route, 'suppliers') => 'سپلائر',
            str_contains($route, 'stock'), str_contains($route, 'cloth') => 'فروخت اور اسٹاک',
            str_contains($route, 'expense') => 'اخراجات',
            str_contains($route, 'setting') => 'ترتیبات',
            default => 'کاروبار',
        };
    }
}
