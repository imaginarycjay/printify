/**
 * Batched Curated Light-Mode Screenshot Capture & DOM Audit (CR-4).
 * Usage:
 *   ./vendor/bin/sail exec laravel.test node capstone_paper/outline_paper/scratch/capture_ui_cr4.mjs --batch 1
 *   ./vendor/bin/sail exec laravel.test node capstone_paper/outline_paper/scratch/capture_ui_cr4.mjs --only 02
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const OUTPUT_DIR = path.resolve(__dirname, '../diagrams/ui');
const BASE_URL = process.env.APP_URL || 'http://localhost';

const require = createRequire(import.meta.url);
const { chromium } = require('/tmp/printify-ui-browser/node_modules/playwright');

function parseArgs(argv) {
  const args = { batch: null, only: null };
  for (let i = 0; i < argv.length; i++) {
    if (argv[i] === '--batch' && argv[i + 1]) {
      args.batch = String(argv[++i]);
    } else if (argv[i] === '--only' && argv[i + 1]) {
      args.only = String(argv[++i]).padStart(2, '0');
    }
  }
  return args;
}

function readPngDimensions(filePath) {
  const buf = fs.readFileSync(filePath);
  if (buf.length < 24 || buf.toString('ascii', 1, 4) !== 'PNG') {
    return { width: 0, height: 0 };
  }
  return {
    width: buf.readUInt32BE(16),
    height: buf.readUInt32BE(20),
  };
}

async function createLightContext(browser) {
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
    colorScheme: 'light',
  });

  await context.addInitScript(() => {
    try {
      localStorage.setItem('flux.appearance', 'light');
    } catch {
      // Ignore storage access restrictions if any.
    }
    if (document.documentElement) {
      document.documentElement.classList.remove('dark');
    }
  });

  const page = await context.newPage();
  const telemetry = {
    consoleErrors: [],
    pageErrors: [],
    httpErrors: [],
  };

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      telemetry.consoleErrors.push(msg.text());
    }
  });
  page.on('pageerror', (err) => {
    telemetry.pageErrors.push(err.message);
  });
  page.on('response', (res) => {
    const status = res.status();
    const url = res.url();
    if (status >= 400 && !url.endsWith('/favicon.ico')) {
      telemetry.httpErrors.push(`${status} ${url}`);
    }
  });

  return { context, page, telemetry };
}

async function loginAs(page, email, password = 'password') {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 15000 }),
    page.locator('[data-test="login-button"]').click(),
  ]);
  await page.waitForLoadState('networkidle');
}

async function resolveShopId(page, shopName) {
  await page.goto(`${BASE_URL}/shops`, { waitUntil: 'networkidle' });
  const href = await page
    .locator('article', { hasText: shopName })
    .locator('a[href*="/shops/"]')
    .first()
    .getAttribute('href');

  if (!href) {
    throw new Error(`Could not resolve shop link for "${shopName}" on /shops`);
  }

  const match = href.match(/\/shops\/(\d+)/);
  if (!match) {
    throw new Error(`Unexpected shop href "${href}" for "${shopName}"`);
  }

  return Number(match[1]);
}

async function captureScreen(page, telemetry, filename, opts = {}) {
  const {
    expectBackLink = false,
    expectedBackText = null,
    fullPage = false,
  } = opts;

  await page.evaluate(async () => {
    document.documentElement.classList.remove('dark');
    if (document.fonts && document.fonts.ready) {
      await document.fonts.ready;
    }
  });
  await page.waitForTimeout(450);

  const audit = await page.evaluate(() => {
    const isDark = document.documentElement.classList.contains('dark');
    const backEl = document.querySelector('[data-test="universal-back-link"]');
    const backText = backEl ? backEl.innerText.replace(/\s+/g, ' ').trim() : null;
    const bodyText = document.body ? document.body.innerText : '';
    const emojiMatches = bodyText.match(/[📚📄⚡📦📕📘🧾🖨✅🎉✕]/gu) || [];
    const printifyMatches = bodyText.match(/\bPrintify\b/gi) || [];
    const overflowX = document.documentElement.scrollWidth > window.innerWidth;
    const scrollHeight = document.documentElement.scrollHeight;

    return {
      isDark,
      hasBackLink: Boolean(backEl),
      backText,
      emojiCount: emojiMatches.length,
      emojiSamples: [...new Set(emojiMatches)].slice(0, 5),
      printifyCount: printifyMatches.length,
      overflowX,
      scrollWidth: document.documentElement.scrollWidth,
      innerWidth: window.innerWidth,
      scrollHeight,
    };
  });

  const outPath = path.join(OUTPUT_DIR, filename);
  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  await page.screenshot({ path: outPath, type: 'png', fullPage });

  const stats = fs.statSync(outPath);
  const dims = readPngDimensions(outPath);

  const issues = [];
  if (audit.isDark) {
    issues.push('html.dark class is active');
  }
  if (expectBackLink && !audit.hasBackLink) {
    issues.push('expected [data-test="universal-back-link"] but none found');
  }
  if (!expectBackLink && audit.hasBackLink) {
    issues.push(`unexpected [data-test="universal-back-link"] found ("${audit.backText}")`);
  }
  if (expectedBackText && (!audit.backText || !audit.backText.includes(expectedBackText))) {
    issues.push(`back link text "${audit.backText}" missing "${expectedBackText}"`);
  }
  if (audit.emojiCount > 0) {
    issues.push(`banned UI emojis found: ${audit.emojiSamples.join(', ')}`);
  }
  if (audit.printifyCount > 0) {
    issues.push(`legacy "Printify" string count: ${audit.printifyCount}`);
  }
  if (audit.overflowX) {
    issues.push(`horizontal overflow: scrollWidth=${audit.scrollWidth} > innerWidth=${audit.innerWidth}`);
  }
  if (telemetry.pageErrors.length > 0) {
    issues.push(`pageErrors: ${telemetry.pageErrors.join(' | ')}`);
  }
  if (telemetry.consoleErrors.length > 0) {
    issues.push(`consoleErrors: ${telemetry.consoleErrors.join(' | ')}`);
  }
  if (telemetry.httpErrors.length > 0) {
    issues.push(`httpErrors: ${telemetry.httpErrors.join(' | ')}`);
  }

  const tag = issues.length === 0 ? '[PASS]' : '[WARN]';
  const kb = (stats.size / 1024).toFixed(1);
  console.log(
    `${tag} ${filename} | ${dims.width}x${dims.height}px | ${kb} KB | scrollH=${audit.scrollHeight}px | dark=${audit.isDark} | back=${audit.hasBackLink ? `"${audit.backText}"` : 'none'} | emojis=${audit.emojiCount} | legacyBrand=${audit.printifyCount} | overflowX=${audit.overflowX}`
  );
  if (issues.length > 0) {
    for (const issue of issues) {
      console.warn(`       -> ${issue}`);
    }
  }

  telemetry.consoleErrors.length = 0;
  telemetry.pageErrors.length = 0;
  telemetry.httpErrors.length = 0;

  return issues.length === 0;
}

const TASKS = [
  // Batch 1 (01-03)
  {
    id: '01',
    batch: '1',
    filename: '01_auth_login.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '01_auth_login.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '02',
    batch: '1',
    filename: '02_super_admin_governance.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'admin@pressqueue.test');
        await page.goto(`${BASE_URL}/admin/shops`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '02_super_admin_governance.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '03',
    batch: '1',
    filename: '03_marketplace_directory.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'customer@pressqueue.test');
        await page.goto(`${BASE_URL}/shops`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '03_marketplace_directory.png', {
          expectBackLink: true,
          expectedBackText: 'My Orders',
        });
      } finally {
        await context.close();
      }
    },
  },

  // Batch 2 (04-07)
  {
    id: '04',
    batch: '2',
    filename: '04_customer_multi_item_checkout.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'customer@pressqueue.test');
        const shopId = await resolveShopId(page, 'Kabacan Academic Press');
        await page.goto(`${BASE_URL}/customer/shops/${shopId}/checkout`, { waitUntil: 'networkidle' });

        // Add Thesis Binding item (default service tab)
        await page.locator('input[wire\\:model="thesis_custom_fields_data.Thesis Title"]').fill('Multi-Tenant Print Queue Optimization');
        await page.locator('input[wire\\:model="thesis_custom_fields_data.Author"]').fill('Alyssa Marie Cruz');
        await page.locator('input[wire\\:model="thesis_custom_fields_data.Degree / Department"]').fill('BS Information Systems');
        await page.getByRole('button', { name: '+ Add Item to Cart' }).click();
        await page.waitForTimeout(450);

        // Switch to Document Printing & add a second item
        await page.getByRole('button', { name: 'Document Printing' }).click();
        await page.waitForTimeout(350);
        await page.locator('select[wire\\:model\\.live="doc_finishing_type"]').selectOption('ring_bind');
        await page.waitForTimeout(250);
        await page.getByRole('button', { name: '+ Add Item to Cart' }).click();
        await page.waitForTimeout(450);

        // Select 50% downpayment option to showcase flexible billing
        await page.locator('select[wire\\:model\\.live="payment_option"]').selectOption('downpayment_50');
        await page.waitForTimeout(300);

        return await captureScreen(page, telemetry, '04_customer_multi_item_checkout.png', {
          expectBackLink: true,
          expectedBackText: 'Kabacan Academic Press',
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '05',
    batch: '2',
    filename: '05_customer_order_tracking.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'customer@pressqueue.test');
        await page.goto(`${BASE_URL}/customer/dashboard`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '05_customer_order_tracking.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '06',
    batch: '2',
    filename: '06_owner_app_launcher.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'owner@pressqueue.test');
        await page.goto(`${BASE_URL}/owner/dashboard`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '06_owner_app_launcher.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '07',
    batch: '2',
    filename: '07_owner_more_services_modal.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'owner@pressqueue.test');
        await page.goto(`${BASE_URL}/owner/dashboard`, { waitUntil: 'networkidle' });
        await page.locator('button[wire\\:click="openMoreServicesModal"]').click();
        await page.getByRole('heading', { name: 'More Services App Store' }).waitFor({ state: 'visible', timeout: 8000 });
        return await captureScreen(page, telemetry, '07_owner_more_services_modal.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },

  // Batch 3 (08-10)
  {
    id: '08',
    batch: '3',
    filename: '08_owner_operations_and_fleet.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'owner@pressqueue.test');
        await page.goto(`${BASE_URL}/owner/platform`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '08_owner_operations_and_fleet.png', {
          expectBackLink: true,
          expectedBackText: 'App Launcher',
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '09',
    batch: '3',
    filename: '09_owner_inventory_bom.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'owner@pressqueue.test');
        await page.goto(`${BASE_URL}/owner/inventory`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '09_owner_inventory_bom.png', {
          expectBackLink: true,
          expectedBackText: 'App Launcher',
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '10',
    batch: '3',
    filename: '10_owner_payment_audit_refunds.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'owner@pressqueue.test');
        await page.goto(`${BASE_URL}/owner/analytics?tab=payments`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '10_owner_payment_audit_refunds.png', {
          expectBackLink: true,
          expectedBackText: 'App Launcher',
        });
      } finally {
        await context.close();
      }
    },
  },

  // Batch 4 (11-13)
  {
    id: '11',
    batch: '4',
    filename: '11_owner_standardized_reports.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'owner@pressqueue.test');
        await page.goto(`${BASE_URL}/owner/analytics?tab=reports`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '11_owner_standardized_reports.png', {
          expectBackLink: true,
          expectedBackText: 'App Launcher',
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '12',
    batch: '4',
    filename: '12_staff_production_queue.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'staff@pressqueue.test');
        await page.goto(`${BASE_URL}/staff/dashboard`, { waitUntil: 'networkidle' });
        return await captureScreen(page, telemetry, '12_staff_production_queue.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },
  {
    id: '13',
    batch: '4',
    filename: '13_staff_direct_browser_print.png',
    run: async (browser) => {
      const { context, page, telemetry } = await createLightContext(browser);
      try {
        await loginAs(page, 'staff@pressqueue.test');
        await page.goto(`${BASE_URL}/staff/dashboard`, { waitUntil: 'networkidle' });
        await page.locator('button[wire\\:click^="openPrintPreview("]').first().click();
        await page.getByRole('heading', { name: /Direct Print Dispatch/i }).waitFor({ state: 'visible', timeout: 8000 });
        await page.waitForTimeout(500);
        return await captureScreen(page, telemetry, '13_staff_direct_browser_print.png', {
          expectBackLink: false,
        });
      } finally {
        await context.close();
      }
    },
  },
];

async function main() {
  const { batch, only } = parseArgs(process.argv.slice(2));
  let selected = [];

  if (only) {
    selected = TASKS.filter((t) => t.id === only);
    if (selected.length === 0) {
      console.error(`Unknown screenshot id "${only}". Available: ${TASKS.map((t) => t.id).join(', ')}`);
      process.exit(1);
    }
  } else if (batch) {
    selected = TASKS.filter((t) => t.batch === batch);
    if (selected.length === 0) {
      console.error(`Unknown batch "${batch}". Valid batches: 1, 2, 3, 4`);
      process.exit(1);
    }
  } else {
    console.error('Specify --batch <1|2|3|4> or --only <01..13>');
    process.exit(1);
  }

  const browser = await chromium.launch({ headless: true });
  let allClean = true;

  try {
    for (const task of selected) {
      const ok = await task.run(browser);
      if (!ok) {
        allClean = false;
      }
    }
  } finally {
    await browser.close();
  }

  if (!allClean) {
    process.exit(2);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
