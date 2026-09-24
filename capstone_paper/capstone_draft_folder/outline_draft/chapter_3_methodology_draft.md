# CHAPTER 3: METHODOLOGY MY PART ONLY
This chapter presents the methodology used in conducting the study. It describes the research design, the participants involved, data collection procedures, system development approach, and evaluation methods applied in developing and assessing the proposed system. It also outlines the procedures followed by the researchers to ensure that the system meets its objectives and satisfies the operational needs of the printing shop.

### Project Design
This study adopts a Developmental Research Design, which focuses on the design, development, implementation, and evaluation of an information system to solve a practical problem. This design is appropriate because the primary goal of the study is to create an integrated web-based system that combines dynamic order management, job scheduling, inventory management, and automated replenishment for printing services. 

Through this design, the researchers systematically identify operational requirements, design and build the system modules, test for functional errors, and evaluate the platform using the ISO 25010 software quality standard to ensure its practical usability and effectiveness.

### Project Participants and Materials
The project participants include the business owners, production staff, and customers of the printing-service establishment. The business owners provide essential information regarding shop operations, printing services, pricing rules, and inventory management, while evaluating the administrative tools of the system. The production staff provide details on actual shop workflows, daily tasks, machinery use, and material consumption, and test the job scheduling and production tracking features. The customers, including students and local clients, share feedback on the ordering process and order tracking to help assess the usability and convenience of the customer portal.

The materials used in developing the system include computers and laptops for programming, mobile phones for testing responsiveness, a stable internet connection, and development tools such as Visual Studio Code, database servers, and web development frameworks. In addition, existing shop records, such as sample receipts, job orders, and price lists, serve as reference materials to ensure that the system reflects the actual daily transactions of the printing business.

### Data Collection
The researchers will gather data using interviews, direct workplace observations, document reviews, and survey questionnaires to understand the operational requirements of the printing business.

First, semi-structured interviews will be conducted with the business owners and production staff to identify current operational challenges, service pricing rules, job scheduling workflows, and inventory restocking habits. Second, direct observations of daily shop activities will be carried out to observe counter order intake, task delegation across workstations, and material handling on the workshop floor. Third, the researchers will examine existing business documents, including sample receipts, manual job order slips, supplier invoices, and price lists, to accurately model pricing formulas and material consumption in the system. Finally, survey questionnaires based on the ISO 25010 software quality standard will be distributed to end-users (owners, staff, and customers) and IT experts during the evaluation phase to assess the system's performance, usability, and functional quality.

### Data Analysis Procedure
The data gathered from interviews and observations will be organized and analyzed qualitatively to determine the core functional requirements and workflow design of the system. 

For the system evaluation, responses collected from the evaluation questionnaires will be analyzed quantitatively using descriptive statistics, specifically frequency counts, percentage distributions, and the weighted mean. The computed weighted means for the ISO 25010 criteria—functional suitability, usability, performance efficiency, and security—will be interpreted using the four-point Likert scale shown in Table 1.

**Table 1. Likert Scale for Evaluating System Quality (ISO 25010 Standard)**

| Scale / Value | Range of Mean | Descriptive Rating | Interpretation |
| :---: | :---: | :--- | :--- |
| **4** | 3.50 – 4.00 | Strongly Agree (SA) | Excellent / Highly Acceptable |
| **3** | 2.50 – 3.49 | Agree (A) | Good / Acceptable |
| **2** | 1.50 – 2.49 | Disagree (D) | Fair / Needs Improvement |
| **1** | 1.00 – 1.49 | Strongly Disagree (SD) | Poor / Unacceptable |

### System Requirement Specifications

This section defines the functional and technical requirements of the Integrated Dynamic Order, Job Scheduling, and Inventory Management System. It outlines the specific behavioral functions of the platform alongside the minimum and recommended software and hardware environments required for development, hosting, and end-user access.

#### Functional Requirements

This section formalizes the functional requirements of the platform. To maintain strict architectural alignment with the behavioral models of the system, each functional requirement directly maps to an atomic use case identified in the Use Case Diagram (Figure 5), incorporating both base operations and stereotype dependencies (`<<include>>` and `<<extend>>`).

**Table 2. Functional Requirements to Use Case Traceability Matrix**

| Requirement ID | Associated Use Case ID | Functional Requirement Title | Primary Actor(s) | UML Relationship Dependency |
| :---: | :---: | :--- | :--- | :--- |
| **FR-01** | **UC-02** | User Account Registration | Customer | Base Use Case |
| **FR-02** | **UC-01** | User Authentication and Profile Management | Owner, Staff, Customer | Base Use Case |
| **FR-03** | **UC-18** | Session Termination and Logout | Owner, Staff, Customer | Base Use Case |
| **FR-04** | **UC-03** | Service Catalog Browsing and Quotation Calculation | Customer | Base Use Case |
| **FR-05** | **UC-04** | Print Order Placement and Document Upload | Customer | Base Use Case |
| **FR-06** | **UC-05** | Print Attribute and Finishing Specification | Customer | `<<include>>` (Included by UC-04) |
| **FR-07** | **UC-06** | Customer-Supplied Substrate Declaration ("Dala ang Papel") | Customer | `<<extend>>` (Extends UC-04) |
| **FR-08** | **UC-07** | Proof of Payment Submission | Customer | Base Use Case |
| **FR-09** | **UC-08** | Live Order Progress and Status Tracking | Customer | Base Use Case |
| **FR-10** | **UC-09** | Payment Transaction Auditing and Verification | Production Staff | Base Use Case |
| **FR-11** | **UC-10** | Production Floor 5-Stage Kanban Queue Management | Production Staff | Base Use Case |
| **FR-12** | **UC-11** | Automated Bill of Materials (BOM) Inventory Deduction | Production Staff / System | `<<include>>` (Included by UC-10) |
| **FR-13** | **UC-12** | Customer-Supplied Substrate Counter Inspection | Production Staff | `<<extend>>` (Extends UC-10) |
| **FR-14** | **UC-13** | Material Spoilage Logging and Stock Adjustments | Production Staff | Base Use Case |
| **FR-15** | **UC-14** | Dynamic Service and Rate Configuration | Business Owner | Base Use Case |
| **FR-16** | **UC-15** | Bill of Materials (BOM) Recipe Definition | Business Owner | `<<include>>` (Included by UC-14) |
| **FR-17** | **UC-16** | Inventory Oversight and Dynamic Reorder Point (ROP) Alerting | Business Owner | Base Use Case |
| **FR-18** | **UC-17** | Sales, Cashflow, and Operational Analytics Generation | Business Owner | Base Use Case |

The detailed behavioral specifications defining the Input, Process, and Output (IPO) for each functional requirement are itemized below:

1. **User Account Registration**
   * **Use Case Mapping:** UC-02 (Base Use Case)
   * **Description:** Provides self-service client account registration for customers wishing to transact through the platform.
   * **Input:** Customer full name, valid email address, mobile phone number, and a secure password with confirmation.
   * **Process:** Validates form syntax and email uniqueness, hashes the password securely using bcrypt, initializes a user record assigned with the default customer role, and persists the entity in the database.
   * **Output:** Account creation confirmation alert and automatic redirection to the authentication interface.

2. **User Authentication and Profile Management**
   * **Use Case Mapping:** UC-01 (Base Use Case)
   * **Description:** Authenticates registered user credentials, enforces role-based access control (RBAC), and allows users to manage their personal credentials and profile details.
   * **Input:** Registered email address, password, and optional profile attribute updates (contact details, password changes).
   * **Process:** Cross-references submitted credentials against hashed database records, initializes an authenticated user session, resolves role privileges, redirects the actor to their designated dashboard (Owner, Staff, or Customer), and validates profile modifications.
   * **Output:** Authenticated user session, role-restricted dashboard access, and profile update status notifications.

3. **Session Termination and Logout**
   * **Use Case Mapping:** UC-18 (Base Use Case)
   * **Description:** Securely invalidates active authentication tokens and terminates user sessions to prevent unauthorized device access.
   * **Input:** Logout command trigger from the user navigation interface.
   * **Process:** Flushes authenticated session storage, invalidates security tokens, regenerates the CSRF token, and destroys cookie references.
   * **Output:** Redirection to the public landing page accompanied by a session termination confirmation.

4. **Service Catalog Browsing and Quotation Calculation**
   * **Use Case Mapping:** UC-03 (Base Use Case)
   * **Description:** Enables customers to explore active printing services and calculate real-time itemized price quotations based on selected operational parameters.
   * **Input:** Selected print service category, document dimensions, page counts, color mode (monochrome/color), paper stock, binding finishing, and copy volume.
   * **Process:** Interrogates database pricing formulas in real time, applies dynamic rate multipliers via reactive Livewire component state, and computes cost subtotals without reloading the page.
   * **Output:** Dynamic, itemized quotation breakdown reflecting base costs, per-page rates, finishing surcharges, and estimated totals.

5. **Print Order Placement and Document Upload**
   * **Use Case Mapping:** UC-04 (Base Use Case)
   * **Description:** Captures finalized customer print requests and facilitates digital document or artwork file submission for prepress inspection.
   * **Input:** Customer order details, fulfillment mode preference (pickup/counter handoff), optional production notes, and digital document files (PDF, DOCX, or high-resolution images).
   * **Process:** Validates file MIME types and size constraints, transfers uploaded files to an encrypted private storage disk with tokenized filenames, writes the parent order record to the database, and generates a unique tracking code (e.g., `ORD-2026-001`).
   * **Output:** Instantiated pending order record, generated tracking code, and visual order submission confirmation.

6. **Print Attribute and Finishing Specification**
   * **Use Case Mapping:** UC-05 (`<<include>>` dependency of UC-04)
   * **Description:** Mandates the capture and technical validation of detailed print configuration attributes required to fulfill an active order.
   * **Input:** Paper dimensions (Short, A4, Long), paper thickness (70 gsm, 80 gsm, 100 gsm), print orientation (simplex single-sided or duplex back-to-back), and binding finishes (spiral coil, sliding cover, softbound, or hardbound foil stamping).
   * **Process:** Encapsulates submitted attributes into a structured line-item specification record, verifies technical feasibility (e.g., maximum page limits for specific binding styles), and calculates exact physical sheet requirements ($\lceil \text{pages}/2 \rceil \times \text{copies}$ for duplex).
   * **Output:** Validated `order_items` specification entity inextricably linked to the parent order.

7. **Customer-Supplied Substrate Declaration ("Dala ang Papel")**
   * **Use Case Mapping:** UC-06 (`<<extend>>` dependency of UC-04)
   * **Description:** Conditionally extends the order placement workflow when a client elects to supply their own physical paper, adjusting financial calculations and tagging production requirements.
   * **Input:** Customer-supplied substrate toggle activation, provided paper description, and declared sheet count.
   * **Process:** Subtracts shop raw paper material charges from the running price quotation, designates line-item fulfillment as `cover_only`, and appends an "Awaiting Paper Delivery" intake flag to the digital job ticket.
   * **Output:** Discounted order total and an intake-tagged digital job ticket for workshop counter tracking.

8. **Proof of Payment Submission**
   * **Use Case Mapping:** UC-07 (Base Use Case)
   * **Description:** Enables customers to submit manual payment verification references for counter cash transactions or mobile wallet remittances (GCash / Maya).
   * **Input:** Selected payment channel, alphanumeric transaction reference number, and a graphic screenshot of the remittance receipt.
   * **Process:** Validates image file structure, attaches the proof payload to the pending order record, flags order payment status as `pending_verification`, and alerts shop staff of pending billing audits.
   * **Output:** Payment submission acknowledgment and updated pending verification status badge on the client portal.

9. **Live Order Progress and Status Tracking**
   * **Use Case Mapping:** UC-08 (Base Use Case)
   * **Description:** Provides transparent, real-time telemetry displaying the operational progression of customer orders across workshop milestones.
   * **Input:** Customer Order ID, tracking code, or authenticated client dashboard session.
   * **Process:** Queries active production status flags in the database and renders an illuminated, color-coded 5-stage visual stepper reflecting milestone progression and stage completion timestamps.
   * **Output:** Real-time visual progress stepper indicating current order stage (Queue, Printing, Finishing, QC, Ready for Pickup) and estimated fulfillment date.

10. **Payment Transaction Auditing and Verification**
    * **Use Case Mapping:** UC-09 (Base Use Case)
    * **Description:** Empowers production staff and business administrators to audit customer-submitted payment proofs against billing ledgers and update order financial states.
    * **Input:** Staff audit decision (Approve or Reject), cross-referenced reference numbers, and optional rejection remarks.
    * **Process:** Verifies transaction validity against shop financial ledgers, transitions `payment_status` to `verified_paid` (or `rejected`), records the verifying staff ID and verification timestamp, and transitions the order into the active production queue.
    * **Output:** Updated order financial status and automated payment confirmation notification broadcast to the customer dashboard.

11. **Production Floor 5-Stage Kanban Queue Management**
    * **Use Case Mapping:** UC-10 (Base Use Case)
    * **Description:** Organizes workshop production orders into an interactive visual 5-stage Kanban board to direct job scheduling, operator delegation, and machine allocation.
    * **Input:** Assigned staff operator ID, assigned printing machine designation, stage progression triggers, and optional quality control (QC) rework remarks.
    * **Process:** Moves active orders across five sequential workshop stages (1. In Queue $\rightarrow$ 2. Printing Pages $\rightarrow$ 3. Finishing & Assembly $\rightarrow$ 4. Quality Inspection $\rightarrow$ 5. Ready for Pickup), logs transition timestamps, synchronizes customer tracking steppers, or executes step-back transitions upon QC failure.
    * **Output:** Interactive visual Kanban board, updated digital job ticket states, and synchronized customer progress updates.

12. **Automated Bill of Materials (BOM) Inventory Deduction**
    * **Use Case Mapping:** UC-11 (`<<include>>` dependency of UC-10)
    * **Description:** Automatically decrements raw material inventories based on linked service BOM recipes as print jobs advance into production or pickup stages.
    * **Input:** Active order line-item parameters (page count, paper dimensions, duplex flags, binding consumables, copy quantity) and stage advance trigger.
    * **Process:** Evaluates item fulfillment status; if designated as "Dala ang Papel", raw paper sheet deduction is completely bypassed. For standard orders, computes exact consumption based on duplex math ($\lceil \text{pages}/2 \rceil \times \text{copies}$) and finishing multipliers, decrements `inventory_items.current_stock`, and records immutable audit entries in `stock_movements`.
    * **Output:** Decremented physical stock balances and recorded inventory transaction audit trails.

13. **Customer-Supplied Substrate Counter Inspection**
    * **Use Case Mapping:** UC-12 (`<<extend>>` dependency of UC-10)
    * **Description:** Conditionally extends the production queue workflow when an order contains customer-supplied paper, requiring staff to inspect physical stock before machine processing begins.
    * **Input:** Physical paper bundle presented at the counter, physical sheet count verification, grammage/condition inspection, and "Mark Paper Received" staff confirmation.
    * **Process:** Cross-references physical stock against digital job ticket parameters; upon staff confirmation, updates order state to `is_paper_received = true`, logs the receiving staff ID, and releases the job ticket from intake hold into the active printing queue.
    * **Output:** Verified substrate receipt status on the job ticket and release into active machine production.

14. **Material Spoilage Logging and Stock Adjustments**
    * **Use Case Mapping:** UC-13 (Base Use Case)
    * **Description:** Allows production operators to record physical material waste and accidental damage resulting from printer jams, paper misfeeds, or binding errors.
    * **Input:** Damaged inventory item selection, wasted quantity, and specific operational failure explanation.
    * **Process:** Validates numeric quantity limits, decrements the item's on-hand stock balance, appends an audit entry to `stock_movements` tagged as `spoilage`, records the reporting operator's ID, and checks remaining stock against reorder thresholds.
    * **Output:** Adjusted on-hand inventory levels, recorded waste audit logs, and dynamic replenishment threshold evaluation.

15. **Dynamic Service and Rate Configuration**
    * **Use Case Mapping:** UC-14 (Base Use Case)
    * **Description:** Allows business owners to dynamically define, modify, or retire service offerings, pricing structures, and rate tariffs without altering application source code.
    * **Input:** Service key, descriptive name, active status toggle, base setup fee, monochrome/color per-page rates, paper profile limits, rush fee multipliers, and finishing add-on prices.
    * **Process:** Validates input parameters against numeric boundaries, writes updated configuration records to `shop_services` and JSON attribute schemas, clears cached application pricing models, and exposes new configurations to the client ordering interface.
    * **Output:** Updated service catalog, active pricing formulas, and instant administrative configuration confirmation.

16. **Bill of Materials (BOM) Recipe Definition**
    * **Use Case Mapping:** UC-15 (`<<include>>` dependency of UC-14)
    * **Description:** Mandates the relational mapping between dynamic shop services and physical inventory stock items to govern automated material depletion formulas.
    * **Input:** Configured service identifier, linked inventory item IDs, applicable variant bindings (hardbound, softbound, spiral comb, sliding folder), and unit consumption coefficients.
    * **Process:** Enforces relational integrity across services and inventory items, stores recipe mappings in `service_boms` and configuration tables, and validates formula mathematical logic.
    * **Output:** Relational Bill of Materials (BOM) recipe linking dynamic services to automated inventory consumption.

17. **Inventory Oversight and Dynamic Reorder Point (ROP) Alerting**
    * **Use Case Mapping:** UC-16 (Base Use Case)
    * **Description:** Monitors physical stock balances, computes rolling consumption velocity, and triggers visual replenishment warnings when supplies reach critical levels.
    * **Input:** Historical stock movement logs, rolling daily consumption data, supplier lead times, and baseline safety stock levels.
    * **Process:** Computes the daily material burn rate ($\text{Burn Rate} = \frac{\text{Total Material Consumed}}{\text{Time Period in Days}}$), derives the dynamic Reorder Point threshold ($\text{ROP} = (\text{Daily Burn Rate} \times \text{Lead Time}) + \text{Safety Stock}$), evaluates current stock against the threshold, and flags items requiring replenishment.
    * **Output:** Real-time stock health summaries, visual low-stock badges on the Inventory Hub, and automated restocking banners on the Owner Master Dashboard.

18. **Sales, Cashflow, and Operational Analytics Generation**
    * **Use Case Mapping:** UC-17 (Base Use Case)
    * **Description:** Aggregates transaction data, service volume, material expenses, and revenues to generate interactive business intelligence and exportable reports.
    * **Input:** Date range selection parameters, service category filters, and CSV report export commands.
    * **Process:** Aggregates database transactions across orders, computes gross revenues, net margins, material consumption expenses, spoilage costs, and rush revenue, rendering visual trend charts and formatted tabular ledgers.
    * **Output:** Interactive visual charts, service product mix reports, and exportable financial CSV audit ledgers.

#### Software Requirements Specification

Table 2 outlines the software environment and developer toolchains utilized in designing, developing, and operating the web-based system.

**Table 2. Software Requirements Specification**

| Component | Minimum Requirement | Recommended Requirement |
| :--- | :--- | :--- |
| **Operating System** | Windows 10 / Ubuntu 20.04 LTS / macOS 12 | Windows 11 / Ubuntu 22.04 LTS / macOS 14 |
| **Web Server** | Apache 2.4 / Nginx 1.18+ / PHP Built-in Server | Nginx 1.24+ / Apache 2.4+ |
| **Database Server** | MySQL 8.0 / SQLite 3 | MySQL 8.0+ / MariaDB 10.6+ |
| **Programming Language / Runtime** | PHP 8.2 | PHP 8.3 or higher |
| **Backend Framework** | Laravel 11.x | Laravel 11.x / 13.x |
| **Frontend Reactive Stack** | Livewire 3.x / Alpine.js | Livewire 4.x / Flux UI |
| **CSS Framework & Bundler** | Tailwind CSS v3 / Vite | Tailwind CSS v4 via Vite |
| **Web Browser** | Modern Browser (Chrome, Firefox, Edge, Safari) | Latest Google Chrome / Brave / Microsoft Edge |

#### Hardware Requirements Specification

Table 3 details the minimum and recommended hardware specifications for the development workstations, hosting servers, and client access devices.

**Table 3. Hardware Requirements Specification**

| Category | Specification | Minimum Requirement | Recommended Requirement |
| :--- | :--- | :--- | :--- |
| **Development Workstation** | Processor<br>Memory (RAM)<br>Storage<br>Display | Dual-Core 2.0 GHz Processor<br>8 GB RAM<br>256 GB SSD<br>1366 × 768 Resolution | Quad-Core 3.0 GHz or higher<br>16 GB RAM or higher<br>512 GB NVMe SSD<br>1920 × 1080 Full HD Display |
| **Web Server (Hosting)** | Processor<br>Memory (RAM)<br>Storage<br>Bandwidth | 1 vCPU (Cloud VM or Dedicated)<br>1 GB RAM<br>20 GB Storage<br>10 Mbps Internet Connection | 2 vCPUs or higher<br>2 GB – 4 GB RAM<br>50 GB SSD Storage<br>50 Mbps+ High-Speed Internet |
| **Client Devices (Users)** | Device Type<br>Memory (RAM)<br>Connectivity | Any Web-Capable Device (PC, Smartphone)<br>2 GB RAM<br>Standard 3G/4G/Wi-Fi Connection | Smartphone / Laptop / Desktop PC<br>4 GB RAM or higher<br>Stable 4G/5G/Broadband Internet |

### Project Design

The project design translates the operational requirements and technical constraints into actionable architectural blueprints. This stage bridges business rules with software implementation. Structurally, it defines the normalized data models and relational constraints that synchronize incoming print orders with automated Bill of Materials (BOM) inventory deductions. Behaviorally, it outlines how business owners, production operators, and customers interact across core functional modules, tracking jobs as they advance through workshop queues from intake to final handoff. The succeeding discussions present the logical database schema through an Entity Relationship Diagram (ERD), followed by behavioral use case models and operational workflow diagrams that direct daily print shop operations.

#### Database Schema / Entity Relationship Diagram (ERD)

Figure 3 illustrates the Entity Relationship Diagram (ERD) of the system, depicting the logical entities, primary keys, and foreign key constraints across the normalized database tables. At the core of the platform is the **Users** entity, which governs authentication and role-based access control for business owners, workshop staff, and customers. A business owner manages a single enterprise instance in the **PrintShops** entity (1:1 via `owner_user_id`), which serves as the organizational tenant root for all shop-level operations. Each print shop maintains its active service catalog through the **ShopServices** entity (1:N), which dynamically encapsulates service offerings, production turnaround lead times, machine capacity quotas, and custom pricing formulas within structured JSON settings attributes.

Customer transactions are captured in the **Orders** entity (1:N from Users and PrintShops), which records the unique tracking number, transaction amounts, payment verification details, assigned staff operator, and five-stage workshop production status (*Queue, Printing, Finishing/Assembly, Quality Check, and Ready for Pickup*). Each order contains one or more line items in the **OrderItems** entity (1:N), employing a universal line-item pattern that stores unit quantities, itemized pricing, document attachment paths, and a structured `specifications` JSON object that dynamically accommodates customization parameters (e.g., paper dimensions, page counts, binding types, apparel sizes, or substrate requirements) without requiring structural database alterations for new service offerings.

Shop inventory and material resources are managed through the **InventoryItems** entity (1:N from PrintShops), which tracks stock balances, units of measurement, unit acquisition costs, and dynamic reorder point thresholds. Automated material consumption is governed by the **ServiceBoms** entity (1:N from ShopServices and N:1 to InventoryItems), a unified Bill of Materials repository that maps specific services and variant conditions to precise physical consumption recipes. All stock movements—including production deductions, supplier restocks, and workshop spoilage—are permanently recorded in the **StockMovements** entity (1:N from InventoryItems and Users, with direct foreign key traceability to Orders), establishing an immutable audit ledger that powers real-time material burn rate calculations and automated replenishment alerts.

*(Figure 3. Entity Relationship Diagram will be placed here)*

#### Use Case Diagram

Figure 4 illustrates the Use Case Diagram of the Integrated Dynamic Order, Job Scheduling, and Inventory Management System. The diagram defines the system boundary and visualizes how external actors interact with core system functions across administrative, production, and customer domains. Three primary actors govern platform activity: the Business Owner, the Production Staff, and the Customer. Access privileges are strictly segregated through role-based authentication, directing each actor to dedicated operational interfaces upon system entry.

The Customer initiates transaction lifecycles through the client-facing ordering interface. Customers browse the active service catalog, configure print specifications, and generate instant price quotations before placing orders and uploading digital artwork. The ordering workflow inherently requires configuring technical print parameters—such as paper dimensions, GSM weight, color mode, and binding options—represented through an *<<include>>* relationship. In contrast, the option to declare customer-supplied substrates (*"Dala ang Papel"*) extends the ordering workflow conditionally via an *<<extend>>* dependency, dynamically discounting service totals and bypassing raw material allocations. Following order placement, customers submit transaction reference numbers and payment proof, subsequently tracking the real-time stage progression of their print jobs through to pickup readiness.

The Production Staff and Business Owner govern floor execution and enterprise management from internal authenticated interfaces. Production operators supervise jobs across a visual five-stage Kanban board (*Queue, Printing, Finishing/Assembly, Quality Check, and Ready for Pickup*). Advancing a job across these production stages automatically triggers an *<<include>>* dependency for automated Bill of Materials (BOM) inventory deductions, decrementing physical stock levels in real time. When an order involves customer-supplied materials, staff verify the delivered paper at the shop counter through an *<<extend>>* verification procedure before machine processing begins. At the managerial level, the Business Owner oversees system configuration. Owners dynamically configure print services and their underlying BOM recipes (*<<include>>*), adjust inventory safety thresholds, track material burn rates against dynamic reorder point thresholds, and generate business analytics to evaluate financial and operational performance.

*(Figure 4. Use Case Diagram will be placed here)*

#### Class Diagram

Figure 5 illustrates the Class Diagram of the Integrated Dynamic Order, Job Scheduling, and Inventory Management System. The diagram models the static structure of the platform, defining system entities, internal state attributes, operational methods, and structural relationships. System actors are formalized through an object-oriented inheritance hierarchy rooted in the generalized **User** superclass. This superclass encapsulates shared authentication states and profile management operations, which are subsequently specialized into three distinct subclasses: **BusinessOwner**, **ProductionStaff**, and **Customer**. Each specialized subclass defines role-specific behaviors, ensuring that administrative configuration, workshop floor job handling, and client order placement remain modular and structurally isolated.

The domain model operationalizes print shop transactions and inventory replenishment through interconnected entity classes. A **BusinessOwner** manages a single **PrintShop** aggregate root (1:1), which anchors the establishment's catalog of **ShopService** offerings and physical **InventoryItem** supplies. Customers initiate transactions by placing an **Order** (1:N), which establishes a composite aggregation with one or more **OrderItem** instances to encapsulate itemized production parameters and document attachments. As production operators in the **ProductionStaff** role advance active orders across workshop stages, the system queries linked **ServiceBom** consumption recipes to calculate exact raw material usage. These material deductions directly decrement stock balances in **InventoryItem** and write immutable audit records to **StockMovement**, preserving strict inventory traceability and providing reliable telemetry for automated reorder alerts.

*(Figure 5. Class Diagram will be placed here)*





