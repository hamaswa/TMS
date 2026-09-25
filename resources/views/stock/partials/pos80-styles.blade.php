<style>
    html.tms-paper-receipt_80 body.tms-stock-print {
        color: #000;
        background: #f3f3f3;
        font-weight: 800;
    }

    html.tms-paper-receipt_80 body.tms-stock-print #invoice-POS {
        box-sizing: border-box !important;
        width: 72mm !important;
        padding: 2mm !important;
        margin: 10px auto !important;
        color: #000;
        background: #fff;
        overflow: visible !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print #fullSection,
    html.tms-paper-receipt_80 body.tms-stock-print #orderSection {
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print #orderSection > div {
        margin: 0 !important;
        padding: 0 !important;
    }

    .stock-receipt-logo {
        margin: 0 0 2px !important;
        line-height: 1 !important;
        text-align: center;
    }

    .stock-receipt-logo img {
        display: block;
        width: 72px !important;
        max-height: 72px;
        margin: 0 auto;
        object-fit: contain;
    }

    .stock-receipt-shop {
        margin: 1px 0 2px !important;
        color: #000 !important;
        font-size: 17px !important;
        font-weight: 900 !important;
        line-height: 1.5 !important;
        text-align: center;
    }

    .stock-receipt-number {
        margin: 0 0 5px !important;
        color: #000 !important;
        font-family: Arial, sans-serif;
        font-size: 11px !important;
        font-weight: 900 !important;
        line-height: 1.35 !important;
        overflow-wrap: anywhere;
        text-align: center;
    }

    .stock-customer-info {
        display: grid;
        gap: 1px;
        margin: 0 0 6px;
        padding: 4px 1px;
        border-top: 1px solid #bbb;
        border-bottom: 1px solid #bbb;
    }

    .stock-customer-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        min-width: 0;
        color: #000;
        font-size: 11.5px;
        font-weight: 900;
        line-height: 1.55;
    }

    .stock-customer-row span {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .stock-customer-row strong {
        min-width: 0;
        color: #000;
        font-weight: 900;
        overflow-wrap: anywhere;
        text-align: left;
    }

    .stock-customer-row.is-ltr strong {
        direction: ltr;
        font-family: Arial, sans-serif;
        font-size: 10.5px;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table {
        width: 100% !important;
        margin: 0 !important;
        color: #000;
        border-collapse: collapse !important;
        table-layout: fixed !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table th,
    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table td {
        padding: 4px 1px !important;
        border-color: #aaa !important;
        color: #000 !important;
        font-size: 10px !important;
        font-weight: 900 !important;
        line-height: 1.45 !important;
        vertical-align: top !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table th:first-child,
    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table td:first-child {
        direction: rtl;
        font-family: 'Noto Nastaliq Urdu', serif;
        font-size: 10.5px !important;
        overflow-wrap: anywhere;
        text-align: right !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table th:not(:first-child),
    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table td:not(:first-child) {
        direction: ltr;
        font-family: Arial, sans-serif;
        font-size: 9.5px !important;
        text-align: center !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table thead th {
        border-top: 1px solid #777 !important;
        border-bottom: 1px solid #777 !important;
        background: #fff !important;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .stock-items-table tbody b {
        color: #000;
        font-weight: 900 !important;
    }

    .stock-items-list {
        display: flex;
        flex-direction: column;
        direction: rtl;
    }

    .stock-item-card {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 5px 1px;
        border-top: 1px solid #999;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .stock-item-card:last-child {
        border-bottom: 1px solid #999;
    }

    .stock-item-heading {
        display: flex;
        align-items: baseline;
        gap: 6px;
        min-width: 0;
        color: #000;
        font-size: 12.5px;
        font-weight: 900;
        line-height: 1.55;
    }

    .stock-item-heading strong {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .stock-item-number {
        direction: ltr;
        flex: 0 0 auto;
        font: 900 10px/1.3 Arial, sans-serif;
    }

    .stock-item-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 2px 10px;
        color: #000;
        font-size: 10.5px;
        font-weight: 800;
        line-height: 1.5;
    }

    .stock-item-calculation {
        direction: ltr;
        display: grid;
        grid-template-columns: auto auto auto auto minmax(0, 1fr);
        align-items: baseline;
        gap: 5px;
        color: #000;
        font: 800 10.5px/1.45 Arial, sans-serif;
        text-align: left;
    }

    .stock-item-calculation strong {
        font-size: 12px;
        font-weight: 900;
        overflow-wrap: anywhere;
        text-align: right;
    }

    .stock-items-empty {
        padding: 10px 0;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
        color: #000;
        font-size: 15px;
        font-weight: 900;
        text-align: center;
    }

    .stock-order-summary {
        display: flex;
        flex-direction: column;
        gap: 1px;
        margin-top: 5px;
        padding: 5px 1px;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
        direction: rtl;
    }

    .stock-order-row {
        display: grid;
        grid-template-columns: minmax(0, 55%) minmax(0, 45%);
        align-items: baseline;
        gap: 6px;
        min-height: 0;
        padding: 1px 0;
    }

    .stock-order-label {
        min-width: 0;
        color: #000;
        font-family: 'Noto Nastaliq Urdu', serif;
        font-size: 12px;
        font-weight: 900;
        line-height: 1.55;
        text-align: right;
        white-space: nowrap;
    }

    .stock-order-value {
        min-width: 0;
        color: #000;
        direction: ltr;
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.4;
        overflow-wrap: anywhere;
        text-align: left;
    }

    .stock-order-row.is-grand-total {
        padding-top: 2px;
    }

    .stock-order-row.is-grand-total .stock-order-label,
    .stock-order-row.is-grand-total .stock-order-value,
    .stock-order-row.is-balance .stock-order-label,
    .stock-order-row.is-balance .stock-order-value {
        font-size: 13px;
    }

    .stock-receipt-footer {
        width: 100%;
        padding-top: 4px;
        color: #000;
        text-align: center;
    }

    .stock-receipt-footer p,
    .stock-receipt-footer b {
        margin: 1px 0 !important;
        color: #000 !important;
        font-size: 10px !important;
        font-weight: 900 !important;
        line-height: 1.45 !important;
    }

    .stock-built-by {
        direction: ltr;
        margin-top: 4px !important;
        padding-top: 3px;
        border-top: 1px dotted #aaa;
        font-family: Arial, sans-serif;
        font-size: 8.5px !important;
        letter-spacing: .15px;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .tms-print-qr {
        gap: 5px;
        margin: 5px auto 0;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .tms-print-qr svg {
        width: 54px;
        height: 54px;
    }

    html.tms-paper-receipt_80 body.tms-stock-print .tms-print-qr-reference {
        font-size: 8px;
    }

    @media print {
        @page tms-receipt {
            size: 80mm auto;
            margin: 2mm 4mm;
        }

        html.tms-paper-receipt_80 body.tms-stock-print #invoice-POS {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
    }
</style>
