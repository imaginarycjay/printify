# Functional Requirements

This section formalizes the functional requirements of the platform. The behavioral specifications defining the Description, Input, Process, and Output (D-IPO) for each functional requirement are itemized below:

1. **User Account Registration**
   * **Description:** Provides self-service client account registration for customers wishing to transact through the platform.
   * **Input:** Customer full name, valid email address, mobile phone number, and a secure password with confirmation.
   * **Process:** Validates form syntax and email uniqueness, hashes the password securely using bcrypt, initializes a user record assigned with the default customer role, and persists the entity in the database.
   * **Output:** Account creation confirmation alert and automatic redirection to the authentication interface.

2. **User Authentication and Profile Management**
   * **Description:** Authenticates registered user credentials, enforces role-based access control (RBAC), and allows users to manage their personal credentials and profile details.
   * **Input:** Registered email address, password, and optional profile attribute updates (contact details, password changes).
   * **Process:** Cross-references submitted credentials against hashed database records, initializes an authenticated user session, resolves role privileges, redirects the actor to their designated dashboard (Owner, Staff, or Customer), and validates profile modifications.
   * **Output:** Authenticated user session, role-restricted dashboard access, and profile update status notifications.

3. **Session Termination and Logout**
   * **Description:** Securely invalidates active authentication tokens and terminates user sessions to prevent unauthorized device access.
   * **Input:** Logout command trigger from the user navigation interface.
   * **Process:** Flushes authenticated session storage, invalidates security tokens, regenerates the CSRF token, and destroys cookie references.
   * **Output:** Redirection to the public landing page accompanied by a session termination confirmation.

4. **Service Catalog Browsing and Quotation Calculation**
   * **Description:** Enables customers to explore active printing services and calculate real-time itemized price quotations based on selected operational parameters.
   * **Input:** Selected print service category, document dimensions, page counts, color mode (monochrome/color), paper stock, binding finishing, and copy volume.
   * **Process:** Interrogates database pricing formulas in real time, applies dynamic rate multipliers via reactive Livewire component state, and computes cost subtotals without reloading the page.
   * **Output:** Dynamic, itemized quotation breakdown reflecting base costs, per-page rates, finishing surcharges, and estimated totals.

5. **Print Order Placement and Document Upload**
   * **Description:** Captures finalized customer print requests and facilitates digital document or artwork file submission for prepress inspection.
   * **Input:** Customer order details, fulfillment mode preference (pickup/counter handoff), optional production notes, and digital document files (PDF, DOCX, or high-resolution images).
   * **Process:** Validates file MIME types and size constraints, transfers uploaded files to an encrypted private storage disk with tokenized filenames, writes the parent order record to the database, and generates a unique tracking code (e.g., ORD-2026-001).
   * **Output:** Instantiated pending order record, generated tracking code, and visual order submission confirmation.

6. **Print Attribute and Finishing Specification**
   * **Description:** Mandates the capture and technical validation of detailed print configuration attributes required to fulfill an active order.
   * **Input:** Paper dimensions (Short, A4, Long), paper thickness (70 gsm, 80 gsm, 100 gsm), print orientation (simplex single-sided or duplex back-to-back), and binding finishes (spiral coil, sliding cover, softbound, or hardbound foil stamping).
   * **Process:** Encapsulates submitted attributes into a structured line-item specification record, verifies technical feasibility (e.g., maximum page limits for specific binding styles), and calculates exact physical sheet requirements (ceil(pages / 2) * copies for duplex).
   * **Output:** Validated line-item specification entity inextricably linked to the parent order.

7. **Customer-Supplied Substrate Declaration ("Dala ang Papel")**
   * **Description:** Conditionally extends the order placement workflow when a client elects to supply their own physical paper, adjusting financial calculations and tagging production requirements.
   * **Input:** Customer-supplied substrate toggle activation, provided paper description, and declared sheet count.
   * **Process:** Subtracts shop raw paper material charges from the running price quotation, designates line-item fulfillment as cover-only, and appends an "Awaiting Paper Delivery" intake flag to the digital job ticket.
   * **Output:** Discounted order total and an intake-tagged digital job ticket for workshop counter tracking.

8. **Proof of Payment Submission**
   * **Description:** Enables customers to submit manual payment verification references for counter cash transactions or mobile wallet remittances (GCash / Maya).
   * **Input:** Selected payment channel, alphanumeric transaction reference number, and a graphic screenshot of the remittance receipt.
   * **Process:** Validates image file structure, attaches the proof payload to the pending order record, flags order payment status as pending verification, and alerts shop staff of pending billing audits.
   * **Output:** Payment submission acknowledgment and updated pending verification status badge on the client portal.

9. **Live Order Progress and Status Tracking**
   * **Description:** Provides transparent, real-time telemetry displaying the operational progression of customer orders across workshop milestones.
   * **Input:** Customer Order ID, tracking code, or authenticated client dashboard session.
   * **Process:** Queries active production status flags in the database and renders an illuminated, color-coded 5-stage visual stepper reflecting milestone progression and stage completion timestamps.
   * **Output:** Real-time visual progress stepper indicating current order stage (Queue, Printing, Finishing, QC, Ready for Pickup) and estimated fulfillment date.

10. **Payment Transaction Auditing and Verification**
    * **Description:** Empowers production staff and business administrators to audit customer-submitted payment proofs against billing ledgers and update order financial states.
    * **Input:** Staff audit decision (Approve or Reject), cross-referenced reference numbers, and optional rejection remarks.
    * **Process:** Verifies transaction validity against shop financial ledgers, transitions payment status to verified paid (or rejected), records the verifying staff ID and verification timestamp, and transitions the order into the active production queue.
    * **Output:** Updated order financial status and automated payment confirmation notification broadcast to the customer dashboard.

11. **Production Floor 5-Stage Kanban Queue Management**
    * **Description:** Organizes workshop production orders into an interactive visual 5-stage Kanban board to direct job scheduling, operator delegation, and machine allocation.
    * **Input:** Assigned staff operator ID, assigned printing machine designation, stage progression triggers, and optional quality control (QC) rework remarks.
    * **Process:** Moves active orders across five sequential workshop stages (1. In Queue -> 2. Printing Pages -> 3. Finishing & Assembly -> 4. Quality Inspection -> 5. Ready for Pickup), logs transition timestamps, synchronizes customer tracking steppers, or executes step-back transitions upon QC failure.
    * **Output:** Interactive visual Kanban board, updated digital job ticket states, and synchronized customer progress updates.

12. **Automated Bill of Materials (BOM) Inventory Deduction**
    * **Description:** Automatically decrements raw material inventories based on linked service BOM recipes as print jobs advance into production or pickup stages.
    * **Input:** Active order line-item parameters (page count, paper dimensions, duplex flags, binding consumables, copy quantity) and stage advance trigger.
    * **Process:** Evaluates item fulfillment status; if designated as customer-supplied paper, raw paper sheet deduction is completely bypassed. For standard orders, computes exact consumption based on duplex math (ceil(pages / 2) * copies) and finishing multipliers, decrements physical inventory stock balances, and records immutable audit entries in the stock movement ledger.
    * **Output:** Decremented physical stock balances and recorded inventory transaction audit trails.

13. **Customer-Supplied Substrate Counter Inspection**
    * **Description:** Conditionally extends the production queue workflow when an order contains customer-supplied paper, requiring staff to inspect physical stock before machine processing begins.
    * **Input:** Physical paper bundle presented at the counter, physical sheet count verification, grammage/condition inspection, and staff verification confirmation.
    * **Process:** Cross-references physical stock against digital job ticket parameters; upon staff confirmation, updates order state to paper received, logs the receiving staff ID, and releases the job ticket from intake hold into the active printing queue.
    * **Output:** Verified substrate receipt status on the job ticket and release into active machine production.

14. **Material Spoilage Logging and Stock Adjustments**
    * **Description:** Allows production operators to record physical material waste and accidental damage resulting from printer jams, paper misfeeds, or binding errors.
    * **Input:** Damaged inventory item selection, wasted quantity, and specific operational failure explanation.
    * **Process:** Validates numeric quantity limits, decrements the item's on-hand stock balance, appends an audit entry to the stock movement ledger tagged as spoilage, records the reporting operator's ID, and checks remaining stock against reorder thresholds.
    * **Output:** Adjusted on-hand inventory levels, recorded waste audit logs, and dynamic replenishment threshold evaluation.

15. **Dynamic Service and Rate Configuration**
    * **Description:** Allows business owners to dynamically define, modify, or retire service offerings, pricing structures, and rate tariffs without altering application source code.
    * **Input:** Service key, descriptive name, active status toggle, base setup fee, monochrome/color per-page rates, paper profile limits, rush fee multipliers, and finishing add-on prices.
    * **Process:** Validates input parameters against numeric boundaries, writes updated configuration records to service entities and JSON attribute schemas, clears cached application pricing models, and exposes new configurations to the client ordering interface.
    * **Output:** Updated service catalog, active pricing formulas, and instant administrative configuration confirmation.

16. **Bill of Materials (BOM) Recipe Definition**
    * **Description:** Mandates the relational mapping between dynamic shop services and physical inventory stock items to govern automated material depletion formulas.
    * **Input:** Configured service identifier, linked inventory item IDs, applicable variant bindings (hardbound, softbound, spiral comb, sliding folder), and unit consumption coefficients.
    * **Process:** Enforces relational integrity across services and inventory items, stores recipe mappings in configuration tables, and validates formula mathematical logic.
    * **Output:** Relational Bill of Materials recipe linking dynamic services to automated inventory consumption.

17. **Inventory Oversight and Dynamic Reorder Point (ROP) Alerting**
    * **Description:** Monitors physical stock balances, computes rolling consumption velocity, and triggers visual replenishment warnings when supplies reach critical levels.
    * **Input:** Historical stock movement logs, rolling daily consumption data, supplier lead times, and baseline safety stock levels.
    * **Process:** Computes the daily material burn rate (Burn Rate = Total Material Consumed / Time Period in Days), derives the dynamic Reorder Point threshold (ROP = (Daily Burn Rate * Lead Time) + Safety Stock), evaluates current stock against the threshold, and flags items requiring replenishment.
    * **Output:** Real-time stock health summaries, visual low-stock badges on the Inventory Hub, and automated restocking banners on the Owner Master Dashboard.

18. **Sales, Cashflow, and Operational Analytics Generation**
    * **Description:** Aggregates transaction data, service volume, material expenses, and revenues to generate interactive business intelligence and exportable reports.
    * **Input:** Date range selection parameters, service category filters, and report export commands.
    * **Process:** Aggregates database transactions across orders, computes gross revenues, net margins, material consumption expenses, spoilage costs, and rush revenue, rendering visual trend charts and formatted tabular ledgers.
    * **Output:** Interactive visual charts, service product mix reports, and exportable financial audit ledgers.
