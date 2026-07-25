<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public const PRINT_PAPER_RECEIPT_80 = 'receipt_80';

    public const PRINT_PAPER_A4 = 'a4';

    protected $fillable = [
        'user_id',
        'name',
        'logo',
        'note',
        'address',
        'status',
        'contact',
        'contact_no',
        'shop_slug',
        'print_paper_size',
        'print_show_qr',
    ];

    protected function casts(): array
    {
        return [
            'print_show_qr' => 'boolean',
        ];
    }

    public static function printPaperSizes(): array
    {
        return [
            self::PRINT_PAPER_RECEIPT_80 => '80 ملی میٹر رسید',
            self::PRINT_PAPER_A4 => 'A4 مکمل صفحہ',
        ];
    }

    /**
     * Create the minimum active print profile required by orders and invoices.
     *
     * Existing settings are never reactivated or overwritten: a client may
     * deliberately keep every profile inactive while changing their branding.
     */
    public static function ensureDefaultFor(User $owner): self
    {
        $ownerId = $owner->businessOwnerId();
        $name = trim((string) ($owner->ownedBusiness?->name ?: $owner->name));

        return static::firstOrCreate(
            ['user_id' => $ownerId],
            [
                'name' => $name !== '' ? $name : 'TMS Shop',
                'logo' => '',
                'note' => '',
                'address' => '',
                'status' => 1,
                'contact_no' => '',
                'shop_slug' => 'tms-shop-'.$ownerId,
                'print_paper_size' => self::PRINT_PAPER_RECEIPT_80,
                'print_show_qr' => true,
            ]
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        $filename = basename(str_replace('\\', '/', (string) $this->logo));
        if ($filename === '' || ! is_file(public_path('images/setting/'.$filename))) {
            return null;
        }

        return asset('images/setting/'.$filename);
    }
}
