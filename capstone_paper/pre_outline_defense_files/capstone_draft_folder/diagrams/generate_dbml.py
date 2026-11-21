import json

dbml_code = """// ============================================================================
// DBML Schema for dbdiagram.io
// Project: Integrated Dynamic Order, Job Scheduling, and Inventory Management
// System for Printing Services with Automated Replenishment (Printify)
// Database: PostgreSQL / MySQL / SQLite Architecture (Laravel Eloquent)
// ============================================================================

// ----------------------------------------------------------------------------
// 1. AUTHENTICATION & MULTI-TENANT PRINT SHOPS
// ----------------------------------------------------------------------------

Table users as U {
  id bigint [pk, increment, note: 'Primary Key']
  name varchar(255) [not null]
  email varchar(255) [unique, not null]
  email_verified_at timestamp [null]
  password varchar(255) [not null]
  role varchar(255) [not null, note: 'business_owner | production_staff | customer']
  avatar varchar(255) [null]
  two_factor_secret text [null]
  two_factor_recovery_codes text [null]
  two_factor_confirmed_at timestamp [null]
  remember_token varchar(100) [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Central authentication table handling RBAC for Owners, Staff, and Customers.'
}

Table print_shops as PS {
  id bigint [pk, increment]
  user_id bigint [not null, unique, note: 'Owner User ID (1:1 with business_owner)']
  name varchar(255) [not null]
  is_setup_completed boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp

  Note: 'Root shop entity representing the printing business instance.'
}

Table shop_services as SS {
  id bigint [pk, increment]
  print_shop_id bigint [not null]
  service_key varchar(255) [not null, note: 'thesis_binding | document_printing | custom']
  name varchar(255) [not null]
  is_active boolean [not null, default: true]
  display_order integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp

  Note: 'Active service catalog modules available in the print shop.'
}

// ----------------------------------------------------------------------------
// 2. ORDER INTAKE & 5-STAGE PRODUCTION KANBAN
// ----------------------------------------------------------------------------

Table orders as O {
  id bigint [pk, increment]
  order_number varchar(255) [unique, not null, note: 'Tracking code: ORD-YYYYMMDD-XXXX']
  print_shop_id bigint [not null]
  customer_id bigint [not null]
  service_key varchar(255) [not null, note: 'thesis_binding | document_printing']
  order_status varchar(255) [not null, default: 'pending_payment', note: 'pending_payment | in_queue | in_production | quality_check | ready_for_pickup | completed | cancelled']
  payment_status varchar(255) [not null, default: 'unpaid', note: 'unpaid | pending_verification | verified_paid | rejected']
  subtotal_amount numeric(10,2) [not null, default: 0.00]
  rush_fee_amount numeric(10,2) [not null, default: 0.00]
  total_amount numeric(10,2) [not null, default: 0.00]
  is_rush boolean [not null, default: false]
  target_completion_date date [null]
  payment_proof_path varchar(255) [null, note: 'Uploaded GCash/Maya receipt image']
  payment_reference_no varchar(255) [null, note: 'GCash / Bank reference number']
  payment_verified_at timestamp [null]
  payment_verified_by bigint [null, note: 'Staff user ID who verified payment']
  assigned_staff_id bigint [null, note: 'Staff operator assigned to job']
  assigned_machine varchar(255) [null, note: 'Allocated printer or binding equipment']
  production_stage varchar(255) [not null, default: 'queue', note: 'queue | printing | binding | quality_check | ready_for_pickup | completed']
  production_started_at timestamp [null]
  production_completed_at timestamp [null]
  staff_notes text [null]
  rejection_reason text [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Master order and job scheduling table driving the 5-stage workshop Kanban.'
}

Table order_items as OI {
  id bigint [pk, increment]
  order_id bigint [not null]
  binding_type varchar(255) [not null, note: 'hardbound | softbound | ring_bind | staple | none']
  fulfillment_type varchar(255) [not null, default: 'full_package', note: 'full_package | cover_only (Dala ang Papel)']
  is_paper_received boolean [not null, default: false, note: 'True once client paper arrives']
  estimated_spine_thickness_mm numeric(6,2) [not null, default: 0.00]
  bw_pages_count integer [not null, default: 0]
  color_pages_count integer [not null, default: 0]
  total_pages_count integer [not null, default: 0]
  cover_color varchar(255) [null]
  foil_color varchar(255) [null]
  paper_size varchar(255) [not null, note: 'Letter | A4 | Legal']
  copies_count integer [not null, default: 1]
  custom_fields_data json [null, note: 'Thesis title, researcher names, print_sides, etc.']
  selected_addons json [null, note: 'Array of selected options/finishes']
  document_file_path varchar(255) [null, note: 'Uploaded customer PDF/Word manuscript']
  document_original_name varchar(255) [null]
  unit_price numeric(10,2) [not null]
  total_price numeric(10,2) [not null]
  created_at timestamp
  updated_at timestamp

  Note: 'Individual customized print item specifications and substrate details.'
}

// ----------------------------------------------------------------------------
// 3. INVENTORY TELEMETRY & STOCK LEDGERS
// ----------------------------------------------------------------------------

Table inventory_items as II {
  id bigint [pk, increment]
  print_shop_id bigint [not null]
  name varchar(255) [not null]
  sku varchar(255) [unique, null]
  category varchar(255) [not null, note: 'Paper | Binding | Ink | Consumable']
  item_type varchar(255) [not null, default: 'raw_material', note: 'raw_material | ready_to_sell | consumable']
  service_tag varchar(255) [null, note: 'thesis_binding | document_printing | all']
  stock_qty numeric(10,2) [not null, default: 0.00]
  unit varchar(255) [not null, note: 'sheets | reams | pcs | rolls | ml']
  reorder_level numeric(10,2) [not null, default: 10.00, note: 'Threshold for low-stock alerts']
  unit_cost numeric(10,2) [not null, default: 0.00]
  selling_price numeric(10,2) [null]
  supplier_name varchar(255) [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Raw material and consumable inventory stocks with dynamic ROP monitoring.'
}

Table stock_movements as SM {
  id bigint [pk, increment]
  inventory_item_id bigint [not null]
  movement_type varchar(255) [not null, note: 'manual_stock_in | production_deduction | spoilage_waste | manual_adjustment']
  quantity numeric(10,2) [not null]
  previous_stock numeric(10,2) [not null]
  resulting_stock numeric(10,2) [not null]
  reference_note varchar(255) [null, note: 'Order ID or restock note']
  logged_by bigint [null, note: 'User ID who recorded or triggered movement']
  created_at timestamp
  updated_at timestamp

  Note: 'Immutable audit trail for every stock change (burn rate calculation source).'
}

// ----------------------------------------------------------------------------
// 4. DYNAMIC SERVICE CONFIGURATIONS & BILL OF MATERIALS (BOM)
// ----------------------------------------------------------------------------

Table thesis_binding_configs as TBC {
  id bigint [pk, increment]
  print_shop_id bigint [not null, unique]
  is_active boolean [not null, default: true]
  hardbound_base_price numeric(8,2) [not null, default: 250.00]
  softbound_base_price numeric(8,2) [not null, default: 150.00]
  allow_customer_supplied_paper boolean [not null, default: true]
  hardbound_cover_only_price numeric(8,2) [not null, default: 200.00]
  page_price_bw numeric(8,2) [not null, default: 1.50]
  page_price_color numeric(8,2) [not null, default: 5.00]
  rush_fee numeric(8,2) [not null, default: 100.00]
  cover_colors json [null, note: '["Maroon", "Dark Blue", "Black", "Green"]']
  foil_colors json [null, note: '["Gold", "Silver"]']
  paper_sizes json [null, note: '["A4", "Letter", "Legal"]']
  auto_deduct_inventory boolean [not null, default: true]
  daily_production_quota integer [not null, default: 15]
  standard_lead_time_days integer [not null, default: 3]
  rush_lead_time_days integer [not null, default: 1]
  require_pdf_upload boolean [not null, default: true]
  custom_cover_fields json [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Dynamic pricing formulas and business rules for Thesis Binding.'
}

Table thesis_binding_bom_items as TBB {
  id bigint [pk, increment]
  thesis_binding_config_id bigint [not null]
  inventory_item_id bigint [not null]
  binding_type varchar(255) [not null, note: 'hardbound | softbound']
  usage_qty numeric(8,2) [not null, note: 'Quantity consumed per copy']
  unit varchar(255) [not null]
  created_at timestamp
  updated_at timestamp

  Note: 'Bill of Materials recipe mapping supplies (boards, foil) to thesis bindings.'
}

Table document_printing_configs as DPC {
  id bigint [pk, increment]
  print_shop_id bigint [not null, unique]
  page_price_bw_short numeric(8,2) [not null, default: 1.50]
  page_price_bw_a4 numeric(8,2) [not null, default: 1.50]
  page_price_bw_long numeric(8,2) [not null, default: 2.00]
  page_price_color_short numeric(8,2) [not null, default: 5.00]
  page_price_color_a4 numeric(8,2) [not null, default: 5.00]
  page_price_color_long numeric(8,2) [not null, default: 6.00]
  paper_stock_70gsm_price numeric(8,2) [not null, default: 0.00]
  paper_stock_80gsm_price numeric(8,2) [not null, default: 0.50]
  paper_stock_100gsm_price numeric(8,2) [not null, default: 1.50]
  duplex_discount_percent smallint [not null, default: 10]
  allow_staple boolean [not null, default: true]
  staple_price numeric(8,2) [not null, default: 2.00]
  allow_folder_fastener boolean [not null, default: true]
  folder_fastener_price numeric(8,2) [not null, default: 15.00]
  allow_ring_binding boolean [not null, default: true]
  ring_bind_base_price numeric(8,2) [not null, default: 45.00]
  allow_booklet_staple boolean [not null, default: true]
  booklet_staple_price numeric(8,2) [not null, default: 20.00]
  allow_rush_orders boolean [not null, default: true]
  rush_fee_amount numeric(8,2) [not null, default: 50.00]
  auto_deduct_inventory boolean [not null, default: true]
  bom_short_paper_item_id bigint [null]
  bom_a4_paper_item_id bigint [null]
  bom_long_paper_item_id bigint [null]
  bom_ring_spine_item_id bigint [null]
  bom_pvc_acetate_item_id bigint [null]
  bom_back_cover_item_id bigint [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Dynamic page rates, duplex rules, finishing addons, and direct BOM item links.'
}

// ----------------------------------------------------------------------------
// 5. RELATIONSHIPS & CARDINALITIES (1:1, 1:N)
// ----------------------------------------------------------------------------

// Multi-tenant Shop Ownership
Ref: print_shops.user_id - users.id // 1:1
Ref: shop_services.print_shop_id > print_shops.id // 1:N

// Dynamic Service Configurations
Ref: thesis_binding_configs.print_shop_id - print_shops.id // 1:1
Ref: document_printing_configs.print_shop_id - print_shops.id // 1:1

// Order Relationships
Ref: orders.print_shop_id > print_shops.id // 1:N
Ref: orders.customer_id > users.id // 1:N
Ref: orders.payment_verified_by > users.id // 1:N (staff verifier)
Ref: orders.assigned_staff_id > users.id // 1:N (staff operator)
Ref: order_items.order_id > orders.id // 1:N

// Inventory & Movements
Ref: inventory_items.print_shop_id > print_shops.id // 1:N
Ref: stock_movements.inventory_item_id > inventory_items.id // 1:N
Ref: stock_movements.logged_by > users.id // 1:N

// Bill of Materials (BOM) Recipes & Linkages
Ref: thesis_binding_bom_items.thesis_binding_config_id > thesis_binding_configs.id // 1:N
Ref: thesis_binding_bom_items.inventory_item_id > inventory_items.id // 1:N

Ref: document_printing_configs.bom_short_paper_item_id > inventory_items.id // 1:N
Ref: document_printing_configs.bom_a4_paper_item_id > inventory_items.id // 1:N
Ref: document_printing_configs.bom_long_paper_item_id > inventory_items.id // 1:N
Ref: document_printing_configs.bom_ring_spine_item_id > inventory_items.id // 1:N
Ref: document_printing_configs.bom_pvc_acetate_item_id > inventory_items.id // 1:N
Ref: document_printing_configs.bom_back_cover_item_id > inventory_items.id // 1:N

// ----------------------------------------------------------------------------
// 6. VISUAL TABLE GROUPS FOR DBDATABASE CANVAS
// ----------------------------------------------------------------------------

TableGroup Auth_and_Shop_Management {
  users
  print_shops
  shop_services
}

TableGroup Ordering_and_Kanban_Production {
  orders
  order_items
}

TableGroup Inventory_and_Telemetry {
  inventory_items
  stock_movements
}

TableGroup Service_Configurations_and_BOM {
  thesis_binding_configs
  thesis_binding_bom_items
  document_printing_configs
}
"""

with open("/home/imaginarycjay/printify/capstone_paper/capstone_draft_folder/diagrams/database_schema.dbml", "w") as f:
    f.write(dbml_code.strip())

print("SUCCESS: database_schema.dbml written successfully.")
