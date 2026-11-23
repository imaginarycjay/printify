/**
 * Shared Pure Academic SVG Diagram Toolkit (CR-3).
 * Modeled strictly on outline_example_1.pdf: white #ffffff canvas,
 * crisp #1e293b strokes, verified Sail fonts, zero slide-deck chrome.
 */

export const FONT_STACK = "'Liberation Sans', 'DejaVu Sans', Arial, sans-serif";

export const COLORS = {
  bg: '#ffffff',
  stroke: '#1e293b',
  strokeMuted: '#475569',
  strokeLight: '#94a3b8',
  borderSubtle: '#cbd5e1',
  textPrimary: '#0f172a',
  textBody: '#334155',
  textMuted: '#475569',
  surfaceLight: '#f8fafc',
  surfaceHeader: '#f1f5f9',
  bevelTop: '#f1f5f9',
  bevelSide: '#e2e8f0',
  navyHeader: '#1b2e4b',
};

export function esc(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function defaultDefs() {
  return `
    <marker id="arrow-solid" viewBox="0 0 10 10" refX="8.6" refY="5" markerWidth="7" markerHeight="7" orient="auto">
      <path d="M 0 1.4 L 9 5 L 0 8.6 z" fill="${COLORS.stroke}"/>
    </marker>
    <marker id="arrow-solid-start" viewBox="0 0 10 10" refX="1.4" refY="5" markerWidth="7" markerHeight="7" orient="auto">
      <path d="M 10 1.4 L 1 5 L 10 8.6 z" fill="${COLORS.stroke}"/>
    </marker>
    <marker id="arrow-open" viewBox="0 0 10 10" refX="8.6" refY="5" markerWidth="8" markerHeight="8" orient="auto">
      <path d="M 1.2 1.5 L 8.6 5 L 1.2 8.5" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
    </marker>
    <marker id="arrow-gen" viewBox="0 0 14 14" refX="12.6" refY="7" markerWidth="11" markerHeight="11" orient="auto">
      <path d="M 1 1.5 L 12.6 7 L 1 12.5 z" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.4"/>
    </marker>
    <marker id="diamond-solid" viewBox="0 0 16 12" refX="1" refY="6" markerWidth="12" markerHeight="9" orient="auto">
      <path d="M 1 6 L 8 1.5 L 15 6 L 8 10.5 z" fill="${COLORS.stroke}"/>
    </marker>
    <marker id="diamond-open" viewBox="0 0 16 12" refX="1" refY="6" markerWidth="12" markerHeight="9" orient="auto">
      <path d="M 1 6 L 8 1.5 L 15 6 L 8 10.5 z" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.3"/>
    </marker>
  `;
}

export function svgDoc({ width, height, defs = '', body = '', extraCss = '' }) {
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <defs>
    ${defaultDefs()}
    ${defs}
    <style>
      text { font-family: ${FONT_STACK}; }
      ${extraCss}
    </style>
  </defs>
  <rect x="0" y="0" width="${width}" height="${height}" fill="${COLORS.bg}" />
  ${body}
</svg>`;
}

/**
 * Draw an isometric UML 2.5 3D node box.
 */
export function drawNode3D({
  x,
  y,
  w,
  h,
  depth = 10,
  fill = '#ffffff',
  topFill = COLORS.bevelTop,
  sideFill = COLORS.bevelSide,
  stroke = COLORS.stroke,
  strokeWidth = 1.6,
  id = '',
  collisionGroup = '',
  obstacleFor = '',
}) {
  const topPts = `${x},${y} ${x + depth},${y - depth} ${x + w + depth},${y - depth} ${x + w},${y}`;
  const sidePts = `${x + w},${y} ${x + w + depth},${y - depth} ${x + w + depth},${y + h - depth} ${x + w},${y + h}`;
  const attrs = [
    id ? `data-container-id="${esc(id)}"` : '',
    collisionGroup ? `data-collision-group="${esc(collisionGroup)}"` : '',
    obstacleFor ? `data-obstacle-for="${esc(obstacleFor)}"` : '',
  ]
    .filter(Boolean)
    .join(' ');

  return `
    <g>
      <polygon points="${topPts}" fill="${topFill}" stroke="${stroke}" stroke-width="${strokeWidth}" stroke-linejoin="round" />
      <polygon points="${sidePts}" fill="${sideFill}" stroke="${stroke}" stroke-width="${strokeWidth}" stroke-linejoin="round" />
      <rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeWidth}" ${attrs} />
    </g>
  `;
}

/**
 * Draw a UML database / artifact cylinder.
 */
export function drawCylinder({
  x,
  y,
  w,
  h,
  ry = 14,
  fill = '#ffffff',
  topFill = COLORS.surfaceLight,
  stroke = COLORS.stroke,
  strokeWidth = 1.6,
  id = '',
}) {
  const rx = w / 2;
  const cx = x + rx;
  const topCy = y + ry;
  const botCy = y + h - ry;
  const bodyPath = [
    `M ${x} ${topCy}`,
    `L ${x} ${botCy}`,
    `A ${rx} ${ry} 0 0 0 ${x + w} ${botCy}`,
    `L ${x + w} ${topCy}`,
    `Z`,
  ].join(' ');

  const containerAttr = id ? `data-container-id="${esc(id)}"` : '';
  return `
    <g>
      <path d="${bodyPath}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeWidth}" stroke-linejoin="round" />
      <ellipse cx="${cx}" cy="${topCy}" rx="${rx}" ry="${ry}" fill="${topFill}" stroke="${stroke}" stroke-width="${strokeWidth}" />
      <rect x="${x + 4}" y="${y + ry * 2 + 2}" width="${w - 8}" height="${h - ry * 3 - 4}" fill="none" stroke="none" ${containerAttr} />
    </g>
  `;
}

/**
 * Render structured multi-line text with optional container tracking for DOM fit verification.
 */
export function drawLines({
  x,
  y,
  lines,
  anchor = 'middle',
  boxId = '',
  collisionGroup = '',
}) {
  const boxAttr = boxId ? `data-box-id="${esc(boxId)}"` : '';
  const groupAttr = collisionGroup ? `data-collision-group="${esc(collisionGroup)}"` : '';
  let curY = y;

  return lines
    .map((line) => {
      if (line.gapBefore) {
        curY += line.gapBefore;
      }
      const size = line.size ?? 12;
      const weight = line.weight ?? 400;
      const fill = line.fill ?? COLORS.textBody;
      const style = line.italic ? 'font-style="italic"' : '';
      const lh = line.lh ?? Math.round(size * 1.38);
      const svgLine = `<text x="${x}" y="${curY}" text-anchor="${anchor}" font-size="${size}" font-weight="${weight}" fill="${fill}" ${style} ${boxAttr} ${groupAttr}>${esc(line.text)}</text>`;
      curY += lh;
      return svgLine;
    })
    .join('\n');
}

/**
 * Draw a labeled pill on a connector line with a clean opaque backdrop so the line does not strike through text.
 */
export function drawEdgeLabel({
  x,
  y,
  text,
  bg = '#ffffff',
  fill = COLORS.textPrimary,
  size = 11.5,
  weight = 600,
  padX = 6,
  padY = 3,
  collisionGroup = 'label',
}) {
  const approxW = Math.ceil(text.length * size * 0.56) + padX * 2;
  const h = Math.ceil(size + padY * 2);
  return `
    <g data-collision-group="${esc(collisionGroup)}">
      <rect x="${x - approxW / 2}" y="${y - h / 2 - 2}" width="${approxW}" height="${h}" rx="3" fill="${bg}" />
      <text x="${x}" y="${y + size * 0.32}" text-anchor="middle" font-size="${size}" font-weight="${weight}" fill="${fill}">${esc(text)}</text>
    </g>
  `;
}

/**
 * Compute exact border point on an ellipse (cx, cy, rx, ry) pointing toward (tx, ty).
 */
export function ellipseBorderPoint(cx, cy, rx, ry, tx, ty) {
  const dx = tx - cx;
  const dy = ty - cy;
  if (dx === 0 && dy === 0) {
    return { x: cx, y: cy };
  }
  const t = 1 / Math.sqrt((dx * dx) / (rx * rx) + (dy * dy) / (ry * ry));
  return { x: cx + dx * t, y: cy + dy * t };
}

/**
 * Draw a UML 2.5 stick-figure actor centered at (cx, cy) with clean role label below.
 */
export function drawActor({ cx, cy, labelLines, id }) {
  const headR = 11;
  const headCy = cy - 26;
  const neckY = headCy + headR;
  const hipY = neckY + 26;
  const armY = neckY + 9;
  const footY = hipY + 22;

  const linesXml = labelLines
    .map((line, i) => {
      const ly = footY + 18 + i * 16;
      return `<text x="${cx}" y="${ly}" text-anchor="middle" font-size="13" font-weight="700" fill="${COLORS.textPrimary}">${esc(line)}</text>`;
    })
    .join('\n');

  return `
    <g data-container-id="${esc(id)}">
      <circle cx="${cx}" cy="${headCy}" r="${headR}" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.6" />
      <line x1="${cx}" y1="${neckY}" x2="${cx}" y2="${hipY}" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-linecap="round" />
      <line x1="${cx - 18}" y1="${armY}" x2="${cx + 18}" y2="${armY}" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-linecap="round" />
      <line x1="${cx}" y1="${hipY}" x2="${cx - 14}" y2="${footY}" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-linecap="round" />
      <line x1="${cx}" y1="${hipY}" x2="${cx + 14}" y2="${footY}" stroke="${COLORS.stroke}" stroke-width="1.6" stroke-linecap="round" />
      ${linesXml}
    </g>
  `;
}

/**
 * Draw a UML 2.5 Use Case system boundary rectangle with simple title centered inside at top.
 */
export function drawBoundary({ x, y, w, h, title, id }) {
  return `
    <g>
      <rect x="${x}" y="${y}" width="${w}" height="${h}" rx="3" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.6" data-container-id="${esc(id)}" />
      <text x="${x + w / 2}" y="${y + 28}" text-anchor="middle" font-size="15" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="${esc(id)}">${esc(title)}</text>
    </g>
  `;
}

/**
 * Draw a solid UML 2.5 Use Case oval with inscribed safe text container for DOM verification.
 */
export function drawUseCaseOval({ cx, cy, rx, ry, lines, id, isPrimary = true }) {
  const strokeW = isPrimary ? 1.55 : 1.3;
  const fontSize = isPrimary ? 12.5 : 11.5;
  const weight = isPrimary ? 600 : 500;
  const lh = isPrimary ? 17 : 15;
  const safeW = Math.round(rx * 1.72);
  const safeH = Math.round(ry * 1.48);
  const totalTextH = (lines.length - 1) * lh;
  const startY = Math.round(cy - totalTextH / 2 + fontSize * 0.34);

  const textXml = drawLines({
    x: cx,
    y: startY,
    boxId: id,
    lines: lines.map((text) => ({ text, size: fontSize, weight, fill: COLORS.textPrimary, lh })),
  });

  return `
    <g>
      <ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="${strokeW}" data-obstacle-for="label" data-label="${esc(lines.join(' '))}" />
      <rect x="${cx - safeW / 2}" y="${cy - safeH / 2}" width="${safeW}" height="${safeH}" fill="none" stroke="none" data-container-id="${esc(id)}" />
      ${textXml}
    </g>
  `;
}

/**
 * Draw a dashed UML 2.5 «include» or «extend» dependency arrow from (x1, y1) to (x2, y2).
 */
export function drawUmlDependency({ x1, y1, x2, y2, stereotype, labelDx = 0, labelDy = -10, bg = '#ffffff' }) {
  const mx = Math.round((x1 + x2) / 2 + labelDx);
  const my = Math.round((y1 + y2) / 2 + labelDy);
  return `
    <g>
      <line x1="${x1.toFixed(1)}" y1="${y1.toFixed(1)}" x2="${x2.toFixed(1)}" y2="${y2.toFixed(1)}" stroke="${COLORS.stroke}" stroke-width="1.35" stroke-dasharray="5 4" marker-end="url(#arrow-open)" />
      ${drawEdgeLabel({ x: mx, y: my, text: stereotype, bg, size: 11, weight: 500, fill: COLORS.textBody, padX: 4, padY: 2 })}
    </g>
  `;
}

/**
 * Draw an ERD table entity box modeled on outline_example_1.pdf Figure 17:
 * dark-slate header bar + 3-column structured attribute rows (Key | Name | Type).
 */
export function drawErdEntity({ x, y, w, title, fields, id }) {
  const headerH = 26;
  const rowH = 19;
  const padBottom = 6;
  const h = headerH + fields.length * rowH + padBottom;
  const parts = [];

  parts.push(
    `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="3" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.4" data-container-id="${esc(id)}" />`
  );
  parts.push(
    `<path d="M ${x} ${y + 3} Q ${x} ${y} ${x + 3} ${y} L ${x + w - 3} ${y} Q ${x + w} ${y} ${x + w} ${y + 3} L ${x + w} ${y + headerH} L ${x} ${y + headerH} Z" fill="${COLORS.stroke}" />`
  );
  parts.push(
    `<text x="${x + 10}" y="${y + 17.5}" font-size="12" font-weight="700" fill="#ffffff" data-box-id="${esc(id)}">${esc(title)}</text>`
  );

  fields.forEach(([key, name, type], idx) => {
    const ry = y + headerH + idx * rowH;
    if (idx > 0) {
      parts.push(`<line x1="${x}" y1="${ry}" x2="${x + w}" y2="${ry}" stroke="#e2e8f0" stroke-width="0.9" />`);
    }
    const ty = ry + 13.5;
    if (key) {
      parts.push(
        `<text x="${x + 9}" y="${ty}" font-size="10" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="${esc(id)}">${esc(key)}</text>`
      );
    }
    parts.push(
      `<text x="${x + 38}" y="${ty}" font-size="10.8" font-weight="${key ? 600 : 400}" fill="${COLORS.textPrimary}" data-box-id="${esc(id)}">${esc(name)}</text>`
    );
    parts.push(
      `<text x="${x + w - 9}" y="${ty}" text-anchor="end" font-size="10.2" font-weight="400" fill="${COLORS.textMuted}" data-box-id="${esc(id)}">${esc(type)}</text>`
    );
  });

  return {
    svg: parts.join('\n'),
    box: { id, x, y, w, h },
  };
}

/**
 * Draw a Crow's Foot cardinality end-cap at point (x, y) on a box border.
 * dir is the direction pointing INTO the box ('left'|'right'|'up'|'down').
 * kind is 'one' | 'zero-one' | 'many' | 'zero-many'.
 */
export function drawCrowsFootCap({ x, y, dir, kind }) {
  // Unit vector pointing outward from box along incoming line
  const vx = dir === 'right' ? -1 : dir === 'left' ? 1 : 0;
  const vy = dir === 'down' ? -1 : dir === 'up' ? 1 : 0;
  // Perpendicular unit vector
  const px = -vy;
  const py = vx;

  const pt = (along, perp) => `${(x + vx * along + px * perp).toFixed(1)},${(y + vy * along + py * perp).toFixed(1)}`;
  const seg = (a1, p1, a2, p2) =>
    `<line x1="${(x + vx * a1 + px * p1).toFixed(1)}" y1="${(y + vy * a1 + py * p1).toFixed(1)}" x2="${(x + vx * a2 + px * p2).toFixed(1)}" y2="${(y + vy * a2 + py * p2).toFixed(1)}" stroke="${COLORS.stroke}" stroke-width="1.35" stroke-linecap="round" />`;

  const out = [];
  if (kind === 'one') {
    out.push(seg(5, -6, 5, 6));
    out.push(seg(10, -6, 10, 6));
  } else if (kind === 'zero-one') {
    out.push(seg(5, -6, 5, 6));
    out.push(`<circle cx="${(x + vx * 12).toFixed(1)}" cy="${(y + vy * 12).toFixed(1)}" r="3.6" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.35" />`);
  } else if (kind === 'many') {
    out.push(`<polyline points="${pt(0, -6)} ${pt(9, 0)} ${pt(0, 6)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.35" stroke-linejoin="round" />`);
    out.push(seg(11, -6, 11, 6));
  } else if (kind === 'zero-many') {
    out.push(`<polyline points="${pt(0, -6)} ${pt(9, 0)} ${pt(0, 6)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.35" stroke-linejoin="round" />`);
    out.push(`<circle cx="${(x + vx * 14).toFixed(1)}" cy="${(y + vy * 14).toFixed(1)}" r="3.6" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.35" />`);
  }
  return out.join('\n');
}

/**
 * Draw a 3-compartment UML 2.5 class box modeled on outline_example_1.pdf Figure 24.
 */
export function drawUmlClassBox({ x, y, w, stereotype = '', name, attributes = [], methods = [], id }) {
  const headerH = stereotype ? 38 : 26;
  const lineH = 15.5;
  const padV = 7;
  const attrH = attributes.length > 0 ? attributes.length * lineH + padV * 2 : 18;
  const methH = methods.length > 0 ? methods.length * lineH + padV * 2 : 18;
  const h = headerH + attrH + methH;
  const parts = [];

  parts.push(
    `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="2" fill="#ffffff" stroke="${COLORS.stroke}" stroke-width="1.4" data-container-id="${esc(id)}" />`
  );
  parts.push(
    `<rect x="${x}" y="${y}" width="${w}" height="${headerH}" fill="${COLORS.surfaceHeader}" stroke="${COLORS.stroke}" stroke-width="1.4" />`
  );
  if (stereotype) {
    parts.push(
      `<text x="${x + w / 2}" y="${y + 14}" text-anchor="middle" font-size="10" font-weight="500" fill="${COLORS.textMuted}" data-box-id="${esc(id)}">${esc(stereotype)}</text>`
    );
    parts.push(
      `<text x="${x + w / 2}" y="${y + 30}" text-anchor="middle" font-size="12.5" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="${esc(id)}">${esc(name)}</text>`
    );
  } else {
    parts.push(
      `<text x="${x + w / 2}" y="${y + 18}" text-anchor="middle" font-size="12.5" font-weight="700" fill="${COLORS.textPrimary}" data-box-id="${esc(id)}">${esc(name)}</text>`
    );
  }

  // Attributes compartment
  const attrTop = y + headerH;
  attributes.forEach((attr, idx) => {
    const ty = attrTop + padV + 10.5 + idx * lineH;
    parts.push(
      `<text x="${x + 9}" y="${ty}" font-size="10.2" font-weight="400" fill="${COLORS.textBody}" data-box-id="${esc(id)}">${esc(attr)}</text>`
    );
  });

  // Divider between attributes & methods
  const methTop = attrTop + attrH;
  parts.push(`<line x1="${x}" y1="${methTop}" x2="${x + w}" y2="${methTop}" stroke="${COLORS.stroke}" stroke-width="1.2" />`);

  // Methods compartment
  methods.forEach((meth, idx) => {
    const ty = methTop + padV + 10.5 + idx * lineH;
    parts.push(
      `<text x="${x + 9}" y="${ty}" font-size="10.2" font-weight="400" fill="${COLORS.textBody}" data-box-id="${esc(id)}">${esc(meth)}</text>`
    );
  });

  return {
    svg: parts.join('\n'),
    box: { id, x, y, w, h },
  };
}

/**
 * Analytical guard: assert that an orthogonal polyline clears all non-ignored boxes.
 */
export function assertOrthogonalPathClearsBoxes(points, boxes, ignoreIds, label) {
  const ignore = new Set(ignoreIds);
  for (let i = 0; i < points.length - 1; i++) {
    const [x1, y1] = points[i];
    const [x2, y2] = points[i + 1];
    const minX = Math.min(x1, x2);
    const maxX = Math.max(x1, x2);
    const minY = Math.min(y1, y2);
    const maxY = Math.max(y1, y2);

    for (const box of boxes) {
      if (ignore.has(box.id)) continue;
      const bx1 = box.x + 1;
      const bx2 = box.x + box.w - 1;
      const by1 = box.y + 1;
      const by2 = box.y + box.h - 1;
      const overlapsX = maxX > bx1 && minX < bx2;
      const overlapsY = maxY > by1 && minY < by2;
      if (overlapsX && overlapsY) {
        throw new Error(
          `Connector "${label}" segment (${x1},${y1})->(${x2},${y2}) pierces box "${box.id}" [${box.x},${box.y},${box.w},${box.h}]`
        );
      }
    }
  }
}
