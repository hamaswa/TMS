<style>
    html.tms-paper-receipt_80 body.tms-stock-print {
        color: #000;
        background: #f3f3f3;
        font-weight: 800;
    }

    html.tms-paper-receipt_80 body.tms-stock-print #invoice-POS {
        box-sizing: border-box !important;
        width: 74mm !important;
        padding: 1.5mm !important;
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
        font-size: 18px !important;
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
        gap: 3px;
        margin: 0 0 6px;
        padding: 5px 2px;
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
        font-size: 13px;
        font-weight: 900;
        line-height: 1.7;
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
        font-size: 12px;
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
        gap: 8px;
        padding: 7px 0;
        border-top: 1px solid #999;
    }

    .stock-item-card:last-child {
        border-bottom: 1px solid #999;
    }

    .stock-item-card .stock-order-label {
        flex-basis: 42%;
    }

    .stock-item-card .stock-order-value {
        flex-basis: 58%;
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
        gap: 8px;
        margin-top: 7px;
        padding: 7px 0;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
        direction: rtl;
    }

    .stock-order-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        min-height: 30px;
    }

    .stock-order-label {
        flex: 0 0 66%;
        color: #000;
        font-family: 'Noto Nastaliq Urdu', serif;
        font-size: 18px;
        font-weight: 900;
        line-height: 1.65;
        text-align: right;
        white-space: nowrap;
    }

    .stock-order-value {
        flex: 1 1 34%;
        min-width: 0;
        color: #000;
        direction: ltr;
        font-family: Arial, sans-serif;
        font-size: 18px;
        font-weight: 900;
        line-height: 1.5;
        overflow-wrap: anywhere;
        text-align: left;
    }

    .stock-receipt-footer {
        width: 100%;
        padding-top: 5px;
        color: #000;
        text-align: center;
    }

    .stock-receipt-footer p,
    .stock-receipt-footer b {
        margin: 1px 0 !important;
        color: #000 !important;
        font-size: 11px !important;
        font-weight: 900 !important;
        line-height: 1.55 !important;
    }

    @media print {
        @page tms-receipt {
            size: 80mm auto;
            margin: 3mm;
        }

        html.tms-paper-receipt_80 body.tms-stock-print #invoice-POS {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
    }
</style>
