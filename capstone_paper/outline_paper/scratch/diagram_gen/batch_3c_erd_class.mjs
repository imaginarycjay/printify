/**
 * Batch 3C Diagram Generators (CR-3):
 *   1. PressQueue_ERD_v2.png (18-table Normalized Multi-Tenant ERD, Fig. 17 style)
 *   2. PressQueue_Class_Diagram_v2.png (UML 2.5 Domain & Service Class Diagram, Fig. 24 style)
 */

import path from 'node:path';
import {
  COLORS,
  assertOrthogonalPathClearsBoxes,
  drawCrowsFootCap,
  drawEdgeLabel,
  drawErdEntity,
  drawUmlClassBox,
  svgDoc,
} from './svg_kit.mjs';

function polyD(pts) {
  return pts.map(([x, y], idx) => `${idx === 0 ? 'M' : 'L'} ${x} ${y}`).join(' ');
}

function buildErdDiagram(diagramsDir) {
  const width = 1900;
  const height = 1380;

  // Exact 18 tables synced to live PostgreSQL 18 migrations
  const columns = [
    {
      x: 40,
      w: 356,
      gap: 22,
      startY: 42,
      tables: [
        {
          id: 'users',
          title: 'users',
          fields: [
            ['PK', 'id', 'bigint'],
            ['UK', 'email', 'varchar'],
            ['', 'name', 'varchar'],
            ['', 'password', 'varchar'],
            ['', 'role', 'varchar'],
            ['FK', 'staff_shop_id', 'bigint?'],
            ['', 'two_factor_secret', 'text?'],
          ],
        },
        {
          id: 'platform_settings',
          title: 'platform_settings',
          fields: [
            ['PK', 'id', 'bigint'],
            ['', 'default_retention_days', 'int4'],
            ['', 'min_retention_days', 'int4'],
            ['', 'max_retention_days', 'int4'],
            ['', 'max_upload_mb', 'int4'],
            ['FK', 'updated_by', 'bigint?'],
          ],
        },
        {
          id: 'print_shops',
          title: 'print_shops',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'user_id', 'bigint'],
            ['', 'name', 'varchar'],
            ['', 'approval_status', 'varchar'],
            ['', 'rejection_reason', 'text?'],
            ['', 'busy_threshold', 'int4'],
            ['', 'at_capacity_threshold', 'int4'],
            ['', 'file_retention_days', 'int4?'],
            ['', 'is_setup_completed', 'bool'],
          ],
        },
        {
          id: 'staff_invitations',
          title: 'staff_invitations',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['', 'email', 'varchar'],
            ['UK', 'token', 'varchar'],
            ['', 'expires_at', 'timestamp'],
            ['', 'accepted_at', 'timestamp?'],
          ],
        },
        {
          id: 'audit_logs',
          title: 'audit_logs',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint?'],
            ['FK', 'actor_id', 'bigint?'],
            ['', 'action', 'varchar'],
            ['', 'auditable_type', 'varchar?'],
            ['', 'auditable_id', 'bigint?'],
            ['', 'metadata', 'json?'],
          ],
        },
        {
          id: 'notifications',
          title: 'notifications',
          fields: [
            ['PK', 'id', 'uuid'],
            ['', 'type', 'varchar'],
            ['', 'notifiable_type', 'varchar'],
            ['FK', 'notifiable_id', 'bigint'],
            ['', 'data', 'text'],
            ['', 'read_at', 'timestamp?'],
          ],
        },
      ],
    },
    {
      x: 500,
      w: 380,
      gap: 44,
      startY: 42,
      tables: [
        {
          id: 'printers',
          title: 'printers',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['', 'name', 'varchar'],
            ['', 'status', 'varchar'],
            ['', 'supported_sizes', 'json?'],
            ['', 'is_wide_format', 'bool'],
          ],
        },
        {
          id: 'shop_services',
          title: 'shop_services',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['', 'service_key', 'varchar'],
            ['', 'is_active', 'bool'],
            ['', 'capacity', 'int4'],
            ['', 'pause_reason', 'text?'],
            ['', 'paused_until', 'timestamp?'],
          ],
        },
        {
          id: 'thesis_binding_configs',
          title: 'thesis_binding_configs',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['', 'hardbound_base_price', 'numeric'],
            ['', 'softbound_base_price', 'numeric'],
            ['', 'allow_customer_supplied_paper', 'bool'],
            ['', 'hardbound_cover_only_price', 'numeric'],
            ['', 'max_binding_sheets', 'int4'],
          ],
        },
        {
          id: 'document_printing_configs',
          title: 'document_printing_configs',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['', 'page_price_bw_short/a4/long', 'numeric'],
            ['', 'page_price_color_short/a4/long', 'numeric'],
            ['', 'paper_stock_70/80/100gsm_price', 'numeric'],
            ['', 'duplex_discount_percent', 'int2'],
            ['FK', 'bom_short/a4/long_paper_item_id', 'bigint?'],
            ['FK', 'bom_ring/pvc/back_item_id', 'bigint?'],
          ],
        },
      ],
    },
    {
      x: 988,
      w: 360,
      gap: 42,
      startY: 224, // Leaves open 182px top highway above Col 3 for printers -> order_items!
      tables: [
        {
          id: 'inventory_items',
          title: 'inventory_items',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['', 'name', 'varchar'],
            ['', 'sku', 'varchar?'],
            ['', 'category', 'varchar'],
            ['', 'item_type', 'varchar'],
            ['', 'stock_qty', 'numeric'],
            ['', 'reserved_qty', 'numeric'],
            ['', 'reorder_level', 'numeric'],
            ['', 'unit_cost', 'numeric'],
          ],
        },
        {
          id: 'thesis_binding_bom_items',
          title: 'thesis_binding_bom_items',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'thesis_binding_config_id', 'bigint'],
            ['FK', 'inventory_item_id', 'bigint'],
            ['', 'binding_type', 'varchar'],
            ['', 'usage_qty', 'numeric'],
            ['', 'unit', 'varchar'],
          ],
        },
        {
          id: 'service_boms',
          title: 'service_boms',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['FK', 'inventory_item_id', 'bigint'],
            ['', 'service_key', 'varchar'],
            ['', 'usage_type', 'varchar'],
            ['', 'usage_qty', 'numeric'],
          ],
        },
        {
          id: 'stock_movements',
          title: 'stock_movements',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'inventory_item_id', 'bigint'],
            ['FK', 'order_item_id', 'bigint?'],
            ['FK', 'logged_by', 'bigint?'],
            ['', 'movement_type', 'varchar'],
            ['', 'quantity', 'numeric'],
            ['', 'previous_stock', 'numeric'],
            ['', 'resulting_stock', 'numeric'],
          ],
        },
      ],
    },
    {
      x: 1456,
      w: 390,
      gap: 24,
      startY: 42,
      tables: [
        {
          id: 'orders',
          title: 'orders',
          fields: [
            ['PK', 'id', 'bigint'],
            ['UK', 'order_number', 'varchar'],
            ['UK', 'tracking_code', 'varchar?'],
            ['FK', 'print_shop_id', 'bigint'],
            ['FK', 'customer_id', 'bigint'],
            ['', 'service_key', 'varchar?'],
            ['', 'order_status', 'varchar'],
            ['', 'payment_status', 'varchar'],
            ['', 'payment_option', 'varchar'],
            ['', 'total_amount', 'numeric'],
            ['', 'amount_paid', 'numeric'],
            ['', 'balance_due', 'numeric'],
            ['', 'refund_due', 'numeric'],
            ['', 'refund_status', 'varchar'],
            ['', 'completed_at', 'timestamp?'],
          ],
        },
        {
          id: 'order_items',
          title: 'order_items',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'order_id', 'bigint'],
            ['', 'service_key', 'varchar'],
            ['FK', 'printer_id', 'bigint?'],
            ['FK', 'assigned_staff_id', 'bigint?'],
            ['', 'fulfillment_type', 'varchar'],
            ['', 'paper_size', 'varchar'],
            ['', 'copies_count', 'int4'],
            ['', 'unit_price', 'numeric'],
            ['', 'total_price', 'numeric'],
            ['', 'production_stage', 'varchar'],
            ['', 'prepress_status', 'varchar'],
            ['', 'is_spec_locked', 'bool'],
            ['', 'shelf_bin_code', 'varchar?'],
            ['', 'ready_at', 'timestamp?'],
            ['', 'claimed_at', 'timestamp?'],
            ['', 'document_file_path', 'varchar?'],
            ['', 'file_purged_at', 'timestamp?'],
          ],
        },
        {
          id: 'payments',
          title: 'payments',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'order_id', 'bigint'],
            ['FK', 'print_shop_id', 'bigint'],
            ['FK', 'submitted_by', 'bigint?'],
            ['FK', 'verified_by', 'bigint?'],
            ['UK', 'reference_no', 'varchar?'],
            ['', 'payment_type', 'varchar'],
            ['', 'payment_method', 'varchar'],
            ['', 'status', 'varchar'],
            ['', 'amount', 'numeric'],
            ['', 'submitted_at', 'timestamp?'],
            ['', 'verified_at', 'timestamp?'],
          ],
        },
        {
          id: 'spec_revisions',
          title: 'spec_revisions',
          fields: [
            ['PK', 'id', 'bigint'],
            ['FK', 'order_item_id', 'bigint'],
            ['FK', 'requested_by', 'bigint?'],
            ['FK', 'approved_by', 'bigint?'],
            ['', 'old_specs', 'json'],
            ['', 'new_specs', 'json'],
            ['', 'old_total', 'numeric'],
            ['', 'new_total', 'numeric'],
            ['', 'delta_amount', 'numeric'],
            ['', 'revision_type', 'varchar'],
            ['', 'status', 'varchar'],
            ['', 'settled_at', 'timestamp?'],
          ],
        },
      ],
    },
  ];

  const boxMap = new Map();
  const boxes = [];
  const entitySvgs = [];

  for (const col of columns) {
    let curY = col.startY;
    for (const t of col.tables) {
      const res = drawErdEntity({ x: col.x, y: curY, w: col.w, title: t.title, fields: t.fields, id: t.id });
      entitySvgs.push(res.svg);
      boxMap.set(t.id, res.box);
      boxes.push(res.box);
      curY += res.box.h + col.gap;
    }
  }

  const B = (id) => boxMap.get(id);
  const rightEdge = (id, frac = 0.5) => [B(id).x + B(id).w, Math.round(B(id).y + B(id).h * frac)];
  const leftEdge = (id, frac = 0.5) => [B(id).x, Math.round(B(id).y + B(id).h * frac)];
  const topEdge = (id, frac = 0.5) => [Math.round(B(id).x + B(id).w * frac), B(id).y];
  const botEdge = (id, frac = 0.5) => [Math.round(B(id).x + B(id).w * frac), B(id).y + B(id).h];

  // Orthogonal Crow's Foot edges using strict highway/corridor lanes
  const edges = [
    // 1. users -> platform_settings (Corridor A lane 414)
    {
      label: 'users -> platform_settings',
      from: rightEdge('users', 0.55),
      to: rightEdge('platform_settings', 0.5),
      via: (a, b) => [a, [414, a[1]], [414, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'left',
      toKind: 'zero-many',
    },
    // 2. users -> print_shops (Left gutter x=26)
    {
      label: 'users -> print_shops',
      from: leftEdge('users', 0.55),
      to: leftEdge('print_shops', 0.35),
      via: (a, b) => [a, [26, a[1]], [26, b[1]], b],
      fromDir: 'right',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 3. users -> audit_logs (Left gutter x=18)
    {
      label: 'users -> audit_logs',
      from: leftEdge('users', 0.38),
      to: leftEdge('audit_logs', 0.5),
      via: (a, b) => [a, [18, a[1]], [18, b[1]], b],
      fromDir: 'right',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 4. users -> notifications (Left gutter x=10)
    {
      label: 'users -> notifications',
      from: leftEdge('users', 0.22),
      to: leftEdge('notifications', 0.5),
      via: (a, b) => [a, [10, a[1]], [10, b[1]], b],
      fromDir: 'right',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 5. users -> orders (Top highway y=14)
    {
      label: 'users -> orders',
      from: topEdge('users', 0.5),
      to: topEdge('orders', 0.5),
      via: (a, b) => [a, [a[0], 14], [b[0], 14], b],
      fromDir: 'down',
      fromKind: 'one',
      toDir: 'down',
      toKind: 'zero-many',
    },
    // 6. print_shops -> staff_invitations (Corridor A lane 414)
    {
      label: 'print_shops -> staff_invitations',
      from: rightEdge('print_shops', 0.72),
      to: rightEdge('staff_invitations', 0.5),
      via: (a, b) => [a, [414, a[1]], [414, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'left',
      toKind: 'zero-many',
    },
    // 7. print_shops -> audit_logs (Corridor A lane 426)
    {
      label: 'print_shops -> audit_logs',
      from: rightEdge('print_shops', 0.84),
      to: rightEdge('audit_logs', 0.45),
      via: (a, b) => [a, [426, a[1]], [426, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'left',
      toKind: 'zero-many',
    },
    // 8. print_shops -> printers (Corridor A lane 438)
    {
      label: 'print_shops -> printers',
      from: rightEdge('print_shops', 0.16),
      to: leftEdge('printers', 0.5),
      via: (a, b) => [a, [438, a[1]], [438, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 9. print_shops -> shop_services (Corridor A lane 450)
    {
      label: 'print_shops -> shop_services',
      from: rightEdge('print_shops', 0.28),
      to: leftEdge('shop_services', 0.5),
      via: (a, b) => [a, [450, a[1]], [450, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 10. print_shops -> thesis_binding_configs (Corridor A lane 462)
    {
      label: 'print_shops -> thesis_binding_configs',
      from: rightEdge('print_shops', 0.4),
      to: leftEdge('thesis_binding_configs', 0.5),
      via: (a, b) => [a, [462, a[1]], [462, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-one',
    },
    // 11. print_shops -> document_printing_configs (Corridor A lane 474)
    {
      label: 'print_shops -> document_printing_configs',
      from: rightEdge('print_shops', 0.52),
      to: leftEdge('document_printing_configs', 0.5),
      via: (a, b) => [a, [474, a[1]], [474, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-one',
    },
    // 12. print_shops -> inventory_items (through gap between printers & shop_services y=206 -> Corridor B 904)
    {
      label: 'print_shops -> inventory_items',
      from: rightEdge('print_shops', 0.22),
      to: leftEdge('inventory_items', 0.35),
      via: (a, b) => [a, [444, a[1]], [444, 204], [904, 204], [904, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 13. print_shops -> service_boms (below Col 2 y=916 -> Corridor B 916 -> service_boms)
    {
      label: 'print_shops -> service_boms',
      from: rightEdge('print_shops', 0.62),
      to: leftEdge('service_boms', 0.5),
      via: (a, b) => [a, [484, a[1]], [484, 922], [916, 922], [916, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 14. print_shops -> orders (Corridor A lane 490 -> top highway y=26 -> orders top)
    {
      label: 'print_shops -> orders',
      from: rightEdge('print_shops', 0.1),
      to: topEdge('orders', 0.25),
      via: (a, b) => [a, [490, a[1]], [490, 26], [b[0], 26], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'down',
      toKind: 'zero-many',
    },
    // 15. print_shops -> payments (below Col 2 & Col 3 y=1068 -> payments left)
    {
      label: 'print_shops -> payments',
      from: rightEdge('print_shops', 0.92),
      to: leftEdge('payments', 0.55),
      via: (a, b) => [a, [432, a[1]], [432, 1084], [1390, 1084], [1390, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 16. printers -> order_items (across empty top of Col 3 y=115 -> Corridor C x=1380 -> order_items)
    {
      label: 'printers -> order_items',
      from: rightEdge('printers', 0.55),
      to: leftEdge('order_items', 0.25),
      via: (a, b) => [a, [1380, a[1]], [1380, b[1]], b],
      fromDir: 'left',
      fromKind: 'zero-one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 17. thesis_binding_configs -> thesis_binding_bom_items (Corridor B lane 934)
    {
      label: 'thesis_binding_configs -> thesis_binding_bom_items',
      from: rightEdge('thesis_binding_configs', 0.5),
      to: leftEdge('thesis_binding_bom_items', 0.5),
      via: (a, b) => [a, [934, a[1]], [934, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 18. inventory_items -> document_printing_configs (Corridor B lane 900)
    {
      label: 'inventory_items -> document_printing_configs',
      from: leftEdge('inventory_items', 0.8),
      to: rightEdge('document_printing_configs', 0.55),
      via: (a, b) => [a, [900, a[1]], [900, b[1]], b],
      fromDir: 'right',
      fromKind: 'zero-one',
      toDir: 'left',
      toKind: 'zero-many',
    },
    // 19. inventory_items -> thesis_binding_bom_items (Corridor B lane 950)
    {
      label: 'inventory_items -> thesis_binding_bom_items',
      from: leftEdge('inventory_items', 0.52),
      to: leftEdge('thesis_binding_bom_items', 0.35),
      via: (a, b) => [a, [950, a[1]], [950, b[1]], b],
      fromDir: 'right',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 20. inventory_items -> service_boms (Corridor B lane 962)
    {
      label: 'inventory_items -> service_boms',
      from: leftEdge('inventory_items', 0.65),
      to: leftEdge('service_boms', 0.35),
      via: (a, b) => [a, [962, a[1]], [962, b[1]], b],
      fromDir: 'right',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 21. inventory_items -> stock_movements (Corridor B lane 974)
    {
      label: 'inventory_items -> stock_movements',
      from: leftEdge('inventory_items', 0.92),
      to: leftEdge('stock_movements', 0.45),
      via: (a, b) => [a, [974, a[1]], [974, b[1]], b],
      fromDir: 'right',
      fromKind: 'one',
      toDir: 'right',
      toKind: 'zero-many',
    },
    // 22. order_items -> stock_movements (Corridor C lane 1412)
    {
      label: 'order_items -> stock_movements',
      from: leftEdge('order_items', 0.8),
      to: rightEdge('stock_movements', 0.45),
      via: (a, b) => [a, [1412, a[1]], [1412, b[1]], b],
      fromDir: 'right',
      fromKind: 'zero-one',
      toDir: 'left',
      toKind: 'zero-many',
    },
    // 23. orders -> order_items (Right gutter x=1862)
    {
      label: 'orders -> order_items',
      from: rightEdge('orders', 0.5),
      to: rightEdge('order_items', 0.3),
      via: (a, b) => [a, [1862, a[1]], [1862, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'left',
      toKind: 'many',
    },
    // 24. orders -> payments (Right gutter x=1876)
    {
      label: 'orders -> payments',
      from: rightEdge('orders', 0.68),
      to: rightEdge('payments', 0.45),
      via: (a, b) => [a, [1876, a[1]], [1876, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'left',
      toKind: 'zero-many',
    },
    // 25. order_items -> spec_revisions (Right gutter x=1888)
    {
      label: 'order_items -> spec_revisions',
      from: rightEdge('order_items', 0.7),
      to: rightEdge('spec_revisions', 0.5),
      via: (a, b) => [a, [1888, a[1]], [1888, b[1]], b],
      fromDir: 'left',
      fromKind: 'one',
      toDir: 'left',
      toKind: 'zero-many',
    },
  ];

  const edgeSvgs = [];
  for (const e of edges) {
    const pts = e.via(e.from, e.to);
    assertOrthogonalPathClearsBoxes(pts, boxes, [], e.label);
    edgeSvgs.push(
      `<path d="${polyD(pts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.35" stroke-linejoin="round" />`
    );
    edgeSvgs.push(drawCrowsFootCap({ x: e.from[0], y: e.from[1], dir: e.fromDir, kind: e.fromKind }));
    edgeSvgs.push(drawCrowsFootCap({ x: e.to[0], y: e.to[1], dir: e.toDir, kind: e.toKind }));
  }

  const svg = svgDoc({
    width,
    height,
    body: [...edgeSvgs, ...entitySvgs].join('\n'),
  });

  return {
    width,
    height,
    scale: 2,
    svg,
    outputPath: path.join(diagramsDir, 'PressQueue_ERD_v2.png'),
  };
}

function buildClassDiagram(diagramsDir) {
  const width = 1880;
  const height = 1320;

  const boxSpecs = [
    // Tier 1: User Superclass
    {
      id: 'User',
      x: 750,
      y: 26,
      w: 380,
      stereotype: '«authenticatable»',
      name: 'User',
      attributes: [
        '+ id: int',
        '+ name, email, role: string',
        '+ staff_shop_id: ?int',
        '+ two_factor_confirmed_at: ?Carbon',
      ],
      methods: [
        '+ isAdmin(), isOwner(): bool',
        '+ isStaff(), isCustomer(): bool',
        '+ operationalShop(): ?PrintShop',
        '+ suspendedShop(): ?PrintShop',
      ],
    },
    // Tier 2: Role Subclasses
    {
      id: 'PlatformAdmin',
      x: 56,
      y: 242,
      w: 340,
      stereotype: '«role»',
      name: 'PlatformAdmin',
      attributes: ['- role: string = platform_admin'],
      methods: ['+ reviewShop(shop, decision): void', '+ updateRetentionBounds(settings): void'],
    },
    {
      id: 'BusinessOwner',
      x: 492,
      y: 242,
      w: 340,
      stereotype: '«role»',
      name: 'BusinessOwner',
      attributes: ['- role: string = business_owner'],
      methods: ['+ configureShopAndPrinters(): void', '+ verifyPaymentProof(payment): void'],
    },
    {
      id: 'ProductionStaff',
      x: 1048,
      y: 242,
      w: 340,
      stereotype: '«role»',
      name: 'ProductionStaff',
      attributes: ['- role: string = production_staff', '- staff_shop_id: int'],
      methods: ['+ inspectPrepress(item): void', '+ dispatchPrintAndQa(item): void'],
    },
    {
      id: 'Customer',
      x: 1484,
      y: 242,
      w: 340,
      stereotype: '«role»',
      name: 'Customer',
      attributes: ['- role: string = customer'],
      methods: ['+ placeMultiItemOrder(cart): Order', '+ requestSpecRevision(item): void'],
    },
    // Tier 3: Tenant & Resource Entities (5 cols, w=324, gap=42 -> x = 46, 412, 778, 1144, 1510)
    {
      id: 'PrintShop',
      x: 46,
      y: 446,
      w: 324,
      stereotype: '«entity»',
      name: 'PrintShop',
      attributes: [
        '+ id, user_id: int',
        '+ name, approval_status: string',
        '+ busy_threshold, at_capacity: int',
        '+ file_retention_days: ?int',
      ],
      methods: [
        '+ workloadStatus(): string',
        '+ activeWorkloadItemCount(): int',
        '+ effectiveFileRetentionDays(): int',
        '+ isApproved(), isSuspended(): bool',
      ],
    },
    {
      id: 'Printer',
      x: 412,
      y: 446,
      w: 324,
      stereotype: '«entity»',
      name: 'Printer',
      attributes: [
        '+ id, print_shop_id: int',
        '+ name, status: string',
        '+ supported_sizes: array',
        '+ is_wide_format: bool',
      ],
      methods: [
        '+ isOnline(): bool',
        '+ isUnderMaintenance(): bool',
        '+ supportsSize(paperSize): bool',
        '+ activeJobsCount(): int',
      ],
    },
    {
      id: 'ShopService',
      x: 778,
      y: 446,
      w: 324,
      stereotype: '«entity»',
      name: 'ShopService',
      attributes: [
        '+ id, print_shop_id: int',
        '+ service_key: string',
        '+ is_active: bool',
        '+ capacity: int',
        '+ paused_until: ?Carbon',
      ],
      methods: ['+ isPaused(): bool', '+ acceptsOrders(): bool', '+ workload(): string'],
    },
    {
      id: 'InventoryItem',
      x: 1144,
      y: 446,
      w: 324,
      stereotype: '«entity»',
      name: 'InventoryItem',
      attributes: [
        '+ id, print_shop_id: int',
        '+ sku, category, item_type: string',
        '+ stock_qty, reserved_qty: float',
        '+ reorder_level: float',
      ],
      methods: [
        '+ availableQty(): float',
        '+ isLowStock(): bool',
        '+ isOutOfStock(): bool',
        '+ stockStatus(): string',
      ],
    },
    {
      id: 'AuditLog',
      x: 1510,
      y: 446,
      w: 324,
      stereotype: '«entity»',
      name: 'AuditLog',
      attributes: [
        '+ id, print_shop_id, actor_id: ?int',
        '+ action, auditable_type: string',
        '+ auditable_id: ?int',
        '+ metadata: array',
      ],
      methods: ['+ printShop(): BelongsTo', '+ actor(): BelongsTo', '+ auditable(): MorphTo'],
    },
    // Tier 4: Transactional & Execution Models (aligned directly under Tier 3 columns)
    {
      id: 'Order',
      x: 46,
      y: 730,
      w: 324,
      stereotype: '«aggregate»',
      name: 'Order',
      attributes: [
        '+ id, print_shop_id, customer_id: int',
        '+ order_number, tracking_code: string',
        '+ order_status, payment_status: string',
        '+ total_amount, amount_paid: float',
        '+ balance_due, refund_due: float',
      ],
      methods: [
        '+ minimumUpfrontAmount(): float',
        '+ syncParentStateFromItems(): void',
        '+ isMultiService(): bool',
        '+ hasAgingUnverifiedPayment(): bool',
      ],
    },
    {
      id: 'OrderItem',
      x: 412,
      y: 730,
      w: 324,
      stereotype: '«entity»',
      name: 'OrderItem',
      attributes: [
        '+ id, order_id, printer_id: ?int',
        '+ service_key, fulfillment_type: string',
        '+ unit_price, total_price: float',
        '+ production_stage, prepress_status: string',
        '+ is_spec_locked: bool',
        '+ shelf_bin_code: ?string',
      ],
      methods: [
        '+ hasStartedPrinting(): bool',
        '+ canReviseSpecifications(): bool',
        '+ isCoverOnly(): bool',
        '+ isUnclaimedAging(days): bool',
      ],
    },
    {
      id: 'Payment',
      x: 778,
      y: 730,
      w: 324,
      stereotype: '«entity»',
      name: 'Payment',
      attributes: [
        '+ id, order_id, print_shop_id: int',
        '+ payment_type, payment_method: string',
        '+ amount: float',
        '+ reference_no, status: string',
        '+ submitted_at, verified_at: ?Carbon',
      ],
      methods: ['+ isAging(hours): bool', '+ order(): BelongsTo', '+ verifiedBy(): BelongsTo'],
    },
    {
      id: 'SpecRevision',
      x: 1144,
      y: 730,
      w: 324,
      stereotype: '«entity»',
      name: 'SpecRevision',
      attributes: [
        '+ id, order_item_id: int',
        '+ old_specs, new_specs: array',
        '+ old_total, new_total: float',
        '+ delta_amount: float',
        '+ revision_type, status: string',
      ],
      methods: ['+ orderItem(): BelongsTo', '+ requestedBy(): BelongsTo', '+ approvedBy(): BelongsTo'],
    },
    {
      id: 'StockMovement',
      x: 1510,
      y: 730,
      w: 324,
      stereotype: '«entity»',
      name: 'StockMovement',
      attributes: [
        '+ id, inventory_item_id: int',
        '+ order_item_id, logged_by: ?int',
        '+ movement_type: string',
        '+ quantity: float',
        '+ previous_stock, resulting_stock: float',
      ],
      methods: ['+ inventoryItem(): BelongsTo', '+ orderItem(): BelongsTo', '+ loggedBy(): BelongsTo'],
    },
    // Tier 5: 6 Domain Services (w=278, gap=24 -> x = 46, 348, 650, 952, 1254, 1556)
    {
      id: 'OrderCheckoutService',
      x: 46,
      y: 1118,
      w: 278,
      stereotype: '«service»',
      name: 'OrderCheckoutService',
      attributes: ['- inventoryDeductionService'],
      methods: [
        '+ calculateDocumentItemPricing()',
        '+ calculateThesisItemPricing()',
        '+ validateBindingLimits()',
        '+ placeOrder()',
      ],
    },
    {
      id: 'ProductionQueueService',
      x: 348,
      y: 1118,
      w: 278,
      stereotype: '«service»',
      name: 'ProductionQueueService',
      attributes: ['- inventoryDeductionService'],
      methods: [
        '+ approvePrepress()',
        '+ rejectCoverOnlyAndShiftToShopPaper()',
        '+ setPrinterStatusWithFailover()',
        '+ failQualityCheckWithDualSpoilage()',
      ],
    },
    {
      id: 'PaymentService',
      x: 650,
      y: 1118,
      w: 278,
      stereotype: '«service»',
      name: 'PaymentService',
      attributes: ['- auditLogger'],
      methods: [
        '+ submitProof()',
        '+ verifyPayment()',
        '+ rejectPayment()',
        '+ recordOfflineCounterPayment()',
      ],
    },
    {
      id: 'SpecRevisionService',
      x: 952,
      y: 1118,
      w: 278,
      stereotype: '«service»',
      name: 'SpecRevisionService',
      attributes: ['- orderCheckoutService'],
      methods: [
        '+ requestRevision()',
        '+ approveAndApplyRevision()',
        '+ rejectRevision()',
        '+ settleManualRefund()',
      ],
    },
    {
      id: 'InventoryDeductionService',
      x: 1254,
      y: 1118,
      w: 278,
      stereotype: '«service»',
      name: 'InventoryDeductionService',
      attributes: ['- auditLogger'],
      methods: [
        '+ reserveForOrderItem()',
        '+ commitReservationOnPrint()',
        '+ recordSpoilage()',
        '+ notifyIfLowStock()',
      ],
    },
    {
      id: 'ReportService',
      x: 1556,
      y: 1118,
      w: 278,
      stereotype: '«service»',
      name: 'ReportService',
      attributes: ['- salesAnalyticsService'],
      methods: [
        '+ buildInventoryReport()',
        '+ buildSalesReport()',
        '+ buildProductionScheduleReport()',
        '+ exportCsv()',
      ],
    },
  ];

  const boxMap = new Map();
  const boxes = [];
  const boxSvgs = [];

  for (const spec of boxSpecs) {
    const res = drawUmlClassBox(spec);
    boxSvgs.push(res.svg);
    boxMap.set(spec.id, res.box);
    boxes.push(res.box);
  }

  const B = (id) => boxMap.get(id);
  const lines = [];
  const labels = [];

  // 1. Upward Generalization: Role Subclasses -> User
  const userBox = B('User');
  const genTargetX = userBox.x + userBox.w / 2;
  const genTargetY = userBox.y + userBox.h;
  const genBusY = 212;
  const roleCenters = ['PlatformAdmin', 'BusinessOwner', 'ProductionStaff', 'Customer'].map((id) => ({
    id,
    cx: Math.round(B(id).x + B(id).w / 2),
    top: B(id).y,
  }));
  // Horizontal generalization bus + vertical drops
  lines.push(
    `<line x1="${roleCenters[0].cx}" y1="${genBusY}" x2="${roleCenters[3].cx}" y2="${genBusY}" stroke="${COLORS.stroke}" stroke-width="1.4" />`
  );
  roleCenters.forEach((rc) => {
    lines.push(`<line x1="${rc.cx}" y1="${rc.top}" x2="${rc.cx}" y2="${genBusY}" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  });
  lines.push(
    `<line x1="${genTargetX}" y1="${genBusY}" x2="${genTargetX}" y2="${genTargetY}" stroke="${COLORS.stroke}" stroke-width="1.5" marker-end="url(#arrow-gen)" />`
  );

  // 2. BusinessOwner (1) <-> (1) PrintShop
  const bo = B('BusinessOwner');
  const ps = B('PrintShop');
  const boPts = [
    [Math.round(bo.x + bo.w / 2), bo.y + bo.h],
    [Math.round(bo.x + bo.w / 2), 376],
    [Math.round(ps.x + ps.w * 0.32), 376],
    [Math.round(ps.x + ps.w * 0.32), ps.y],
  ];
  assertOrthogonalPathClearsBoxes(boPts, boxes, [], 'BusinessOwner-PrintShop');
  lines.push(`<path d="${polyD(boPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: boPts[0][0] - 14, y: boPts[0][1] + 13, text: '1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: boPts[3][0] - 14, y: boPts[3][1] - 13, text: '1', size: 10.5 }));

  // 3. PrintShop (1) ◆── (0..*) Printer, ShopService, InventoryItem, AuditLog via horizontal bus at y=406
  const compBusY = 406;
  const psCompX = Math.round(ps.x + ps.w * 0.78);
  const compTargets = [
    { id: 'Printer', x: Math.round(B('Printer').x + B('Printer').w * 0.5) },
    { id: 'ShopService', x: Math.round(B('ShopService').x + B('ShopService').w * 0.5) },
    { id: 'InventoryItem', x: Math.round(B('InventoryItem').x + B('InventoryItem').w * 0.5) },
    { id: 'AuditLog', x: Math.round(B('AuditLog').x + B('AuditLog').w * 0.5) },
  ];
  // Main composition spine from PrintShop top up to compBusY and across to AuditLog
  lines.push(
    `<path d="M ${psCompX} ${ps.y} L ${psCompX} ${compBusY} L ${compTargets[3].x} ${compBusY}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" marker-start="url(#diamond-solid)" />`
  );
  compTargets.forEach((ct) => {
    const tb = B(ct.id);
    lines.push(`<line x1="${ct.x}" y1="${compBusY}" x2="${ct.x}" y2="${tb.y}" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
    labels.push(drawEdgeLabel({ x: ct.x + 18, y: tb.y - 13, text: '0..*', size: 10.5 }));
  });
  labels.push(drawEdgeLabel({ x: psCompX + 14, y: ps.y - 18, text: '1', size: 10.5 }));

  // 4. PrintShop (1) -> (0..*) Order (vertical drop at x=208)
  const ord = B('Order');
  const psOrdPts = [
    [208, ps.y + ps.h],
    [208, ord.y],
  ];
  assertOrthogonalPathClearsBoxes(psOrdPts, boxes, [], 'PrintShop-Order');
  lines.push(`<path d="${polyD(psOrdPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: 194, y: psOrdPts[0][1] + 15, text: '1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: 190, y: psOrdPts[1][1] - 13, text: '0..*', size: 10.5 }));

  // 5. Printer (0..1) -> (0..*) OrderItem (vertical drop at x=574)
  const prt = B('Printer');
  const oi = B('OrderItem');
  const prtOiPts = [
    [574, prt.y + prt.h],
    [574, oi.y],
  ];
  assertOrthogonalPathClearsBoxes(prtOiPts, boxes, [], 'Printer-OrderItem');
  lines.push(`<path d="${polyD(prtOiPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: 556, y: prtOiPts[0][1] + 15, text: '0..1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: 556, y: prtOiPts[1][1] - 13, text: '0..*', size: 10.5 }));

  // 6. Order (1) ◆── (1..*) OrderItem (horizontal composition across 42px gap at y=835)
  const ordOiPts = [
    [ord.x + ord.w, 835],
    [oi.x, 835],
  ];
  lines.push(`<path d="${polyD(ordOiPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" marker-start="url(#diamond-solid)" />`);
  labels.push(drawEdgeLabel({ x: ord.x + ord.w + 13, y: 820, text: '1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: oi.x - 14, y: 851, text: '1..*', size: 10.5 }));

  // 7. OrderItem (1) -> (0..*) SpecRevision (Tier 3-4 corridor y=664: x=636 -> 1306)
  const sr = B('SpecRevision');
  const oiSrPts = [
    [636, oi.y],
    [636, 664],
    [1306, 664],
    [1306, sr.y],
  ];
  assertOrthogonalPathClearsBoxes(oiSrPts, boxes, [], 'OrderItem-SpecRevision');
  lines.push(`<path d="${polyD(oiSrPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: 650, y: oi.y - 13, text: '1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: 1286, y: sr.y - 13, text: '0..*', size: 10.5 }));

  // 8. InventoryItem (1) -> (0..*) StockMovement (Tier 3-4 corridor y=686: x=1360 -> 1672)
  const inv = B('InventoryItem');
  const sm = B('StockMovement');
  const invSmPts = [
    [1360, inv.y + inv.h],
    [1360, 686],
    [1672, 686],
    [1672, sm.y],
  ];
  assertOrthogonalPathClearsBoxes(invSmPts, boxes, [], 'InventoryItem-StockMovement');
  lines.push(`<path d="${polyD(invSmPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: 1374, y: inv.y + inv.h + 15, text: '1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: 1652, y: sm.y - 13, text: '0..*', size: 10.5 }));

  // 9. Order (1) -> (0..*) Payment (Tier 4-5 corridor y=986: x=240 -> 940)
  const pay = B('Payment');
  const ordPayPts = [
    [240, ord.y + ord.h],
    [240, 986],
    [940, 986],
    [940, pay.y + pay.h],
  ];
  assertOrthogonalPathClearsBoxes(ordPayPts, boxes, [], 'Order-Payment');
  lines.push(`<path d="${polyD(ordPayPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: 254, y: ord.y + ord.h + 15, text: '1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: 922, y: pay.y + pay.h + 15, text: '0..*', size: 10.5 }));

  // 10. OrderItem (0..1) -> (0..*) StockMovement (Tier 4-5 corridor y=1016: x=620 -> 1680)
  const oiSmPts = [
    [620, oi.y + oi.h],
    [620, 1016],
    [1680, 1016],
    [1680, sm.y + sm.h],
  ];
  assertOrthogonalPathClearsBoxes(oiSmPts, boxes, [], 'OrderItem-StockMovement');
  lines.push(`<path d="${polyD(oiSmPts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.4" />`);
  labels.push(drawEdgeLabel({ x: 640, y: oi.y + oi.h + 15, text: '0..1', size: 10.5 }));
  labels.push(drawEdgeLabel({ x: 1660, y: sm.y + sm.h + 15, text: '0..*', size: 10.5 }));

  // 11. Dashed Tier 5 Service Dependencies (Tier 4-5 corridor lanes y=1046..1094)
  const deps = [
    {
      label: 'OrderCheckoutService -> Order',
      pts: [
        [150, B('OrderCheckoutService').y],
        [150, ord.y + ord.h],
      ],
    },
    {
      label: 'OrderCheckoutService -> OrderItem',
      pts: [
        [220, B('OrderCheckoutService').y],
        [220, 1062],
        [456, 1062],
        [456, oi.y + oi.h],
      ],
    },
    {
      label: 'ProductionQueueService -> OrderItem',
      pts: [
        [520, B('ProductionQueueService').y],
        [520, oi.y + oi.h],
      ],
    },
    {
      label: 'PaymentService -> Payment',
      pts: [
        [790, B('PaymentService').y],
        [790, 1062],
        [860, 1062],
        [860, pay.y + pay.h],
      ],
    },
    {
      label: 'SpecRevisionService -> SpecRevision',
      pts: [
        [1090, B('SpecRevisionService').y],
        [1090, 1062],
        [1240, 1062],
        [1240, sr.y + sr.h],
      ],
    },
    {
      label: 'InventoryDeductionService -> StockMovement',
      pts: [
        [1393, B('InventoryDeductionService').y],
        [1393, 1062],
        [1560, 1062],
        [1560, sm.y + sm.h],
      ],
    },
    {
      label: 'ReportService -> StockMovement',
      pts: [
        [1745, B('ReportService').y],
        [1745, sm.y + sm.h],
      ],
    },
    {
      label: 'ReportService -> Payment',
      pts: [
        [1635, B('ReportService').y],
        [1635, 1086],
        [1020, 1086],
        [1020, pay.y + pay.h],
      ],
    },
  ];

  for (const d of deps) {
    assertOrthogonalPathClearsBoxes(d.pts, boxes, [], d.label);
    lines.push(
      `<path d="${polyD(d.pts)}" fill="none" stroke="${COLORS.stroke}" stroke-width="1.3" stroke-dasharray="5 4" stroke-linejoin="round" marker-end="url(#arrow-open)" />`
    );
  }

  const svg = svgDoc({
    width,
    height,
    body: [...lines, ...boxSvgs, ...labels].join('\n'),
  });

  return {
    width,
    height,
    scale: 2,
    svg,
    outputPath: path.join(diagramsDir, 'PressQueue_Class_Diagram_v2.png'),
  };
}

export function getBatch3CDiagrams(diagramsDir) {
  return [
    {
      id: 'erd_v2',
      build: () => buildErdDiagram(diagramsDir),
    },
    {
      id: 'class_v2',
      build: () => buildClassDiagram(diagramsDir),
    },
  ];
}
