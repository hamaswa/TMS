<?php

namespace App\Support;

use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontTailoringService;
use Illuminate\Support\Str;

class StorefrontSeo
{
    public static function description(?string $value, string $fallback): string
    {
        $description = Str::of(strip_tags($value ?: $fallback))->squish()->toString();

        return Str::limit($description, 160, '');
    }

    public static function json(array $schema): string
    {
        return (string) json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
    }

    public static function graph(array ...$nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($nodes)),
        ];
    }

    public static function website(string $name, string $description, string $url): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $url.'#website',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $url.'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function localBusiness(Storefront $storefront): array
    {
        $url = route('storefront.show', $storefront);
        $schema = [
            '@type' => 'LocalBusiness',
            '@id' => $url.'#business',
            'name' => $storefront->display_name,
            'url' => $url,
            'description' => self::description(
                $storefront->description ?: $storefront->tagline,
                __('storefront.home.default_description')
            ),
            'image' => self::absoluteUrl($storefront->cover_url ?: $storefront->logo_url),
            'telephone' => $storefront->public_phone,
            'email' => $storefront->public_email,
            'areaServed' => $storefront->city ?: __('storefront.common.pakistan'),
        ];
        if ($storefront->address || $storefront->city) {
            $schema['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $storefront->address,
                'addressLocality' => $storefront->city,
                'addressCountry' => 'PK',
            ]);
        }

        return array_filter($schema, fn ($value) => $value !== null && $value !== '');
    }

    public static function collection(
        string $name,
        string $description,
        string $url,
        ?Storefront $storefront = null
    ): array {
        $schema = [
            '@type' => 'CollectionPage',
            '@id' => $url.'#collection',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'inLanguage' => app()->getLocale(),
        ];
        if ($storefront) {
            $schema['isPartOf'] = ['@id' => route('storefront.show', $storefront).'#business'];
        }

        return $schema;
    }

    public static function product(Storefront $storefront, StorefrontClothingListing $listing): array
    {
        $url = route('storefront.clothing.show', [$storefront, $listing]);
        $images = $listing->cloth->images
            ->map(fn ($image) => self::absoluteUrl($image->image_url))
            ->filter()
            ->values()
            ->all();
        $price = (float) ($listing->cloth->sale_price ?: $listing->cloth->price);
        $available = (float) $listing->cloth->colors
            ->sum(fn ($color) => $color->reservableLength());

        return array_filter([
            '@type' => 'Product',
            '@id' => $url.'#product',
            'url' => $url,
            'name' => $listing->display_name,
            'description' => self::description(
                $listing->description,
                collect([
                    $listing->cloth->brand?->name,
                    $listing->cloth->type?->name,
                    $storefront->display_name,
                ])->filter()->implode(' · ')
            ),
            'image' => $images ?: null,
            'brand' => $listing->cloth->brand?->name
                ? ['@type' => 'Brand', 'name' => $listing->cloth->brand->name]
                : null,
            'category' => $listing->cloth->type?->name,
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => 'PKR',
                'price' => PakistanCurrency::isoAmount($price),
                'availability' => $available > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => [
                    '@type' => 'LocalBusiness',
                    '@id' => route('storefront.show', $storefront).'#business',
                    'name' => $storefront->display_name,
                ],
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }

    public static function service(Storefront $storefront, StorefrontTailoringService $service): array
    {
        $url = route('storefront.tailoring.show', [$storefront, $service]);
        $schema = [
            '@type' => 'Service',
            '@id' => $url.'#service',
            'url' => $url,
            'name' => $service->name,
            'description' => self::description(
                $service->description,
                __('storefront.tailoring.default_description')
            ),
            'serviceType' => $service->name,
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Pakistan',
            ],
            'provider' => [
                '@type' => 'LocalBusiness',
                '@id' => route('storefront.show', $storefront).'#business',
                'name' => $storefront->display_name,
            ],
        ];
        if ($service->price_from !== null) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => 'PKR',
                'price' => PakistanCurrency::isoAmount($service->price_from),
            ];
        }

        return $schema;
    }

    public static function absoluteUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
