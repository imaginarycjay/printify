/**
 * Batch 3B Diagram Generators (CR-3):
 * All 4 Modular UML 2.5 Use Case Diagrams (UC-1 to UC-4), modeled strictly on
 * outline_example_1.pdf Figures 18-23 with exact locked FR-01..FR-17 titles.
 */

import path from 'node:path';
import {
  COLORS,
  drawActor,
  drawBoundary,
  drawUmlDependency,
  drawUseCaseOval,
  ellipseBorderPoint,
  svgDoc,
} from './svg_kit.mjs';

/**
 * Exact anchor on the left or right nose of an ellipse with optional vertical fan offset.
 */
function ovalNosePoint(oval, side, dyRatio = 0) {
  const clamped = Math.max(-0.42, Math.min(0.42, dyRatio));
  const dy = clamped * oval.ry;
  const dx = oval.rx * Math.sqrt(1 - clamped * clamped);
  return {
    x: side === 'left' ? oval.cx - dx : oval.cx + dx,
    y: oval.cy + dy,
  };
}

/**
 * Analytical guard: verify segment (x1,y1)->(x2,y2) does not pierce any non-target ellipse interior.
 */
function segmentIntersectsEllipse(x1, y1, x2, y2, oval) {
  const steps = 40;
  for (let i = 1; i < steps; i++) {
    const t = i / steps;
    const px = x1 + (x2 - x1) * t;
    const py = y1 + (y2 - y1) * t;
    const norm = ((px - oval.cx) / (oval.rx - 1)) ** 2 + ((py - oval.cy) / (oval.ry - 1)) ** 2;
    if (norm < 0.98) {
      return true;
    }
  }
  return false;
}

function buildUseCaseDiagram({
  diagramsDir,
  filename,
  width,
  height,
  boundary,
  primaryOvals,
  secondaryOvals,
  dependencies,
  actors,
  associations,
}) {
  const ovalMap = new Map();
  primaryOvals.forEach((o) => ovalMap.set(o.id, o));
  secondaryOvals.forEach((o) => ovalMap.set(o.id, o));

  const actorMap = new Map();
  actors.forEach((a) => actorMap.set(a.id, a));

  const allOvals = [...primaryOvals, ...secondaryOvals];
  const parts = [];

  // 1. System boundary
  parts.push(drawBoundary(boundary));

  // 2. Association lines anchored cleanly to oval left/right noses (zero oval crossings)
  for (const assoc of associations) {
    const actor = actorMap.get(assoc.actor);
    const oval = ovalMap.get(assoc.oval);
    const ax = assoc.ax ?? (actor.side === 'right' ? actor.cx - 32 : actor.cx + 32);
    const ay = assoc.ay ?? actor.cy - 2;
    const side = actor.side === 'right' ? 'right' : 'left';
    const bp = ovalNosePoint(oval, side, assoc.dyRatio ?? 0);

    for (const other of allOvals) {
      if (other.id !== oval.id && segmentIntersectsEllipse(ax, ay, bp.x, bp.y, other)) {
        throw new Error(`Association ${assoc.actor} -> ${assoc.oval} crosses oval ${other.id} in ${filename}`);
      }
    }

    parts.push(
      `<line x1="${ax.toFixed(1)}" y1="${ay.toFixed(1)}" x2="${bp.x.toFixed(1)}" y2="${bp.y.toFixed(1)}" stroke="${COLORS.stroke}" stroke-width="1.35" />`
    );
  }

  // 3. Ovals (primary FR ovals + secondary ovals)
  for (const o of primaryOvals) {
    parts.push(drawUseCaseOval({ ...o, isPrimary: true }));
  }
  for (const o of secondaryOvals) {
    parts.push(drawUseCaseOval({ ...o, isPrimary: false }));
  }

  // 4. UML 2.5 «include» / «extend» dependency arrows
  for (const dep of dependencies) {
    const fromO = ovalMap.get(dep.from);
    const toO = ovalMap.get(dep.to);
    const p1 = ellipseBorderPoint(fromO.cx, fromO.cy, fromO.rx, fromO.ry, toO.cx, toO.cy);
    const p2 = ellipseBorderPoint(toO.cx, toO.cy, toO.rx, toO.ry, fromO.cx, fromO.cy);
    parts.push(
      drawUmlDependency({
        x1: p1.x,
        y1: p1.y,
        x2: p2.x,
        y2: p2.y,
        stereotype: dep.type === 'include' ? '«include»' : '«extend»',
        labelDx: dep.labelDx ?? 0,
        labelDy: dep.labelDy ?? -12,
      })
    );
  }

  // 5. Stick-figure actors
  for (const a of actors) {
    parts.push(drawActor(a));
  }

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
    outputPath: path.join(diagramsDir, 'use_cases', filename),
  };
}

export function getBatch3BDiagrams(diagramsDir) {
  return [
    {
      id: 'uc_1',
      build: () =>
        buildUseCaseDiagram({
          diagramsDir,
          filename: 'PressQueue_UC-1_Access_Governance_ShopConfig.png',
          width: 1360,
          height: 980,
          boundary: {
            id: 'uc1-boundary',
            x: 245,
            y: 28,
            w: 875,
            h: 924,
            title: 'Account Access, Tenant Governance, and Shop Configuration',
          },
          primaryOvals: [
            {
              id: 'fr-01',
              cx: 525,
              cy: 148,
              rx: 225,
              ry: 46,
              lines: ['FR-01 Account Registration and Tenant', 'Onboarding'],
            },
            {
              id: 'fr-02',
              cx: 525,
              cy: 326,
              rx: 225,
              ry: 46,
              lines: ['FR-02 Authentication, Profile Security,', 'and Session Logout'],
            },
            {
              id: 'fr-03',
              cx: 525,
              cy: 504,
              rx: 225,
              ry: 46,
              lines: ['FR-03 Multi-Tenant Approval and Workspace', 'Isolation'],
            },
            {
              id: 'fr-04',
              cx: 525,
              cy: 682,
              rx: 225,
              ry: 46,
              lines: ['FR-04 Shop Operations, Printer Fleet, and', 'Service Availability Configuration'],
            },
            {
              id: 'fr-05',
              cx: 525,
              cy: 860,
              rx: 225,
              ry: 46,
              lines: ['FR-05 Dynamic Service Catalog and Bill of', 'Materials Configuration'],
            },
          ],
          secondaryOvals: [
            {
              id: 'uc1-2fa',
              cx: 975,
              cy: 148,
              rx: 120,
              ry: 34,
              lines: ['Two-Factor Auth &', 'Passkeys'],
            },
            {
              id: 'uc1-suspend',
              cx: 975,
              cy: 682,
              rx: 120,
              ry: 34,
              lines: ['Tenant Suspension &', 'Retention Bounds'],
            },
            {
              id: 'uc1-apps',
              cx: 975,
              cy: 860,
              rx: 120,
              ry: 34,
              lines: ['Install / Remove', 'Service Apps'],
            },
          ],
          dependencies: [
            { from: 'uc1-2fa', to: 'fr-02', type: 'extend', labelDx: -16, labelDy: -16 },
            { from: 'uc1-suspend', to: 'fr-03', type: 'extend', labelDx: 14, labelDy: -16 },
            { from: 'fr-05', to: 'uc1-apps', type: 'include', labelDx: 0, labelDy: -14 },
          ],
          actors: [
            { id: 'actor-cust', cx: 102, cy: 170, side: 'left', labelLines: ['Customer'] },
            { id: 'actor-staff', cx: 102, cy: 355, side: 'left', labelLines: ['Production Staff'] },
            { id: 'actor-owner', cx: 102, cy: 680, side: 'left', labelLines: ['Printing Shop', 'Owner'] },
            { id: 'actor-admin', cx: 1248, cy: 415, side: 'right', labelLines: ['Super Admin'] },
          ],
          associations: [
            { actor: 'actor-cust', oval: 'fr-01', ax: 136, ay: 168, dyRatio: -0.15 },
            { actor: 'actor-cust', oval: 'fr-02', ax: 136, ay: 168, dyRatio: -0.22 },
            { actor: 'actor-staff', oval: 'fr-01', ax: 136, ay: 353, dyRatio: 0.08 },
            { actor: 'actor-staff', oval: 'fr-02', ax: 136, ay: 353, dyRatio: 0.0 },
            { actor: 'actor-owner', oval: 'fr-01', ax: 136, ay: 678, dyRatio: 0.25 },
            { actor: 'actor-owner', oval: 'fr-02', ax: 136, ay: 678, dyRatio: 0.22 },
            { actor: 'actor-owner', oval: 'fr-03', ax: 136, ay: 678, dyRatio: 0.0 },
            { actor: 'actor-owner', oval: 'fr-04', ax: 136, ay: 678, dyRatio: 0.0 },
            { actor: 'actor-owner', oval: 'fr-05', ax: 136, ay: 678, dyRatio: 0.0 },
            { actor: 'actor-admin', oval: 'fr-02', ax: 1212, ay: 413, dyRatio: 0.12 },
            { actor: 'actor-admin', oval: 'fr-03', ax: 1212, ay: 413, dyRatio: -0.12 },
          ],
        }),
    },
    {
      id: 'uc_2',
      build: () =>
        buildUseCaseDiagram({
          diagramsDir,
          filename: 'PressQueue_UC-2_Marketplace_Ordering_Payment.png',
          width: 1360,
          height: 880,
          boundary: {
            id: 'uc2-boundary',
            x: 245,
            y: 28,
            w: 875,
            h: 824,
            title: 'Marketplace Ordering, Payment, and Settlement',
          },
          primaryOvals: [
            {
              id: 'fr-07',
              cx: 525,
              cy: 152,
              rx: 225,
              ry: 44,
              lines: ['FR-07 Marketplace Shop Directory and Live', 'Workload Display'],
            },
            {
              id: 'fr-08',
              cx: 525,
              cy: 316,
              rx: 225,
              ry: 44,
              lines: ['FR-08 Dynamic Quotation, Print Validation,', 'and Multi-Item Order Placement'],
            },
            {
              id: 'fr-09',
              cx: 525,
              cy: 480,
              rx: 225,
              ry: 44,
              lines: ['FR-09 Upfront Payment Submission and Proof', 'Retry Recovery'],
            },
            {
              id: 'fr-10',
              cx: 525,
              cy: 644,
              rx: 225,
              ry: 44,
              lines: ['FR-10 Payment Proof Verification, Counter', 'Settlement, and Official Receipt'],
            },
          ],
          secondaryOvals: [
            {
              id: 'uc2-fallback',
              cx: 975,
              cy: 316,
              rx: 120,
              ry: 32,
              lines: ['Short-Stock Fallback', 'Options'],
            },
            {
              id: 'uc2-retry',
              cx: 975,
              cy: 480,
              rx: 120,
              ry: 32,
              lines: ['Two-Step Proof', 'Retry Recovery'],
            },
            {
              id: 'uc2-receipt',
              cx: 525,
              cy: 786,
              rx: 145,
              ry: 30,
              lines: ['Generate Official', 'Receipt (PDF)'],
            },
          ],
          dependencies: [
            { from: 'uc2-fallback', to: 'fr-08', type: 'extend', labelDx: 0, labelDy: -14 },
            { from: 'uc2-retry', to: 'fr-09', type: 'extend', labelDx: 0, labelDy: -14 },
            { from: 'fr-10', to: 'uc2-receipt', type: 'include', labelDx: 44, labelDy: 0 },
          ],
          actors: [
            { id: 'actor-cust', cx: 102, cy: 400, side: 'left', labelLines: ['Customer'] },
            { id: 'actor-owner', cx: 1248, cy: 595, side: 'right', labelLines: ['Printing Shop', 'Owner'] },
            { id: 'actor-staff', cx: 1248, cy: 730, side: 'right', labelLines: ['Production Staff'] },
          ],
          associations: [
            { actor: 'actor-cust', oval: 'fr-07', ax: 136, ay: 398, dyRatio: 0.0 },
            { actor: 'actor-cust', oval: 'fr-08', ax: 136, ay: 398, dyRatio: 0.0 },
            { actor: 'actor-cust', oval: 'fr-09', ax: 136, ay: 398, dyRatio: 0.0 },
            { actor: 'actor-cust', oval: 'fr-10', ax: 136, ay: 398, dyRatio: 0.0 },
            { actor: 'actor-owner', oval: 'fr-10', ax: 1212, ay: 593, dyRatio: -0.14 },
            { actor: 'actor-staff', oval: 'fr-10', ax: 1212, ay: 728, dyRatio: 0.14 },
          ],
        }),
    },
    {
      id: 'uc_3',
      build: () =>
        buildUseCaseDiagram({
          diagramsDir,
          filename: 'PressQueue_UC-3_Prepress_Scheduling_Print_QA.png',
          width: 1360,
          height: 900,
          boundary: {
            id: 'uc3-boundary',
            x: 245,
            y: 28,
            w: 875,
            h: 844,
            title: 'Prepress, Scheduling, Direct Print, and Quality Control',
          },
          primaryOvals: [
            {
              id: 'fr-11',
              cx: 525,
              cy: 152,
              rx: 225,
              ry: 44,
              lines: ['FR-11 Prepress Artwork Inspection, Cover-Only', 'Intake, and Specification Lock'],
            },
            {
              id: 'fr-12',
              cx: 525,
              cy: 318,
              rx: 225,
              ry: 44,
              lines: ['FR-12 Pre-Print Specification Revision and', 'Manual Refund Management'],
            },
            {
              id: 'fr-13',
              cx: 525,
              cy: 484,
              rx: 225,
              ry: 44,
              lines: ['FR-13 Multi-Printer Scheduling, Failover,', 'and Split Order Tracking'],
            },
            {
              id: 'fr-14',
              cx: 525,
              cy: 650,
              rx: 225,
              ry: 44,
              lines: ['FR-14 Direct Browser Print Dispatch and', 'Quality Inspection'],
            },
          ],
          secondaryOvals: [
            {
              id: 'uc3-shift',
              cx: 975,
              cy: 152,
              rx: 120,
              ry: 32,
              lines: ['Shift Cover-Only to', 'Shop Paper'],
            },
            {
              id: 'uc3-refund',
              cx: 975,
              cy: 318,
              rx: 120,
              ry: 32,
              lines: ['Manual Refund', 'Settlement'],
            },
            {
              id: 'uc3-failover',
              cx: 975,
              cy: 484,
              rx: 120,
              ry: 32,
              lines: ['Printer Failover &', 'Split Pickup'],
            },
            {
              id: 'uc3-spoilage',
              cx: 525,
              cy: 796,
              rx: 148,
              ry: 30,
              lines: ['Dual Paper & Ink', 'Spoilage Log'],
            },
          ],
          dependencies: [
            { from: 'uc3-shift', to: 'fr-11', type: 'extend', labelDx: 0, labelDy: -14 },
            { from: 'uc3-refund', to: 'fr-12', type: 'extend', labelDx: 0, labelDy: -14 },
            { from: 'fr-13', to: 'uc3-failover', type: 'include', labelDx: 0, labelDy: -14 },
            { from: 'uc3-spoilage', to: 'fr-14', type: 'extend', labelDx: 44, labelDy: 0 },
          ],
          actors: [
            { id: 'actor-cust', cx: 102, cy: 210, side: 'left', labelLines: ['Customer'] },
            { id: 'actor-owner', cx: 102, cy: 455, side: 'left', labelLines: ['Printing Shop', 'Owner'] },
            { id: 'actor-staff', cx: 102, cy: 675, side: 'left', labelLines: ['Production Staff'] },
            { id: 'actor-rip', cx: 1248, cy: 650, side: 'right', labelLines: ['Browser Print /', 'RIP'] },
          ],
          associations: [
            { actor: 'actor-cust', oval: 'fr-11', ax: 136, ay: 208, dyRatio: -0.1 },
            { actor: 'actor-cust', oval: 'fr-12', ax: 136, ay: 208, dyRatio: -0.18 },
            { actor: 'actor-cust', oval: 'fr-13', ax: 136, ay: 208, dyRatio: -0.22 },
            { actor: 'actor-owner', oval: 'fr-12', ax: 136, ay: 453, dyRatio: 0.14 },
            { actor: 'actor-owner', oval: 'fr-13', ax: 136, ay: 453, dyRatio: 0.0 },
            { actor: 'actor-staff', oval: 'fr-11', ax: 136, ay: 673, dyRatio: 0.2 },
            { actor: 'actor-staff', oval: 'fr-13', ax: 136, ay: 673, dyRatio: 0.22 },
            { actor: 'actor-staff', oval: 'fr-14', ax: 136, ay: 673, dyRatio: 0.0 },
            { actor: 'actor-rip', oval: 'fr-14', ax: 1212, ay: 648, dyRatio: 0.0 },
          ],
        }),
    },
    {
      id: 'uc_4',
      build: () =>
        buildUseCaseDiagram({
          diagramsDir,
          filename: 'PressQueue_UC-4_Inventory_Reports_Lifecycle.png',
          width: 1360,
          height: 880,
          boundary: {
            id: 'uc4-boundary',
            x: 245,
            y: 28,
            w: 875,
            h: 824,
            title: 'Inventory Control, Operational Reporting, and File Lifecycle',
          },
          primaryOvals: [
            {
              id: 'fr-06',
              cx: 525,
              cy: 152,
              rx: 225,
              ry: 44,
              lines: ['FR-06 Raw Material Inventory, Stock-In', 'Replenishment, and Reorder Point Alerting'],
            },
            {
              id: 'fr-15',
              cx: 525,
              cy: 316,
              rx: 225,
              ry: 44,
              lines: ['FR-15 Concurrent Stock Reservation and', 'Atomic BOM Deduction'],
            },
            {
              id: 'fr-16',
              cx: 525,
              cy: 480,
              rx: 225,
              ry: 44,
              lines: ['FR-16 Standardized Printable and Exportable', 'Operational Reports'],
            },
            {
              id: 'fr-17',
              cx: 525,
              cy: 644,
              rx: 225,
              ry: 44,
              lines: ['FR-17 Automated File Retention, Audit Trail,', 'and Workflow Notifications'],
            },
          ],
          secondaryOvals: [
            {
              id: 'uc4-rop',
              cx: 975,
              cy: 152,
              rx: 120,
              ry: 32,
              lines: ['ROP Low-Stock', 'Alerts'],
            },
            {
              id: 'uc4-lock',
              cx: 975,
              cy: 316,
              rx: 120,
              ry: 32,
              lines: ['Row-Level Stock', 'Locking'],
            },
            {
              id: 'uc4-export',
              cx: 975,
              cy: 480,
              rx: 120,
              ry: 32,
              lines: ['Print View, PDF', '& CSV Export'],
            },
            {
              id: 'uc4-purge',
              cx: 525,
              cy: 786,
              rx: 145,
              ry: 30,
              lines: ['TTL Artwork Purge', '& Audit Log'],
            },
          ],
          dependencies: [
            { from: 'fr-06', to: 'uc4-rop', type: 'include', labelDx: 0, labelDy: -14 },
            { from: 'fr-15', to: 'uc4-lock', type: 'include', labelDx: 0, labelDy: -14 },
            { from: 'fr-16', to: 'uc4-export', type: 'include', labelDx: 0, labelDy: -14 },
            { from: 'fr-17', to: 'uc4-purge', type: 'include', labelDx: 44, labelDy: 0 },
          ],
          actors: [
            { id: 'actor-owner', cx: 102, cy: 310, side: 'left', labelLines: ['Printing Shop', 'Owner'] },
            { id: 'actor-staff', cx: 102, cy: 560, side: 'left', labelLines: ['Production Staff'] },
            { id: 'actor-admin', cx: 1248, cy: 595, side: 'right', labelLines: ['Super Admin'] },
            { id: 'actor-cron', cx: 1248, cy: 730, side: 'right', labelLines: ['System', 'Scheduler'] },
          ],
          associations: [
            { actor: 'actor-owner', oval: 'fr-06', ax: 136, ay: 308, dyRatio: 0.0 },
            { actor: 'actor-owner', oval: 'fr-15', ax: 136, ay: 308, dyRatio: -0.12 },
            { actor: 'actor-owner', oval: 'fr-16', ax: 136, ay: 308, dyRatio: -0.15 },
            { actor: 'actor-owner', oval: 'fr-17', ax: 136, ay: 308, dyRatio: -0.18 },
            { actor: 'actor-staff', oval: 'fr-15', ax: 136, ay: 558, dyRatio: 0.16 },
            { actor: 'actor-staff', oval: 'fr-17', ax: 136, ay: 558, dyRatio: 0.12 },
            { actor: 'actor-admin', oval: 'fr-17', ax: 1212, ay: 593, dyRatio: -0.14 },
            { actor: 'actor-cron', oval: 'fr-17', ax: 1212, ay: 728, dyRatio: 0.14 },
          ],
        }),
    },
  ];
}
