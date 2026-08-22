<?php

namespace App\Services;

use App\Models\Setting;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class PrintDocumentService
{
    public function make(
        ?Setting $setting,
        Request $request,
        string $documentType,
        string|int $reference
    ): array {
        $allowedPapers = array_keys(Setting::printPaperSizes());
        $requestedPaper = $request->query('paper');
        $paper = in_array($requestedPaper, $allowedPapers, true)
            ? $requestedPaper
            : ($setting?->print_paper_size ?: Setting::PRINT_PAPER_RECEIPT_80);

        if (! in_array($paper, $allowedPapers, true)) {
            $paper = Setting::PRINT_PAPER_RECEIPT_80;
        }

        $reference = (string) $reference;
        $payload = implode('|', [
            'TMS',
            'SHOP:'.($setting?->shop_slug ?: $setting?->user_id ?: 'UNCONFIGURED'),
            'TYPE:'.strtoupper($documentType),
            'REF:'.$reference,
        ]);

        return [
            'paper' => $paper,
            'paper_options' => Setting::printPaperSizes(),
            'show_qr' => (bool) $setting?->print_show_qr,
            'qr_svg' => $setting?->print_show_qr ? $this->qrSvg($payload) : null,
            'reference' => $reference,
        ];
    }

    public function qrSvg(string $payload, int $size = 160): string
    {
        $size = max(80, min(400, $size));

        $qrCode = new QrCode(
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 4,
        );
        $result = (new SvgWriter)->write($qrCode);

        return $result->getString();
    }
}
