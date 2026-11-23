/**
 * Playwright SVG-to-PNG Batch Renderer & Automated DOM Collision Auditor (CR-3).
 * Usage:
 *   node render.mjs --batch 3A
 *   node render.mjs --only deployment_v5
 *   node render.mjs --only gantt_v2
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { getBatch3ADiagrams } from './batch_3a_deploy_gantt.mjs';
import { getBatch3BDiagrams } from './batch_3b_use_cases.mjs';
import { getBatch3CDiagrams } from './batch_3c_erd_class.mjs';
import { getBatch3DDiagrams } from './batch_3d_activities.mjs';
import { getBatch3EDiagrams } from './batch_3e_activities.mjs';
import { getBatch3FDiagrams } from './batch_3f_activities.mjs';
import { getBatch3GDiagrams } from './batch_3g_activities.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const DIAGRAMS_DIR = path.resolve(__dirname, '../../diagrams');

const require = createRequire(import.meta.url);
const { chromium } = require('/tmp/printify-ui-browser/node_modules/playwright');

function parseArgs(argv) {
  const args = { batch: null, only: null };
  for (let i = 0; i < argv.length; i++) {
    if (argv[i] === '--batch' && argv[i + 1]) {
      args.batch = argv[++i].toUpperCase();
    } else if (argv[i] === '--only' && argv[i + 1]) {
      args.only = argv[++i];
    }
  }
  return args;
}

function buildRegistry() {
  return {
    '3A': getBatch3ADiagrams(DIAGRAMS_DIR),
    '3B': getBatch3BDiagrams(DIAGRAMS_DIR),
    '3C': getBatch3CDiagrams(DIAGRAMS_DIR),
    '3D': getBatch3DDiagrams(DIAGRAMS_DIR),
    '3E': getBatch3EDiagrams(DIAGRAMS_DIR),
    '3F': getBatch3FDiagrams(DIAGRAMS_DIR),
    '3G': getBatch3GDiagrams(DIAGRAMS_DIR),
  };
}

async function auditSvgDom(page, width, height) {
  return page.evaluate(({ W, H }) => {
    const errors = [];
    const svg = document.querySelector('svg');
    if (!svg) {
      return ['No <svg> root element found in page.'];
    }

    const intersect = (a, b, tol = 1.0) =>
      a.left < b.right - tol &&
      a.right > b.left + tol &&
      a.top < b.bottom - tol &&
      a.bottom > b.top + tol;

    // 1. Verify every <text data-box-id="..."> stays inside its matching [data-container-id="..."]
    const containers = new Map();
    svg.querySelectorAll('[data-container-id]').forEach((el) => {
      containers.set(el.getAttribute('data-container-id'), el.getBoundingClientRect());
    });

    svg.querySelectorAll('[data-box-id]').forEach((textEl) => {
      const boxId = textEl.getAttribute('data-box-id');
      const boxRect = containers.get(boxId);
      if (!boxRect) {
        errors.push(`Missing container [data-container-id="${boxId}"] for text "${textEl.textContent?.trim()}"`);
        return;
      }
      const tr = textEl.getBoundingClientRect();
      if (tr.width === 0 || tr.height === 0) return;
      const pad = 2.0;
      if (
        tr.left < boxRect.left - pad ||
        tr.right > boxRect.right + pad ||
        tr.top < boxRect.top - pad ||
        tr.bottom > boxRect.bottom + pad
      ) {
        errors.push(
          `Text overflow in "${boxId}": "${textEl.textContent?.trim()}" ` +
            `[text=${Math.round(tr.left)},${Math.round(tr.top)},${Math.round(tr.right)},${Math.round(tr.bottom)} ` +
            `vs box=${Math.round(boxRect.left)},${Math.round(boxRect.top)},${Math.round(boxRect.right)},${Math.round(boxRect.bottom)}]`
        );
      }
    });

    // 2. Pairwise collision checks within each data-collision-group
    const groups = new Map();
    svg.querySelectorAll('[data-collision-group]').forEach((el) => {
      const g = el.getAttribute('data-collision-group');
      if (!groups.has(g)) groups.set(g, []);
      groups.get(g).push({ el, rect: el.getBoundingClientRect() });
    });

    for (const [groupName, items] of groups.entries()) {
      for (let i = 0; i < items.length; i++) {
        for (let j = i + 1; j < items.length; j++) {
          if (intersect(items[i].rect, items[j].rect, 1.5)) {
            errors.push(
              `Group collision in "${groupName}": "${items[i].el.textContent?.trim() || items[i].el.tagName}" overlaps "${items[j].el.textContent?.trim() || items[j].el.tagName}"`
            );
          }
        }
      }
    }

    // 3. Collision checks between collision groups and designated obstacles ([data-obstacle-for])
    svg.querySelectorAll('[data-obstacle-for]').forEach((obsEl) => {
      const targets = (obsEl.getAttribute('data-obstacle-for') || '')
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);
      const obsRect = obsEl.getBoundingClientRect();
      if (obsRect.width === 0 || obsRect.height === 0) return;

      for (const targetGroup of targets) {
        const items = groups.get(targetGroup) || [];
        for (const item of items) {
          if (intersect(obsRect, item.rect, 1.5)) {
            errors.push(
              `Obstacle collision: [${targetGroup}] "${item.el.textContent?.trim() || item.el.tagName}" intersects obstacle "${obsEl.getAttribute('data-label') || obsEl.tagName}"`
            );
          }
        }
      }
    });

    // 4. Viewport bounds check
    svg.querySelectorAll('rect, circle, ellipse, polygon, path, text').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.width === 0 && r.height === 0) return;
      if (r.left < -1 || r.top < -1 || r.right > W + 1 || r.bottom > H + 1) {
        errors.push(
          `Out-of-bounds element <${el.tagName}> "${el.textContent?.trim().slice(0, 40) || ''}": ` +
            `[${Math.round(r.left)},${Math.round(r.top)},${Math.round(r.right)},${Math.round(r.bottom)}] outside [0,0,${W},${H}]`
        );
      }
    });

    return errors;
  }, { W: width, H: height });
}

async function main() {
  const { batch, only } = parseArgs(process.argv.slice(2));
  const registry = buildRegistry();

  let targets = [];
  if (batch) {
    if (!registry[batch]) {
      console.error(`Unknown batch "${batch}". Registered batches: ${Object.keys(registry).join(', ')}`);
      process.exit(1);
    }
    targets = registry[batch];
  } else if (only) {
    const all = Object.values(registry).flat();
    targets = all.filter((d) => d.id === only);
    if (targets.length === 0) {
      console.error(`Unknown diagram id "${only}". Available: ${all.map((d) => d.id).join(', ')}`);
      process.exit(1);
    }
  } else {
    console.error('Specify --batch <3A|...> or --only <id>');
    process.exit(1);
  }

  const browser = await chromium.launch({ headless: true });
  let hasFailure = false;

  try {
    for (const item of targets) {
      const spec = item.build();
      fs.mkdirSync(path.dirname(spec.outputPath), { recursive: true });

      const context = await browser.newContext({
        viewport: { width: spec.width, height: spec.height },
        deviceScaleFactor: spec.scale ?? 2,
      });
      const page = await context.newPage();

      const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <style>
    html, body { margin: 0; padding: 0; background: #ffffff; width: ${spec.width}px; height: ${spec.height}px; overflow: hidden; }
    svg { display: block; width: ${spec.width}px; height: ${spec.height}px; }
  </style>
</head>
<body>${spec.svg}</body>
</html>`;

      await page.setContent(html, { waitUntil: 'load' });
      const auditErrors = await auditSvgDom(page, spec.width, spec.height);

      if (auditErrors.length > 0) {
        hasFailure = true;
        console.error(`[FAIL] ${item.id} (${path.basename(spec.outputPath)}): ${auditErrors.length} DOM audit error(s):`);
        for (const err of auditErrors) {
          console.error(`  - ${err}`);
        }
      } else {
        await page.screenshot({ path: spec.outputPath, type: 'png' });
        const stats = fs.statSync(spec.outputPath);
        console.log(
          `[PASS] ${item.id} -> ${path.relative(process.cwd(), spec.outputPath)} (${spec.width * (spec.scale ?? 2)}x${spec.height * (spec.scale ?? 2)}, ${(stats.size / 1024).toFixed(1)} KB, 0 collisions)`
        );
      }

      await context.close();
    }
  } finally {
    await browser.close();
  }

  if (hasFailure) {
    process.exit(2);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
