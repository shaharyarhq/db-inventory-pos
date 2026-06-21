@php
    $generalSettings = app(App\Settings\GeneralSettings::class);
@endphp
@if (!$params['group_variants'])
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Sale Invoice #{!! $record->sale_number !!}</title>
        <style>
            /*-----------------------------------------------
                RESET & BASE STYLES
            -----------------------------------------------*/
            html,
            body {
                height: auto;
                overflow: visible;
                margin: 0;
                padding: 0;
            }

            /* ULTRA COMPACT - 2 invoices per page (5-6 items each) */
            body {
                margin: 0;
                padding: 0.2cm;
                font-family: 'Trebuchet MS', 'Helvetica', 'Arial', sans-serif;
                font-size: 7.5pt;
                line-height: 1.25;
                color: #1e2b37;
                background: #fff;
                max-width: 100%;
            }

            /*-----------------------------------------------
                INVOICE WRAPPER
            -----------------------------------------------*/
            .sale-wrapper {
                position: relative;
                border: 0.8px solid #d9e0e6;
                border-bottom: 1.2px solid #cbd2d9;
                border-right: 1.2px solid #cbd2d9;
                background: #fff;
                padding: 0.2cm;
                margin-bottom: 0.2cm;
            }

            /*-----------------------------------------------
                CLEARFIX
            -----------------------------------------------*/
            .clearfix:before,
            .clearfix:after {
                content: "";
                display: table;
            }

            .clearfix:after {
                clear: both;
            }

            .clearfix {
                zoom: 1;
            }

            /*-----------------------------------------------
                HEADER SECTION
            -----------------------------------------------*/
            .header-left {
                float: left;
                width: 49%;
                margin: 0 0 0.1cm;
            }

            .header-right {
                float: right;
                width: 49%;
                margin: 0 0 0.1cm;
                text-align: right;
            }

            .company-name {
                font-size: 16pt;
                font-weight: normal;
                letter-spacing: 0.3px;
                color: #0b1c26;
                margin: 0;
                padding: 0 0 1px;
                line-height: 1.15;
                border-bottom: 1.8px solid #2e7d32;
                display: inline-block;
            }

            .company-detail {
                font-size: 6.5pt;
                color: #405b69;
                margin: 2px 0 0;
                line-height: 1.2;
            }

            /* Document meta */
            .doc-label {
                font-size: 10pt;
                font-weight: bold;
                color: #2e7d32;
                text-transform: uppercase;
                letter-spacing: 0.7px;
                margin: 0;
            }

            .doc-number {
                font-size: 12pt;
                color: #1e2b37;
                margin: 0;
                font-weight: bold;
                border-bottom: 1.8px solid #2e7d32;
                padding-bottom: 1px;
                display: inline-block;
            }

            .doc-meta {
                font-size: 6.5pt;
                color: #5b7482;
                margin-top: 0.06cm;
                line-height: 1.2;
            }

            /*-----------------------------------------------
                PARTY CARDS (Customer & Outlet)
            -----------------------------------------------*/
            .party-section {
                width: 100%;
                margin: 0.1cm 0 0.15cm;
                border-collapse: collapse;
            }

            .party-section td {
                width: 48%;
                vertical-align: top;
                background: #f6f9fc;
                border: 0.8px solid #dde5ec;
                padding: 0.15cm;
            }

            .party-title {
                font-size: 9pt;
                font-weight: bold;
                color: #1c3b4f;
                margin: 0 0 0.06cm;
                text-transform: uppercase;
                border-bottom: 1.8px solid #8ba0ae;
                padding-bottom: 1px;
                display: inline-block;
            }

            .party-detail {
                font-size: 7pt;
                color: #2a3f4d;
                line-height: 1.2;
                margin: 0.06cm 0 0;
            }

            .party-detail strong {
                color: #0f2938;
                font-size: 7.5pt;
            }

            .badge {
                background: #2c4556;
                color: #fff;
                padding: 1px 6px;
                font-size: 6pt;
                text-transform: uppercase;
                letter-spacing: 0.2px;
                display: inline-block;
                margin-top: 4px;
            }

            .badge-customer {
                background: #2e7d32;
            }

            /*-----------------------------------------------
                DESCRIPTION CARD
            -----------------------------------------------*/
            .desc-card {
                background: #e8f5e9;
                border: 0.8px solid #a5d6a7;
                padding: 0.15cm;
                margin: 0.15cm 0;
            }

            .desc-label {
                font-size: 7pt;
                font-weight: bold;
                color: #2e7d32;
                text-transform: uppercase;
                margin: 0 0 1.5px;
            }

            .desc-text {
                font-size: 7pt;
                color: #2b4b5e;
                font-style: italic;
                line-height: 1.2;
            }

            /*-----------------------------------------------
                SUMMARY INFO ROW
            -----------------------------------------------*/
            .summary-info {
                width: 100%;
                margin: 0.06cm 0;
                font-size: 6.5pt;
                color: #1e2b37;
            }

            .summary-info .info-box {
                background: #f0f5fa;
                border: 0.8px solid #ccdae5;
                padding: 1.5px 6px;
                display: inline-block;
            }

            .summary-info strong {
                color: #2e7d32;
            }

            /*-----------------------------------------------
                ITEMS TABLE
            -----------------------------------------------*/
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0.15cm 0;
                font-size: 7pt;
                table-layout: fixed;
            }

            .items-table th {
                background: #2e7d32;
                color: #fff;
                font-weight: normal;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                font-size: 6.5pt;
                padding: 4px;
                border: 0.8px solid #1b5e20;
                text-align: left;
            }

            .items-table td {
                padding: 4px;
                border: 0.8px solid #cddae3;
                color: #1f2e39;
                vertical-align: top;
            }

            .items-table tr.alt td {
                background: #f7fafd;
            }

            .qty-cell,
            .rate-cell,
            .total-cell {
                text-align: left;
                font-weight: bold;
            }

            .product-sku {
                font-size: 5.5pt;
                color: #6f8a9c;
                display: block;
                line-height: 1.1;
                margin-top: 1px;
            }

            /*-----------------------------------------------
                SUMMARY PANELS (Left & Right)
            -----------------------------------------------*/
            .summary-left-panel {
                float: left;
                width: 48%;
                margin: 0.1cm 0;
                border-collapse: collapse;
            }

            .summary-left-panel td {
                padding: 3px 5px;
                border: 0.8px solid #ccdae5;
                font-size: 7pt;
            }

            .summary-left-panel .label {
                background: #eef3f8;
                font-weight: bold;
                color: #1f3f52;
                text-align: left;
                width: 60%;
            }

            .summary-left-panel .value {
                text-align: right;
                background: #fff;
                font-weight: bold;
            }

            .summary-panel {
                float: right;
                width: 48%;
                margin: 0.1cm 0;
                border-collapse: collapse;
            }

            .summary-panel td {
                padding: 3px 5px;
                border: 0.8px solid #ccdae5;
                font-size: 7pt;
            }

            .summary-panel .label {
                background: #eef3f8;
                font-weight: bold;
                color: #1f3f52;
                text-align: left;
                width: 60%;
            }

            .summary-panel .value {
                text-align: right;
                background: #fff;
                font-weight: bold;
            }

            .summary-panel .total-row td {
                background: #e8f5e9;
                font-size: 8.5pt;
                font-weight: bold;
                color: #2e7d32;
                border: 0.8px solid #a5d6a7;
                padding: 4px;
            }

            .positive {
                color: #2e7d32;
            }

            .negative {
                color: #c62828;
            }

            /*-----------------------------------------------
                FOOTER (3-column layout)
            -----------------------------------------------*/
            .footer-note {
                width: 100%;
                margin-top: 0.15cm;
                border-top: 1.8px solid #2e7d32;
                padding-top: 0.1cm;
                overflow: hidden;
            }

            .footer-left {
                float: left;
                width: 33%;
                text-align: left;
            }

            .footer-center {
                float: left;
                width: 34%;
                text-align: center;
            }

            .footer-right {
                float: right;
                width: 33%;
                text-align: right;
            }

            .disclaimer-text {
                font-size: 6pt;
                color: #6f8a9c;
                font-style: italic;
            }

            .stamp {
                display: inline-block;
                padding: 2px 8px;
                border: 1.5px dashed #2c4556;
                background: #f0f5fa;
                font-size: 8pt;
                font-weight: bold;
                color: #2c4556;
                letter-spacing: 1px;
                opacity: 0.9;
                text-transform: uppercase;
            }

            hr {
                border: 0;
                border-top: 1.2px solid #a5d6a7;
                margin: 0.1cm 0;
            }

            /*-----------------------------------------------
                WATERMARK
            -----------------------------------------------*/
            .watermark {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                text-align: center;
                opacity: 0.08;
                padding-top: 5.8cm;
                pointer-events: none;
                z-index: 1;
            }

            .watermark img {
                max-width: 60%;
                max-height: 5cm;
                object-fit: contain;
            }

            /*-----------------------------------------------
                MARKETING FOOTER
            -----------------------------------------------*/
            .marketing-footer {
                text-align: center;
                color: #6f8a9c;
                font-size: 5.5pt;
                margin-top: 0.05cm;
                border-top: 0.5px dotted #ccdae5;
                padding-top: 0.05cm;
            }

            .marketing-footer span {
                display: inline-block;
            }

            /*-----------------------------------------------
                OVERRIDES & PAGE SETTINGS
            -----------------------------------------------*/
            div[style*="margin-top:0.7cm"] {
                margin-top: 0.15cm !important;
            }

            div[style*="height:0.1cm"] {
                height: 0.04cm !important;
            }

            @page {
                size: A4;
                margin: 0.4cm;
            }
        </style>
    </head>

    <body>

        @php
            $getImagePath = function ($setting) use ($generalSettings) {
                $image = $generalSettings->$setting ?? '';
                if (!$image) {
                    return null;
                }

                $url = filter_var($image, FILTER_VALIDATE_URL)
                    ? $image
                    : config('app.url') . '/storage/' . ltrim($image, '/');

                $path = public_path(str_replace(config('app.url'), '', $url));
                return file_exists($path) ? $path : null;
            };

            $watermarkPath = $getImagePath('invoice_watermark_logo');
            $logoPath = $getImagePath('site_logo');
            $footerLogoPath = $getImagePath('invoice_footer_logo');

            $totalItems = $record->items->count();
            $totalQty = $record->items->sum('qty');
            $subtotal = $record->items->sum('total');
            $previousBalance = $record->customer->getCustomerBalanceAsOf($record->created_at);
            $updatedBalance = $previousBalance + $record->grand_total;

            $copies = [
                ['label' => 'Office Copy', 'footer_right' => 'no_signature'],
                ['label' => 'Customer Copy', 'footer_right' => 'signature'],
            ];
        @endphp

        @foreach ($copies as $copy)
            <div class="sale-wrapper">
                @if ($watermarkPath)
                    <div class="watermark">
                        <img src="{{ $watermarkPath }}" alt="Watermark">
                    </div>
                @endif

                <!-- HEADER -->
                <div class="clearfix">
                    <div class="header-left">
                        @if ($logoPath)
                            <img src="{{ $logoPath }}" alt="{{ $generalSettings->site_name ?? 'Logo' }}"
                                style="max-height: 1.8cm; max-width: 5.5cm; margin-bottom: 0.06cm; display: block;">
                        @else
                            <div class="company-name">{{ $generalSettings->site_name ?? 'My App' }}</div>
                        @endif
                    </div>
                    <div class="header-right">
                        <div class="doc-label">sale invoice</div>
                        <div class="doc-number">{{ $record->sale_number }}</div>
                        <div class="doc-meta">
                            {{ $record->created_at->format(app_date_time_format()) }} | By: {{ $record->creator->name }}
                        </div>
                    </div>
                </div>

                <!-- Customer & Outlet Details -->
                <table class="party-section" cellspacing="0">
                    <tr>
                        <td class="left-cell">
                            <div class="party-title">customer</div>
                            <div class="party-detail">
                                <strong>{{ $record->customer->name }}</strong><br>
                                {{ $record->customer->address ?? '' }}
                                @if ($record->customer->area || $record->customer->city)
                                    <br>{{ $record->customer->area?->name }} {{ $record->customer->city?->name }}
                                @endif
                                @if ($record->customer->contact)
                                    <br><strong>Tel:</strong> {{ $record->customer->contact }}
                                @endif
                            </div>
                        </td>
                        <td class="right-cell">
                            <div class="party-title">outlet</div>
                            <div class="party-detail">
                                <strong>{{ $record->outlet->name }}</strong><br>
                                {{ $record->outlet->address ?? '' }}
                                @if ($record->outlet->phone_number)
                                    <br><strong>Tel:</strong> {{ $record->outlet->phone_number }}
                                @endif
                                @if ($record->rider)
                                    <br><strong>Rider:</strong> {{ $record->rider->name }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Description -->
                @if ($record->description)
                    <div class="desc-card">
                        <div class="desc-label">note</div>
                        {{-- <div class="desc-text">{{ Str::limit($record->description, 90) }}</div> --}}
                        <div class="desc-text">{{ $record->description }}</div>
                    </div>
                @endif

                <!-- Summary Info -->
                <div class="summary-info clearfix">
                    <div class="info-box">
                        <strong>Items:</strong> {{ $totalItems }} | <strong>Qty:</strong>
                        {{ qty_format($totalQty) }}
                    </div>
                </div>

                <!-- Items Table -->
                <table class="items-table" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="4%">#</th>
                            <th width="12%">Qty</th>
                            <th width="48%">Product</th>
                            <th width="13%">Price</th>
                            <th width="11%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($record->items as $index => $item)
                            @php
                                $details = collect([$item->product->brand?->name, $item->product->category?->name])
                                    ->filter()
                                    ->map(fn($n) => "- $n")
                                    ->join(' ');
                            @endphp
                            <tr @if ($index % 2 == 1) class="alt" @endif>
                                <td>{{ $index + 1 }}</td>
                                <td class="qty-cell">{{ qty_format($item->qty) }} {{ $item->unit->symbol }}</td>
                                <td>{{ $item->product->name }} {{ $details }}</td>
                                <td class="rate-cell">{{ currency_format($item->rate ?? 0) }}</td>
                                <td class="total-cell">{{ currency_format($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Summary Totals - Left Panel (Payment) & Right Panel (Totals) -->
                <div class="clearfix">
                    <table class="summary-left-panel" cellspacing="0">
                        <tr>
                            <td class="label">Amount Received</td>
                            <td class="value"></td>
                        </tr>
                        <tr>
                            <td class="label">Net Balance</td>
                            <td class="value"></td>
                        </tr>
                    </table>

                    <table class="summary-panel" cellspacing="0">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">{{ currency_format($subtotal) }}</td>
                        </tr>
                        @if ($record->discount_amount > 0)
                            <tr>
                                <td class="label">Discount</td>
                                <td class="value negative">-{{ currency_format($record->discount_amount) }}</td>
                            </tr>
                        @endif
                        @if ($record->delivery_charges > 0)
                            <tr>
                                <td class="label">Delivery</td>
                                <td class="value">{{ currency_format($record->delivery_charges) }}</td>
                            </tr>
                        @endif
                        @if ($record->tax_charges > 0)
                            <tr>
                                <td class="label">Tax</td>
                                <td class="value">{{ currency_format($record->tax_charges) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td class="label">Grand total</td>
                            <td class="value">{{ currency_format($record->grand_total) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Previous balance</td>
                            <td class="value">{{ currency_format($previousBalance) }}</td>
                        </tr>
                        <tr>
                            <td class="label">New balance</td>
                            <td style="font-weight: bold; font-size: 8.5pt;"
                                class="value {{ $updatedBalance >= 0 ? 'positive' : 'negative' }}">
                                {{ currency_format($updatedBalance) }}
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="clear:both; height:0.06cm;"></div>

                <!-- Footer -->
                <div class="footer-note clearfix">
                    <div class="footer-left">
                        @if ($copy['footer_right'] === 'signature')
                            <span class="disclaimer-text">Computer generated</span>
                        @else
                            <span class="stamp">Verified By</span>
                        @endif
                    </div>
                    @if ($footerLogoPath)
                        <div class="footer-center">
                            <img style="max-height: 0.7cm;" src="{{ $footerLogoPath }}" alt="Footer">
                        </div>
                    @endif
                    <div class="footer-right">
                        @if ($copy['footer_right'] === 'signature')
                            <span class="stamp">Signature</span>
                        @else
                            <span class="disclaimer-text">No signature required</span>
                        @endif
                    </div>
                </div>

                <!-- Marketing Footer - Config Driven -->
                @if (config('software.marketing_footer_enabled', false))
                    <div class="marketing-footer">
                        <span>
                            {{ config('software.marketing_headline') }}
                            <strong>{{ config('software.developer_name') }}</strong>
                        </span>
                        <br>
                        <span style="font-size:5pt;">
                            {{ collect([
                            config('software.developer_contact'),
                            config('software.developer_email'),
                            config('software.developer_portfolio'),
                        ])->filter()->join(' | ') }}
                        </span>
                    </div>
                @endif
            </div>
        @endforeach
    </body>

    </html>
@else
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Sale Invoice #{!! $record->sale_number !!}</title>
        <style>
            /*-----------------------------------------------
                RESET & BASE STYLES
            -----------------------------------------------*/
            html,
            body {
                height: auto;
                overflow: visible;
                margin: 0;
                padding: 0;
            }

            /* ULTRA COMPACT - 2 invoices per page (5-6 items each) */
            body {
                margin: 0;
                padding: 0.2cm;
                font-family: 'Trebuchet MS', 'Helvetica', 'Arial', sans-serif;
                font-size: 7.5pt;
                line-height: 1.25;
                color: #1e2b37;
                background: #fff;
                max-width: 100%;
            }

            /*-----------------------------------------------
                INVOICE WRAPPER
            -----------------------------------------------*/
            .sale-wrapper {
                position: relative;
                border: 0.8px solid #d9e0e6;
                border-bottom: 1.2px solid #cbd2d9;
                border-right: 1.2px solid #cbd2d9;
                background: #fff;
                padding: 0.2cm;
                margin-bottom: 0.2cm;
            }

            /*-----------------------------------------------
                CLEARFIX
            -----------------------------------------------*/
            .clearfix:before,
            .clearfix:after {
                content: "";
                display: table;
            }

            .clearfix:after {
                clear: both;
            }

            .clearfix {
                zoom: 1;
            }

            /*-----------------------------------------------
                HEADER SECTION
            -----------------------------------------------*/
            .header-left {
                float: left;
                width: 49%;
                margin: 0 0 0.1cm;
            }

            .header-right {
                float: right;
                width: 49%;
                margin: 0 0 0.1cm;
                text-align: right;
            }

            .company-name {
                font-size: 16pt;
                font-weight: normal;
                letter-spacing: 0.3px;
                color: #0b1c26;
                margin: 0;
                padding: 0 0 1px;
                line-height: 1.15;
                border-bottom: 1.8px solid #2e7d32;
                display: inline-block;
            }

            .company-detail {
                font-size: 6.5pt;
                color: #405b69;
                margin: 2px 0 0;
                line-height: 1.2;
            }

            /* Document meta */
            .doc-label {
                font-size: 10pt;
                font-weight: bold;
                color: #2e7d32;
                text-transform: uppercase;
                letter-spacing: 0.7px;
                margin: 0;
            }

            .doc-number {
                font-size: 12pt;
                color: #1e2b37;
                margin: 0;
                font-weight: bold;
                border-bottom: 1.8px solid #2e7d32;
                padding-bottom: 1px;
                display: inline-block;
            }

            .doc-meta {
                font-size: 6.5pt;
                color: #5b7482;
                margin-top: 0.06cm;
                line-height: 1.2;
            }

            /*-----------------------------------------------
                PARTY CARDS (Customer & Outlet)
            -----------------------------------------------*/
            .party-section {
                width: 100%;
                margin: 0.1cm 0 0.15cm;
                border-collapse: collapse;
            }

            .party-section td {
                width: 48%;
                vertical-align: top;
                background: #f6f9fc;
                border: 0.8px solid #dde5ec;
                padding: 0.15cm;
            }

            .party-title {
                font-size: 9pt;
                font-weight: bold;
                color: #1c3b4f;
                margin: 0 0 0.06cm;
                text-transform: uppercase;
                border-bottom: 1.8px solid #8ba0ae;
                padding-bottom: 1px;
                display: inline-block;
            }

            .party-detail {
                font-size: 7pt;
                color: #2a3f4d;
                line-height: 1.2;
                margin: 0.06cm 0 0;
            }

            .party-detail strong {
                color: #0f2938;
                font-size: 7.5pt;
            }

            .badge {
                background: #2c4556;
                color: #fff;
                padding: 1px 6px;
                font-size: 6pt;
                text-transform: uppercase;
                letter-spacing: 0.2px;
                display: inline-block;
                margin-top: 4px;
            }

            .badge-customer {
                background: #2e7d32;
            }

            /*-----------------------------------------------
                DESCRIPTION CARD
            -----------------------------------------------*/
            .desc-card {
                background: #e8f5e9;
                border: 0.8px solid #a5d6a7;
                padding: 0.15cm;
                margin: 0.15cm 0;
            }

            .desc-label {
                font-size: 7pt;
                font-weight: bold;
                color: #2e7d32;
                text-transform: uppercase;
                margin: 0 0 1.5px;
            }

            .desc-text {
                font-size: 7pt;
                color: #2b4b5e;
                font-style: italic;
                line-height: 1.2;
            }

            /*-----------------------------------------------
                SUMMARY INFO ROW
            -----------------------------------------------*/
            .summary-info {
                width: 100%;
                margin: 0.06cm 0;
                font-size: 6.5pt;
                color: #1e2b37;
            }

            .summary-info .info-box {
                background: #f0f5fa;
                border: 0.8px solid #ccdae5;
                padding: 1.5px 6px;
                display: inline-block;
            }

            .summary-info strong {
                color: #2e7d32;
            }

            /*-----------------------------------------------
                ITEMS TABLE
            -----------------------------------------------*/
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0.15cm 0;
                font-size: 7pt;
                table-layout: fixed;
            }

            .items-table th {
                background: #2e7d32;
                color: #fff;
                font-weight: normal;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                font-size: 6.5pt;
                padding: 4px;
                border: 0.8px solid #1b5e20;
                text-align: left;
            }

            .items-table td {
                padding: 4px;
                border: 0.8px solid #cddae3;
                color: #1f2e39;
                vertical-align: top;
            }

            .items-table tr.alt td {
                background: #f7fafd;
            }

            .qty-cell,
            .rate-cell,
            .total-cell {
                text-align: left;
                font-weight: bold;
            }

            .product-sku {
                font-size: 5.5pt;
                color: #6f8a9c;
                display: block;
                line-height: 1.1;
                margin-top: 1px;
            }

            /*-----------------------------------------------
                SUMMARY PANELS (Left & Right)
            -----------------------------------------------*/
            .summary-left-panel {
                float: left;
                width: 48%;
                margin: 0.1cm 0;
                border-collapse: collapse;
            }

            .summary-left-panel td {
                padding: 3px 5px;
                border: 0.8px solid #ccdae5;
                font-size: 7pt;
            }

            .summary-left-panel .label {
                background: #eef3f8;
                font-weight: bold;
                color: #1f3f52;
                text-align: left;
                width: 60%;
            }

            .summary-left-panel .value {
                text-align: right;
                background: #fff;
                font-weight: bold;
            }

            .summary-panel {
                float: right;
                width: 48%;
                margin: 0.1cm 0;
                border-collapse: collapse;
            }

            .summary-panel td {
                padding: 3px 5px;
                border: 0.8px solid #ccdae5;
                font-size: 7pt;
            }

            .summary-panel .label {
                background: #eef3f8;
                font-weight: bold;
                color: #1f3f52;
                text-align: left;
                width: 60%;
            }

            .summary-panel .value {
                text-align: right;
                background: #fff;
                font-weight: bold;
            }

            .summary-panel .total-row td {
                background: #e8f5e9;
                font-size: 8.5pt;
                font-weight: bold;
                color: #2e7d32;
                border: 0.8px solid #a5d6a7;
                padding: 4px;
            }

            .positive {
                color: #2e7d32;
            }

            .negative {
                color: #c62828;
            }

            /*-----------------------------------------------
                FOOTER (3-column layout)
            -----------------------------------------------*/
            .footer-note {
                width: 100%;
                margin-top: 0.15cm;
                border-top: 1.8px solid #2e7d32;
                padding-top: 0.1cm;
                overflow: hidden;
            }

            .footer-left {
                float: left;
                width: 33%;
                text-align: left;
            }

            .footer-center {
                float: left;
                width: 34%;
                text-align: center;
            }

            .footer-right {
                float: right;
                width: 33%;
                text-align: right;
            }

            .disclaimer-text {
                font-size: 6pt;
                color: #6f8a9c;
                font-style: italic;
            }

            .stamp {
                display: inline-block;
                padding: 2px 8px;
                border: 1.5px dashed #2c4556;
                background: #f0f5fa;
                font-size: 8pt;
                font-weight: bold;
                color: #2c4556;
                letter-spacing: 1px;
                opacity: 0.9;
                text-transform: uppercase;
            }

            hr {
                border: 0;
                border-top: 1.2px solid #a5d6a7;
                margin: 0.1cm 0;
            }

            /*-----------------------------------------------
                WATERMARK
            -----------------------------------------------*/
            .watermark {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                text-align: center;
                opacity: 0.08;
                padding-top: 5.8cm;
                pointer-events: none;
                z-index: 1;
            }

            .watermark img {
                max-width: 60%;
                max-height: 5cm;
                object-fit: contain;
            }

            /*-----------------------------------------------
                MARKETING FOOTER
            -----------------------------------------------*/
            .marketing-footer {
                text-align: center;
                color: #6f8a9c;
                font-size: 5.5pt;
                margin-top: 0.05cm;
                border-top: 0.5px dotted #ccdae5;
                padding-top: 0.05cm;
            }

            .marketing-footer span {
                display: inline-block;
            }

            /*-----------------------------------------------
                OVERRIDES & PAGE SETTINGS
            -----------------------------------------------*/
            div[style*="margin-top:0.7cm"] {
                margin-top: 0.15cm !important;
            }

            div[style*="height:0.1cm"] {
                height: 0.04cm !important;
            }

            @page {
                size: A4;
                margin: 0.4cm;
            }
        </style>
    </head>

    <body>

        @php
            $getImagePath = function ($setting) use ($generalSettings) {
                $image = $generalSettings->$setting ?? '';
                if (!$image) {
                    return null;
                }

                $url = filter_var($image, FILTER_VALIDATE_URL)
                    ? $image
                    : config('app.url') . '/storage/' . ltrim($image, '/');

                $path = public_path(str_replace(config('app.url'), '', $url));
                return file_exists($path) ? $path : null;
            };

            $watermarkPath = $getImagePath('invoice_watermark_logo');
            $logoPath = $getImagePath('site_logo');
            $footerLogoPath = $getImagePath('invoice_footer_logo');

            $totalItems = $record->items->count();
            $totalQty = $record->items->sum('qty');
            $subtotal = $record->items->sum('total');
            $previousBalance = $record->customer->getCustomerBalanceAsOf($record->created_at);
            $updatedBalance = $previousBalance + $record->grand_total;

            // Build grouped items for print display only
            $groupedItems = collect();
            foreach ($record->items as $item) {
                $parentId = $item->product->parent_product_id ?? null;

                if ($parentId) {
                    $key = 'parent_' . $parentId;
                    if ($groupedItems->has($key)) {
                        $existing = $groupedItems->get($key);
                        $existing['qty'] += $item->qty;
                        $existing['total'] += $item->total;
                        // Weighted average rate calculation
                        $existing['rate'] = $existing['total'] / $existing['qty'];
                        $groupedItems->put($key, $existing);
                    } else {
                        // First occurrence - calculate initial average rate
                        $avgRate = $item->total / $item->qty;
                        $groupedItems->put($key, [
                            'type' => 'grouped',
                            'label' => $item->product->parent->name,
                            'unit' => $item->unit,
                            'qty' => $item->qty,
                            'total' => $item->total,
                            'rate' => $avgRate,
                        ]);
                    }
                } else {
                    $details = collect([$item->product->brand?->name, $item->product->category?->name])
                        ->filter()
                        ->map(fn($n) => "- $n")
                        ->join(' ');

                    $groupedItems->push([
                        'type' => 'single',
                        'label' => $item->product->name . ($details ? ' ' . $details : ''),
                        'unit' => $item->unit,
                        'qty' => $item->qty,
                        'total' => $item->total,
                        'rate' => $item->rate,
                    ]);
                }
            }
            $groupedItems = $groupedItems->values();

            $copies = [
                ['label' => 'Office Copy', 'footer_right' => 'no_signature'],
                ['label' => 'Customer Copy', 'footer_right' => 'signature'],
            ];
        @endphp

        @foreach ($copies as $copy)
            <div class="sale-wrapper">
                @if ($watermarkPath)
                    <div class="watermark">
                        <img src="{{ $watermarkPath }}" alt="Watermark">
                    </div>
                @endif

                <!-- HEADER -->
                <div class="clearfix">
                    <div class="header-left">
                        @if ($logoPath)
                            <img src="{{ $logoPath }}" alt="{{ $generalSettings->site_name ?? 'Logo' }}"
                                style="max-height: 1.8cm; max-width: 5.5cm; margin-bottom: 0.06cm; display: block;">
                        @else
                            <div class="company-name">{{ $generalSettings->site_name ?? 'My App' }}</div>
                        @endif
                    </div>
                    <div class="header-right">
                        <div class="doc-label">sale invoice</div>
                        <div class="doc-number">{{ $record->sale_number }}</div>
                        <div class="doc-meta">
                            {{ $record->created_at->format(app_date_time_format()) }} | By:
                            {{ $record->creator->name }}
                        </div>
                    </div>
                </div>

                <!-- Customer & Outlet Details -->
                <table class="party-section" cellspacing="0">
                    <tr>
                        <td class="left-cell">
                            <div class="party-title">customer</div>
                            <div class="party-detail">
                                <strong>{{ $record->customer->name }}</strong><br>
                                {{ $record->customer->address ?? '' }}
                                @if ($record->customer->area || $record->customer->city)
                                    <br>{{ $record->customer->area?->name }} {{ $record->customer->city?->name }}
                                @endif
                                @if ($record->customer->contact)
                                    <br><strong>Tel:</strong> {{ $record->customer->contact }}
                                @endif
                            </div>
                        </td>
                        <td class="right-cell">
                            <div class="party-title">outlet</div>
                            <div class="party-detail">
                                <strong>{{ $record->outlet->name }}</strong><br>
                                {{ $record->outlet->address ?? '' }}
                                @if ($record->outlet->phone_number)
                                    <br><strong>Tel:</strong> {{ $record->outlet->phone_number }}
                                @endif
                                @if ($record->rider)
                                    <br><strong>Rider:</strong> {{ $record->rider->name }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Description -->
                @if ($record->description)
                    <div class="desc-card">
                        <div class="desc-label">note</div>
                        {{-- <div class="desc-text">{{ Str::limit($record->description, 90) }}</div> --}}
                        <div class="desc-text">{{ $record->description }}</div>
                    </div>
                @endif

                <!-- Summary Info -->
                <div class="summary-info clearfix">
                    <div class="info-box">
                        <strong>Items:</strong> {{ $totalItems }} | <strong>Qty:</strong>
                        {{ qty_format($totalQty) }}
                    </div>
                </div>

                <!-- Items Table -->
                <table class="items-table" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="4%">#</th>
                            <th width="12%">Qty</th>
                            <th width="48%">Product</th>
                            <th width="13%">Price</th>
                            <th width="11%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedItems as $index => $row)
                            <tr @if ($index % 2 == 1) class="alt" @endif>
                                <td>{{ $index + 1 }}</td>
                                <td class="qty-cell">{{ qty_format($row['qty']) }} {{ $row['unit']->symbol }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td class="rate-cell">{{ currency_format($row['rate']) }}</td>
                                <td class="total-cell">{{ currency_format($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Summary Totals - Left Panel (Payment) & Right Panel (Totals) -->
                <div class="clearfix">
                    <table class="summary-left-panel" cellspacing="0">
                        <tr>
                            <td class="label">Amount Received</td>
                            <td class="value"></td>
                        </tr>
                        <tr>
                            <td class="label">Net Balance</td>
                            <td class="value"></td>
                        </tr>
                    </table>

                    <table class="summary-panel" cellspacing="0">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">{{ currency_format($subtotal) }}</td>
                        </tr>
                        @if ($record->discount_amount > 0)
                            <tr>
                                <td class="label">Discount</td>
                                <td class="value negative">-{{ currency_format($record->discount_amount) }}</td>
                            </tr>
                        @endif
                        @if ($record->delivery_charges > 0)
                            <tr>
                                <td class="label">Delivery</td>
                                <td class="value">{{ currency_format($record->delivery_charges) }}</td>
                            </tr>
                        @endif
                        @if ($record->tax_charges > 0)
                            <tr>
                                <td class="label">Tax</td>
                                <td class="value">{{ currency_format($record->tax_charges) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td class="label">Grand total</td>
                            <td class="value">{{ currency_format($record->grand_total) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Previous balance</td>
                            <td class="value">{{ currency_format($previousBalance) }}</td>
                        </tr>
                        <tr>
                            <td class="label">New balance</td>
                            <td style="font-weight: bold; font-size: 8.5pt;"
                                class="value {{ $updatedBalance >= 0 ? 'positive' : 'negative' }}">
                                {{ currency_format($updatedBalance) }}
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="clear:both; height:0.06cm;"></div>

                <!-- Footer -->
                <div class="footer-note clearfix">
                    <div class="footer-left">
                        @if ($copy['footer_right'] === 'signature')
                            <span class="disclaimer-text">Computer generated</span>
                        @else
                            <span class="stamp">Verified By</span>
                        @endif
                    </div>
                    @if ($footerLogoPath)
                        <div class="footer-center">
                            <img style="max-height: 0.7cm;" src="{{ $footerLogoPath }}" alt="Footer">
                        </div>
                    @endif
                    <div class="footer-right">
                        @if ($copy['footer_right'] === 'signature')
                            <span class="stamp">Signature</span>
                        @else
                            <span class="disclaimer-text">No signature required</span>
                        @endif
                    </div>
                </div>

                <!-- Marketing Footer - Config Driven -->
                @if (config('software.marketing_footer_enabled', false))
                    <div class="marketing-footer">
                        <span>
                            {{ config('software.marketing_headline') }}
                            <strong>{{ config('software.developer_name') }}</strong>
                        </span>
                        <br>
                        <span style="font-size:5pt;">
                            {{ collect([
                            config('software.developer_contact'),
                            config('software.developer_email'),
                            config('software.developer_portfolio'),
                        ])->filter()->join(' | ') }}
                        </span>
                    </div>
                @endif
            </div>
        @endforeach
    </body>

    </html>
@endif