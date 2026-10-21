const fs = require('fs');
const path = require('path');
const { Resvg } = require('./node_modules/@resvg/resvg-js');

function esc(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

// 1920 x 1080 Slide Standard
const W = 1920;
const H = 1080;

const topBannerY = 20;
const topBannerH = 65;

const headerY = topBannerY + topBannerH + 15; // 100
const headerH = 46;

const startY = headerY + headerH + 10; // 156
const bottomMargin = 26;
const rowH = Math.floor((H - startY - bottomMargin) / 5); // 179px

const startX = 35;
const colPhaseW = 250;
const colCustW = 515;
const colStaffW = 570;
const colOwnerW = 515;

const colPhaseX = startX;
const colCustX = colPhaseX + colPhaseW; // 285
const colStaffX = colCustX + colCustW;   // 800
const colOwnerX = colStaffX + colStaffW; // 1370
const totalTableW = colPhaseW + colCustW + colStaffW + colOwnerW; // 1850

let svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif">
  <defs>
    <!-- Arrowhead markers -->
    <marker id="arr-gray" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#64748b" />
    </marker>
    <marker id="arr-red" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#dc2626" />
    </marker>
    <marker id="arr-blue" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#2563eb" />
    </marker>

    <!-- Card Shadow -->
    <filter id="card-shadow" x="-6%" y="-8%" width="112%" height="120%">
      <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#0f172a" flood-opacity="0.07"/>
    </filter>
  </defs>

  <!-- Pure White Background for Slide Clarity -->
  <rect width="${W}" height="${H}" fill="#ffffff"/>

  <!-- Top Title Banner -->
  <rect x="${startX}" y="${topBannerY}" width="${totalTableW}" height="${topBannerH}" rx="8" fill="#0f172a"/>
  <text x="${startX + totalTableW / 2}" y="${topBannerY + 28}" text-anchor="middle" fill="#ffffff" font-size="20" font-weight="800" letter-spacing="0.5">CURRENT PROCESS OF PRINTING SERVICES (AS-IS SWIMLANE DIAGRAM)</text>
  <text x="${startX + totalTableW / 2}" y="${topBannerY + 50}" text-anchor="middle" fill="#94a3b8" font-size="13.5" font-weight="500">Mapping Traditional Manual Workflows, Fragmented Communication, and Operational Bottlenecks Across Shop Stakeholders</text>

  <!-- Column Header Row -->
  <!-- Phase Header -->
  <rect x="${colPhaseX}" y="${headerY}" width="${colPhaseW - 3}" height="${headerH}" rx="6" fill="#334155"/>
  <text x="${colPhaseX + colPhaseW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="14" font-weight="700" letter-spacing="0.4">OPERATIONAL PHASE</text>

  <!-- Customer Header -->
  <rect x="${colCustX}" y="${headerY}" width="${colCustW - 3}" height="${headerH}" rx="6" fill="#1e40af"/>
  <text x="${colCustX + colCustW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="14" font-weight="700" letter-spacing="0.4">CUSTOMER (CLIENT)</text>

  <!-- Counter & Production Staff Header -->
  <rect x="${colStaffX}" y="${headerY}" width="${colStaffW - 3}" height="${headerH}" rx="6" fill="#0f766e"/>
  <text x="${colStaffX + colStaffW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="14" font-weight="700" letter-spacing="0.4">COUNTER &amp; PRODUCTION STAFF</text>

  <!-- Business Owner Header -->
  <rect x="${colOwnerX}" y="${headerY}" width="${colOwnerW}" height="${headerH}" rx="6" fill="#581c87"/>
  <text x="${colOwnerX + colOwnerW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="14" font-weight="700" letter-spacing="0.4">BUSINESS OWNER / MANAGEMENT</text>
`;

// Draw Grid Lines
// Horizontal row lines
for (let i = 0; i <= 5; i++) {
  const y = startY + (i * rowH);
  const strokeColor = (i === 0 || i === 5) ? '#94a3b8' : '#e2e8f0';
  const strokeW = (i === 0 || i === 5) ? '2' : '1.2';
  svg += `<line x1="${startX}" y1="${y}" x2="${startX + totalTableW}" y2="${y}" stroke="${strokeColor}" stroke-width="${strokeW}"/>\n`;
}

// Vertical Column separators (dashed subtle lines)
[colCustX, colStaffX, colOwnerX, startX + totalTableW].forEach(x => {
  svg += `<line x1="${x - 1.5}" y1="${startY}" x2="${x - 1.5}" y2="${startY + 5 * rowH}" stroke="#cbd5e1" stroke-width="1.2" stroke-dasharray="5 4"/>\n`;
});

// Card renderer function
function renderCard(x, y, w, h, title, bullets, opts = {}) {
  const isBottleneck = !!opts.bottleneck;
  const isWarning = !!opts.warning;

  let bg = '#ffffff';
  let stroke = '#cbd5e1';
  let strokeWidth = '1.3';
  let titleColor = '#0f172a';

  if (isBottleneck) {
    bg = '#fff1f2';
    stroke = '#f43f5e';
    strokeWidth = '1.8';
    titleColor = '#9f1239';
  } else if (isWarning) {
    bg = '#fffbeb';
    stroke = '#f59e0b';
    strokeWidth = '1.5';
    titleColor = '#92400e';
  }

  let badge = '';
  let titleY = y + 23;
  if (isBottleneck) {
    badge = `
      <rect x="${x + 10}" y="${y + 7}" width="105" height="17" rx="3" fill="#ffe4e6" stroke="#fb7185" stroke-width="0.8"/>
      <text x="${x + 62}" y="${y + 19}" font-size="9.5" fill="#be123c" font-weight="800" text-anchor="middle">⚠ BOTTLENECK</text>
    `;
    titleY = y + 41;
  }

  let content = `
    <g filter="url(#card-shadow)">
      <rect x="${x}" y="${y}" width="${w}" height="${h}" rx="6" fill="${bg}" stroke="${stroke}" stroke-width="${strokeWidth}"/>
      ${badge}
      <text x="${x + 12}" y="${titleY}" font-size="12.5" font-weight="700" fill="${titleColor}">${esc(title)}</text>
  `;

  const bulletStartY = titleY + 17;
  bullets.forEach((b, idx) => {
    content += `<text x="${x + 12}" y="${bulletStartY + (idx * 15)}" font-size="11" fill="#475569" font-weight="450">${esc(b)}</text>\n`;
  });

  content += `</g>\n`;
  return content;
}

// Phase badge on the left column
function renderPhaseHeader(rowIdx, line1, line2) {
  const y = startY + (rowIdx * rowH) + 12;
  const h = rowH - 24;
  return `
    <rect x="${colPhaseX + 6}" y="${y}" width="${colPhaseW - 14}" height="${h}" rx="8" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.4"/>
    <circle cx="${colPhaseX + 32}" cy="${y + h / 2}" r="16" fill="#1e293b"/>
    <text x="${colPhaseX + 32}" y="${y + h / 2 + 5}" font-size="13" font-weight="800" fill="#ffffff" text-anchor="middle">${rowIdx + 1}</text>
    <text x="${colPhaseX + 58}" y="${y + h / 2 - 6}" font-size="12.5" font-weight="800" fill="#0f172a">${esc(line1)}</text>
    <text x="${colPhaseX + 58}" y="${y + h / 2 + 12}" font-size="12.5" font-weight="800" fill="#0f172a">${esc(line2)}</text>
  `;
}

// ==========================================
// ROW 1: PRICING & QUOTATION
// ==========================================
svg += renderPhaseHeader(0, 'PRICING &', 'QUOTATION');
const r1Y = startY + (0 * rowH) + 12;

// Customer box
svg += renderCard(colCustX + 16, r1Y + 15, 470, 85, 'Inquires Rates & Job Specifications', [
  '• Walks in or chats Facebook page for printing & binding rates',
  '• Specifies paper type, color pages, binding style, & quantity',
  '• Waits at counter while staff looks up pricelists'
]);

// Arrow Cust -> Staff
svg += `<path d="M ${colCustX + 486} ${r1Y + 57} L ${colStaffX + 18} ${r1Y + 57}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

// Staff Box: manual calculation
svg += renderCard(colStaffX + 22, r1Y + 2, 520, 72, 'Manual Tariff Lookup & Math', [
  '• Consults printed paper charts & calculates fees by hand',
  '• Manually computes duplex page counts, color pages, & binding'
]);

// Staff Bottleneck Box (Rose/Red)
svg += renderCard(colStaffX + 22, r1Y + 79, 520, 75, 'Slow Quotations & Price Discrepancies', [
  '• Inconsistent pricing given by different staff members',
  '• Math mistakes on duplex counts, binding add-ons, & volume tiers'
], { bottleneck: true });

// Owner Box
svg += renderCard(colOwnerX + 16, r1Y + 20, 480, 92, 'Manual Rate Updates & Spreadsheets', [
  '• Maintains offline Excel pricelists & printed shop tariffs',
  '• Rate updates take days to communicate across shifts & branches',
  '• No real-time synchronization of consumable cost hikes'
], { warning: true });


// ==========================================
// ROW 2: ORDER INTAKE & PAYMENT
// ==========================================
svg += renderPhaseHeader(1, 'ORDER INTAKE', '& PAYMENT');
const r2Y = startY + (1 * rowH) + 12;

// Cust Box: File transfer
svg += renderCard(colCustX + 16, r2Y + 2, 470, 72, 'File Handover & Payment Attempt', [
  '• Hands over USB thumbdrive or sends files via Messenger',
  '• Pays cash at counter or shows personal GCash transfer receipt'
]);

// Cust Substrate Box ("Dala ang Papel")
svg += renderCard(colCustX + 16, r2Y + 79, 470, 72, 'Customer-Supplied Paper ("Dala")', [
  '• Hands over personal paper pack without formal receipt slip',
  '• Expects paper deduction without standard sheet verification'
]);

// Arrow Cust -> Staff (two parallel arrows)
svg += `<path d="M ${colCustX + 486} ${r2Y + 38} L ${colStaffX + 18} ${r2Y + 38}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;
svg += `<path d="M ${colCustX + 486} ${r2Y + 115} L ${colStaffX + 18} ${r2Y + 115}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

// Staff Box: Physical Logbook
svg += renderCard(colStaffX + 22, r2Y + 2, 520, 72, 'Physical Logbook & Handwritten Slips', [
  '• Writes specs on paper job slip & logs cash in daily notebook',
  '• Checks GCash reference code manually on personal smartphone'
]);

// Staff Bottleneck: Lost files & Fake payments
svg += renderCard(colStaffX + 22, r2Y + 79, 520, 75, 'Scattered Files & Payment Fraud Risks', [
  '• Misplaced paper slips; USB files mixed across multiple PCs',
  '• Unverified or reused GCash screenshots slip through undetected'
], { bottleneck: true });

// Owner Bottleneck: Paper deduction leakages
svg += renderCard(colOwnerX + 16, r2Y + 20, 480, 92, 'Unmonitored Substrate & Cash Leakages', [
  '• No audit trail for customer-supplied paper discounts',
  '• Unreconciled cash drawers & unaccounted walk-in transactions',
  '• Vulnerable to unlogged jobs and employee pocketing'
], { bottleneck: true });


// ==========================================
// ROW 3: JOB SCHEDULING & PRODUCTION
// ==========================================
svg += renderPhaseHeader(2, 'JOB SCHEDULING', '& PRODUCTION');
const r3Y = startY + (2 * rowH) + 12;

// Staff Step 1 & 2
svg += renderCard(colStaffX + 22, r3Y + 2, 245, 72, 'Verbal Queue Handoff', [
  '• Physical paper job slip handed off',
  '• Jobs queued by memory or piles'
]);

svg += `<path d="M ${colStaffX + 267} ${r3Y + 38} L ${colStaffX + 292} ${r3Y + 38}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

svg += renderCard(colStaffX + 295, r3Y + 2, 247, 72, 'Physical Machine Work', [
  '• Printing, collating, & binding',
  '• Machine status unrecorded'
]);

// Staff Bottleneck: Floor Chaos
svg += renderCard(colStaffX + 22, r3Y + 79, 520, 75, 'Shop Floor Chaos & Queue Jams', [
  '• No visual status board; rush jobs jump line without tracking',
  '• Multiple staff collide on the same printer; deadlines missed'
], { bottleneck: true });

// Arrow Staff -> Owner
svg += `<path d="M ${colStaffX + 542} ${r3Y + 55} L ${colOwnerX + 14} ${r3Y + 55}" stroke="#dc2626" stroke-width="1.8" stroke-dasharray="4 3" fill="none" marker-end="url(#arr-red)"/>\n`;

// Owner Box: Blindness
svg += renderCard(colOwnerX + 16, r3Y + 20, 480, 92, 'Zero Real-Time Shop Floor Visibility', [
  '• Owner unaware of machine bottlenecks, breakdowns, and delays',
  '• Cannot evaluate staff operator workload or daily throughput',
  '• Blind to rush-order queue disruptions and promised deadlines'
], { bottleneck: true });


// ==========================================
// ROW 4: INVENTORY & REPLENISHMENT
// ==========================================
svg += renderPhaseHeader(3, 'INVENTORY &', 'REPLENISHMENT');
const r4Y = startY + (3 * rowH) + 12;

// Staff Box: Unrecorded Stock Consumption
svg += renderCard(colStaffX + 22, r4Y + 2, 520, 72, 'Unrecorded Stock Consumption', [
  '• Takes paper reams, toner, & coil spines without deduction log',
  '• Material scrap & misprints thrown in bin without recording waste'
]);

// Arrow Staff -> Owner
svg += `<path d="M ${colStaffX + 542} ${r4Y + 38} L ${colOwnerX + 14} ${r4Y + 38}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

// Owner Guesswork
svg += renderCard(colOwnerX + 16, r4Y + 2, 480, 72, 'Visual Checks & Guesswork Reordering', [
  '• Visually checks stockroom shelves when walking past',
  '• Reorders supplies based on subjective gut feeling, not burn rates'
]);

// Owner Bottleneck: Stockouts
svg += renderCard(colOwnerX + 16, r4Y + 79, 480, 75, 'Peak-Season Stockouts & Emergency Fees', [
  '• Sudden thesis paper stockouts halt shop production abruptly',
  '• Emergency retail restocking incurs exorbitant supplier rush costs'
], { bottleneck: true });


// ==========================================
// ROW 5: ORDER TRACKING & FINANCIALS
// ==========================================
svg += renderPhaseHeader(4, 'ORDER TRACKING', '& FINANCIALS');
const r5Y = startY + (4 * rowH) + 12;

// Customer Follow-ups
svg += renderCard(colCustX + 16, r5Y + 2, 470, 72, 'Repeated Shop Inquiries & Follow-ups', [
  '• Visits shop in person or calls repeatedly: "Tapos na po ba?"',
  '• Forced to wait in shop lobby due to unexpected finish delays'
]);

// Arrow Cust -> Staff
svg += `<path d="M ${colCustX + 486} ${r5Y + 38} L ${colStaffX + 18} ${r5Y + 38}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

// Staff Box: Physical Search
svg += renderCard(colStaffX + 22, r5Y + 2, 520, 72, 'Physical Search Across Finished Piles', [
  '• Drops ongoing machine work to search through finished piles',
  '• Cannot provide definitive ETA if job is delayed at binding'
]);

// Cust Bottleneck: Frustration
svg += renderCard(colCustX + 16, r5Y + 79, 470, 75, 'Customer Frustration & Missed Deadlines', [
  '• Wasted travel time, anxious waiting, & missed school submissions',
  '• Erosion of customer trust leading to competitor defection'
], { bottleneck: true });

// Owner Bottleneck: Delayed Reports
svg += renderCard(colOwnerX + 16, r5Y + 20, 480, 96, 'Delayed & Inaccurate Financial Reporting', [
  '• Manually consolidates handwritten ledgers at end of month',
  '• Material costs and spoilage scrap are completely unaccounted for',
  '• Unreliable profit figures hinder pricing decisions and expansion'
], { bottleneck: true });

svg += `</svg>`;

// Render SVG to PNG via Resvg
try {
  const resvg = new Resvg(svg, {
    fitTo: { mode: 'width', value: 1920 }
  });
  const pngData = resvg.render();
  const pngBuffer = pngData.asPng();

  const outPath = path.resolve(__dirname, 'as_is_swimlane_diagram.png');
  fs.writeFileSync(outPath, pngBuffer);
  console.log('SUCCESS: Generated AS-IS diagram PNG at:', outPath);
  console.log('File size:', (pngBuffer.length / 1024).toFixed(1), 'KB');
} catch (err) {
  console.error('ERROR rendering SVG:', err);
  process.exit(1);
}
