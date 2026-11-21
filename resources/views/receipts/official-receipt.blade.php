<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OR-{{ $order->order_number }} — Official Receipt</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1c1917;
            background-color: {{ ($isPdf ?? false) ? '#ffffff' : '#f5f5f4' }};
            margin: 0;
            padding: {{ ($isPdf ?? false) ? '16px' : '24px 16px' }};
            font-size: 12px;
            line-height: 1.45;
        }
        .toolbar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #d6d3d1;
            background: #ffffff;
            color: #1c1917;
        }
        .btn-primary {
            background: #d97706;
            border-color: #d97706;
            color: #ffffff;
        }
        .receipt-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e7e5e4;
            border-radius: {{ ($isPdf ?? false) ? '0' : '12px' }};
            padding: 28px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1c1917;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header-table td {
            vertical-align: top;
            padding-bottom: 14px;
        }
        .shop-title {
            font-size: 20px;
            font-weight: 700;
            color: #1c1917;
            margin: 0 0 4px 0;
        }
        .muted {
            color: #57534e;
            font-size: 11px;
        }
        .receipt-badge {
            text-align: right;
        }
        .receipt-tag {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 3px 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .or-number {
            font-size: 16px;
            font-weight: 700;
            color: #1c1917;
            margin: 2px 0;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            background: #fafaf9;
            border: 1px solid #e7e5e4;
        }
        .meta-table td {
            width: 50%;
            padding: 10px 12px;
            vertical-align: top;
            border-right: 1px solid #e7e5e4;
        }
        .meta-table td:last-child {
            border-right: none;
        }
        .section-heading {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #57534e;
            margin: 16px 0 8px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table th {
            background: #1c1917;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e7e5e4;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .totals-wrap td.spacer {
            width: 52%;
            vertical-align: top;
            padding-right: 16px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e7e5e4;
        }
        .totals-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e7e5e4;
        }
        .totals-table tr.grand td {
            background: #fafaf9;
            font-weight: 700;
            font-size: 13px;
        }
        .totals-table tr.balance td {
            font-weight: 700;
            color: {{ ((float) $order->balance_due) > 0 ? '#b45309' : '#047857' }};
        }
        .status-pill {
            display: inline-block;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            border: 1px solid #d6d3d1;
            background: #f5f5f4;
            color: #44403c;
        }
        .footer-note {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px dashed #d6d3d1;
            font-size: 10px;
            color: #78716c;
            text-align: center;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .receipt-sheet {
                border: none;
                border-radius: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @if (! ($isPdf ?? false))
        @php
            $receiptBack = \App\Support\Navigation::backTarget(auth()->user(), 'orders.receipt')
                ?? \App\Support\Navigation::workspaceHome(auth()->user());
        @endphp
        <div class="toolbar no-print">
            <div>
                <a href="{{ $receiptBack['url'] }}" class="btn">&larr; {{ $receiptBack['label'] }}</a>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="window.print()" class="btn">Print Receipt</button>
                <a href="{{ route('orders.receipt', ['order' => $order, 'format' => 'pdf']) }}" class="btn btn-primary">Download PDF</a>
            </div>
        </div>
    @endif

    @php
        $paymentOptionLabel = match ($order->payment_option) {
            \App\Models\Order::PAYMENT_OPTION_DOWNPAYMENT_50 => '50% Downpayment + Balance on Pickup',
            \App\Models\Order::PAYMENT_OPTION_COUNTER_OFFLINE => 'Pay at Shop Counter',
            default => '100% Full Upfront Payment',
        };
    @endphp

    <div class="receipt-sheet">
        <table class="header-table">
            <tr>
                <td style="width: 58%;">
                    <p class="shop-title">{{ $order->printShop?->name ?? 'PressQueue Partner Shop' }}</p>
                    <div class="muted">
                        {{ $order->printShop?->getAttribute('address') ?: 'Address on file' }}<br>
                        @if ($order->printShop?->getAttribute('contact'))
                            Contact: {{ $order->printShop->getAttribute('contact') }}<br>
                        @endif
                        Powered by PressQueue Print Operations
                    </div>
                </td>
                <td class="receipt-badge" style="width: 42%;">
                    <span class="receipt-tag">Official Receipt</span>
                    <div class="or-number">OR-{{ $order->order_number }}</div>
                    <div class="muted">
                        Order No: <strong>{{ $order->order_number }}</strong><br>
                        Tracking Code: <strong>{{ $order->tracking_code ?? '—' }}</strong><br>
                        Issued: {{ now()->format('M d, Y h:i A') }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td>
                    <strong>Billed To</strong><br>
                    {{ $order->customer?->name ?? 'Customer' }}<br>
                    <span class="muted">{{ $order->customer?->email ?? 'No email on record' }}</span>
                </td>
                <td>
                    <strong>Order &amp; Payment Details</strong><br>
                    Status: <span class="status-pill">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
                    &nbsp;Payment: <span class="status-pill">{{ $order->paymentStatusLabel() }}</span><br>
                    <span class="muted">
                        Plan: {{ $paymentOptionLabel }} &bull;
                        {{ $order->is_rush ? 'Rush Service' : 'Standard Lead Time' }}
                        @if ($order->target_completion_date)
                            &bull; Est. Ready: {{ $order->target_completion_date->format('M d, Y') }}
                        @endif
                    </span>
                </td>
            </tr>
        </table>

        <div class="section-heading">Itemized Print &amp; Binding Services</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 50%;">Service &amp; Specifications</th>
                    <th class="text-center" style="width: 10%;">Copies</th>
                    <th class="text-right" style="width: 16%;">Unit Price</th>
                    <th class="text-right" style="width: 18%;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $idx => $item)
                    @php
                        $serviceKey = $item->resolvedServiceKey();
                        $serviceMeta = \App\Services\PrintServiceCatalog::find($serviceKey);
                        $serviceName = $serviceMeta['name'] ?? ucwords(str_replace('_', ' ', $serviceKey));
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $serviceName }}</strong>
                            @if ($item->isThesisBinding())
                                — {{ ucfirst($item->binding_type) }} ({{ $item->isCoverOnly() ? 'Cover Only' : 'Full Package' }})
                            @else
                                — {{ $item->getFinishingLabel() }}
                            @endif
                            <div class="muted">
                                Paper: {{ $item->paper_size }} &bull;
                                Sides: {{ ucfirst($item->getPrintSides()) }} &bull;
                                Pages: {{ $item->bw_pages_count }} B&amp;W / {{ $item->color_pages_count }} Color ({{ $item->total_pages_count }} total)
                                @if ($item->cover_color && $item->cover_color !== 'none')
                                    &bull; Cover: {{ $item->cover_color }}
                                @endif
                                @if ($item->foil_color)
                                    &bull; Foil: {{ $item->foil_color }}
                                @endif
                                @if ($item->document_original_name)
                                    <br>File: {{ $item->document_original_name }}
                                @endif
                                @if (! empty($item->selected_addons))
                                    <br>Add-ons:
                                    @foreach ($item->selected_addons as $addon)
                                        {{ $addon['name'] ?? 'Add-on' }} (₱{{ number_format((float) ($addon['price'] ?? 0), 2) }})@if (! $loop->last), @endif
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        <td class="text-center">{{ $item->copies_count }}</td>
                        <td class="text-right">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-right"><strong>₱{{ number_format((float) $item->total_price, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($order->payments->isNotEmpty())
            <div class="section-heading">Payment Transaction Ledger</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Reference No.</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->payments as $payment)
                        <tr>
                            <td>{{ ($payment->verified_at ?? $payment->submitted_at ?? $payment->created_at)?->format('M d, Y h:i A') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td><strong>{{ $payment->reference_no ?? '—' }}</strong></td>
                            <td>
                                <span class="status-pill">{{ ucwords(str_replace('_', ' ', $payment->status)) }}</span>
                                @if ($payment->rejection_reason)
                                    <div class="muted">{{ $payment->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="text-right">₱{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table class="totals-wrap">
            <tr>
                <td class="spacer">
                    <div class="muted">
                        <strong>Terms &amp; Notes</strong><br>
                        Please present Tracking Code <strong>{{ $order->tracking_code ?? $order->order_number }}</strong> upon claiming your finished job.<br>
                        Any remaining balance must be settled before final release.
                        @if ($order->staff_notes)
                            <br><br>Order Note: {{ $order->staff_notes }}
                        @endif
                    </div>
                </td>
                <td>
                    <table class="totals-table">
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right">₱{{ number_format((float) $order->subtotal_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Rush Processing Fee</td>
                            <td class="text-right">₱{{ number_format((float) $order->rush_fee_amount, 2) }}</td>
                        </tr>
                        <tr class="grand">
                            <td>Order Total</td>
                            <td class="text-right">₱{{ number_format((float) $order->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Verified Amount Paid</td>
                            <td class="text-right">₱{{ number_format((float) $order->amount_paid, 2) }}</td>
                        </tr>
                        <tr class="balance">
                            <td>Remaining Balance Due</td>
                            <td class="text-right">₱{{ number_format((float) $order->balance_due, 2) }}</td>
                        </tr>
                        @if ((float) $order->refund_due > 0)
                            <tr>
                                <td>Refund Due ({{ ucwords(str_replace('_', ' ', $order->refund_status)) }})</td>
                                <td class="text-right">₱{{ number_format((float) $order->refund_due, 2) }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Electronic Official Receipt {{ 'OR-'.$order->order_number }} &bull;
            @if ($order->verifiedBy)
                Last Verified By: {{ $order->verifiedBy->name }}
                @if ($order->payment_verified_at)
                    on {{ $order->payment_verified_at->format('M d, Y h:i A') }}
                @endif
            @else
                Generated via PressQueue
            @endif
        </div>
    </div>
</body>
</html>
