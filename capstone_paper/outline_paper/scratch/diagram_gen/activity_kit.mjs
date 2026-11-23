/**
 * Shared Pure Monochrome Swimlane Activity Diagram Engine (CR-3 Batches 3D–3G).
 * Modeled strictly on outline_example_1.pdf Figures 25–35:
 *   - Outer bordered frame with single centered title row inside top (verbatim FR title)
 *   - Equal-width actor/system swimlane headers below title row
 *   - Pure white action nodes, white decision diamonds, neutral gray merge bars
 *   - Solid dark initial node & bullseye activity final node
 *   - Zero slide-deck banners, zero pill badges, zero bottom color-key bars
 *   - Analytical box-clearance + Chromium DOM text/label collision guards
 */

import path from 'node:path';
import {
  COLORS,
  assertOrthogonalPathClearsBoxes,
  drawEdgeLabel,
  drawLines,
  esc,
  svgDoc,
} from './svg_kit.mjs';

function polyD(pts) {
  return pts.map(([x, y], idx) => `${idx === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`).join(' ');
}

export function buildActivityDiagram({
  diagramsDir,
  filename,
  title,
  lanes,
  width = 960,
  height = 1120,
  nodes,
  edges,
}) {
  const FRAME_X = 20;
  const FRAME_Y = 20;
  const FRAME_W = width - 40;
  const FRAME_H = height - 40;
  const TITLE_H = 38;
  const LANE_HEADER_H = 34;
  const BODY_TOP = FRAME_Y + TITLE_H + LANE_HEADER_H;
  const LANE_W = FRAME_W / lanes.length;

  const laneCenterX = (laneIdx, xOffset = 0) => Math.round(FRAME_X + (laneIdx + 0.5) * LANE_W + xOffset);
  const laneLeftX = (laneIdx) => Math.round(FRAME_X + laneIdx * LANE_W);
  const laneRightX = (laneIdx) => Math.round(FRAME_X + (laneIdx + 1) * LANE_W);

  const frameParts = [];

  // 1. Outer frame + centered title bar + swimlane headers
  frameParts.push(
    `<rect x="${FRAME_X}" y="${FRAME_Y}" width="${FRAME_W}" height="${FRAME_H}" rx="4" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.45" data-container-id="ad-title-bar" />`
  );
  frameParts.push(
    `<line x1="${FRAME_X}" y1="${FRAME_Y + TITLE_H}" x2="${FRAME_X + FRAME_W}" y2="${FRAME_Y + TITLE_H}" stroke="${COLORS.stroke}" stroke-width="1.2" />`
  );
  frameParts.push(
    `<text x="${FRAME_X + FRAME_W / 2}" y="${FRAME_Y + 24}" text-anchor="middle" font-size="13.8" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="ad-title-bar">${esc(title)}</text>`
  );
  frameParts.push(
    `<line x1="${FRAME_X}" y1="${BODY_TOP}" x2="${FRAME_X + FRAME_W}" y2="${BODY_TOP}" stroke="${COLORS.stroke}" stroke-width="1.2" />`
  );

  lanes.forEach((laneTitle, idx) => {
    const cx = laneCenterX(idx);
    const headerId = `lane-hdr-${idx}`;
    frameParts.push(
      `<rect x="${laneLeftX(idx) + 2}" y="${FRAME_Y + TITLE_H + 1}" width="${Math.round(LANE_W) - 4}" height="${LANE_HEADER_H - 2}" fill="none" stroke="none" data-container-id="${headerId}" />`
    );
    frameParts.push(
      `<text x="${cx}" y="${FRAME_Y + TITLE_H + 22}" text-anchor="middle" font-size="12.8" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="${headerId}">${esc(laneTitle)}</text>`
    );
    if (idx > 0) {
      const vx = laneLeftX(idx);
      frameParts.push(
        `<line x1="${vx}" y1="${FRAME_Y + TITLE_H}" x2="${vx}" y2="${FRAME_Y + FRAME_H}" stroke="#64748b" stroke-width="1.1" />`
      );
    }
  });

  // 2. Compute node geometries and SVG
  const nodeMap = new Map();
  const obstacleBoxes = [];
  const nodeParts = [];

  for (const n of nodes) {
    const cx = n.cx ?? laneCenterX(n.lane, n.xOffset ?? 0);
    const cy = n.y;

    if (n.type === 'start') {
      const r = 12;
      const box = { id: n.id, type: 'start', cx, cy, x: cx - r, y: cy - r, w: r * 2, h: r * 2 };
      nodeMap.set(n.id, box);
      obstacleBoxes.push(box);
      nodeParts.push(`<circle cx="${cx}" cy="${cy}" r="${r}" fill="${COLORS.textPrimary}" data-obstacle-for="label" data-label="Start" />`);
    } else if (n.type === 'end') {
      const r = 13;
      const box = { id: n.id, type: 'end', cx, cy, x: cx - r, y: cy - r, w: r * 2, h: r * 2 };
      nodeMap.set(n.id, box);
      obstacleBoxes.push(box);
      nodeParts.push(
        `<g data-obstacle-for="label" data-label="End">
          <circle cx="${cx}" cy="${cy}" r="${r}" fill="#ffffff" stroke="${COLORS.textPrimary}" stroke-width="1.6" />
          <circle cx="${cx}" cy="${cy}" r="8" fill="${COLORS.textPrimary}" />
        </g>`
      );
    } else if (n.type === 'merge') {
      const w = n.w ?? 140;
      const h = n.h ?? 10;
      const x = Math.round(cx - w / 2);
      const y = Math.round(cy - h / 2);
      const box = { id: n.id, type: 'merge', cx, cy, x, y, w, h };
      nodeMap.set(n.id, box);
      obstacleBoxes.push(box);
      nodeParts.push(
        `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="2" fill="#cbd5e1" stroke="#334155" stroke-width="1.25" data-obstacle-for="label" data-label="MergeBar" />`
      );
    } else if (n.type === 'action') {
      const defaultW = Math.min(248, Math.round(LANE_W - 44));
      const w = n.w ?? defaultW;
      const lh = 16;
      const h = n.h ?? Math.max(42, n.lines.length * lh + 18);
      const x = Math.round(cx - w / 2);
      const y = Math.round(cy - h / 2);
      const box = { id: n.id, type: 'action', cx, cy, x, y, w, h };
      nodeMap.set(n.id, box);
      obstacleBoxes.push(box);

      const fontSize = n.fontSize ?? 11.5;
      const totalTextH = (n.lines.length - 1) * lh;
      const textStartY = Math.round(cy - totalTextH / 2 + fontSize * 0.34);

      nodeParts.push(
        `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="6" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.35" data-container-id="${esc(n.id)}" data-obstacle-for="label" data-label="${esc(n.lines.join(' '))}" />`
      );
      nodeParts.push(
        drawLines({
          x: cx,
          y: textStartY,
          boxId: n.id,
          lines: n.lines.map((text) => ({ text, size: fontSize, weight: 500, fill: COLORS.textPrimary, lh })),
        })
      );
    } else if (n.type === 'decision') {
      const hw = n.hw ?? 78;
      const hh = n.hh ?? 36;
      const box = { id: n.id, type: 'decision', cx, cy, x: cx - hw, y: cy - hh, w: hw * 2, h: hh * 2, hw, hh };
      nodeMap.set(n.id, box);
      obstacleBoxes.push(box);

      const pts = `${cx},${cy - hh} ${cx + hw},${cy} ${cx},${cy + hh} ${cx - hw},${cy}`;
      const fontSize = n.fontSize ?? 10.8;
      const lh = 14;
      const totalTextH = (n.lines.length - 1) * lh;
      const textStartY = Math.round(cy - totalTextH / 2 + fontSize * 0.34);
      const safeW = Math.round(hw * 1.48);
      const safeH = Math.round(hh * 1.38);

      nodeParts.push(
        `<polygon points="${pts}" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.35" stroke-linejoin="round" data-obstacle-for="label" data-label="${esc(n.lines.join(' '))}" />`
      );
      nodeParts.push(
        `<rect x="${cx - Math.round(safeW / 2)}" y="${cy - Math.round(safeH / 2)}" width="${safeW}" height="${safeH}" fill="none" stroke="none" data-container-id="${esc(n.id)}" />`
      );
      nodeParts.push(
        drawLines({
          x: cx,
          y: textStartY,
          boxId: n.id,
          lines: n.lines.map((text) => ({ text, size: fontSize, weight: 600, fill: COLORS.textPrimary, lh })),
        })
      );
    }
  }

  function portOf(nodeId, side = 'bottom', offset = 0) {
    const b = nodeMap.get(nodeId);
    if (!b) throw new Error(`Unknown node "${nodeId}" in ${filename}`);
    if (b.type === 'decision' && offset !== 0) {
      throw new Error(`Decision vertex "${nodeId}.${side}" must have offset=0 in ${filename}`);
    }
    if (side === 'top') return [b.cx + offset, b.y];
    if (side === 'bottom') return [b.cx + offset, b.y + b.h];
    if (side === 'left') return [b.x, b.cy + offset];
    if (side === 'right') return [b.x + b.w, b.cy + offset];
    throw new Error(`Invalid side "${side}"`);
  }

  // 3. Route orthogonal edges & validate zero box crossings
  const edgeParts = [];
  const labelParts = [];

  for (const e of edges) {
    const startPt = portOf(e.from, e.fromSide ?? 'bottom', e.fromOffset ?? 0);
    const endPt = portOf(e.to, e.toSide ?? 'top', e.toOffset ?? 0);
    let pts;

    if (typeof e.via === 'function') {
      pts = e.via(startPt, endPt, { nodeMap, laneLeftX, laneRightX, laneCenterX, FRAME_X, FRAME_W });
    } else if (Array.isArray(e.via)) {
      pts = [startPt, ...e.via, endPt];
    } else if (startPt[0] === endPt[0] || startPt[1] === endPt[1]) {
      pts = [startPt, endPt];
    } else if (e.fromSide === 'left' || e.fromSide === 'right') {
      if (e.toSide === 'top' || e.toSide === 'bottom') {
        pts = [startPt, [endPt[0], startPt[1]], endPt];
      } else {
        const midX = Math.round((startPt[0] + endPt[0]) / 2);
        pts = [startPt, [midX, startPt[1]], [midX, endPt[1]], endPt];
      }
    } else {
      if (e.toSide === 'left' || e.toSide === 'right') {
        pts = [startPt, [startPt[0], endPt[1]], endPt];
      } else {
        const midY = Math.round((startPt[1] + endPt[1]) / 2);
        pts = [startPt, [startPt[0], midY], [endPt[0], midY], endPt];
      }
    }

    assertOrthogonalPathClearsBoxes(pts, obstacleBoxes, [e.from, e.to], `${filename}: ${e.from}->${e.to}`);

    edgeParts.push(
      `<path d="${polyD(pts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.3" stroke-linejoin="round" marker-end="url(#arrow-solid)" />`
    );

    if (e.label) {
      let lx;
      let ly;
      if (e.labelPos) {
        [lx, ly] = e.labelPos;
      } else {
        const segIdx = e.labelSeg ?? 0;
        const pA = pts[segIdx];
        const pB = pts[segIdx + 1];
        lx = Math.round((pA[0] + pB[0]) / 2) + (e.labelDx ?? 0);
        ly = Math.round((pA[1] + pB[1]) / 2) + (e.labelDy ?? 0);
      }
      labelParts.push(
        drawEdgeLabel({
          x: lx,
          y: ly,
          text: e.label,
          bg: '#ffffff',
          fill: COLORS.textPrimary,
          size: 10.8,
          weight: 700,
          padX: 5,
          padY: 2,
          collisionGroup: 'label',
        })
      );
    }
  }

  const svg = svgDoc({
    width,
    height,
    body: [...frameParts, ...edgeParts, ...nodeParts, ...labelParts].join('\n'),
  });

  return {
    width,
    height,
    scale: 2,
    svg,
    outputPath: path.join(diagramsDir, 'activity_diagrams', filename),
  };
}
