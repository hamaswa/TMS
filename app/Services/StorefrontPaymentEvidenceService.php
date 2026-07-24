<?php

namespace App\Services;

use App\Models\Storefront;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorefrontPaymentEvidenceService
{
    public const DISK = 'local';

    public function store(UploadedFile $file, Storefront $storefront): array
    {
        $path = $file->store(
            'storefront-payment-evidence/'.$storefront->id,
            self::DISK
        );
        if (! $path) {
            throw ValidationException::withMessages([
                'payment_evidence' => __('storefront.messages.payment_evidence_store_failed'),
            ]);
        }

        return [
            'payment_evidence_path' => $path,
            'payment_evidence_original_name' => $this->safeOriginalName($file),
            'payment_evidence_mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'payment_evidence_size' => $file->getSize(),
            'payment_evidence_submitted_at' => now(),
        ];
    }

    public function delete(?array $evidence): void
    {
        $path = $evidence['payment_evidence_path'] ?? null;
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function response(string $path, ?string $originalName, ?string $mimeType): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        $mimeType = $mimeType ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ];
        $name = $originalName ?: 'payment-evidence';

        return str_starts_with($mimeType, 'image/')
            ? Storage::disk(self::DISK)->response($path, $name, $headers)
            : Storage::disk(self::DISK)->download($path, $name, $headers);
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename($file->getClientOriginalName());
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\]+/u', '-', $name) ?: 'payment-evidence';

        return Str::limit($name, 240, '');
    }
}
