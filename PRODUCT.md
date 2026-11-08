# Printify

<!-- impeccable:product-schema 1 -->

## Platform

web

## Record Basis

Initialized from the repository and business workflows on 2026-11-07, at the user's direction to infer context without an interview. Implemented behavior below is evidence of the current product, not proof of production deployment. Strategic priorities are explicitly identified as inferred.

## Users

- Customers browse shops, configure document printing or thesis binding, submit orders and payment details, and track progress to pickup.
- Business owners configure their shop, services, pricing, materials, availability, and staff; monitor orders and analytics; and manage their shop's web presence.
- Production staff work within their assigned shop to handle job tickets, payment verification, physical manuscript intake, production stages, quality checks, and spoilage reporting.
- Platform administrators review shop applications for approval or rejection.

Inferred priority: treat customers, owners, and staff as distinct task audiences. Customer ordering and staff fulfillment form the central end-to-end workflow; the repository does not establish one audience as commercially dominant.

## Product Purpose

Connect customer print orders with the shop operations that fulfill them. The welcome page describes a unified printing services system with configurable services, job queues, and transparent order tracking.

Inferred success criteria: customers can choose an available service and submit an accurate order; staff can identify and complete the next production action; owners can maintain service settings and understand material use and sales.

## Positioning

The implemented mechanism combines a multi-shop service directory with shop-specific ordering, production tracking, configurable pricing, and bill-of-materials inventory deductions. This is the local application named Printify; the repository provides no evidence of affiliation with any similarly named external service or of a unique market advantage.

## Operating Context

1. An owner registers a shop, completes setup, and configures services. Administrative approval and completed setup control public shop visibility.
2. Customers browse approved shops and service availability, then choose a shop-specific document-printing or thesis-binding flow.
3. Order configuration captures the applicable files, printing or binding specifications, quantities, finishing options, and calculated charges. Thesis binding also supports cover-only jobs using customer-supplied printed pages.
4. Document checkout offers GCash, Maya, and cash at the counter. Thesis checkout collects a GCash reference and optional proof image. Electronic payment submissions await verification; counter payment is represented separately as unpaid.
5. Staff inspect job tickets, verify payments, record physical paper receipt when needed, and move work through production and quality inspection. The model represents queue, printing, binding, quality check, ready for pickup, and completion; actual service behavior must be checked before assuming every order needs every stage.
6. Inventory deductions use configured service recipes and service-specific material calculations. Staff can record material spoilage and quality-check rejection.
7. Customers follow order and payment statuses; owners inspect analytics and manage ongoing operations.

The implemented monetary displays use Philippine pesos and local wallet terminology. A Philippine operating context is inferred; a specific city, university, exclusive audience, or launch territory is not established.

## Capabilities and Constraints

- Laravel 13, Livewire 4, Flux Blade components, and Tailwind CSS 4 form the existing application stack. Full-page components predominantly live in `resources/views/pages/` as inline Blade components.
- Shop ownership, staff membership, customer access, and administrative access are separate authorization concerns. UI changes must preserve shop boundaries and protected document/payment-file access.
- Service availability includes active state, capacity, temporary pauses, and pause reasons. Public workload is refreshed every 30 seconds and explicitly does not promise a completion time; a busy shop may still accept orders.
- Payment status and production status are separate information. Payment submission must not be presented as verified payment.
- Cover-only binding requires physical paper intake; file upload cannot silently replace that handoff.
- Existing features include inventory, service configuration, analytics, a web builder, staff invitations, order tracking, and account settings.
- Preserve existing routes, calculated prices, validation, file requirements, role restrictions, and workflow actions during an audit or refinement unless a separate behavior change is requested.
- Production deployment, delivery services, automated wallet settlement, subscriptions, commercial pricing, and service-level guarantees are not established by this record.

## Brand Commitments

The existing application name is Printify, with shared logo components under `resources/views/components/`. Existing interface copy is English. Preserve the incumbent identity during audit and refinement; this initialization does not authorize a rebrand or establish new visual rules.

## Evidence on Hand

- `routes/web.php`: public directory, shop profiles, owner registration, shop review, staff invitations, role-specific dashboards, and ordering entry points.
- `resources/views/welcome.blade.php`: current product description and public entry actions.
- `resources/views/pages/`: implemented customer, owner, staff, and administrator interfaces.
- `app/Models/Order.php`: order/payment terminology, production stages, and physical-paper intake state.
- `app/Http/Controllers/PlatformController.php`: shop review, availability, invitations, and protected files.
- `app/Services/InventoryDeductionService.php`: service recipes and material consumption.
- `tests/Feature/TenantPlatformTest.php`, `CustomerThesisOrderTest.php`, `DocumentPrintingServiceTest.php`, and `ProductionStaffQueueTest.php`: executable workflow examples; their presence alone does not establish that the current checkout passes them.
- `resources/css/app.css` and shared view components: incumbent visual implementation for a later audit to inspect, rather than a new design specification.
- `capstone_paper/`: project academic sources and diagrams; not independently validated as current product requirements during initialization.

No verified customer testimonials, adoption metrics, performance benchmarks, or production screenshots were established during this initialization. Do not fabricate them.

## Product Principles

Inferred from the implemented business process:

1. Make the user's next valid action clear for their role and order state.
2. Keep pricing, service availability, payment verification, and production progress understandable and distinct.
3. Preserve the connection between online orders and physical shop work, including material use and pickup.
4. Keep shop-specific data and actions within their authorization boundaries.
5. Evaluate UI quality against complete tasks and exceptional states, including paused services, rejected payments, missing paper intake, and quality-check rework.

## Open Decisions

No product-specific accessibility standard, device distribution, primary commercial audience, geographic restriction, or marketing claim has been confirmed. Responsive behavior, keyboard access, readable status information, and form accessibility should be inspected during the UI audit without treating them as already verified.

No persistent image-first versus code-first preference was supplied. This initialization concerns audit preparation and documentation; it does not set a workflow default for future landing-page creation.
