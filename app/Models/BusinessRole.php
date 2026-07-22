<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessRole extends Model
{
    use HasFactory;

    public const TAILORING_ACCESS = 'tailoring.access';
    public const TAILORING_CUSTOMERS = 'tailoring.customers';
    public const TAILORING_ORDERS = 'tailoring.orders';
    public const TAILORING_WORKSHOP = 'tailoring.workshop';
    public const TAILORING_TAILORS = 'tailoring.tailors';
    public const TAILORING_CONFIGURATION = 'tailoring.configuration';
    public const CLOTHING_ACCESS = 'clothing.access';
    public const CLOTHING_SALES = 'clothing.sales';
    public const CLOTHING_INVENTORY = 'clothing.inventory';
    public const CLOTHING_PURCHASES = 'clothing.purchases';
    public const CLOTHING_SUPPLIERS = 'clothing.suppliers';
    public const FINANCE_VIEW = 'finance.view';
    public const EXPENSES_MANAGE = 'expenses.manage';
    public const SETTINGS_MANAGE = 'settings.manage';
    public const TEAM_MANAGE = 'team.manage';
    public const ACTIVITY_VIEW = 'activity.view';

    public const PERMISSIONS = [
        self::TAILORING_ACCESS => 'ٹیلرنگ سسٹم استعمال کریں',
        self::TAILORING_CUSTOMERS => 'گاہک اور پیمائش',
        self::TAILORING_ORDERS => 'ٹیلرنگ آرڈرز اور ادائیگیاں',
        self::TAILORING_WORKSHOP => 'ورکشاپ کام اور مراحل',
        self::TAILORING_TAILORS => 'درزی، اجرت اور ریٹس',
        self::TAILORING_CONFIGURATION => 'ڈیزائن اور پیمائش کی ترتیب',
        self::CLOTHING_ACCESS => 'دکان، فروخت اور خریداری استعمال کریں',
        self::CLOTHING_SALES => 'فروخت اور آن لائن آرڈرز',
        self::CLOTHING_INVENTORY => 'اسٹاک، کپڑا اور مالیت',
        self::CLOTHING_PURCHASES => 'خریداری، وصولی اور واپسی',
        self::CLOTHING_SUPPLIERS => 'سپلائرز اور ادائیگیاں',
        self::FINANCE_VIEW => 'مالی رپورٹس دیکھیں',
        self::EXPENSES_MANAGE => 'کاروباری اخراجات کا انتظام کریں',
        self::SETTINGS_MANAGE => 'دکان کی ترتیبات تبدیل کریں',
        self::TEAM_MANAGE => 'ملازمین اور رولز کا انتظام کریں',
        self::ACTIVITY_VIEW => 'ملازمین کی سرگرمی دیکھیں',
    ];

    protected $fillable = ['business_id', 'name', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}
