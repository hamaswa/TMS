@php($printPaper = $printConfig['paper'] ?? \App\Models\Setting::PRINT_PAPER_RECEIPT_80)
<script>
    document.documentElement.classList.add(@json('tms-paper-'.$printPaper));
</script>
<style>
    :root {
        --tms-print-page-width: 80mm;
        --tms-print-content-width: 74mm;
        --tms-print-page-margin: 3mm;
    }

    html.tms-paper-a4 {
        --tms-print-page-width: 210mm;
        --tms-print-content-width: 186mm;
        --tms-print-page-margin: 12mm;
    }

    html[class*="tms-paper-"] body {
        min-width: 0 !important;
        overflow-x: hidden;
    }

    html[class*="tms-paper-"] #invoice-POS,
    html[class*="tms-paper-"] .receipt,
    html[class*="tms-paper-"] .tms-print-document {
        box-sizing: border-box !important;
        width: var(--tms-print-content-width) !important;
        max-width: 100% !important;
        margin: 12px auto !important;
        overflow: visible !important;
    }

    html.tms-paper-a4 #invoice-POS,
    html.tms-paper-a4 .receipt,
    html.tms-paper-a4 .tms-print-document {
        padding: 10mm !important;
    }

    html[class*="tms-paper-"] table {
        max-width: 100% !important;
    }

    html.tms-paper-receipt_80 table {
        table-layout: fixed !important;
        font-size: 9px !important;
        line-height: 1.3 !important;
    }

    html.tms-paper-receipt_80 table th,
    html.tms-paper-receipt_80 table td {
        padding: 4px 1px !important;
        overflow-wrap: anywhere;
        word-break: normal !important;
    }

    html.tms-paper-receipt_80 table th:not(:first-child),
    html.tms-paper-receipt_80 table td:not(:first-child) {
        direction: ltr;
        font-family: Arial, sans-serif;
        font-size: 8px !important;
        text-align: center !important;
    }

    /* Keep the Urdu order receipt readable inside a true 80mm paper width. */
    html.tms-paper-receipt_80 body.tms-order-print #invoice-POS {
        padding: 3mm !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print #orderSection {
        width: 100% !important;
        max-width: none !important;
        margin-top: 0 !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print #orderSection > h1 {
        margin: 0 0 8px !important;
        font-size: 17px !important;
        line-height: 1.7 !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print #orderSection > .pl-3.pr-3 {
        padding-right: 0 !important;
        padding-left: 0 !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .receipt-date,
    html.tms-paper-receipt_80 body.tms-order-print .order-section h2 {
        margin: 0 !important;
        font-size: 15px !important;
        line-height: 1.55 !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-detail-list {
        gap: 2px !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-detail-row {
        display: grid !important;
        grid-template-columns: minmax(0, 56%) minmax(0, 44%);
        gap: 7px !important;
        min-height: 0 !important;
        padding: 4px 0;
        border-bottom: 1px dotted #d3d3d3;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-detail-label,
    html.tms-paper-receipt_80 body.tms-order-print .order-detail-value {
        min-width: 0 !important;
        font-size: 14px !important;
        line-height: 1.75 !important;
        overflow-wrap: break-word !important;
        word-break: normal !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-detail-label {
        text-align: right !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-detail-value {
        text-align: left !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-tracking-qr {
        gap: 8px;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-tracking-qr svg {
        flex-basis: 72px;
        width: 72px;
        height: 72px;
    }

    html.tms-paper-receipt_80 body.tms-order-print .order-tracking-qr__text {
        max-width: none;
        font-size: 10px;
        line-height: 1.7;
    }

    html.tms-paper-receipt_80 body.tms-order-print .measurement-label,
    html.tms-paper-receipt_80 body.tms-order-print .measurement-value {
        font-size: 14px !important;
        line-height: 1.65 !important;
    }

    html.tms-paper-receipt_80 body.tms-order-print .measurement-label {
        white-space: normal !important;
    }

    body.tms-stock-print .printbtn {
        display: none !important;
    }

    body.tms-sale-print .receipt-actions,
    body.tms-worker-print .actions {
        display: none !important;
    }

    html[class*="tms-paper-"] img {
        max-width: 100%;
    }

    html[class*="tms-paper-"] tr,
    html[class*="tms-paper-"] .tms-print-section,
    html[class*="tms-paper-"] .summary,
    html[class*="tms-paper-"] .info {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .tms-print-toolbar {
        direction: rtl;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        width: min(var(--tms-print-content-width), calc(100% - 20px));
        margin: 10px auto;
        font-family: "Noto Nastaliq Urdu", Tahoma, sans-serif;
    }

    .tms-print-toolbar a,
    .tms-print-toolbar button {
        appearance: none;
        border: 1px solid #1677c8;
        border-radius: 7px;
        background: #fff;
        color: #155f9d;
        cursor: pointer;
        padding: 7px 12px;
        font: inherit;
        line-height: 1.4;
        text-decoration: none;
    }

    .tms-print-toolbar .is-active,
    .tms-print-toolbar .tms-print-now {
        background: #1677c8;
        color: #fff;
    }

    .tms-print-qr {
        direction: ltr;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin: 12px auto 4px;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .tms-print-qr svg {
        display: block;
        width: 76px;
        height: 76px;
    }

    .tms-print-qr-reference {
        color: #52616b;
        font: 10px/1.35 Arial, sans-serif;
        overflow-wrap: anywhere;
    }

    @media print {
        @page {
            size: var(--tms-print-page-width) auto;
            margin: var(--tms-print-page-margin);
        }

        html.tms-paper-a4 {
            page: tms-a4;
        }

        html.tms-paper-receipt_80 {
            page: tms-receipt;
        }

        @page tms-a4 {
            size: A4 portrait;
            margin: 12mm;
        }

        @page tms-receipt {
            size: 80mm auto;
            margin: 3mm;
        }

        .tms-print-toolbar,
        .actions,
        .receipt-actions,
        .printbtn {
            display: none !important;
        }

        html[class*="tms-paper-"] body {
            margin: 0 !important;
            background: #fff !important;
        }

        html[class*="tms-paper-"] #invoice-POS,
        html[class*="tms-paper-"] .receipt,
        html[class*="tms-paper-"] .tms-print-document {
            width: 100% !important;
            margin: 0 auto !important;
            box-shadow: none !important;
        }

        html.tms-paper-a4 #invoice-POS,
        html.tms-paper-a4 .receipt,
        html.tms-paper-a4 .tms-print-document {
            padding: 0 !important;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }
    }
</style>
