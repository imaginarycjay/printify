<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['title'] }} — {{ $shop->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1c1917;
            background: {{ ($isPdf ?? false) ? '#ffffff' : '#f5f5f4' }};
            margin: 0;
            padding: {{ ($isPdf ?? false) ? '14px' : '24px 16px' }};
            font-size: 11px;
            line-height: 1.45;
        }
        .toolbar {
            max-width: 980px;
            margin: 0 auto 14px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
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
        .sheet {
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e7e5e4;
            padding: 24px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1c1917;
            margin-bottom: 16px;
        }
        .header-table td {
            vertical-align: top;
            padding-bottom: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }
        .muted {
            color: #57534e;
            font-size: 10px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .kpi-table td {
            border: 1px solid #e7e5e4;
            background: #fafaf9;
            padding: 8px 10px;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #78716c;
            font-weight: 700;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }
        .section-heading {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #44403c;
            margin: 16px 0 8px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table th {
            background: #1c1917;
            color: #ffffff;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e7e5e4;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .pill {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: 700;
            border: 1px solid #d6d3d1;
            background: #f5f5f4;
        }
        .pill-danger {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        .pill-warn {
            background: #fef3c7;
            border-color: #fcd34d;
            color: #92400e;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print { display: none !important; }
            .sheet { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    @if (! ($isPdf ?? false))
        <div class="toolbar no-print">
            <a href="{{ route('owner.analytics-hub') }}" class="btn">&larr; Analytics &amp; Reports Hub</a>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="window.print()" class="btn">Print Report</button>
                <a href="{{ route('owner.reports.show', ['type' => $report['report_type'], 'period' => $report['period'], 'format' => 'pdf']) }}" class="btn btn-primary">Download PDF</a>
                <a href="{{ route('owner.reports.show', ['type' => $report['report_type'], 'period' => $report['period'], 'format' => 'csv']) }}" class="btn">Export CSV</a>
            </div>
        </div>
    @endif

    <div class="sheet">
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <p class="title">{{ $report['title'] }}</p>
                    <div class="muted">
                        <strong>{{ $shop->name }}</strong> &bull;
                        {{ $shop->getAttribute('address') ?: 'PressQueue Partner Shop' }}
                    </div>
                </td>
                <td style="width: 35%; text-align: right;">
                    <div class="muted">
                        Period: <strong>{{ strtoupper(str_replace('_', ' ', (string) $report['period'])) }}</strong><br>
                        Generated: {{ $report['generated_at']->format('M d, Y h:i A') }}<br>
                        PressQueue Standardized Operations Report
                    </div>
                </td>
            </tr>
        </table>

        @php($summary = $report['summary'])

        @if ($report['report_type'] === 'inventory')
            <table class="kpi-table">
                <tr>
                    <td><div class="kpi-label">Total SKUs</div><div class="kpi-value">{{ $summary['total_skus'] }}</div></td>
                    <td><div class="kpi-label">Stock Valuation</div><div class="kpi-value">₱{{ number_format((float) $summary['total_valuation'], 2) }}</div></td>
                    <td><div class="kpi-label">Low / Out of Stock</div><div class="kpi-value">{{ $summary['low_stock_count'] }} / {{ $summary['out_of_stock_count'] }}</div></td>
                    <td><div class="kpi-label">Spoilage &amp; Rework Loss</div><div class="kpi-value">₱{{ number_format((float) $summary['spoilage_loss_cost'], 2) }}</div></td>
                </tr>
            </table>

            <div class="section-heading">Current Stock Ledger</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th class="text-right">Physical</th>
                        <th class="text-right">Reserved</th>
                        <th class="text-right">Available</th>
                        <th class="text-right">Valuation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['items'] as $item)
                        <tr>
                            <td>{{ $item['sku'] }}</td>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td>{{ $item['category'] }}</td>
                            <td class="text-right">{{ $item['stock_qty'] }} {{ $item['unit'] }}</td>
                            <td class="text-right">{{ $item['reserved_qty'] }} {{ $item['unit'] }}</td>
                            <td class="text-right"><strong>{{ $item['available_qty'] }} {{ $item['unit'] }}</strong></td>
                            <td class="text-right">₱{{ number_format((float) $item['stock_value'], 2) }}</td>
                            <td><span class="pill {{ $item['status'] === 'Out of Stock' ? 'pill-danger' : ($item['status'] === 'Low Stock' ? 'pill-warn' : '') }}">{{ $item['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No inventory items recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="section-heading">Stock Movements &amp; Rework Audit</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Before &rarr; After</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['movements'] as $mov)
                        <tr>
                            <td>{{ $mov['created_at'] }}</td>
                            <td>{{ $mov['item_name'] }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $mov['movement_type'])) }}</td>
                            <td class="text-right">{{ $mov['quantity'] }} {{ $mov['unit'] }}</td>
                            <td class="text-right">{{ $mov['previous_stock'] }} &rarr; {{ $mov['resulting_stock'] }}</td>
                            <td>{{ $mov['reference_note'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No stock movements during this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif ($report['report_type'] === 'sales')
            <table class="kpi-table">
                <tr>
                    <td><div class="kpi-label">Gross Billed</div><div class="kpi-value">₱{{ number_format((float) $summary['gross_billed'], 2) }}</div></td>
                    <td><div class="kpi-label">50% Downpayments</div><div class="kpi-value">₱{{ number_format((float) $summary['downpayments_50'], 2) }}</div></td>
                    <td><div class="kpi-label">100% Prepayments</div><div class="kpi-value">₱{{ number_format((float) $summary['full_prepayments_100'], 2) }}</div></td>
                    <td><div class="kpi-label">Counter Settlements</div><div class="kpi-value">₱{{ number_format((float) $summary['counter_balance_settlements'], 2) }}</div></td>
                </tr>
                <tr>
                    <td><div class="kpi-label">Manual Refunds Settled</div><div class="kpi-value">₱{{ number_format((float) $summary['manual_refunds_settled'], 2) }}</div></td>
                    <td><div class="kpi-label">Net Cash Collected</div><div class="kpi-value">₱{{ number_format((float) $summary['net_cash_collected'], 2) }}</div></td>
                    <td><div class="kpi-label">Receivables Balance Due</div><div class="kpi-value">₱{{ number_format((float) $summary['outstanding_receivables'], 2) }}</div></td>
                    <td><div class="kpi-label">Spoilage &amp; Rework Loss</div><div class="kpi-value">₱{{ number_format((float) $summary['spoilage_rework_loss'], 2) }}</div></td>
                </tr>
            </table>

            <div class="section-heading">Verified Payment &amp; Refund Ledger</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['payments'] as $payment)
                        <tr>
                            <td>{{ $payment['verified_at'] }}</td>
                            <td><strong>{{ $payment['order_number'] }}</strong></td>
                            <td>{{ $payment['customer_name'] }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment['payment_type'])) }}</td>
                            <td>{{ $payment['reference_no'] }}</td>
                            <td class="text-right">₱{{ number_format((float) $payment['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No verified payments recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="section-heading">Orders Summary</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Tracking</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Payment Status</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Balance Due</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['orders'] as $ord)
                        <tr>
                            <td><strong>{{ $ord['order_number'] }}</strong></td>
                            <td>{{ $ord['tracking_code'] }}</td>
                            <td>{{ $ord['customer_name'] }}</td>
                            <td>{{ $ord['service_label'] }}</td>
                            <td>{{ $ord['payment_status'] }}</td>
                            <td class="text-right">₱{{ number_format((float) $ord['total_amount'], 2) }}</td>
                            <td class="text-right">₱{{ number_format((float) $ord['amount_paid'], 2) }}</td>
                            <td class="text-right">₱{{ number_format((float) $ord['balance_due'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No orders recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="kpi-table">
                <tr>
                    <td><div class="kpi-label">Shop Workload Status</div><div class="kpi-value">{{ $summary['workload_status'] }}</div></td>
                    <td><div class="kpi-label">Currently Printing</div><div class="kpi-value">{{ $summary['currently_printing_count'] }}</div></td>
                    <td><div class="kpi-label">In Finishing / QC</div><div class="kpi-value">{{ $summary['in_finishing_count'] }}</div></td>
                    <td><div class="kpi-label">Ready for Pickup (Aging)</div><div class="kpi-value">{{ $summary['ready_for_pickup_count'] }} ({{ $summary['aging_pickup_count'] }})</div></td>
                </tr>
            </table>

            @foreach ([
                'Currently Printing' => $report['currently_printing'],
                'Already Printed / In Finishing & QC' => $report['in_finishing'],
                'Ready for Pickup (Shelf / Bin & Aging)' => $report['ready_for_pickup'],
            ] as $bucketTitle => $bucketRows)
                <div class="section-heading">{{ $bucketTitle }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Service &amp; Qty</th>
                            <th>Printer</th>
                            <th>Operator</th>
                            <th>Shelf / Bin</th>
                            <th class="text-right">Balance Due</th>
                            <th>Aging</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bucketRows as $row)
                            <tr>
                                <td><strong>{{ $row['order_number'] }}</strong><div class="muted">{{ $row['tracking_code'] }}</div></td>
                                <td>{{ $row['customer_name'] }}</td>
                                <td>{{ $row['service_label'] }} (x{{ $row['copies_count'] }}, {{ $row['paper_size'] }})</td>
                                <td>{{ $row['printer_name'] }}</td>
                                <td>{{ $row['staff_name'] }}</td>
                                <td><strong>{{ $row['shelf_bin_code'] }}</strong></td>
                                <td class="text-right">₱{{ number_format((float) $row['balance_due'], 2) }}</td>
                                <td>
                                    @if ($row['is_aging'])
                                        <span class="pill pill-danger">Aging ({{ $row['aging_days'] }}d)</span>
                                    @else
                                        <span class="pill">{{ $row['stage_label'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No jobs currently in this bucket.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endforeach
        @endif
    </div>
</body>
</html>
