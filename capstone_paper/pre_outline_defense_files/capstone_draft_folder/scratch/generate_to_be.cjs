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
// 5 Columns: Phase (210), Customer (365), Printify System (520), Staff (410), Owner (345) -> Total = 1850
const colPhaseW = 210;
const colCustW = 365;
const colSysW = 520;
const colStaffW = 410;
const colOwnerW = 345;

const colPhaseX = startX;
const colCustX = colPhaseX + colPhaseW; // 245
const colSysX = colCustX + colCustW;     // 610
const colStaffX = colSysX + colSysW;     // 1130
const colOwnerX = colStaffX + colStaffW; // 1540
const totalTableW = colPhaseW + colCustW + colSysW + colStaffW + colOwnerW; // 1850

// Precise card placement parameters for clean margins and arrow clearances
const custCardX = colCustX + 16;
const custCardW = 320;

const sysCardX = colSysX + 35;
const sysCardW = 450;

const staffCardX = colStaffX + 25;
const staffCardW = 345;

const ownerCardX = colOwnerX + 18;
const ownerCardW = 310;

let svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif">
  <defs>
    <!-- Arrowhead markers with standard orient="auto" for exact directional alignment -->
    <marker id="arr-gray" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#64748b" />
    </marker>
    <marker id="arr-emerald" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#059669" />
    </marker>
    <marker id="arr-blue" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#2563eb" />
    </marker>
    <marker id="arr-amber" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#d97706" />
    </marker>
    <marker id="arr-purple" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto">
      <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#7c3aed" />
    </marker>

    <!-- Card Shadow -->
    <filter id="card-shadow" x="-6%" y="-8%" width="112%" height="120%">
      <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#0f172a" flood-opacity="0.07"/>
    </filter>
  </defs>

  <!-- Pure White Background for Slide Clarity -->
  <rect width="${W}" height="${H}" fill="#ffffff"/>

  <!-- Subtle Tint for Printify System Central Column -->
  <rect x="${colSysX}" y="${headerY}" width="${colSysW}" height="${startY + (5 * rowH) - headerY}" fill="#f0fdf4" rx="4"/>

  <!-- Top Title Banner -->
  <rect x="${startX}" y="${topBannerY}" width="${totalTableW}" height="${topBannerH}" rx="8" fill="#0f172a"/>
  <text x="${startX + totalTableW / 2}" y="${topBannerY + 28}" text-anchor="middle" fill="#ffffff" font-size="20" font-weight="800" letter-spacing="0.5">PROPOSED PROCESS WITH PRINTIFY (TO-BE SWIMLANE DIAGRAM)</text>
  <text x="${startX + totalTableW / 2}" y="${topBannerY + 50}" text-anchor="middle" fill="#6ee7b7" font-size="13.5" font-weight="600">Centralized Digital Architecture, Automated Dynamic Quotations, 5-Stage Kanban Queue, BOM Deductions &amp; Live Tracking</text>

  <!-- Column Header Row -->
  <!-- Phase Header -->
  <rect x="${colPhaseX}" y="${headerY}" width="${colPhaseW - 3}" height="${headerH}" rx="6" fill="#334155"/>
  <text x="${colPhaseX + colPhaseW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="13" font-weight="700" letter-spacing="0.4">OPERATIONAL PHASE</text>

  <!-- Customer Header -->
  <rect x="${colCustX}" y="${headerY}" width="${colCustW - 3}" height="${headerH}" rx="6" fill="#1e40af"/>
  <text x="${colCustX + colCustW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="13" font-weight="700" letter-spacing="0.4">CUSTOMER (CLIENT)</text>

  <!-- PRINTIFY SYSTEM Central Column Header (Highlighted Emerald) -->
  <rect x="${colSysX}" y="${headerY}" width="${colSysW - 3}" height="${headerH}" rx="6" fill="#047857" stroke="#10b981" stroke-width="1.5"/>
  <text x="${colSysX + colSysW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="13.5" font-weight="800" letter-spacing="0.5">PRINTIFY SYSTEM (CENTRAL PLATFORM)</text>

  <!-- Production Staff Header -->
  <rect x="${colStaffX}" y="${headerY}" width="${colStaffW - 3}" height="${headerH}" rx="6" fill="#0f766e"/>
  <text x="${colStaffX + colStaffW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="13" font-weight="700" letter-spacing="0.4">PRODUCTION STAFF</text>

  <!-- Business Owner Header -->
  <rect x="${colOwnerX}" y="${headerY}" width="${colOwnerW}" height="${headerH}" rx="6" fill="#581c87"/>
  <text x="${colOwnerX + colOwnerW / 2}" y="${headerY + 28}" text-anchor="middle" fill="#ffffff" font-size="13" font-weight="700" letter-spacing="0.4">BUSINESS OWNER</text>
`;

// Draw Grid Lines
// Horizontal row lines
for (let i = 0; i <= 5; i++) {
  const y = startY + (i * rowH);
  const strokeColor = (i === 0 || i === 5) ? '#94a3b8' : '#e2e8f0';
  const strokeW = (i === 0 || i === 5) ? '2' : '1.2';
  svg += `<line x1="${startX}" y1="${y}" x2="${startX + totalTableW}" y2="${y}" stroke="${strokeColor}" stroke-width="${strokeW}"/>\n`;
}

// Vertical Column separators
[colCustX, colSysX, colStaffX, colOwnerX, startX + totalTableW].forEach((x, idx) => {
  const isSysBorder = (idx === 1 || idx === 2);
  const strokeColor = isSysBorder ? '#10b981' : '#cbd5e1';
  const strokeWidth = isSysBorder ? '1.6' : '1.2';
  const strokeDash = isSysBorder ? 'none' : '5 4';
  svg += `<line x1="${x - 1.5}" y1="${startY}" x2="${x - 1.5}" y2="${startY + 5 * rowH}" stroke="${strokeColor}" stroke-width="${strokeWidth}" stroke-dasharray="${strokeDash}"/>\n`;
});

// Card renderer function
function renderCard(x, y, w, h, title, bullets, opts = {}) {
  const isAutomated = !!opts.automated;
  const isDecision = !!opts.decision;
  const isSuccess = !!opts.success;

  let bg = '#ffffff';
  let stroke = '#cbd5e1';
  let strokeWidth = '1.3';
  let titleColor = '#0f172a';

  if (isAutomated) {
    bg = '#f0fdf4';
    stroke = '#10b981';
    strokeWidth = '1.8';
    titleColor = '#065f46';
  } else if (isDecision) {
    bg = '#fffbeb';
    stroke = '#f59e0b';
    strokeWidth = '1.6';
    titleColor = '#92400e';
  } else if (isSuccess) {
    bg = '#ecfdf5';
    stroke = '#059669';
    strokeWidth = '1.4';
    titleColor = '#047857';
  }

  let badge = '';
  let titleY = y + 23;
  if (isAutomated) {
    badge = `
      <rect x="${x + 10}" y="${y + 7}" width="105" height="17" rx="3" fill="#dcfce7" stroke="#34d399" stroke-width="0.8"/>
      <text x="${x + 62}" y="${y + 19}" font-size="9.5" fill="#047857" font-weight="800" text-anchor="middle">⚡ AUTOMATED</text>
    `;
    titleY = y + 41;
  } else if (isDecision) {
    badge = `
      <rect x="${x + 10}" y="${y + 7}" width="112" height="17" rx="3" fill="#fef3c7" stroke="#fcd34d" stroke-width="0.8"/>
      <text x="${x + 66}" y="${y + 19}" font-size="9.5" fill="#b45309" font-weight="800" text-anchor="middle">◆ VERIFICATION</text>
    `;
    titleY = y + 41;
  }

  let content = `
    <g filter="url(#card-shadow)">
      <rect x="${x}" y="${y}" width="${w}" height="${h}" rx="6" fill="${bg}" stroke="${stroke}" stroke-width="${strokeWidth}"/>
      ${badge}
      <text x="${x + 12}" y="${titleY}" font-size="12" font-weight="700" fill="${titleColor}">${esc(title)}</text>
  `;

  const bulletStartY = titleY + 16;
  bullets.forEach((b, idx) => {
    content += `<text x="${x + 12}" y="${bulletStartY + (idx * 14.5)}" font-size="10.5" fill="#475569" font-weight="450">${esc(b)}</text>\n`;
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
    <circle cx="${colPhaseX + 28}" cy="${y + h / 2}" r="15" fill="#047857"/>
    <text x="${colPhaseX + 28}" y="${y + h / 2 + 5}" font-size="12" font-weight="800" fill="#ffffff" text-anchor="middle">${rowIdx + 1}</text>
    <text x="${colPhaseX + 50}" y="${y + h / 2 - 6}" font-size="11.5" font-weight="800" fill="#0f172a">${esc(line1)}</text>
    <text x="${colPhaseX + 50}" y="${y + h / 2 + 11}" font-size="11.5" font-weight="800" fill="#0f172a">${esc(line2)}</text>
  `;
}

// ==========================================
// ROW 1: PRICING & QUOTATION
// ==========================================
svg += renderPhaseHeader(0, 'PRICING &', 'QUOTATION');
const r1Y = startY + (0 * rowH) + 12;

// Cust Box
svg += renderCard(custCardX, r1Y + 14, custCardW, 90, 'Selects Specs & Configures Job', [
  '• Browses interactive service catalog online',
  '• Selects paper GSM, duplex mode, color pages,',
  '  and book binding style with live previews'
]);

// Arrow Cust -> System (rightward)
svg += `<path d="M ${custCardX + custCardW} ${r1Y + 59} L ${sysCardX - 2} ${r1Y + 59}" stroke="#2563eb" stroke-width="1.8" fill="none" marker-end="url(#arr-blue)"/>\n`;

// Printify System Box (Automated)
svg += renderCard(sysCardX, r1Y + 8, sysCardW, 102, 'Reactive Quotation Engine', [
  '• Automatically computes exact per-page rates & sheet requirements',
  '• Calculates duplex math, volume tier discounts, & binding add-ons',
  '• Displays immediate transparent price breakdown to customer'
], { automated: true });

// Owner Box
svg += renderCard(ownerCardX, r1Y + 14, ownerCardW, 90, 'No-Code Catalog & Dynamic Tariffs', [
  '• Sets base rates, color page surcharges,',
  '  and paper substrate fees via No-Code UI',
  '• Instantly synchronizes shop-wide pricing'
]);

// Arrow Owner -> System (Leftward arrow across empty staff lane, broken neatly around center label pill)
const r1MidX = 1327;
const r1PillW = 280;
const r1PillLeft = r1MidX - (r1PillW / 2);
const r1PillRight = r1MidX + (r1PillW / 2);

svg += `<path d="M ${ownerCardX} ${r1Y + 59} L ${r1PillRight} ${r1Y + 59}" stroke="#7c3aed" stroke-width="1.8" fill="none"/>\n`;
svg += `<rect x="${r1PillLeft}" y="${r1Y + 47}" width="${r1PillW}" height="24" rx="4" fill="#faf5ff" stroke="#d8b4fe" stroke-width="1"/>\n`;
svg += `<text x="${r1MidX}" y="${r1Y + 63}" text-anchor="middle" font-size="10" font-weight="700" fill="#6d28d9">Pre-Configured Tariffs &amp; BOM Formulas</text>\n`;
svg += `<path d="M ${r1PillLeft} ${r1Y + 59} L ${sysCardX + sysCardW + 2} ${r1Y + 59}" stroke="#7c3aed" stroke-width="1.8" fill="none" marker-end="url(#arr-purple)"/>\n`;


// ==========================================
// ROW 2: ORDER INTAKE & PAYMENT
// ==========================================
svg += renderPhaseHeader(1, 'ORDER INTAKE', '& PAYMENT');
const r2Y = startY + (1 * rowH) + 12;

// Cust Box
svg += renderCard(custCardX, r2Y + 8, custCardW, 102, 'Digital Order & "Dala" Option', [
  '• Uploads document file (PDF / DOCX)',
  '• Toggles "Dala ang Papel" checkbox if',
  '  bringing own paper pack to shop',
  '• Submits GCash ref / counter payment proof'
]);

// Arrow Cust -> System (rightward)
svg += `<path d="M ${custCardX + custCardW} ${r2Y + 59} L ${sysCardX - 2} ${r2Y + 59}" stroke="#2563eb" stroke-width="1.8" fill="none" marker-end="url(#arr-blue)"/>\n`;

// Printify System Box
svg += renderCard(sysCardX, r2Y + 8, sysCardW, 102, 'Tokenized Order & Dynamic Deduction', [
  '• Validates document, creates Order ID, & generates tracking token',
  '• Auto-applies cover-only discount if "Dala ang Papel" is enabled',
  '• Queues order in pending verification state for staff'
], { automated: true });

// Arrow System -> Staff (rightward)
svg += `<path d="M ${sysCardX + sysCardW} ${r2Y + 59} L ${staffCardX - 2} ${r2Y + 59}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Staff Box (Verification)
svg += renderCard(staffCardX, r2Y + 8, staffCardW, 102, 'Counter & Payment Verification', [
  '• Cross-checks GCash ref against shop account',
  '• Inspects customer-supplied paper GSM & count',
  '• Marks order "Paid & Paper Received" in system',
  '• Releases verified ticket to active production queue'
], { decision: true });


// ==========================================
// ROW 3: JOB SCHEDULING & PRODUCTION
// ==========================================
svg += renderPhaseHeader(2, 'JOB SCHEDULING', '& PRODUCTION');
const r3Y = startY + (2 * rowH) + 12;

// Printify System: 5-Stage Kanban
svg += renderCard(sysCardX, r3Y + 8, sysCardW, 105, '5-Stage Visual Kanban Production Queue', [
  '• Organizes jobs into real-time visual stages:',
  '  [Queue] → [Printing] → [Finishing] → [QC Inspect] → [Pickup]',
  '• Live drag-and-drop status board shared across all workshop PCs'
], { automated: true });

// Arrow System -> Staff (Job Queue dispatch - rightward)
svg += `<path d="M ${sysCardX + sysCardW} ${r3Y + 40} L ${staffCardX - 2} ${r3Y + 40}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Staff Box: Operator Assignment & QC
svg += renderCard(staffCardX, r3Y + 8, staffCardW, 105, 'Operator Assignment & QC Loop', [
  '• Assigns operator & production machine in UI',
  '• Advances job through stages as physical work finishes',
  '• QC Inspection: Passes QC? -> Advance to Ready for Pickup',
  '  Failed QC? -> Steps back for rework with logged reason'
]);

// Arrow Staff -> System (QC Stepback - leftward arrow with neat QC Rework pill)
const r3GapMidX = (sysCardX + sysCardW + staffCardX) / 2;
svg += `<path d="M ${staffCardX} ${r3Y + 82} L ${r3GapMidX + 28} ${r3Y + 82}" stroke="#d97706" stroke-width="1.6" stroke-dasharray="4 3" fill="none"/>\n`;
svg += `<rect x="${r3GapMidX - 27}" y="${r3Y + 74}" width="54" height="16" rx="3" fill="#fef3c7" stroke="#fcd34d" stroke-width="0.8"/>\n`;
svg += `<text x="${r3GapMidX}" y="${r3Y + 85.5}" text-anchor="middle" font-size="8.5" font-weight="800" fill="#b45309">QC Rework</text>\n`;
svg += `<path d="M ${r3GapMidX - 27} ${r3Y + 82} L ${sysCardX + sysCardW + 2} ${r3Y + 82}" stroke="#d97706" stroke-width="1.6" stroke-dasharray="4 3" fill="none" marker-end="url(#arr-amber)"/>\n`;

// Arrow Staff -> Owner (Live Queue Status)
svg += `<path d="M ${staffCardX + staffCardW} ${r3Y + 59} L ${ownerCardX - 2} ${r3Y + 59}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

// Owner Box
svg += renderCard(ownerCardX, r3Y + 15, ownerCardW, 88, 'Live Workshop Monitoring', [
  '• Views real-time floor load & machine status',
  '• Tracks operator turnaround time and',
  '  identifies production bottlenecks instantly'
]);


// ==========================================
// ROW 4: INVENTORY & REPLENISHMENT
// ==========================================
svg += renderPhaseHeader(3, 'INVENTORY &', 'REPLENISHMENT');
const r4Y = startY + (3 * rowH) + 12;

// Printify System: BOM Deduction & ROP
svg += renderCard(sysCardX, r4Y + 5, sysCardW, 110, 'Automated BOM Deduction & Dynamic ROP', [
  '• Automatically deducts linked paper, toner, & coil spines via BOM',
  '• Bypasses paper ream deduction if marked "Customer-Supplied"',
  '• Computes rolling daily burn rate & Dynamic ROP = (d × L) + SS',
  '• Triggers system Low-Stock Alert banner when balance ≤ ROP'
], { automated: true });

// Arrow Staff -> System (Spoilage - leftward arrow)
svg += `<path d="M ${staffCardX} ${r4Y + 59} L ${sysCardX + sysCardW + 2} ${r4Y + 59}" stroke="#64748b" stroke-width="1.8" fill="none" marker-end="url(#arr-gray)"/>\n`;

// Staff Box: Spoilage logging
svg += renderCard(staffCardX, r4Y + 15, staffCardW, 88, 'Material Spoilage & Scrap Logging', [
  '• Logs paper jams, misprints, & damaged covers',
  '  directly into digital job ticket',
  '• Ensures material loss is audited immediately'
]);

// Orthogonal Arrow System -> Owner below Staff card with neat central pill
const bypassY4 = r4Y + 135;
const r4MidX = 1327;
const r4PillW = 250;
const r4PillLeft = r4MidX - (r4PillW / 2);
const r4PillRight = r4MidX + (r4PillW / 2);

svg += `<path d="M ${sysCardX + sysCardW} ${bypassY4} L ${r4PillLeft} ${bypassY4}" stroke="#059669" stroke-width="1.8" fill="none"/>\n`;
svg += `<rect x="${r4PillLeft}" y="${bypassY4 - 11}" width="${r4PillW}" height="22" rx="4" fill="#ecfdf5" stroke="#34d399" stroke-width="1"/>\n`;
svg += `<text x="${r4MidX}" y="${bypassY4 + 4}" text-anchor="middle" font-size="9.5" font-weight="700" fill="#047857">⚡ Dynamic ROP Alert (Low Stock Trigger)</text>\n`;
svg += `<path d="M ${r4PillRight} ${bypassY4} L ${ownerCardX - 16} ${bypassY4} L ${ownerCardX - 16} ${r4Y + 59} L ${ownerCardX - 2} ${r4Y + 59}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Owner Box: Restocking
svg += renderCard(ownerCardX, r4Y + 15, ownerCardW, 88, 'Proactive Restocking Alerts', [
  '• Receives automated restock notification',
  '  well before stock levels run empty',
  '• Prevents rush supply fees & thesis downtime'
], { success: true });


// ==========================================
// ROW 5: ORDER TRACKING & FINANCIALS
// ==========================================
svg += renderPhaseHeader(4, 'ORDER TRACKING', '& FINANCIALS');
const r5Y = startY + (4 * rowH) + 12;

// Cust Box
svg += renderCard(custCardX, r5Y + 15, custCardW, 88, 'Live Progress Tracking & Pickup', [
  '• Tracks 5-stage progress milestone stepper online',
  '• Visits shop only when status is "Ready for Pickup"',
  '• Eliminates repetitive shop follow-ups & wait times'
]);

// Arrow System -> Cust (Leftward arrow from System to Customer)
svg += `<path d="M ${sysCardX} ${r5Y + 59} L ${custCardX + custCardW + 2} ${r5Y + 59}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Printify System: Sync & BI Analytics
svg += renderCard(sysCardX, r5Y + 8, sysCardW, 105, 'Real-Time Milestone Sync & BI Financials', [
  '• Broadcasts live status updates to customer portal stepper',
  '• Aggregates gross revenue, material COGS, and spoilage losses',
  '• Calculates net profit margins and generates exportable ledgers'
], { automated: true });

// Arrow System -> Staff (Ready for Pickup Handover notice)
svg += `<path d="M ${sysCardX + sysCardW} ${r5Y + 59} L ${staffCardX - 2} ${r5Y + 59}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Staff Box
svg += renderCard(staffCardX, r5Y + 15, staffCardW, 88, 'Order Release & Handover', [
  '• Verifies order claim code with customer at counter',
  '• Releases finished print job & marks order "Claimed"',
  '• Completes job lifecycle with zero misplaced items'
]);

// Arrow Staff -> Owner (Claimed Transaction Settlement)
svg += `<path d="M ${staffCardX + staffCardW} ${r5Y + 45} L ${ownerCardX - 2} ${r5Y + 45}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Orthogonal Arrow System -> Owner below Staff card for BI Ledger Sync with neat central pill
const bypassY5 = r5Y + 135;
const r5MidX = 1327;
const r5PillW = 260;
const r5PillLeft = r5MidX - (r5PillW / 2);
const r5PillRight = r5MidX + (r5PillW / 2);

svg += `<path d="M ${sysCardX + sysCardW} ${bypassY5} L ${r5PillLeft} ${bypassY5}" stroke="#059669" stroke-width="1.8" fill="none"/>\n`;
svg += `<rect x="${r5PillLeft}" y="${bypassY5 - 11}" width="${r5PillW}" height="22" rx="4" fill="#ecfdf5" stroke="#34d399" stroke-width="1"/>\n`;
svg += `<text x="${r5MidX}" y="${bypassY5 + 4}" text-anchor="middle" font-size="9.5" font-weight="700" fill="#047857">⚡ Automated Sales, COGS &amp; Ledger Sync</text>\n`;
svg += `<path d="M ${r5PillRight} ${bypassY5} L ${ownerCardX - 16} ${bypassY5} L ${ownerCardX - 16} ${r5Y + 75} L ${ownerCardX - 2} ${r5Y + 75}" stroke="#059669" stroke-width="1.8" fill="none" marker-end="url(#arr-emerald)"/>\n`;

// Owner Box: BI Analytics
svg += renderCard(ownerCardX, r5Y + 12, ownerCardW, 94, 'Automated BI Financial Reports', [
  '• Views real-time sales, COGS, & net margin charts',
  '• Tracks spoilage waste costs by operator & machine',
  '• Exports auditable CSV accounting ledgers'
], { success: true });

svg += `</svg>`;

// Render SVG to PNG via Resvg
try {
  const resvg = new Resvg(svg, {
    fitTo: { mode: 'width', value: 1920 }
  });
  const pngData = resvg.render();
  const pngBuffer = pngData.asPng();

  const outPath = path.resolve(__dirname, 'to_be_swimlane_diagram.png');
  fs.writeFileSync(outPath, pngBuffer);
  console.log('SUCCESS: Generated TO-BE diagram PNG at:', outPath);
  console.log('File size:', (pngBuffer.length / 1024).toFixed(1), 'KB');
} catch (err) {
  console.error('ERROR rendering SVG:', err);
  process.exit(1);
}
