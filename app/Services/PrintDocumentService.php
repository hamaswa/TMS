<?php

namespace App\Services;

use App\Models\Setting;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
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

    private function qrSvg(string $payload): string
    {
        $result = Builder::create()
            ->writer(new SvgWriter)
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size(160)
            ->margin(4)
            ->build();

        return $result->getString();
    }
}
