# Comprehensive Activity Diagram Guide for Printify (1-to-1 Functional Requirements Alignment)

**Project Title:** Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment  
**Alignment Standard:** Exactly **18 Activity Diagrams** numbered and named in strict 1-to-1 order matching the **18 Functional Requirements (FR-01 to FR-18)** and **18 UML Use Cases (UC-01 to UC-18)**.  
**Layout Standard:** Left swimlane is strictly reserved for the primary Actor and process initiation (`● Start`); Right swimlane(s) handle System Engines, Validation, and Database Storage.  
**Sequential Pattern:** Academic Introductory Narrative $\longrightarrow$ Visual Swimlane Flowchart (ASCII/Box Layout) $\longrightarrow$ Activity Diagram Title Underneath.  
**Drawing Fallback:** Section 4 provides an exhaustive text-based step-by-step drawing blueprint for Draw.io and StarUML.

---

## 1. Executive Evaluation: Audit of Drafted Diagrams against the 18 Functional Requirements

In the original manuscript draft, only 9 to 10 activity diagrams were provided, which created a structural mismatch against the system requirements and use case specifications. Below is the formal audit explaining why the previous diagrams failed the adviser's requirement and how they are now systematically resolved into 18 dedicated workflows:

| FR # | Functional Requirement & Activity Diagram Name | Original Draft Status | Audit Findings & Resolution |
| :---: | :--- | :---: | :--- |
| **1** | **User Account Registration** | **FAILED** ❌ | **Defect in Draft:** Erroneously stated that registration routes newly registered users to the "Owner Dashboard, Staff Dashboard, or Customer Dashboard." In the actual system and FR-01, public self-registration is strictly for clients (default customer role). Fixed to reflect client-only self-registration. |
| **2** | **User Authentication and Profile Management** | **PASSED** ✅ | **Fidelity:** Accurately authenticates credentials and routes to role-restricted portals (Owner, Staff, Customer), including user profile updates. |
| **3** | **Session Termination and Logout** | **MISSING** ❌ | **Defect in Draft:** Was completely omitted as a separate diagram despite being an explicit requirement (FR-03 / UC-18). Now isolated into a dedicated session invalidation workflow. |
| **4** | **Service Catalog Browsing and Quotation Calculation** | **MISSING** ❌ | **Defect in Draft:** Was lumped into general ordering without explaining the reactive Livewire dynamic pricing recalculation engine. Now given a dedicated quotation workflow. |
| **5** | **Print Order Placement and Document Upload** | **FAILED** ❌ | **Defect in Draft:** Conflated payment proof upload with order creation and omitted private encrypted storage validation. Now cleanly isolated. |
| **6** | **Print Attribute and Finishing Specification** | **MISSING** ❌ | **Defect in Draft:** Omitted technical attribute validation and duplex physical sheet math ($\lceil \text{Pages}/2 \rceil \times \text{Copies}$). Now modeled in detail. |
| **7** | **Customer-Supplied Substrate Declaration ("Dala ang Papel")** | **MISSING** ❌ | **Defect in Draft:** Client-side toggle declaration was lumped with staff counter intake. Now isolated as the client checkout extension. |
| **8** | **Proof of Payment Submission** | **PASSED** ✅ | **Fidelity:** Accurately models customer payment channel selection and receipt screenshot upload. |
| **9** | **Live Order Progress and Status Tracking** | **MISSING** ❌ | **Defect in Draft:** Omitted customer-facing telemetry stepper queries. Now given a dedicated monitoring workflow. |
| **10** | **Payment Transaction Auditing and Verification** | **PASSED** ✅ | **Fidelity:** Accurately models staff cross-checking of merchant ledgers and payment approval/rejection. |
| **11** | **Production Floor 5-Stage Kanban Queue Management** | **FAILED** ❌ | **Defect in Draft:** Cites six (6) stages with an erroneous "Completed" stage. The implemented system strictly operates across **five (5) active columns** (`Queue`, `Printing`, `Finishing/Assembly`, `Quality Inspection`, `Ready for Pickup`). Corrected to 5 stages with quality-check step-back loops. |
| **12** | **Automated Bill of Materials (BOM) Inventory Deduction** | **FAILED** ❌ | **Defect in Draft:** Omitted the duplex sheet formula and the "Dala ang Papel" raw paper bypass rule. Both computational rules are now fully modeled. |
| **13** | **Customer-Supplied Substrate Counter Inspection** | **PASSED** ✅ | **Fidelity:** Correctly models counter paper counting, grammage inspection, and releasing orders from intake hold. |
| **14** | **Material Spoilage Logging and Stock Adjustments** | **PASSED** ✅ | **Fidelity:** Accurately models floor waste reporting and ledger updates. |
| **15** | **Dynamic Service and Rate Configuration** | **PASSED** ✅ | **Fidelity:** Accurately illustrates administrative setup of base charges, page rates, and finishing surcharges. |
| **16** | **Bill of Materials (BOM) Recipe Definition** | **MISSING** ❌ | **Defect in Draft:** Was lumped into general service setup without separating the relational mapping between services and inventory items. Now given a dedicated recipe modeling workflow. |
| **17** | **Inventory Oversight and Dynamic Reorder Point (ROP) Alerting** | **MISSING** ❌ | **Defect in Draft:** Burn rate calculations and automated restocking alert generation were not illustrated as an independent oversight workflow. Now fully modeled. |
| **18** | **Sales, Cashflow, and Operational Analytics Generation** | **PASSED** ✅ | **Fidelity:** Accurately models period filtering, multi-factor revenue/cost aggregation, and CSV export. |

---

## 2. Standardized Activity Diagram Guides (Workflows 1 to 18)

---

### 1. Activity Diagram for User Account Registration (FR-01 / UC-02)

#### Narrative Paragraph:
The activity diagram for User Account Registration models the self-service client onboarding workflow. A prospective customer initiates the process by accessing the registration interface and submitting their full name, valid email address, mobile phone number, and a secure password with confirmation. The authentication subsystem performs syntactic validation and checks the database to verify email uniqueness. If validation fails or a duplicate email is detected, the system displays inline error alerts, returning the client to the form for correction. Upon successful validation, the backend hashes the password using the bcrypt cryptographic algorithm, initializes a user record assigned strictly with the default customer role, and persists the entity in the database. The workflow concludes by returning an account creation confirmation alert and automatically redirecting the customer to the authentication portal.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: AUTH ENGINE & DB (RIGHT)     |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Enter Full Name, Email,                 |                                          |
|  Mobile No., & Password]                 |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate Form Syntax &                  |
|                                          |  Check Email Uniqueness]                 |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Email Unique & Valid? >               |
|                                          |     │                 │                  |
| [Review Form & Correct Inputs] <─────────┼─────┘ [No]            │ [Yes]            |
|     │                                    |                       ▼                  |
|     └───────────────────────────────────>│ [Hash Password via Bcrypt Algorithm]     |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Assign Default Role: 'Customer']        |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [(Save Record to 'users' Table)]         |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Redirect to Login Portal] <─────────────┼─────┘ [Display Confirmation Toast]       |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Account Created)                |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 1: User Account Registration**

---

### 2. Activity Diagram for User Authentication and Profile Management (FR-02 / UC-01)

#### Narrative Paragraph:
The activity diagram for User Authentication and Profile Management illustrates the operational flow governing credential verification, role-based redirection, and personal account maintenance. A user initiates the workflow by providing their registered email address and password into the login interface. The authentication guard cross-references the credentials against bcrypt password hashes in the database. If authentication fails, the system records the invalid attempt, updates rate-limiting counters, and returns an error alert. If verified, the system initializes an authenticated session and evaluates the user's role: business owners are routed to the Owner Hub, production staff to the Floor Operations Console, and customers to the Customer Dashboard. While authenticated, the user may access profile settings to modify contact details or update passwords, which are validated, hashed, and updated in the database.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: USER - ALL ROLES (LEFT)      | SWIMLANE 2: AUTH GUARD & DB (RIGHT)      |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Enter Registered Email & Password]      |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Cross-Reference Password Hash in DB]    |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Credentials Valid? >                  |
|                                          |     │                 │                  |
| [View Error & Re-enter Credentials] <────┼─────┘ [No]            │ [Yes]            |
|     │                                    |                       ▼                  |
|     └───────────────────────────────────>│ [Initialize Session & Security Token]   |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Evaluate User Role >                  |
|                                          |     │            │             │         |
|                                          |  [Owner]      [Staff]     [Customer]     |
|                                          |     │            │             │         |
| [Access Owner Master Hub] <──────────────┼─────┘            │             │         |
| [Access Staff Production Console] <──────┼──────────────────┘             │         |
| [Access Customer Order Portal] <─────────┼────────────────────────────────┘         |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Optional: Update Profile Information]   |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate & Persist Updates in 'users']  |
|                                          |     │                                    |
| [View Profile Updated Toast] <───────────┼─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Authenticated & Managed)        |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 2: User Authentication and Profile Management**

---

### 3. Activity Diagram for Session Termination and Logout (FR-03 / UC-18)

#### Narrative Paragraph:
The activity diagram for Session Termination and Logout details the security procedure for terminating authenticated sessions and invalidating active authorization tokens. An authenticated user (Owner, Staff, or Customer) initiates the sequence by selecting the logout command from the navigation bar. The application session controller intercepts the request, clears all session variables from server-side memory, invalidates active CSRF security tokens, and destroys authentication cookies on the client browser. Once session data is flushed, the platform revokes access to protected routes and redirects the actor to the public landing page, mitigating unauthorized physical workstation access.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: AUTHENTICATED USER (LEFT)    | SWIMLANE 2: SESSION CONTROLLER (RIGHT)   |
+------------------------------------------+------------------------------------------+
|  (● Start: User Authenticated)           |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Click 'Log Out' in User Navigation]     |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Flush Active Session Data from Memory]  |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Regenerate CSRF Security Token]         |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Destroy Client Authentication Cookies]  |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Redirect to Public Landing Page] <──────┼─────┘ [Invalidate Authenticated Routes]  |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Session Terminated)             |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 3: Session Termination and Logout**

---

### 4. Activity Diagram for Service Catalog Browsing and Quotation Calculation (FR-04 / UC-03)

#### Narrative Paragraph:
The activity diagram for Service Catalog Browsing and Quotation Calculation depicts the interactive estimation process on the customer storefront. The client navigates through available service offerings (e.g., document printing, thesis binding, merchandise) and adjusts operational parameters, including page quantities, color profiles (monochrome or full color), paper grammage, and copy volume. The reactive Livewire component intercepts each parameter change without triggering a manual browser reload. The engine interrogates database pricing formulas, computes unit sheet rates, incorporates finishing surcharges, and renders an illuminated, itemized cost subtotal in real time, providing transparent pricing before order commitment.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: LIVEWIRE PRICING ENGINE (RIGHT)
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Browse Active Service Catalog]          |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Service & Alter Parameters:      |                                          |
|  Page Count, Color Mode, & Copies]       |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Fetch Base Rates & Price Formulas in DB]|
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Execute Real-Time Price Computation]    |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [View Live Itemized Quotation Breakdown] <┼────┘ [Re-render Subtotal via Livewire]  |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Quotation Derived)              |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 4: Service Catalog Browsing and Quotation Calculation**

---

### 5. Activity Diagram for Print Order Placement and Document Upload (FR-05 / UC-04)

#### Narrative Paragraph:
The activity diagram for Print Order Placement and Document Upload illustrates the formalization of customer orders and prepress file ingestion. After finalizing service selections, the customer selects their fulfillment preference (pickup or counter handoff), inputs production instructions, and uploads digital artwork files (PDF, DOCX, PNG). The server validates uploaded files against permitted MIME types and size constraints. If validation fails, an error alert is returned. Once validated, the system moves the file to an encrypted private storage disk under a tokenized filename, instantiates the order in the database, generates a human-readable tracking identifier (e.g., ORD-2026-001), and renders an order confirmation screen.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: ORDER ENGINE & DISK (RIGHT)  |
+------------------------------------------+------------------------------------------+
|  (● Start: Finalized Parameters)         |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Fulfillment Mode & Upload Files] |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate File MIME Type & File Size]    |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < File Constraints Valid? >             |
|                                          |     │                 │                  |
| [View Upload Error Alert] <──────────────┼─────┘ [No]            │ [Yes]            |
|     │                                    |                       ▼                  |
| [Click 'Confirm & Place Order'] ────────>│ [Store File on Disk with Tokenized Name] |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [(Write Record to 'orders' Table)]       |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Receive Tracking Code: ORD-2026-X] <────┼─────┘ [Generate Unique Tracking Code]    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Order Placed)                   |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 5: Print Order Placement and Document Upload**

---

### 6. Activity Diagram for Print Attribute and Finishing Specification (FR-06 / UC-05)

#### Narrative Paragraph:
The activity diagram for Print Attribute and Finishing Specification models the structural parameterization of custom print jobs. The customer configures exact technical attributes: paper dimensions (Short, A4, Long), paper thickness (70, 80, 100 gsm), print orientation (simplex single-sided or duplex double-sided), and binding finishes (plastic comb, sliding folder, softbound, or hardbound foil stamping). The specification engine inspects page counts against physical binding thresholds (e.g., maximum page limits for sliding folders) and executes duplex mathematical calculations where physical sheet requirements equal $\lceil \text{Pages}/2 \rceil \times \text{Copies}$. The system encapsulates these attributes into structured JSON specifications within the `order_items` entity.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: SPECIFICATION ENGINE (RIGHT) |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Specify Paper Size, GSM, Orientation    |                                          |
|  (Simplex/Duplex), & Binding Style]      |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Evaluate Binding Feasibility & Limits]  |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Calculate Physical Sheets Needed:       |
|                                          |  Sheets = ceil(Pages / 2) * Copies]      |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Encode Specifications into JSON Object] |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Review Validated Print Specifications] <┼─────┘ [(Save Item to 'order_items' Table)]|
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Specifications Attached)        |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 6: Print Attribute and Finishing Specification**

---

### 7. Activity Diagram for Customer-Supplied Substrate Declaration ("Dala ang Papel") (FR-07 / UC-06)

#### Narrative Paragraph:
The activity diagram for Customer-Supplied Substrate Declaration illustrates the client checkout extension for "Dala ang Papel" orders. When placing an order, the customer activates the substrate declaration toggle, specifying their paper brand, type, and sheet volume. The pricing engine detects the flag, zeroes out shop raw paper material fees from the running subtotal, and designates the line item as cover-only fulfillment. Upon order submission, the system attaches an "Awaiting Paper Delivery" intake flag to the digital job ticket, ensuring that shop paper is not billed while alerting the client to deliver their paper bundle to the counter.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: CHECKOUT PRICING ENGINE (RIGHT)
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Toggle 'Dala ang Papel / Cover Only']   |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Enter Paper Description & Sheet Volume] |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Deduct Raw Paper Cost from Quotation]   |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Designate Item as 'cover_only']         |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Flag Job Ticket: 'Awaiting Paper']      |
|                                          |     │                                    |
| [View Discounted Subtotal & Intake Notice] <───┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Substrate Declared)             |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 7: Customer-Supplied Substrate Declaration ("Dala ang Papel")**

---

### 8. Activity Diagram for Proof of Payment Submission (FR-08 / UC-07)

#### Narrative Paragraph:
The activity diagram for Proof of Payment Submission models client remittance submission. Following order placement, the customer navigates to the payment step, selecting either counter cash settlement or mobile wallet options (GCash or Maya). For digital transactions, the customer scans the shop payment QR code, inputs the transaction reference number, and uploads a screenshot of the remittance receipt. The server validates image dimensions and file formats, stores the receipt file on private disk, updates the order payment status to "pending_verification", and alerts workshop staff for audit.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: PAYMENT RECEIVER (RIGHT)     |
+------------------------------------------+------------------------------------------+
|  (● Start: Order Created)                |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Payment Channel (GCash, Maya)]   |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Input Ref No. & Upload Receipt Image]   |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate File Structure & File Type]    |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Save Receipt Image on Private Disk]     |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Update Status: 'pending_verification']  |
|                                          |     │                                    |
| [View 'Payment Pending' Status Badge] <──┼─────┘ [Dispatch Notification to Staff]   |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Proof Submitted)                |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 8: Proof of Payment Submission**

---

### 9. Activity Diagram for Live Order Progress and Status Tracking (FR-09 / UC-08)

#### Narrative Paragraph:
The activity diagram for Live Order Progress and Status Tracking depicts real-time operational telemetry available to clients. The customer accesses their dashboard or inputs their unique tracking code. The tracking subsystem queries active order records from the database, extracts current production flags, and checks stage transition timestamps. The system renders an illuminated, color-coded five-stage stepper component (Queue, Printing, Finishing/Assembly, Quality Inspection, Ready for Pickup). Completed milestones display checkmarks and recorded timestamps, the active milestone displays an illuminated pulse badge, and estimated fulfillment times are updated dynamically.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: CUSTOMER (LEFT)              | SWIMLANE 2: TRACKING TELEMETRY (RIGHT)   |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Open Customer Dashboard or Enter Code]  |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Query Order Record & Current Stage Flag]|
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Retrieve Milestone Timestamps from DB]  |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [View Illuminated 5-Stage Stepper:       | [Render Visual Stepper Component &       |
|  Queue, Print, Finish, QC, Ready] <──────┼──┘ Calculate Estimated Pickup Date]      |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Status Inspected)               |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 9: Live Order Progress and Status Tracking**

---

### 10. Activity Diagram for Payment Transaction Auditing and Verification (FR-10 / UC-09)

#### Narrative Paragraph:
The activity diagram for Payment Transaction Auditing and Verification outlines the financial control procedure executed by workshop staff. Production staff access the billing queue to inspect pending customer payments. Staff view the submitted transaction reference number and receipt screenshot, cross-referencing them against the shop's external merchant bank ledger. If the payment reference is invalid, staff click "Reject Payment" and enter explanatory remarks, triggering a customer re-upload notification. If authentic, staff confirm the payment; the system transitions order payment status to "Paid", records the staff ID and verification timestamp, and releases the job ticket into the active production queue.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: PRODUCTION STAFF (LEFT)      | SWIMLANE 2: BILLING AUDIT ENGINE (RIGHT) |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Open Staff Billing Audit Queue]         |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Inspect Customer Receipt & Ref No.]     |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Cross-Check against Merchant Ledger]    |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  < Payment Authentic & Matches Ledger? > |                                          |
|     │                             │      |                                          |
|     ├─ [No: Discrepancy]          │ [Yes]|                                          |
|     │    │                        │      |                                          |
|     │    ▼                        │      |                                          |
|     │ [Click 'Reject Payment'     │      |                                          |
|     │  with Rejection Notes] ─────┼─────>│ [Update Status: 'payment_rejected']      |
|     │                             │      |     │                                    |
|     │                             │      |     ▼                                    |
|     │                             │      | [Notify Customer to Re-submit Proof]     |
|     │                             │      |                                          |
|     └────────────────────────────>│      |                                          |
|                                   │      |                                          |
|                                   ▼      |                                          |
|                   [Click 'Confirm Paid'] |                                          |
|                                   │      |                                          |
|                                   └─────>│ [Update Order Payment Status to 'Paid']  |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Record Verifier Staff ID & Timestamp]   |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Order Released into Workshop Queue] <───┼─────┘ [Unlock Job Ticket into Kanban]    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Payment Audited)                |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 10: Payment Transaction Auditing and Verification**

---

### 11. Activity Diagram for Production Floor 5-Stage Kanban Queue Management (FR-11 / UC-10)

#### Narrative Paragraph:
The activity diagram for Production Floor 5-Stage Kanban Queue Management models workshop scheduling and stage progression across five active columns. Upon payment clearance, orders enter Stage 1: In Queue. Production staff claim a ticket, assign machinery, record prepress notes, and advance the order to Stage 2: Printing Pages. Once printing concludes, the operator transitions the job to Stage 3: Finishing and Assembly, where binding, trimming, and folding occur. The ticket then enters Stage 4: Quality Inspection. A quality control inspector evaluates physical alignment, color accuracy, and binding durability. If defects are identified, staff log rework remarks and execute a step-back action to Printing or Finishing. If verified, the order advances to Stage 5: Ready for Pickup. Each transition synchronizes customer tracking telemetry in real time.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: PRODUCTION OPERATOR & QC     | SWIMLANE 2: FLOOR KANBAN ENGINE (RIGHT)  |
+------------------------------------------+------------------------------------------+
|  (● Start: Paid Order in Queue)          |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Claim Ticket in 'In Queue' Column]      |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Assign Machine & Advance Stage] ───────>│ [Transition to Stage 2: 'Printing Pages']|
|     │                                    |     │                                    |
| [Execute Physical Printing Run] <────────┼─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Advance to Finishing] ─────────────────>│ [Transition Stage 3: 'Finishing/Assembly']
|     │                                    |     │                                    |
| [Perform Binding, Trimming, & Assembly] <┼─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Advance to Quality Inspection] ────────>│ [Transition Stage 4: 'Quality Inspection']
|     │                                    |     │                                    |
| [Inspect Finished Product against Specs]<┼─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  < Quality Inspection Passed? >          |                                          |
|     │                          │         |                                          |
|     ├─ [No: Defect Detected]   │ [Yes]   |                                          |
|     │    │                     │         |                                          |
|     │    ▼                     │         |                                          |
|     │ [Log Defect Reason &     │         |                                          |
|     │  Step-Back for Rework] ──┼────────>│ [Execute Step-Back State Reversal]       |
|     │                          │         |                                          |
|     │                          ▼         |                                          |
|     └─────────────────────────>│         |                                          |
|                                │         |                                          |
|                                └────────>│ [Transition Stage 5: 'Ready for Pickup'] |
|                                          |     │                                    |
| [Move Order to Workshop Pickup Bay] <────┼─────┘ [Synchronize Live Customer Stepper]|
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Job Ready for Customer Claim)   |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 11: Production Floor 5-Stage Kanban Queue Management**

---

### 12. Activity Diagram for Automated Bill of Materials (BOM) Inventory Deduction (FR-12 / UC-11)

#### Narrative Paragraph:
The activity diagram for Automated Bill of Materials (BOM) Inventory Deduction illustrates the automated raw material depletion engine. When an order advances into production, the inventory deduction service triggers a transactional audit. The engine evaluates line-item fulfillment flags: if marked as customer-supplied substrate ("Dala ang Papel"), raw paper sheet deduction is completely bypassed. For standard orders, physical sheet usage is computed using duplex math ($\lceil \text{Pages}/2 \rceil \times \text{Copies}$). Linked finishing consumables (e.g., ring spines, PVC covers, backings) are derived from unit multipliers registered in `service_boms`. The engine decrements physical inventory stock balances in `inventory_items`, creates an immutable audit row in `stock_movements`, and initiates restocking threshold evaluation.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: PRODUCTION STAGE TRIGGER     | SWIMLANE 2: AUTOMATED BOM ENGINE (RIGHT) |
+------------------------------------------+------------------------------------------+
|  (● Start: Stage Advanced to Printing)   |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Query Order Line-Items & Service BOM]   |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Is Item 'Dala ang Papel'? >           |
|                                          |     │                       │            |
|                                          |     ├─ [Yes]                │ [No]       |
|                                          |     │    │                  │    │       |
|                                          |     │    ▼                  │    ▼       |
|                                          |     │ [Bypass Paper]        │ [Calculate |
|                                          |     │ [Deduct 0 Sheets]     │  Sheets =  |
|                                          |     │    │                  │  ceil(p/2) |
|                                          |     │    │                  │  * copies] |
|                                          |     │    └─────────┬────────┘    │       |
|                                          |     │              ▼             │       |
|                                          |     └─────────────>│ <───────────┘       |
|                                          |                    │                     |
|                                          |                    ▼                     |
|                                          | [Calculate Consumables via BOM Multiplier|
|                                          |  (e.g., 1 Spine, 2 PVC Covers per Book)] |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Decrement 'current_stock' in Inventory] |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [(Append Row to 'stock_movements' Table)]|
|                                          |     │                                    |
| [Inventory Balances Decremented] <───────┼─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Materials Deducted)             |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 12: Automated Bill of Materials (BOM) Inventory Deduction**

---

### 13. Activity Diagram for Customer-Supplied Substrate Counter Inspection (FR-13 / UC-12)

#### Narrative Paragraph:
The activity diagram for Customer-Supplied Substrate Counter Inspection details the physical verification workflow governing "Dala ang Papel" orders. When an order containing customer paper arrives at the shop counter, workshop staff open the corresponding digital job ticket. Staff physically inspect the paper stock to verify sheet count, grammage (GSM), and surface quality against stated job ticket specifications. If damaged or insufficient sheets are presented, staff reject the substrate and request replacement sheets. Once verified, staff click "Mark Paper Received" on the console and place the bundle into a designated staging bin. The system transitions the job ticket state, releases the order from intake hold, and activates it in the production queue.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: WORKSHOP COUNTER STAFF (LEFT)| SWIMLANE 2: PRODUCTION QUEUE (RIGHT)     |
+------------------------------------------+------------------------------------------+
|  (● Start: Customer Presents Paper)      |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Retrieve Job Ticket on Counter Console] |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Physically Count Sheets & Inspect GSM,  |                                          |
|  Thickness, & Sheet Cleanliness]         |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  < Paper Quantity & Quality Valid? >     |                                          |
|     │                             │      |                                          |
|     ├─ [No: Defect / Shortage]    │ [Yes]|                                          |
|     │    │                        │      |                                          |
|     │    ▼                        │      |                                          |
|     │ [Issue Rejection Notice;    │      |                                          |
|     │  Customer Replaces Sheets]  │      |                                          |
|     │    │                        │      |                                          |
|     │    └────────┐               │      |                                          |
|     │             ▼               │      |                                          |
|     └────────────>│               │      |                                          |
|                   │               ▼      |                                          |
|                   └────────> [Click 'Mark Paper Received']                          |
|                                   │      |                                          |
|                                   └─────>│ [Update 'is_paper_received = true']      |
|                                          |     │                                    |
| [Place Bundle in Tagged Staging Bin] <───┼─────┤                                    |
|                                          |     ▼                                    |
| [Order Released into Active Queue] <─────┼─────┘ [Unlock Ticket from Intake Hold]   |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Substrate Verified & Released)  |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 13: Customer-Supplied Substrate Counter Inspection**

---

### 14. Activity Diagram for Material Spoilage Logging and Stock Adjustments (FR-14 / UC-13)

#### Narrative Paragraph:
The activity diagram for Material Spoilage Logging and Stock Adjustments outlines floor waste accountability. When equipment jams or binding errors damage raw materials during production, the operator opens the "Report Spoilage" modal from the Kanban interface. The operator selects the damaged inventory item from the catalog, enters the wasted quantity, specifies the operational failure cause, and submits the report. The inventory service validates numeric boundaries, decrements the item's on-hand stock balance, appends an immutable transaction record to `stock_movements` tagged with type "spoilage", and triggers dynamic reorder point evaluation.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: PRODUCTION OPERATOR (LEFT)   | SWIMLANE 2: INVENTORY LEDGER (RIGHT)     |
+------------------------------------------+------------------------------------------+
|  (● Start: Material Damaged/Wasted)      |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Open 'Report Spoilage' Modal in Kanban] |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Damaged Supply Item, Enter       |                                          |
|  Wasted Quantity, & Specify Incident]    |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Click 'Confirm Spoilage Deduction']     |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate Numeric Limits & Item Status]  |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Decrement 'current_stock' Balance]      |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Receive Spoilage Confirmation Toast] <──┼─────┘ [(Insert Audit Row: Type = 'spoilage')]
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Spoilage Logged & Stock Decremented)                                       |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 14: Material Spoilage Logging and Stock Adjustments**

---

### 15. Activity Diagram for Dynamic Service and Rate Configuration (FR-15 / UC-14)

#### Narrative Paragraph:
The activity diagram for Dynamic Service and Rate Configuration models administrative catalog management. The business owner opens the Service Configuration Hub from the administrative console, choosing to edit an existing service or create a new offering. The owner specifies commercial attributes: service title, active status toggle, base preparation fee, per-page rates for monochrome and color output, paper dimensions, and rush fee multipliers. The system validates inputs against boundary constraints, writes configuration records to the database, flushes cached pricing models from application memory, and immediately exposes updated rates to the client ordering interface without source code alterations.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: BUSINESS OWNER (LEFT)        | SWIMLANE 2: SERVICE HUB & CACHE (RIGHT)  |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Open Service Configuration Hub]         |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Input Service Name, Base Fees, Page     |                                          |
|  Rates (B&W/Color), & Rush Multipliers]  |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Click 'Save Service Configuration']     |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate Parameter Constraints]        |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Constraints Valid? >                  |
|                                          |     │                 │                  |
| [Review Inline Form Errors] <────────────┼─────┘ [No]            │ [Yes]            |
|     │                                    |                       ▼                  |
|     └───────────────────────────────────>│ [(Write Record to 'shop_services' Table)]|
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Flush Pricing Cache in Application]     |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [View Confirmation Toast Notification] <─┼─────┘ [Expose New Rates to Storefront]   |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Service Rates Active)           |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 15: Dynamic Service and Rate Configuration**

---

### 16. Activity Diagram for Bill of Materials (BOM) Recipe Definition (FR-16 / UC-15)

#### Narrative Paragraph:
The activity diagram for Bill of Materials (BOM) Recipe Definition depicts the administrative linking between commercial services and inventory consumables. The business owner accesses the BOM Recipe Manager, selects a target service (e.g., Thesis Hardbound), and maps required raw stock items (e.g., chipboard, book cloth, foil ribbon, end-sheets) from the inventory catalog. The owner defines consumption coefficients (such as 2 sheets per book or 1 coil per booklet). The system validates foreign key relationships, writes recipe mappings to the `service_boms` configuration table, and binds the operational consumption rules to automated floor deduction routines.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: BUSINESS OWNER (LEFT)        | SWIMLANE 2: RELATIONAL BOM ENGINE (RIGHT)|
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Open BOM Recipe Management Interface]   |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Print Service Offering]          |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Linked Inventory Items & Assign  |                                          |
|  Unit Consumption Coefficients]          |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Click 'Save BOM Recipe Mappings']       |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Enforce Foreign Keys & Formula Logic]   |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [(Insert Mappings in 'service_boms')]    |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [View Recipe Registered Confirmation] <──┼─────┘ [Bind Deduction Rules to Inventory] |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: BOM Recipe Defined)             |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 16: Bill of Materials (BOM) Recipe Definition**

---

### 17. Activity Diagram for Inventory Oversight and Dynamic Reorder Point (ROP) Alerting (FR-17 / UC-16)

#### Narrative Paragraph:
The activity diagram for Inventory Oversight and Dynamic Reorder Point (ROP) Alerting models continuous stock monitoring and proactive replenishment alerting. The automated inventory monitor computes material daily burn rates based on rolling consumption velocity: $\text{Burn Rate} = \text{Total Consumed} / \text{Days}$. The system derives the dynamic Reorder Point threshold using $\text{ROP} = (\text{Daily Burn Rate} \times \text{Lead Time}) + \text{Safety Stock}$, and evaluates current on-hand stock balances against this threshold. If on-hand stock falls to or below the dynamic ROP, the platform flags the inventory item with a visual low-stock badge on the Inventory Hub and renders a high-priority restocking recommendation banner on the Owner Master Dashboard.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: BUSINESS OWNER & HUB (LEFT)  | SWIMLANE 2: ROP CALCULATION ENGINE (RIGHT)
+------------------------------------------+------------------------------------------+
|  (● Start: Background Telemetry / Trigger)                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Query Stock Movements & Rolling Velocity|
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Calculate Daily Burn Rate: Consumed/Days|
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Derive Dynamic ROP = (Burn*Lead) + Safe]|
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Current Stock <= Dynamic ROP? >       |
|                                          |     │                       │            |
|                                          |     ├─ [Yes: Critical]      │ [No]       |
|                                          |     │    │                  │    │       |
|                                          |     │    ▼                  │    ▼       |
| [View Amber/Red Low-Stock Badge on Hub] <┼─────┘ [Activate Low-Stock   │ [Maintain  |
| [Inspect Restock Banner on Master Console] <───── Warning State]       │  Routine]  |
|     │                                    |              │              │    │       |
|     ▼                                    |              ▼              ▼    │       |
|  (◉ End: Oversight Telemetry Rendered) <─┼──────────────────────────────────┘       |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 17: Inventory Oversight and Dynamic Reorder Point (ROP) Alerting**

---

### 18. Activity Diagram for Sales, Cashflow, and Operational Analytics Generation (FR-18 / UC-17)

#### Narrative Paragraph:
The activity diagram for Sales, Cashflow, and Operational Analytics Generation outlines business intelligence aggregation and financial report exporting. The business owner opens the Analytics Hub and selects reporting parameters, including date range filters and service categories. The analytics engine queries completed orders, verified payments, raw material consumption ledgers, and spoilage losses from the database. It calculates gross revenue, net profit margins, consumable expenses, and rush fees, rendering interactive trend charts and itemized summary tables. When the owner triggers the export command, the system formats transaction records into an audit-compliant CSV ledger and initiates an automated file download to the browser.

#### Visual Swimlane Flowchart:
```
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: BUSINESS OWNER (LEFT)        | SWIMLANE 2: ANALYTICS QUERY ENGINE (RIGHT)
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Open Sales & Financial Analytics Hub]   |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Select Date Range & Service Filters]    |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Query Orders, Payments, & Spoilage Logs] |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          | [Compute Gross Revenue, Net Margin,      |
|                                          |  Material Costs, & Spoilage Losses]      |
|                                          |     │                                    |
|                                          |     ▼                                    |
| [Inspect Interactive Trend Charts &      | [Render Metric Cards & Ledger Tables]    |
|  Categorized Financial Summaries] <──────┼─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  < Export CSV Ledger Requested? >        |                                          |
|     │                       │            |                                          |
|     ├─ [Yes]                │ [No]       |                                          |
|     │    │                  │            |                                          |
|     │    ▼                  │            |                                          |
|     │ [Click 'Export CSV']  │            |                                          |
|     │    │                  │            |                                          |
|     │    └─────────────────>│ [Compile Transaction Rows into Formatted CSV]         |
|     │                       │     │                                                 |
|     │                       │     ▼                                                 |
| [Download CSV Report File] <┼─────┘ [Stream CSV File Payload to Browser]            |
|     │                       │                                                       |
|     ▼                       ▼                                                       |
|  (◉ End: Business Analysis Complete)                                                |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 18: Sales, Cashflow, and Operational Analytics Generation**

---

## 3. Bidirectional Traceability Matrix (18-to-18)

| Item # | Functional Requirement Name | Use Case ID & Name | Activity Diagram Title |
| :---: | :--- | :--- | :--- |
| **1** | User Account Registration | **UC-02**: Register Customer Account | **Activity Diagram 1: User Account Registration** |
| **2** | User Authentication and Profile Management | **UC-01**: Log In & Manage Profile | **Activity Diagram 2: User Authentication and Profile Management** |
| **3** | Session Termination and Logout | **UC-18**: Log Out & Terminate Session | **Activity Diagram 3: Session Termination and Logout** |
| **4** | Service Catalog Browsing and Quotation Calculation | **UC-03**: Browse Catalog & Calculate Quotation | **Activity Diagram 4: Service Catalog Browsing and Quotation Calculation** |
| **5** | Print Order Placement and Document Upload | **UC-04**: Place Order & Upload Documents | **Activity Diagram 5: Print Order Placement and Document Upload** |
| **6** | Print Attribute and Finishing Specification | **UC-05**: Specify Print Attributes & Finishing | **Activity Diagram 6: Print Attribute and Finishing Specification** |
| **7** | Customer-Supplied Substrate Declaration ("Dala ang Papel") | **UC-06**: Declare Customer-Supplied Substrates | **Activity Diagram 7: Customer-Supplied Substrate Declaration ("Dala ang Papel")** |
| **8** | Proof of Payment Submission | **UC-07**: Submit Proof of Payment | **Activity Diagram 8: Proof of Payment Submission** |
| **9** | Live Order Progress and Status Tracking | **UC-08**: Track Live Order Progress & Status | **Activity Diagram 9: Live Order Progress and Status Tracking** |
| **10** | Payment Transaction Auditing and Verification | **UC-09**: Verify Payment Transactions | **Activity Diagram 10: Payment Transaction Auditing and Verification** |
| **11** | Production Floor 5-Stage Kanban Queue Management | **UC-10**: Manage 5-Stage Kanban Queue | **Activity Diagram 11: Production Floor 5-Stage Kanban Queue Management** |
| **12** | Automated Bill of Materials (BOM) Inventory Deduction | **UC-11**: Deduct Materials via Automated BOM | **Activity Diagram 12: Automated Bill of Materials (BOM) Inventory Deduction** |
| **13** | Customer-Supplied Substrate Counter Inspection | **UC-12**: Inspect Customer-Supplied Substrates | **Activity Diagram 13: Customer-Supplied Substrate Counter Inspection** |
| **14** | Material Spoilage Logging and Stock Adjustments | **UC-13**: Record Spoilage & Inventory Adjustments | **Activity Diagram 14: Material Spoilage Logging and Stock Adjustments** |
| **15** | Dynamic Service and Rate Configuration | **UC-14**: Configure Services & Dynamic Rates | **Activity Diagram 15: Dynamic Service and Rate Configuration** |
| **16** | Bill of Materials (BOM) Recipe Definition | **UC-15**: Define Bill of Materials Recipes | **Activity Diagram 16: Bill of Materials (BOM) Recipe Definition** |
| **17** | Inventory Oversight and Dynamic Reorder Point (ROP) Alerting | **UC-16**: Manage Inventory & Reorder Thresholds | **Activity Diagram 17: Inventory Oversight and Dynamic Reorder Point Alerting** |
| **18** | Sales, Cashflow, and Operational Analytics Generation | **UC-17**: Generate Sales & Cashflow Analytics | **Activity Diagram 18: Sales, Cashflow, and Operational Analytics Generation** |

---

## 4. Comprehensive Text-Based Style Guide & Step-by-Step Drawing Blueprint (Fallback)

Use this step-by-step specification when drawing the 18 diagrams in **Draw.io** or **StarUML**. This guarantees that all diagrams maintain visual consistency, professional alignment, and zero crossed lines.

### Standard Modeling Rules & Conventions:
1. **Swimlane Geometry**:
   - Exactly two vertical swimlane partitions.
   - **Left Swimlane**: Primary Human Actor (`Customer`, `Business Owner`, or `Production Staff`). The Initial Node (`● Start`) is **always** placed at the top of the Left Swimlane.
   - **Right Swimlane**: `System Engine`, `Validation Layer`, and `Database Tables`.
2. **UML 2.5 Shapes to Use**:
   - **Initial Node**: Filled solid circle `●`.
   - **Action State**: Rounded rectangle with clean text.
   - **Decision Diamond**: Diamond with outward condition guards in brackets: `[Yes]` and `[No]`.
   - **Object Node / Data Store**: Database cylinder icon `[(Table Name)]`.
   - **Activity Final Node**: Bullseye circle with a filled inner dot `◉`.

---

### Step-by-Step Drawing Directives for All 18 Diagrams:

#### Blueprint 1: User Account Registration
* **Left Swimlane (Customer):**
  1. Add `● Start`.
  2. Action 1: *"Enter Full Name, Email, Mobile No., & Password"*. Connect across to Right Swimlane.
  3. Action 3 (Error Loopback): *"Review Form & Correct Erroneous Inputs"*. Connects back into Action 1.
  4. Action 7: *"Redirect to Login Portal"*. Connect to `◉ End: Account Created`.
* **Right Swimlane (Authentication Engine & Database):**
  1. Action 2: *"Validate Form Syntax & Check Email Uniqueness"*. Connect to Decision 1.
  2. Decision 1: *"< Email Unique & Valid? >"*.
     - Branch `[No]`: Connects left to Action 3 in Left Swimlane.
     - Branch `[Yes]`: Connects to Action 4.
  3. Action 4: *"Hash Password using Bcrypt Algorithm"*. Connect to Action 5.
  4. Action 5: *"Assign Default Role: 'Customer'"*. Connect to Data Store.
  5. Data Store: *"[Insert Record into 'users' Table]"*. Connect to Action 6.
  6. Action 6: *"Display Confirmation Toast"*. Connects left to Action 7 in Left Swimlane.

#### Blueprint 2: User Authentication and Profile Management
* **Left Swimlane (User - All Roles):**
  1. Add `● Start`.
  2. Action 1: *"Enter Registered Email & Password"*. Connect across to Right Swimlane.
  3. Action 3 (Error Loopback): *"View Error & Re-enter Credentials"*. Loops back to Action 1.
  4. Action 6a: *"Access Owner Master Hub"* (if Owner).
  5. Action 6b: *"Access Staff Production Console"* (if Staff).
  6. Action 6c: *"Access Customer Order Portal"* (if Customer).
  7. Action 7: *"Optional: Update Profile Information"*. Connect to Right Swimlane.
  8. Action 9: *"View Profile Updated Toast"*. Connect to `◉ End: Authenticated & Managed`.
* **Right Swimlane (Auth Guard & DB):**
  1. Action 2: *"Cross-Reference Password Hash in DB"*. Connect to Decision 1.
  2. Decision 1: *"< Credentials Valid? >"*.
     - Branch `[No]`: Connects left to Action 3 in Left Swimlane.
     - Branch `[Yes]`: Connects to Action 4.
  3. Action 4: *"Initialize Session & Security Token"*. Connect to Decision 2.
  4. Decision 2: *"< Evaluate User Role >"*. Branches to Owner (6a), Staff (6b), or Customer (6c).
  5. Action 8: *"Validate & Persist Updates in 'users' Table"*. Connects left to Action 9.

#### Blueprint 3: Session Termination and Logout
* **Left Swimlane (Authenticated User):**
  1. Add `● Start: User Authenticated`.
  2. Action 1: *"Click 'Log Out' in User Navigation"*. Connect across to Right Swimlane.
  3. Action 5: *"Redirect to Public Landing Page"*. Connect to `◉ End: Session Terminated`.
* **Right Swimlane (Session Controller):**
  1. Action 2: *"Flush Active Session Data from Memory"*.
  2. Action 3: *"Regenerate CSRF Security Token"*.
  3. Action 4: *"Destroy Client Authentication Cookies & Invalidate Routes"*. Connects left to Action 5.

#### Blueprint 4: Service Catalog Browsing and Quotation Calculation
* **Left Swimlane (Customer):**
  1. Add `● Start`.
  2. Action 1: *"Browse Active Service Catalog"*.
  3. Action 2: *"Select Service & Alter Parameters: Page Count, Color Mode, & Copies"*. Connect to Right Swimlane.
  4. Action 5: *"View Live Itemized Quotation Breakdown"*. Connect to `◉ End: Quotation Derived`.
* **Right Swimlane (Livewire Pricing Engine):**
  1. Action 3: *"Fetch Base Rates & Price Formulas in DB"*.
  2. Action 4: *"Execute Real-Time Price Computation: Base + (Pages * Rate * Copies)"*.
  3. Action 4b: *"Re-render Subtotal via Livewire without Reload"*. Connects left to Action 5.

#### Blueprint 5: Print Order Placement and Document Upload
* **Left Swimlane (Customer):**
  1. Add `● Start: Finalized Parameters`.
  2. Action 1: *"Select Fulfillment Mode & Upload Files"*. Connect to Right Swimlane.
  3. Action 4 (Error Loopback): *"View Upload Error Alert"*. Loops back to Action 1.
  4. Action 5: *"Click 'Confirm & Place Order'"*. Connect to Right Swimlane.
  5. Action 8: *"Receive Tracking Code: ORD-2026-X"*. Connect to `◉ End: Order Placed`.
* **Right Swimlane (Order Engine & Disk):**
  1. Action 2: *"Validate File MIME Type & File Size"*. Connect to Decision 1.
  2. Decision 1: *"< File Constraints Valid? >"*.
     - Branch `[No]`: Connects left to Action 4.
     - Branch `[Yes]`: Connects left to Action 5.
  3. Action 6: *"Store File on Disk with Tokenized Name"*.
  4. Action 7: *"Write Record to 'orders' Table & Generate Tracking Code"*. Connects left to Action 8.

#### Blueprint 6: Print Attribute and Finishing Specification
* **Left Swimlane (Customer):**
  1. Add `● Start`.
  2. Action 1: *"Specify Paper Size, GSM, Orientation (Simplex/Duplex), & Binding Style"*. Connect to Right Swimlane.
  3. Action 5: *"Review Validated Print Specifications"*. Connect to `◉ End: Specifications Attached`.
* **Right Swimlane (Specification Engine):**
  1. Action 2: *"Evaluate Binding Feasibility & Physical Page Limits"*.
  2. Action 3: *"Calculate Physical Sheets Needed: Sheets = ceil(Pages / 2) * Copies"*.
  3. Action 4: *"Encode Specifications into JSON Object & Save to 'order_items'"*. Connects left to Action 5.

#### Blueprint 7: Customer-Supplied Substrate Declaration ("Dala ang Papel")
* **Left Swimlane (Customer):**
  1. Add `● Start`.
  2. Action 1: *"Toggle 'Dala ang Papel / Cover Only'"*.
  3. Action 2: *"Enter Paper Description & Sheet Volume"*. Connect to Right Swimlane.
  4. Action 5: *"View Discounted Subtotal & Intake Notice"*. Connect to `◉ End: Substrate Declared`.
* **Right Swimlane (Checkout Pricing Engine):**
  1. Action 3: *"Deduct Raw Paper Cost from Quotation"*.
  2. Action 4: *"Designate Item as 'cover_only' & Flag Ticket: 'Awaiting Paper'"*. Connects left to Action 5.

#### Blueprint 8: Proof of Payment Submission
* **Left Swimlane (Customer):**
  1. Add `● Start: Order Created`.
  2. Action 1: *"Select Payment Channel (GCash, Maya, Counter Cash)"*.
  3. Action 2: *"Input Ref No. & Upload Receipt Image"*. Connect to Right Swimlane.
  4. Action 5: *"View 'Payment Pending' Status Badge"*. Connect to `◉ End: Proof Submitted`.
* **Right Swimlane (Payment Receiver):**
  1. Action 3: *"Validate File Structure & File Type"*.
  2. Action 4: *"Save Receipt Image on Private Disk & Set Status 'pending_verification'"*. Connects left to Action 5.

#### Blueprint 9: Live Order Progress and Status Tracking
* **Left Swimlane (Customer):**
  1. Add `● Start`.
  2. Action 1: *"Open Customer Dashboard or Enter Tracking Code"*. Connect to Right Swimlane.
  3. Action 4: *"View Illuminated 5-Stage Stepper: Queue, Print, Finish, QC, Ready"*. Connect to `◉ End: Status Inspected`.
* **Right Swimlane (Tracking Telemetry):**
  1. Action 2: *"Query Order Record & Current Stage Flag in DB"*.
  2. Action 3: *"Retrieve Milestone Timestamps & Render Visual Stepper Component"*. Connects left to Action 4.

#### Blueprint 10: Payment Transaction Auditing and Verification
* **Left Swimlane (Production Staff / Owner):**
  1. Add `● Start`.
  2. Action 1: *"Open Staff Billing Audit Queue & Inspect Receipt Ref No."*.
  3. Action 2: *"Cross-Check against Merchant Bank Ledger"*. Connect to Decision 1.
  4. Decision 1: *"< Payment Authentic & Matches Ledger? >"*.
     - Branch `[No]`: Click *"Reject Payment"* with Rejection Notes $\longrightarrow$ Customer notified.
     - Branch `[Yes]`: Click *"Confirm Paid"*. Connect to Right Swimlane.
  5. Action 5: *"Order Released into Workshop Queue"*. Connect to `◉ End: Payment Audited`.
* **Right Swimlane (Billing Audit Engine):**
  1. Action 3: *"Update Order Payment Status to 'Paid'"*.
  2. Action 4: *"Record Verifier Staff ID, Timestamp, & Unlock Ticket into Kanban"*. Connects left to Action 5.

#### Blueprint 11: Production Floor 5-Stage Kanban Queue Management
* **Left Swimlane (Production Operator & QC):**
  1. Add `● Start: Paid Order in Queue`.
  2. Action 1: *"Claim Ticket in 'In Queue' Column & Assign Machine"*. Connect to Right Swimlane.
  3. Action 3: *"Execute Physical Printing Run & Advance Stage"*. Connect to Right Swimlane.
  4. Action 5: *"Perform Binding, Trimming, & Assembly"*. Connect to Right Swimlane.
  5. Action 7: *"Inspect Finished Product against Specifications"*. Connect to Decision 1.
  6. Decision 1: *"< Quality Inspection Passed? >"*.
     - Branch `[No]`: *"Log Defect Reason & Step-Back for Rework"* $\longrightarrow$ Loops to Printing/Finishing.
     - Branch `[Yes]`: Connect to Right Swimlane.
  7. Action 9: *"Move Order to Workshop Pickup Bay"*. Connect to `◉ End: Job Ready for Customer Claim`.
* **Right Swimlane (Floor Kanban Engine):**
  1. Action 2: *"Transition to Stage 2: 'Printing Pages'"*.
  2. Action 4: *"Transition to Stage 3: 'Finishing/Assembly'"*.
  3. Action 6: *"Transition to Stage 4: 'Quality Inspection'"*.
  4. Action 8: *"Transition to Stage 5: 'Ready for Pickup' & Synchronize Customer Stepper"*. Connects left to Action 9.

#### Blueprint 12: Automated Bill of Materials (BOM) Inventory Deduction
* **Left Swimlane (Production Stage Trigger):**
  1. Add `● Start: Stage Advanced to Printing`. Connect to Right Swimlane.
  2. Action 6: *"Inventory Balances Decremented & Audited"*. Connect to `◉ End: Materials Deducted`.
* **Right Swimlane (Automated BOM Engine):**
  1. Action 1: *"Query Order Line-Items & Service BOM Recipe"*. Connect to Decision 1.
  2. Decision 1: *"< Is Item 'Dala ang Papel'? >"*.
     - Branch `[Yes]`: *"Bypass Raw Paper Deduction (Deduct 0 Sheets)"*.
     - Branch `[No]`: *"Calculate Sheets = ceil(Pages / 2) * Copies"*.
  3. Action 3: *"Calculate Consumables via BOM Multiplier (Spines, PVC Covers, Backers)"*.
  4. Action 4: *"Decrement 'current_stock' in 'inventory_items'"*.
  5. Action 5: *"Append Immutable Transaction Row to 'stock_movements' Table"*. Connects left to Action 6.

#### Blueprint 13: Customer-Supplied Substrate Counter Inspection
* **Left Swimlane (Workshop Counter Staff & Customer):**
  1. Add `● Start: Customer Presents Paper Bundle at Counter`.
  2. Action 1: *"Retrieve Job Ticket on Counter Console"*.
  3. Action 2: *"Physically Count Sheets & Inspect GSM, Thickness, & Cleanliness"*. Connect to Decision 1.
  4. Decision 1: *"< Paper Quantity & Quality Valid? >"*.
     - Branch `[No]`: *"Issue Rejection Notice; Customer Replaces Sheets"*. Loops back to Action 2.
     - Branch `[Yes]`: Click *"Mark Paper Received"*. Connect to Right Swimlane.
  5. Action 5: *"Place Bundle in Tagged Staging Bin"*.
  6. Action 6: *"Order Released into Active Queue"*. Connect to `◉ End: Substrate Verified & Released`.
* **Right Swimlane (Production Queue Engine):**
  1. Action 3: *"Update 'is_paper_received = true' with Staff ID & Timestamp"*.
  2. Action 4: *"Unlock Job Ticket from Intake Hold into Active Kanban Queue"*. Connects left to Action 6.

#### Blueprint 14: Material Spoilage Logging and Stock Adjustments
* **Left Swimlane (Production Operator):**
  1. Add `● Start: Material Damaged/Wasted`.
  2. Action 1: *"Open 'Report Spoilage' Modal in Kanban"*.
  3. Action 2: *"Select Damaged Supply Item, Enter Wasted Qty, & State Incident Reason"*.
  4. Action 3: *"Click 'Confirm Spoilage Deduction'"*. Connect to Right Swimlane.
  5. Action 6: *"Receive Spoilage Confirmation Toast"*. Connect to `◉ End: Spoilage Logged & Stock Decremented`.
* **Right Swimlane (Inventory Ledger):**
  1. Action 4: *"Validate Numeric Limits & Item Status"*.
  2. Action 5: *"Decrement 'current_stock' Balance & Insert Audit Row into 'stock_movements'"*. Connects left to Action 6.

#### Blueprint 15: Dynamic Service and Rate Configuration
* **Left Swimlane (Business Owner):**
  1. Add `● Start`.
  2. Action 1: *"Open Service Configuration Hub"*.
  3. Action 2: *"Input Service Name, Base Fees, Page Rates (B&W/Color), & Rush Multipliers"*.
  4. Action 3: *"Click 'Save Service Configuration'"*. Connect to Right Swimlane.
  5. Action 5 (Error Loopback): *"Review Inline Form Errors"*. Loops back to Action 2.
  6. Action 8: *"View Confirmation Toast Notification"*. Connect to `◉ End: Service Rates Active`.
* **Right Swimlane (Service Hub & Cache):**
  1. Action 4: *"Validate Parameter Constraints"*. Connect to Decision 1.
  2. Decision 1: *"< Constraints Valid? >"*.
     - Branch `[No]`: Connects left to Action 5.
     - Branch `[Yes]`: Connects to Action 6.
  3. Action 6: *"Write Record to 'shop_services' Table"*.
  4. Action 7: *"Flush Pricing Cache in Application & Expose New Rates to Storefront"*. Connects left to Action 8.

#### Blueprint 16: Bill of Materials (BOM) Recipe Definition
* **Left Swimlane (Business Owner):**
  1. Add `● Start`.
  2. Action 1: *"Open BOM Recipe Management Interface"*.
  3. Action 2: *"Select Print Service Offering"*.
  4. Action 3: *"Select Linked Inventory Items & Assign Unit Consumption Coefficients"*.
  5. Action 4: *"Click 'Save BOM Recipe Mappings'"*. Connect to Right Swimlane.
  6. Action 7: *"View Recipe Registered Confirmation"*. Connect to `◉ End: BOM Recipe Defined`.
* **Right Swimlane (Relational BOM Engine):**
  1. Action 5: *"Enforce Foreign Keys & Formula Logic"*.
  2. Action 6: *"Insert Mappings in 'service_boms' Table & Bind Rules to Inventory"*. Connects left to Action 7.

#### Blueprint 17: Inventory Oversight and Dynamic Reorder Point (ROP) Alerting
* **Left Swimlane (Business Owner & Inventory Hub):**
  1. Add `● Start: Background Telemetry / Trigger`. Connect to Right Swimlane.
  2. Action 5: *"View Amber/Red Low-Stock Badge on Hub & Inspect Restock Banner on Master Console"*. Connect to `◉ End: Oversight Telemetry Rendered`.
* **Right Swimlane (ROP Calculation Engine):**
  1. Action 1: *"Query Stock Movements & Rolling Consumption Velocity"*.
  2. Action 2: *"Calculate Daily Burn Rate: Consumed / Days"*.
  3. Action 3: *"Derive Dynamic ROP = (Burn Rate * Lead Time) + Safety Stock"*. Connect to Decision 1.
  4. Decision 1: *"< Current Stock <= Dynamic ROP? >"*.
     - Branch `[No]`: *"Maintain Routine Operational State"*. Connect to `◉ End`.
     - Branch `[Yes]`: Action 4: *"Activate Low-Stock Warning State & Banner"*. Connects left to Action 5.

#### Blueprint 18: Sales, Cashflow, and Operational Analytics Generation
* **Left Swimlane (Business Owner):**
  1. Add `● Start`.
  2. Action 1: *"Open Sales & Financial Analytics Hub"*.
  3. Action 2: *"Select Date Range & Service Filters"*. Connect to Right Swimlane.
  4. Action 4: *"Inspect Interactive Trend Charts & Categorized Financial Summaries"*. Connect to Decision 1.
  5. Decision 1: *"< Export CSV Ledger Requested? >"*.
     - Branch `[No]`: Connect to `◉ End: Business Analysis Complete`.
     - Branch `[Yes]`: Action 5: *"Click 'Export CSV'"*. Connect to Right Swimlane.
  6. Action 7: *"Download CSV Report File"*. Connect to `◉ End: Business Analysis Complete`.
* **Right Swimlane (Analytics Query Engine & CSV Dispatcher):**
  1. Action 3: *"Query Orders, Payments, & Spoilage Logs"*.
  2. Action 3b: *"Compute Gross Revenue, Net Margin, Material Costs, & Spoilage Losses"*. Connects left to Action 4.
  3. Action 6: *"Compile Transaction Rows & Stream CSV File to Browser"*. Connects left to Action 7.
