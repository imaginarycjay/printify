/**
 * Batch 3A Diagram Generators (CR-3):
 *   1. PressQueue_Deployment_Diagram_v5.png (Pure UML 2.5 Deployment Diagram, Scope Note stripped)
 *   2. PressQueue_Gantt_Chart_v2.png (Pure 5-Phase Modified Waterfall Gantt Chart, Jul–Dec 2026)
 */

import path from 'node:path';
import {
  COLORS,
  drawCylinder,
  drawEdgeLabel,
  drawLines,
  drawNode3D,
  esc,
  svgDoc,
} from './svg_kit.mjs';

function buildDeploymentDiagramV5(diagramsDir) {
  const width = 1640;
  const height = 840;
  const parts = [];

  // ── 1. Left Top: Client Devices ─────────────────────────────────────────────
  parts.push(
    drawNode3D({
      x: 28,
      y: 44,
      w: 286,
      h: 152,
      depth: 10,
      id: 'client-devices',
    })
  );
  parts.push(
    drawLines({
      x: 171,
      y: 66,
      boxId: 'client-devices',
      lines: [
        { text: '«device»', size: 11.5, fill: COLORS.textMuted, lh: 19 },
        { text: 'Client Devices', size: 15, weight: 700, fill: COLORS.textPrimary, lh: 22 },
        { text: 'Customer, Printing Shop Owner, Super Admin', size: 11.5, fill: COLORS.textBody, gapBefore: 8, lh: 20 },
        { text: 'Desktop or mobile web browser', size: 11.5, fill: COLORS.textBody, lh: 20 },
        { text: 'Ordering, 50%/100% proof retry & reports', size: 11.5, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );
  parts.push(`<line x1="46" y1="100" x2="296" y2="100" stroke="#cbd5e1" stroke-width="1" />`);

  // ── 2. Left Bottom: Print Shop Environment (Dashed Boundary) ────────────────
  parts.push(
    `<rect x="28" y="276" width="296" height="448" rx="6" fill="${COLORS.surfaceLight}" stroke="#64748b" stroke-width="1.5" stroke-dasharray="6 4" />`
  );
  parts.push(
    `<path d="M 28 282 Q 28 276 34 276 L 318 276 Q 324 276 324 282 L 324 310 L 28 310 Z" fill="${COLORS.surfaceHeader}" stroke="#64748b" stroke-width="1.2" />`
  );
  parts.push(
    `<text x="176" y="298" text-anchor="middle" font-size="13.5" font-weight="700" fill="${COLORS.textPrimary}">Print Shop Environment</text>`
  );

  // Shop Floor Workstation
  parts.push(
    drawNode3D({
      x: 46,
      y: 332,
      w: 252,
      h: 214,
      depth: 9,
      id: 'workstation',
    })
  );
  parts.push(
    drawLines({
      x: 172,
      y: 352,
      boxId: 'workstation',
      lines: [
        { text: '«device»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Shop Floor Workstation', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 20 },
        { text: 'Production Staff & Printing Shop Owner', size: 11, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );
  // Inner Chrome / Edge Print Dialog box
  parts.push(
    `<rect x="60" y="400" width="224" height="72" rx="5" fill="${COLORS.surfaceLight}" stroke="#475569" stroke-width="1.2" data-container-id="print-dialog" />`
  );
  parts.push(
    drawLines({
      x: 172,
      y: 418,
      boxId: 'print-dialog',
      lines: [
        { text: '«executionEnvironment»', size: 10.5, fill: COLORS.textMuted, lh: 17 },
        { text: 'Chrome / Edge Print Dialog', size: 12.5, weight: 700, fill: COLORS.textPrimary, lh: 18 },
        { text: 'Direct locked stream (no loose saves)', size: 11, fill: COLORS.textBody, lh: 16 },
      ],
    })
  );
  parts.push(
    drawLines({
      x: 172,
      y: 494,
      boxId: 'workstation',
      lines: [
        { text: 'OS printer drivers & wide-format RIP', size: 11, fill: COLORS.textBody, lh: 18 },
        { text: 'Prepress inspection & QA spoilage', size: 11, fill: COLORS.textBody, lh: 16 },
      ],
    })
  );

  // Shop Printers & Plotters
  parts.push(
    drawNode3D({
      x: 46,
      y: 616,
      w: 252,
      h: 92,
      depth: 9,
      id: 'printers',
    })
  );
  parts.push(
    drawLines({
      x: 172,
      y: 636,
      boxId: 'printers',
      lines: [
        { text: '«device»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Shop Printers & Plotters', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 20 },
        { text: 'Multi-printer fleet (A4/A3 & thesis)', size: 11, fill: COLORS.textBody, lh: 17 },
        { text: 'Specialty plotters via host RIP spool', size: 11, fill: COLORS.textBody, lh: 16 },
      ],
    })
  );

  // Vertical connection Workstation -> Printers
  parts.push(
    `<line x1="172" y1="546" x2="172" y2="606" stroke="${COLORS.stroke}" stroke-width="1.8" marker-end="url(#arrow-solid)" />`
  );
  parts.push(
    drawEdgeLabel({
      x: 172,
      y: 576,
      text: 'USB / LAN / RIP Spool',
      bg: COLORS.surfaceLight,
      size: 11,
    })
  );

  // ── 3. Right Main Container: Linux Cloud VPS (Ubuntu 24.04 LTS) ─────────────
  parts.push(
    drawNode3D({
      x: 496,
      y: 36,
      w: 1106,
      h: 688,
      depth: 12,
      id: 'vps-host',
    })
  );
  parts.push(
    drawLines({
      x: 1049,
      y: 58,
      boxId: 'vps-host',
      lines: [
        { text: '«device» Hosting Environment', size: 12, fill: COLORS.textMuted, lh: 20 },
        { text: 'Linux Cloud VPS (Ubuntu 24.04 LTS)', size: 16, weight: 700, fill: COLORS.textPrimary, lh: 22 },
      ],
    })
  );

  // Column 1 inside VPS: Nginx + PHP-FPM / PressQueue Web Application
  parts.push(
    drawNode3D({
      x: 522,
      y: 104,
      w: 346,
      h: 82,
      depth: 8,
      id: 'nginx',
    })
  );
  parts.push(
    drawLines({
      x: 695,
      y: 124,
      boxId: 'nginx',
      lines: [
        { text: '«executionEnvironment»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Nginx 1.24+ Web Server', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 21 },
        { text: 'TLS 1.3 HTTPS Reverse Proxy & CSRF', size: 11.5, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );

  // PHP-FPM outer box
  parts.push(
    drawNode3D({
      x: 522,
      y: 216,
      w: 346,
      h: 484,
      depth: 9,
      id: 'php-fpm',
    })
  );
  parts.push(
    drawLines({
      x: 695,
      y: 236,
      boxId: 'php-fpm',
      lines: [
        { text: '«executionEnvironment»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'PHP-FPM 8.4 / 8.5', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 20 },
      ],
    })
  );

  // PressQueue Web Application inner artifact box
  parts.push(
    `<rect x="540" y="268" width="310" height="414" rx="4" fill="#ffffff" stroke="#475569" stroke-width="1.4" data-container-id="laravel-app" />`
  );
  parts.push(
    drawLines({
      x: 695,
      y: 288,
      boxId: 'laravel-app',
      lines: [
        { text: '«artifact»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'PressQueue Web Application', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 20 },
        { text: 'Laravel 13 • Livewire 4 • Fortify 2FA', size: 11.5, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );

  const appModules = [
    ['Identity, RBAC & Tenant Guard', 'Onboarding, logout & tenant isolation'],
    ['Ordering, Prepress & Billing', 'Quotes, duplex, 50%/100% retry & refunds'],
    ['Queue & Direct Browser Print', 'Multi-printer failover & split pickup'],
    ['BOM Inventory & Reports', 'Row locks, spoilage & Print/PDF/CSV'],
  ];
  appModules.forEach(([title, sub], idx) => {
    const my = 342 + idx * 84;
    const modId = `app-mod-${idx}`;
    parts.push(
      `<rect x="556" y="${my}" width="278" height="72" rx="4" fill="${COLORS.surfaceLight}" stroke="#64748b" stroke-width="1.2" data-container-id="${modId}" />`
    );
    parts.push(
      drawLines({
        x: 695,
        y: my + 27,
        boxId: modId,
        lines: [
          { text: title, size: 12.5, weight: 700, fill: COLORS.textPrimary, lh: 20 },
          { text: sub, size: 11, fill: COLORS.textBody, lh: 17 },
        ],
      })
    );
  });

  // Column 2 inside VPS: Queue Worker & Task Scheduler
  parts.push(
    drawNode3D({
      x: 980,
      y: 316,
      w: 222,
      h: 108,
      depth: 8,
      id: 'queue-worker',
    })
  );
  parts.push(
    drawLines({
      x: 1091,
      y: 338,
      boxId: 'queue-worker',
      lines: [
        { text: '«executionEnvironment»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Queue Worker', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 21 },
        { text: 'Supervisor service', size: 11.5, fill: COLORS.textBody, gapBefore: 4, lh: 18 },
        { text: 'Email, aging & ROP alerts', size: 11.5, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );

  parts.push(
    drawNode3D({
      x: 980,
      y: 458,
      w: 222,
      h: 108,
      depth: 8,
      id: 'task-scheduler',
    })
  );
  parts.push(
    drawLines({
      x: 1091,
      y: 480,
      boxId: 'task-scheduler',
      lines: [
        { text: '«executionEnvironment»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Task Scheduler', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 21 },
        { text: 'Cron (1-minute runner)', size: 11.5, fill: COLORS.textBody, gapBefore: 4, lh: 18 },
        { text: 'Expired file cleanup (TTL)', size: 11.5, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );

  // Column 3 inside VPS: Relational Database, Encrypted DB Backups, Private Tenant Upload Store
  parts.push(
    drawCylinder({
      x: 1316,
      y: 98,
      w: 258,
      h: 188,
      ry: 13,
      id: 'rel-db',
    })
  );
  parts.push(
    drawLines({
      x: 1445,
      y: 140,
      boxId: 'rel-db',
      lines: [
        { text: '«database»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Relational Database', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 20 },
        { text: 'PostgreSQL 18 / MySQL 8.0+', size: 11.5, weight: 700, fill: COLORS.textPrimary, lh: 20 },
        { text: 'ACID row-level BOM locks', size: 11.5, fill: COLORS.textBody, gapBefore: 4, lh: 18 },
        { text: 'Multi-tenant orders & billing', size: 11.5, fill: COLORS.textBody, lh: 18 },
        { text: 'Immutable audit logs', size: 11.5, fill: COLORS.textBody, lh: 18 },
      ],
    })
  );

  parts.push(
    drawCylinder({
      x: 1330,
      y: 344,
      w: 230,
      h: 96,
      ry: 10,
      id: 'db-backups',
    })
  );
  parts.push(
    drawLines({
      x: 1445,
      y: 376,
      boxId: 'db-backups',
      lines: [
        { text: '«artifactStore»', size: 11, fill: COLORS.textMuted, lh: 17 },
        { text: 'Encrypted DB Backups', size: 13.5, weight: 700, fill: COLORS.textPrimary, lh: 19 },
        { text: 'Nightly automated snapshots', size: 11.5, fill: COLORS.textBody, lh: 17 },
      ],
    })
  );

  parts.push(
    drawNode3D({
      x: 1316,
      y: 484,
      w: 258,
      h: 214,
      depth: 9,
      id: 'upload-store',
    })
  );
  parts.push(
    drawLines({
      x: 1445,
      y: 508,
      boxId: 'upload-store',
      lines: [
        { text: '«storage»', size: 11, fill: COLORS.textMuted, lh: 18 },
        { text: 'Private Tenant Upload Store', size: 14, weight: 700, fill: COLORS.textPrimary, lh: 22 },
        { text: 'Print artwork (PDF, PNG, DOCX)', size: 11.5, fill: COLORS.textBody, gapBefore: 8, lh: 21 },
        { text: '50%/100% payment proof files', size: 11.5, fill: COLORS.textBody, lh: 21 },
        { text: '7-day default post-completion TTL', size: 11.5, fill: COLORS.textBody, lh: 21 },
        { text: 'Active & on-hold orders protected', size: 11.5, fill: COLORS.textBody, lh: 21 },
        { text: 'Financial receipts retained intact', size: 11.5, fill: COLORS.textBody, lh: 19 },
      ],
    })
  );

  // ── 4. Connectors (Strict Orthogonal Routing, Zero Crossings) ───────────────
  // Client Devices -> Nginx
  parts.push(
    `<path d="M 324 122 L 520 122" fill="none" stroke="${COLORS.stroke}" stroke-width="1.8" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 422, y: 108, text: 'HTTPS (TLS 1.3)', bg: '#ffffff', size: 11.5 }));

  // Shop Floor Workstation <-> Nginx
  parts.push(
    `<path d="M 307 420 L 412 420 L 412 162 L 520 162" fill="none" stroke="${COLORS.stroke}" stroke-width="1.8" marker-start="url(#arrow-solid-start)" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 412, y: 292, text: 'Staff TLS / Print Stream', bg: '#ffffff', size: 11.5 }));

  // PressQueue Web Application -> Relational Database (routed cleanly above Queue Worker)
  parts.push(
    `<path d="M 850 286 L 916 286 L 916 238 L 1314 238" fill="none" stroke="${COLORS.stroke}" stroke-width="1.8" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 1091, y: 223, text: 'ACID SQL & Row Locks', bg: '#ffffff', size: 11.5 }));

  // PressQueue Web Application -> Queue Worker (dashed)
  parts.push(
    `<path d="M 850 370 L 978 370" fill="none" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-dasharray="6 4" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 924, y: 353, text: 'Dispatch jobs', bg: '#ffffff', size: 11 }));

  // Queue Worker -> Relational Database (dashed, clean corridor above Task Scheduler & left of DB)
  parts.push(
    `<path d="M 1210 364 L 1264 364 L 1264 264 L 1314 264" fill="none" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-dasharray="6 4" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 1264, y: 314, text: 'Job / Audit', bg: '#ffffff', size: 11 }));

  // Relational Database -> Encrypted DB Backups (dashed vertical)
  parts.push(
    `<path d="M 1445 286 L 1445 342" fill="none" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-dasharray="6 4" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 1500, y: 314, text: 'Nightly backup', bg: '#ffffff', size: 11 }));

  // Task Scheduler -> Private Tenant Upload Store (dashed horizontal)
  parts.push(
    `<path d="M 1210 512 L 1314 512" fill="none" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-dasharray="6 4" marker-end="url(#arrow-solid)" />`
  );
  parts.push(drawEdgeLabel({ x: 1263, y: 495, text: 'Purge > TTL', bg: '#ffffff', size: 11 }));

  // PressQueue Web Application -> Private Tenant Upload Store (solid horizontal below Task Scheduler)
  parts.push(
    `<path d="M 850 644 L 1314 644" fill="none" stroke="${COLORS.stroke}" stroke-width="1.8" marker-end="url(#arrow-solid)" />`
  );
  parts.push(
    drawEdgeLabel({
      x: 1091,
      y: 628,
      text: 'Authenticated File I/O & Print Stream',
      bg: '#ffffff',
      size: 11.5,
    })
  );

  // ── 5. Bottom-Right Compact 2-Line Arrow Legend ONLY (No Scope Note Box) ────
  parts.push(
    `<rect x="1044" y="746" width="558" height="66" rx="6" fill="#ffffff" stroke="${COLORS.borderSubtle}" stroke-width="1.3" />`
  );
  parts.push(
    `<line x1="1068" y1="768" x2="1130" y2="768" stroke="${COLORS.stroke}" stroke-width="1.8" marker-end="url(#arrow-solid)" />`
  );
  parts.push(
    `<text x="1146" y="772" font-size="12" font-weight="500" fill="${COLORS.textBody}">Synchronous HTTPS, SQL, File I/O, or Print link</text>`
  );
  parts.push(
    `<line x1="1068" y1="794" x2="1130" y2="794" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-dasharray="6 4" marker-end="url(#arrow-solid)" />`
  );
  parts.push(
    `<text x="1146" y="798" font-size="12" font-weight="500" fill="${COLORS.textBody}">Asynchronous worker job, TTL purge, or backup</text>`
  );

  const svg = svgDoc({
    width,
    height,
    body: parts.join('\n'),
  });

  return {
    width,
    height,
    scale: 2,
    svg,
    outputPath: path.join(diagramsDir, 'PressQueue_Deployment_Diagram_v5.png'),
  };
}

function buildGanttChartV2(diagramsDir) {
  const width = 1660;
  const height = 1040;
  const parts = [];

  const TABLE_X = 24;
  const TABLE_Y = 24;
  const TABLE_W = 1612;
  const COL1_W = 548; // Phase / Activity
  const COL2_W = 152; // Duration
  const TIME_X = TABLE_X + COL1_W + COL2_W; // 724
  const MONTH_W = 152; // 6 * 152 = 912 -> 724 + 912 = 1636
  const TABLE_RIGHT = TABLE_X + TABLE_W; // 1636

  const HEADER_H = 44;
  const PHASE_H = 34;
  const TASK_H = 31;

  // Linear date mapping across Jul 1 .. Dec 31, 2026
  const MONTH_DAYS = [31, 31, 30, 31, 30, 31]; // Jul..Dec
  const months = ['Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026', 'Nov 2026', 'Dec 2026'];

  // Convert (month 7..12, day 1..31, isEnd) to pixel X
  function dateX(month, day, isEnd = false) {
    const mIdx = month - 7;
    const daysInM = MONTH_DAYS[mIdx];
    const frac = isEnd ? day / daysInM : (day - 1) / daysInM;
    return Math.round(TIME_X + (mIdx + frac) * MONTH_W);
  }

  const phases = [
    {
      title: '1. Requirements Analysis',
      duration: 'Jul – Early Aug 2026',
      color: '#5b9bd5',
      tint: '#e8f1fa',
      milestone: { month: 8, day: 7, label: 'Requirements Approved', dateLabel: '(Aug 7, 2026)', placement: 'right' },
      tasks: [
        { text: '1.1 Audit local printing shop & prepress workflows', s: [7, 1], e: [7, 14] },
        { text: '1.2 Define multi-tenant boundaries & workload rules', s: [7, 10], e: [7, 24] },
        { text: '1.3 Specify multi-printer scheduling & BOM inventory rules', s: [7, 18], e: [7, 31] },
        { text: '1.4 Finalize SRS (FR-01–FR-17) & verification plan', s: [7, 27], e: [8, 7] },
      ],
    },
    {
      title: '2. System and UI/UX Design',
      duration: 'Aug – Mid Sep 2026',
      color: '#7c6cc4',
      tint: '#eeebf9',
      milestone: { month: 9, day: 14, label: 'Design Approved', dateLabel: '(Sep 14, 2026)', placement: 'right' },
      tasks: [
        { text: '2.1 Design multi-tenant architecture & normalized 18-table ERD', s: [8, 8], e: [8, 22] },
        { text: '2.2 Model modular Use Case, Class & Activity Diagrams', s: [8, 18], e: [9, 2] },
        { text: '2.3 Design Editorial Light-Mode role interfaces & navigation', s: [8, 28], e: [9, 11] },
        { text: '2.4 Review and finalize system design & test specifications', s: [9, 6], e: [9, 14] },
      ],
    },
    {
      title: '3. Implementation',
      duration: 'Mid Sep – Mid Nov 2026',
      color: '#45a29e',
      tint: '#e4f4f3',
      milestone: { month: 11, day: 15, label: 'Core Implementation Complete', dateLabel: '(Nov 15, 2026)', placement: 'right' },
      tasks: [
        { text: '3.1 Build authentication, tenant governance & workspace isolation', s: [9, 15], e: [9, 27] },
        { text: '3.2 Build shop operations, printer fleet & service/BOM catalog', s: [9, 25], e: [10, 10] },
        { text: '3.3 Build marketplace directory, multi-item cart & 50%/100% billing', s: [10, 6], e: [10, 21] },
        { text: '3.4 Build prepress locks, spec revisions & manual refund ledger', s: [10, 18], e: [11, 1] },
        { text: '3.5 Build multi-printer queue, failover, direct print & QA spoilage', s: [10, 27], e: [11, 10] },
        { text: '3.6 Build standardized reports (Print/PDF/CSV), alerts & TTL cleanup', s: [11, 4], e: [11, 15] },
      ],
    },
    {
      title: '4. Testing and Refinement',
      duration: 'Mid Nov – Mid Dec 2026',
      color: '#e09f3e',
      tint: '#fbf2e2',
      milestone: { month: 12, day: 12, label: 'Testing & Refinement Complete', dateLabel: '(Dec 12, 2026)', placement: 'right-above-4' },
      tasks: [
        { text: '4.1 Prepare isolated multi-tenant test datasets & sample files', s: [11, 16], e: [11, 20] },
        { text: '4.2 Execute Smoke Testing (ST-01–ST-12) across all four roles', s: [11, 19], e: [11, 24] },
        { text: '4.3 Execute Role-Based Functional Testing (FT-01–FT-46)', s: [11, 23], e: [12, 4] },
        { text: '4.4 Refine defects, UI consistency & universal navigation', s: [12, 1], e: [12, 8] },
        { text: '4.5 Conduct targeted Regression Testing & final verification', s: [12, 7], e: [12, 12] },
      ],
    },
    {
      title: '5. Deployment Planning and Readiness',
      duration: 'Mid – End Dec 2026',
      color: '#6aa84f',
      tint: '#e9f4e4',
      milestone: { month: 12, day: 29, label: 'PressQueue Ready for Deployment', dateLabel: '(Dec 30, 2026)', placement: 'left-gap-5' },
      tasks: [
        { text: '5.1 Document production VPS topology & security specifications', s: [12, 13], e: [12, 19] },
        { text: '5.2 Prepare operational fallback & recovery procedures', s: [12, 17], e: [12, 23] },
        { text: '5.3 Finalize technical documentation, diagrams & UI captures', s: [12, 21], e: [12, 27] },
        { text: '5.4 Complete final manuscript review and capstone handover', s: [12, 25], e: [12, 29] },
      ],
    },
  ];

  // Compute exact total height
  const bodyH = phases.reduce((acc, p) => acc + PHASE_H + p.tasks.length * TASK_H, 0);
  const TABLE_H = HEADER_H + bodyH; // 44 + 883 = 927

  // Table background
  parts.push(`<rect x="${TABLE_X}" y="${TABLE_Y}" width="${TABLE_W}" height="${TABLE_H}" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.5" />`);

  // Header row
  parts.push(`<rect x="${TABLE_X}" y="${TABLE_Y}" width="${TABLE_W}" height="${HEADER_H}" fill="${COLORS.navyHeader}" />`);
  parts.push(`<text x="${TABLE_X + 18}" y="${TABLE_Y + 27}" font-size="13.5" font-weight="700" fill="#ffffff">Phase / Activity</text>`);
  parts.push(`<text x="${TABLE_X + COL1_W + COL2_W / 2}" y="${TABLE_Y + 27}" text-anchor="middle" font-size="13.5" font-weight="700" fill="#ffffff">Duration</text>`);
  months.forEach((mLabel, idx) => {
    const mx = TIME_X + idx * MONTH_W + MONTH_W / 2;
    parts.push(`<text x="${mx}" y="${TABLE_Y + 27}" text-anchor="middle" font-size="13.5" font-weight="700" fill="#ffffff">${esc(mLabel)}</text>`);
  });

  // Render phase bands and rows first (grid & bars)
  let curY = TABLE_Y + HEADER_H;
  const phaseGeometry = [];

  phases.forEach((phase) => {
    const phaseTop = curY;
    // Phase header tint band across all columns
    parts.push(`<rect x="${TABLE_X}" y="${curY}" width="${TABLE_W}" height="${PHASE_H}" fill="${phase.tint}" />`);
    parts.push(`<line x1="${TABLE_X}" y1="${curY}" x2="${TABLE_RIGHT}" y2="${curY}" stroke="#cbd5e1" stroke-width="1.1" />`);
    parts.push(`<text x="${TABLE_X + 14}" y="${curY + 22}" font-size="13" font-weight="700" fill="${COLORS.textPrimary}">${esc(phase.title)}</text>`);
    parts.push(`<text x="${TABLE_X + COL1_W + COL2_W / 2}" y="${curY + 22}" text-anchor="middle" font-size="11.5" font-weight="600" fill="${COLORS.textBody}">${esc(phase.duration)}</text>`);

    curY += PHASE_H;
    const taskGeoms = [];

    phase.tasks.forEach((task, tIdx) => {
      const rowY = curY;
      parts.push(`<line x1="${TABLE_X}" y1="${rowY}" x2="${TABLE_RIGHT}" y2="${rowY}" stroke="#e2e8f0" stroke-width="0.9" />`);
      parts.push(`<text x="${TABLE_X + 26}" y="${rowY + 20}" font-size="11.5" font-weight="400" fill="${COLORS.textBody}">${esc(task.text)}</text>`);

      const sx = dateX(task.s[0], task.s[1], false);
      const ex = dateX(task.e[0], task.e[1], true);
      const barW = Math.max(14, ex - sx);
      const barY = rowY + 6;
      const barH = TASK_H - 12;

      parts.push(
        `<rect x="${sx}" y="${barY}" width="${barW}" height="${barH}" rx="4" fill="${phase.color}" data-obstacle-for="callout" data-label="${esc(task.text)}" />`
      );

      taskGeoms.push({ rowY, barY, barH, sx, ex, cy: rowY + TASK_H / 2 });
      curY += TASK_H;
    });

    phaseGeometry.push({ phase, phaseTop, phaseBottom: curY, taskGeoms });
  });

  // Vertical column dividers across full table
  parts.push(`<line x1="${TABLE_X + COL1_W}" y1="${TABLE_Y}" x2="${TABLE_X + COL1_W}" y2="${TABLE_Y + TABLE_H}" stroke="#cbd5e1" stroke-width="1.2" />`);
  parts.push(`<line x1="${TIME_X}" y1="${TABLE_Y}" x2="${TIME_X}" y2="${TABLE_Y + TABLE_H}" stroke="#cbd5e1" stroke-width="1.2" />`);
  for (let i = 1; i < 6; i++) {
    const vx = TIME_X + i * MONTH_W;
    parts.push(`<line x1="${vx}" y1="${TABLE_Y + HEADER_H}" x2="${vx}" y2="${TABLE_Y + TABLE_H}" stroke="#cbd5e1" stroke-width="1" />`);
    parts.push(`<line x1="${vx}" y1="${TABLE_Y}" x2="${vx}" y2="${TABLE_Y + HEADER_H}" stroke="#334b6e" stroke-width="1" />`);
  }
  parts.push(`<line x1="${TABLE_X + COL1_W}" y1="${TABLE_Y}" x2="${TABLE_X + COL1_W}" y2="${TABLE_Y + HEADER_H}" stroke="#334b6e" stroke-width="1" />`);
  parts.push(`<line x1="${TIME_X}" y1="${TABLE_Y}" x2="${TIME_X}" y2="${TABLE_Y + HEADER_H}" stroke="#334b6e" stroke-width="1" />`);

  // Outer frame stroke crisp on top
  parts.push(`<rect x="${TABLE_X}" y="${TABLE_Y}" width="${TABLE_W}" height="${TABLE_H}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.5" />`);

  // Phase dependency arrows + milestone diamonds + callout boxes (drawn after grid so they sit crisply on top)
  phaseGeometry.forEach((pg, idx) => {
    const lastTask = pg.taskGeoms[pg.taskGeoms.length - 1];
    const dmX = dateX(pg.phase.milestone.month, pg.phase.milestone.day, true);
    const dmY = lastTask.cy;
    const dmR = 8;

    // Phase dependency arrow from this phase's milestone to next phase's first task bar
    if (idx < phaseGeometry.length - 1) {
      const nextFirst = phaseGeometry[idx + 1].taskGeoms[0];
      // Route cleanly from bottom of diamond down to next phase first bar top/left
      const targetX = Math.max(dmX + 6, nextFirst.sx);
      const midY = nextFirst.cy;
      parts.push(
        `<path d="M ${dmX} ${dmY + dmR} L ${dmX} ${midY} L ${nextFirst.sx - 2} ${midY}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.6" marker-end="url(#arrow-solid)" />`
      );
    }

    // Milestone Diamond
    const dPts = `${dmX},${dmY - dmR} ${dmX + dmR},${dmY} ${dmX},${dmY + dmR} ${dmX - dmR},${dmY}`;
    parts.push(
      `<polygon points="${dPts}" fill="${COLORS.navyHeader}" stroke="#ffffff" stroke-width="1.2" data-obstacle-for="callout" data-label="Milestone ${esc(pg.phase.milestone.label)}" />`
    );

    // Callout Box placement
    const ms = pg.phase.milestone;
    const labelW = Math.round(Math.max(ms.label.length, ms.dateLabel.length) * 5.9 + 24);
    const boxW = ms.placement === 'right-above-4' ? 176 : labelW;
    const boxH = 38;
    const calloutId = `ms-callout-${idx}`;

    if (ms.placement === 'right') {
      const bx = dmX + 22;
      const by = Math.round(dmY - boxH / 2 - 4);
      parts.push(
        `<line x1="${dmX + dmR}" y1="${dmY}" x2="${bx}" y2="${dmY}" stroke="#475569" stroke-width="1.2" />`
      );
      parts.push(
        `<rect x="${bx}" y="${by}" width="${boxW}" height="${boxH}" rx="4" fill="#ffffff" stroke="#475569" stroke-width="1.2" data-container-id="${calloutId}" data-collision-group="callout" />`
      );
      parts.push(
        drawLines({
          x: bx + boxW / 2,
          y: by + 16,
          boxId: calloutId,
          lines: [
            { text: ms.label, size: 11, weight: 700, fill: COLORS.textPrimary, lh: 14 },
            { text: ms.dateLabel, size: 10, weight: 500, fill: COLORS.textMuted, lh: 13 },
          ],
        })
      );
    } else if (ms.placement === 'right-above-4') {
      // Open whitespace to the right of 4.1 & 4.2 (4.2 ends ~1446), strictly above 4.3-4.5; drops vertically at dmX (clear of all bars & arrows)
      const firstTask = pg.taskGeoms[0];
      const bx = Math.max(pg.taskGeoms[1].ex + 8, TABLE_RIGHT - boxW - 8);
      const by = Math.round(firstTask.rowY + 6);
      parts.push(
        `<line x1="${dmX}" y1="${by + boxH}" x2="${dmX}" y2="${dmY - dmR - 1}" stroke="#475569" stroke-width="1.2" />`
      );
      parts.push(
        `<rect x="${bx}" y="${by}" width="${boxW}" height="${boxH}" rx="4" fill="#ffffff" stroke="#475569" stroke-width="1.2" data-container-id="${calloutId}" data-collision-group="callout" />`
      );
      parts.push(
        drawLines({
          x: bx + boxW / 2,
          y: by + 16,
          boxId: calloutId,
          lines: [
            { text: 'Testing & Refinement', size: 10.5, weight: 700, fill: COLORS.textPrimary, lh: 14 },
            { text: 'Complete (Dec 12, 2026)', size: 10, weight: 600, fill: COLORS.textMuted, lh: 13 },
          ],
        })
      );
    } else {
      // 'left-gap-5': sits left of 5.2-5.3; routes along clean gap between 5.1 & 5.2 (below Phase 4->5 arrow!) right to dmX then down to diamond
      const firstTask = pg.taskGeoms[0];
      const secondTask = pg.taskGeoms[1];
      const bx = firstTask.sx - boxW - 24;
      const by = Math.round(secondTask.rowY + 2);
      const gapY = Math.round(secondTask.rowY);
      parts.push(
        `<path d="M ${bx + boxW} ${gapY + 8} L ${bx + boxW + 10} ${gapY + 8} L ${bx + boxW + 10} ${gapY} L ${dmX} ${gapY} L ${dmX} ${dmY - dmR - 1}" fill="none" stroke="#475569" stroke-width="1.2" />`
      );
      parts.push(
        `<rect x="${bx}" y="${by}" width="${boxW}" height="${boxH}" rx="4" fill="#ffffff" stroke="#475569" stroke-width="1.2" data-container-id="${calloutId}" data-collision-group="callout" />`
      );
      parts.push(
        drawLines({
          x: bx + boxW / 2,
          y: by + 16,
          boxId: calloutId,
          lines: [
            { text: ms.label, size: 11, weight: 700, fill: COLORS.textPrimary, lh: 14 },
            { text: ms.dateLabel, size: 10, weight: 500, fill: COLORS.textMuted, lh: 13 },
          ],
        })
      );
    }
  });

  // ── Compact Bottom Legend Bar (Modeled on outline_example_1.pdf Fig. 36) ───
  const LEG_Y = TABLE_Y + TABLE_H + 18; // 969
  const LEG_H = 48;
  parts.push(
    `<rect x="${TABLE_X}" y="${LEG_Y}" width="${TABLE_W}" height="${LEG_H}" rx="5" fill="#ffffff" stroke="${COLORS.borderSubtle}" stroke-width="1.3" data-container-id="gantt-legend" />`
  );

  const ly = LEG_Y + 29;
  parts.push(`<text x="42" y="${ly}" font-size="12" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="gantt-legend">Legend:</text>`);

  const legPhases = [
    ['Requirements', '#5b9bd5'],
    ['System & UI/UX', '#7c6cc4'],
    ['Implementation', '#45a29e'],
    ['Testing & Refinement', '#e09f3e'],
    ['Deployment Readiness', '#6aa84f'],
  ];
  let lx = 112;
  legPhases.forEach(([label, color]) => {
    parts.push(`<circle cx="${lx}" cy="${ly - 4}" r="6.5" fill="${color}" />`);
    parts.push(`<text x="${lx + 13}" y="${ly}" font-size="11.5" font-weight="500" fill="${COLORS.textBody}" data-box-id="gantt-legend">${esc(label)}</text>`);
    lx += label.length * 6.3 + 38;
  });

  // Milestone diamond symbol in legend
  const mdx = lx + 6;
  parts.push(`<polygon points="${mdx},${ly - 11} ${mdx + 7},${ly - 4} ${mdx},${ly + 3} ${mdx - 7},${ly - 4}" fill="${COLORS.navyHeader}" />`);
  parts.push(`<text x="${mdx + 14}" y="${ly}" font-size="11.5" font-weight="500" fill="${COLORS.textBody}" data-box-id="gantt-legend">Milestone (Key Deliverable)</text>`);
  lx = mdx + 178;

  // Dependency arrow symbol in legend
  parts.push(`<line x1="${lx}" y1="${ly - 4}" x2="${lx + 28}" y2="${ly - 4}" stroke="${COLORS.stroke}" stroke-width="1.6" marker-end="url(#arrow-solid)" />`);
  parts.push(`<text x="${lx + 36}" y="${ly}" font-size="11.5" font-weight="500" fill="${COLORS.textBody}" data-box-id="gantt-legend">Dependency</text>`);
  lx += 116;

  // Divider + Methodology note
  parts.push(`<line x1="${lx}" y1="${LEG_Y + 10}" x2="${lx}" y2="${LEG_Y + LEG_H - 10}" stroke="${COLORS.borderSubtle}" stroke-width="1.2" />`);
  parts.push(
    `<text x="${lx + 14}" y="${ly}" font-size="11" font-weight="600" fill="${COLORS.textPrimary}" data-box-id="gantt-legend">Methodology:</text>`
  );
  parts.push(
    `<text x="${lx + 92}" y="${ly}" font-size="11" font-weight="400" fill="${COLORS.textBody}" data-box-id="gantt-legend">Modified Waterfall (sequential phases with feedback loops)</text>`
  );

  const svg = svgDoc({
    width,
    height,
    body: parts.join('\n'),
  });

  return {
    width,
    height,
    scale: 2,
    svg,
    outputPath: path.join(diagramsDir, 'PressQueue_Gantt_Chart_v2.png'),
  };
}

export function getBatch3ADiagrams(diagramsDir) {
  return [
    {
      id: 'deployment_v5',
      build: () => buildDeploymentDiagramV5(diagramsDir),
    },
    {
      id: 'gantt_v2',
      build: () => buildGanttChartV2(diagramsDir),
    },
  ];
}
