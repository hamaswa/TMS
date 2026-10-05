<?php

namespace Database\Seeders;

use App\Models\Storefront;
use App\Models\StorefrontCollection;
use Illuminate\Database\Seeder;

class StorefrontBrandingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            'khan-tailors-fabrics-rawalpindi' => [
                'display_name_ur' => 'خان ٹیلرز اینڈ فیبرکس',
                'display_name_en' => 'Khan Tailors & Fabrics',
                'hero_title_ur' => 'کپڑا بھی، نفیس سلائی بھی',
                'hero_title_en' => 'Fine fabric. Expert tailoring.',
                'hero_text_ur' => 'راولپنڈی میں منتخب کپڑا، کسٹم پیمائش اور مکمل سلائی ایک ہی قابل اعتماد دکان سے۔',
                'hero_text_en' => 'Selected fabrics, custom measurements and complete tailoring from one trusted Rawalpindi shop.',
                'announcement_ur' => 'دکان سے وصولی اور مقامی ڈیلیوری دستیاب ہے',
                'announcement_en' => 'Shop pickup and local delivery available',
                'theme_primary_color' => '#0b5d46',
                'theme_accent_color' => '#d7a33d',
                'theme_background_color' => '#f3f7f4',
                'theme_surface_color' => '#ffffff',
                'theme_text_color' => '#16372e',
                'font_style' => 'modern',
                'hero_layout' => 'overlay',
                'hero_alignment' => 'start',
                'hero_overlay_strength' => 75,
                'hero_height' => 'compact',
                'hero_media_type' => 'image',
                'cover_path' => 'images/storefront/demo/khan-rawalpindi-hero.png',
                'hero_media_fit' => 'cover',
                'hero_media_position' => 'center',
                'hero_text_animation' => 'fade_up',
                'corner_style' => 'soft',
                'product_columns' => 3,
                'product_columns_tablet' => 2,
                'product_columns_mobile' => 1,
                'product_image_ratio' => 'portrait',
                'header_layout' => 'menu_first',
                'sticky_header' => true,
                'footer_style' => 'columns',
                'footer_text_ur' => 'اصلی کپڑا، درست پیمائش اور نفیس سلائی — راولپنڈی میں ایک ہی جگہ۔',
                'footer_text_en' => 'Authentic fabric, precise measurements and fine tailoring in Rawalpindi.',
            ],
            'bilal-tailors-peshawar' => [
                'display_name_ur' => 'بلال ٹیلرز پشاور',
                'display_name_en' => 'Bilal Tailors Peshawar',
                'hero_title_ur' => 'روایت کی پہچان، آج کی فٹنگ',
                'hero_title_en' => 'Traditional craft, modern fit.',
                'hero_text_ur' => 'پشاوری مہارت کے ساتھ شلوار قمیض، ویسٹ کوٹ اور بچوں کے ملبوسات کی سلائی۔',
                'hero_text_en' => 'Peshawari craftsmanship for shalwar kameez, waistcoats and children’s clothing.',
                'announcement_ur' => 'پیمائش اور فٹنگ ٹرائل کے لیے وقت بک کریں',
                'announcement_en' => 'Book a measurement and fitting visit',
                'theme_primary_color' => '#18324a',
                'theme_accent_color' => '#b7793e',
                'theme_background_color' => '#f5f0e8',
                'theme_surface_color' => '#fffdf8',
                'theme_text_color' => '#2c2925',
                'font_style' => 'classic',
                'hero_layout' => 'split',
                'hero_alignment' => 'start',
                'hero_overlay_strength' => 60,
                'hero_height' => 'standard',
                'hero_media_type' => 'image',
                'cover_path' => 'images/storefront/demo/bilal-peshawar-hero.png',
                'hero_media_fit' => 'cover',
                'hero_media_position' => 'center',
                'hero_text_animation' => 'slide',
                'corner_style' => 'square',
                'product_columns' => 2,
                'product_columns_tablet' => 2,
                'product_columns_mobile' => 1,
                'product_image_ratio' => 'square',
                'header_layout' => 'menu_first',
                'sticky_header' => true,
                'footer_style' => 'brand',
                'footer_text_ur' => 'پشاور کی روایتی سلائی، جدید فٹنگ اور ذمہ دار کاریگری۔',
                'footer_text_en' => 'Traditional Peshawari tailoring with modern fitting and dependable craft.',
            ],
            'ali-fabrics-faisalabad' => [
                'display_name_ur' => 'علی فیبرکس فیصل آباد',
                'display_name_en' => 'Ali Fabrics Faisalabad',
                'hero_title_ur' => 'نیا موسم، نئی کلیکشن',
                'hero_title_en' => 'New season. Fresh fabrics.',
                'hero_text_ur' => 'فیصل آباد کے معیاری موسمی کپڑے، واضح فی میٹر قیمت اور آن لائن آرڈر کی سہولت۔',
                'hero_text_en' => 'Quality seasonal fabrics from Faisalabad with clear per-metre prices and online ordering.',
                'announcement_ur' => 'منتخب آرڈرز پر گھر تک فراہمی',
                'announcement_en' => 'Home delivery on selected orders',
                'theme_primary_color' => '#7a285d',
                'theme_accent_color' => '#ef9d67',
                'theme_background_color' => '#fff6f9',
                'theme_surface_color' => '#ffffff',
                'theme_text_color' => '#3f2435',
                'font_style' => 'minimal',
                'hero_layout' => 'minimal',
                'hero_alignment' => 'center',
                'hero_overlay_strength' => 45,
                'hero_height' => 'compact',
                'hero_media_type' => 'image',
                'cover_path' => 'images/storefront/demo/ali-faisalabad-hero.png',
                'hero_media_fit' => 'cover',
                'hero_media_position' => 'center',
                'hero_text_animation' => 'fade',
                'corner_style' => 'rounded',
                'product_columns' => 4,
                'product_columns_tablet' => 2,
                'product_columns_mobile' => 2,
                'product_image_ratio' => 'portrait',
                'header_layout' => 'menu_first',
                'sticky_header' => false,
                'footer_style' => 'simple',
                'footer_text_ur' => 'فیصل آباد کے معیاری کپڑے، واضح قیمت اور آسان آن لائن آرڈر۔',
                'footer_text_en' => 'Quality Faisalabad fabrics, clear pricing and easy online ordering.',
            ],
        ];

        foreach ($presets as $slug => $branding) {
            $storefront = Storefront::where('slug', $slug)->first();
            if (! $storefront) {
                continue;
            }
            $branding['hero_media_type'] = 'image';
            if ($slug === 'khan-tailors-fabrics-rawalpindi') {
                $branding['cover_path'] = 'images/storefront/demo/khan-rawalpindi-hero.png';
            }
            $storefront->update([
                ...$branding,
                'show_nav_categories' => true,
                'show_nav_brands' => true,
                'show_featured_products' => true,
                'show_benefits' => true,
                'show_services' => true,
                'show_about' => true,
                'show_product_brand' => true,
                'show_product_category' => true,
                'show_product_stock' => true,
            ]);

            $storefront->clothingListings()->where('is_featured', true)->get()->each(function ($listing) {
                $listing->update(['tags' => collect($listing->tags)->push('premium')->push('featured')->unique()->values()->all()]);
            });

            $featured = $this->collection($storefront, 'featured', 'نمایاں کپڑے', 'Featured fabrics', 'featured', [], 10);
            $new = $this->collection($storefront, 'new-arrivals', 'نئی آمد', 'New arrivals', 'new_arrivals', [], 20);
            $sale = $this->collection($storefront, 'sale', 'خصوصی قیمت', 'Sale', 'sale', [], 30);

            $storefront->heroSlides()->delete();
            $storefront->heroSlides()->create([
                'title_ur' => $branding['hero_title_ur'], 'title_en' => $branding['hero_title_en'],
                'text_ur' => $branding['hero_text_ur'], 'text_en' => $branding['hero_text_en'],
                'media_type' => 'image', 'image_path' => $branding['cover_path'],
                'alignment' => $branding['hero_alignment'], 'overlay_strength' => $branding['hero_overlay_strength'],
                'primary_label_ur' => $storefront->show_clothing ? 'ابھی خریدیں' : 'خدمات دیکھیں',
                'primary_label_en' => $storefront->show_clothing ? 'Shop now' : 'View services',
                'primary_link_type' => $storefront->show_clothing ? 'collection' : 'tailoring',
                'primary_collection_id' => $storefront->show_clothing ? $featured->id : null,
                'secondary_label_ur' => $storefront->show_tailoring && $storefront->show_clothing ? 'سلائی کرائیں' : 'رابطہ کریں',
                'secondary_label_en' => $storefront->show_tailoring && $storefront->show_clothing ? 'Book tailoring' : 'Contact us',
                'secondary_link_type' => $storefront->show_tailoring && $storefront->show_clothing ? 'tailoring' : 'contact',
                'is_active' => true, 'sort_order' => 10,
            ]);
            if ($slug === 'khan-tailors-fabrics-rawalpindi') {
                $storefront->heroSlides()->create([
                    'title_ur' => 'اپنی پسند کا کپڑا، اپنی پیمائش کی سلائی',
                    'title_en' => 'Your fabric. Your measurements. Your fit.',
                    'text_ur' => 'کپڑا منتخب کریں، سلائی کا انداز چنیں اور اپنی محفوظ یا نئی پیمائش استعمال کریں۔',
                    'text_en' => 'Choose fabric, select a tailoring style and use saved or custom measurements.',
                    'media_type' => 'image', 'image_path' => 'images/about/tailoring-studio-hero.png',
                    'alignment' => 'center', 'overlay_strength' => 72,
                    'primary_label_ur' => 'سلائی کی خدمات', 'primary_label_en' => 'Tailoring services',
                    'primary_link_type' => 'tailoring',
                    'secondary_label_ur' => 'نئی آمد', 'secondary_label_en' => 'New arrivals',
                    'secondary_link_type' => 'collection', 'secondary_collection_id' => $new->id,
                    'is_active' => true, 'sort_order' => 20,
                ]);
            }

            $storefront->menuItems()->delete();
            if ($storefront->show_clothing) {
                $shopMenu = $storefront->menuItems()->create([
                    'label_ur' => 'کپڑے', 'label_en' => 'Shop', 'item_type' => 'catalog',
                    'location' => 'both', 'is_visible' => true, 'sort_order' => 10,
                ]);
                foreach ([[$featured, 'نمایاں', 'Featured', 10], [$new, 'نئی آمد', 'New arrivals', 20], [$sale, 'سیل', 'Sale', 30]] as [$collection, $ur, $en, $order]) {
                    $storefront->menuItems()->create([
                        'parent_id' => $shopMenu->id, 'storefront_collection_id' => $collection->id,
                        'label_ur' => $ur, 'label_en' => $en, 'item_type' => 'collection',
                        'location' => 'both', 'is_visible' => true, 'sort_order' => $order,
                    ]);
                }
            }
            if ($storefront->show_tailoring) {
                $storefront->menuItems()->create(['label_ur' => 'سلائی', 'label_en' => 'Tailoring', 'item_type' => 'tailoring', 'location' => 'header', 'is_visible' => true, 'sort_order' => 20]);
            }
            $storefront->menuItems()->create(['label_ur' => 'رابطہ', 'label_en' => 'Contact', 'item_type' => 'contact', 'location' => 'both', 'is_visible' => true, 'sort_order' => 30]);
        }
    }

    private function collection(Storefront $storefront, string $slug, string $nameUr, string $nameEn, string $source, array $rules, int $order): StorefrontCollection
    {
        return $storefront->collections()->updateOrCreate(['slug' => $slug], [
            'name_ur' => $nameUr,
            'name_en' => $nameEn,
            'description_ur' => $nameUr.' میں دکان کی تازہ منتخب مصنوعات دیکھیں۔',
            'description_en' => 'Browse the shop’s current '.$nameEn.' selection.',
            'source_type' => $source,
            'rules' => $rules,
            'sort_mode' => $source === 'new_arrivals' ? 'newest' : 'featured',
            'is_published' => true,
            'sort_order' => $order,
        ]);
    }
}
