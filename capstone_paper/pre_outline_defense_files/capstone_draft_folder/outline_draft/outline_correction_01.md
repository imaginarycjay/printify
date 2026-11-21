# CHAPTER 3: METHODOLOGY
## Master Alignment, Task Assignment, and System Revision Blueprint
**Capstone Project:** Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment (*Printify*)  
**Institutional Standard:** University of Southern Mindanao (USM) BSIS Capstone Guidelines  
**Document Purpose:** Master reference for Google Docs synchronization, task allocation, and adviser correction resolution.

---

## 1. Executive Summary & Root Cause Analysis

During preliminary manuscript review, **96% of the adviser's corrections targeted formatting, spacing, duplicate figure numbers, and misplaced tables/diagrams**. This occurred because combining separately drafted sections introduced critical structural collisions:

1. **Duplicate Figure Numbers:** The Use Case Diagram and Class Diagram were labeled Figure 5 and Figure 6, while the User Interface section repeated Figure 5 (Registration) and Figure 6 (Login).
2. **Lumped Figure Identifiers:** Three separate interfaces were compressed into a single line: *"Figure 13, 14, 15 . Customer Ordering Wizard and Live Five-Stage Progress Stepper"*. Every distinct interface requires an individual figure number, dedicated screenshot, and descriptive narrative.
3. **Severed Architectural Sections:** The Use Case and Class Diagrams were positioned *above* the `Project Design` heading, while the Entity Relationship Diagram (ERD) was pushed after the UI section.
4. **Duplicate Section Headings:** Section 2 and Section 10 both used `Project Design`. In this revision, Section 2 is formalized as `Project Design` (Developmental Project Design Framework) and Section 10 is formalized as `Design of the Project` (System Architecture & Software Modeling), matching the USM Comission reference layout.
5. **Codebase Misalignments in Diagrams:** The drafted Activity Diagrams cited 6 Kanban stages instead of the 5 active production columns implemented in the Laravel code, omitted the duplex paper sheet formula ($\lceil \text{Pages}/2 \rceil \times \text{Copies}$) and the *"Dala ang Papel"* substrate bypass, and lacked three essential operational workflows.
6. **Terminology Compliance:** In accordance with BSIS capstone guidelines, all instances of *"research"*, *"researchers"*, and *"study"* are replaced with **"project"**, **"project developers"**, and **"capstone project"**.

---

## 2. Master Chapter 3 Responsibility Matrix & Sequential Mapping

This unified sequence establishes continuous, non-overlapping figure and table numbering across the entire chapter.

| Section # | Section / Heading Title | Specific Assignee | Primary Deliverable | Label Assignment |
| :--- | :--- | :--- | :--- | :--- |
| **1** | **Methodology (Introductory Overview)** | **Author** | Introductory narrative of capstone methodology | *(No Figure)* |
| **2** | **Project Design** | **Author** | Developmental Project Design framework | *(No Figure)* |
| **3** | **Project Participants and Materials** | **Author** | Characterization of 3 user roles & physical shop tools | *(No Figure)* |
| **4** | **Data Collection** | **Author** | Interviews, observations, document review, surveys | *(No Figure)* |
| **5** | **Data Analysis Procedure** | **Author** | Descriptive statistics & ISO 25010 Likert scale | **Table 1:** Likert Scale (ISO 25010) |
| **6** | **Validity** | **Comission** | 5 IT Expert Panelists + UAT across 3 Print Shops | *(No Figure)* |
| **7** | **Ethical Considerations** | **Comission** | RA 10173, student IP protection, payment data privacy | *(No Figure)* |
| **8** | **Project Developmental Approach** | **Comission** | Agile SDLC framework mapped across 4 project sprints | **Figure 2:** Agile SDLC Model |
| **9** | **System Requirement Specifications (SRS)** | **Author** | Functional Requirements 1–9 (Input-Process-Output) | *(No Figure)* |
| | *Software Requirements Specification* | **Author** | Minimum & recommended developer toolchain | **Table 2:** Software Specs |
| | *Hardware Requirements Specification* | **Author** | Workstations, hosting server, client devices | **Table 3:** Hardware Specs |
| **10** | **Design of the Project** | **Shared** | **System Architecture & Software Modeling** | |
| | **10.1 Structural Modeling** | | | |
| | • Entity Relationship Diagram (ERD) | **Author** | 8-table normalized database schema (3NF) | **Figure 3:** Entity Relationship Diagram |
| | • Class Diagram | **Author** | 11-class object-oriented domain hierarchy | **Figure 4:** Class Diagram |
| | **10.2 Behavioral Modeling** | | | |
| | • Use Case Diagram | **Author** | Actor boundaries, `<<include>>` & `<<extend>>` | **Figure 5:** Use Case Diagram |
| | • Activity Diagrams (10 Workflows): | **Comission** | Operational swimlane workflow diagrams: | |
| |   1. User Registration Process | **Comission** | Account creation & default role assignment | **Figure 6:** User Registration Workflow |
| |   2. User Authentication Process | **Comission** | Credential validation & portal routing | **Figure 7:** Authentication Workflow |
| |   3. Dynamic Service & BOM Config | **Comission** | Owner dynamic pricing & BOM setup *(NEW)* | **Figure 8:** Service & BOM Config Workflow |
| |   4. Customer Ordering & Upload | **Comission** | Parameter selection, file upload, & quotation | **Figure 9:** Customer Ordering Workflow |
| |   5. Substrate Intake & Verification | **Comission** | "Dala ang Papel" physical inspection *(NEW)* | **Figure 10:** Substrate Intake Workflow |
| |   6. Payment Verification Process | **Comission** | Manual receipt validation by staff/owner | **Figure 11:** Payment Verification Workflow |
| |   7. 5-Stage Kanban Progression | **Comission** | Floor queue advancement across 5 columns | **Figure 12:** Kanban Floor Progression |
| |   8. Spoilage Logging & Alerting | **Comission** | Waste recording & dynamic ROP trigger *(NEW)* | **Figure 13:** Spoilage & ROP Alert Workflow |
| |   9. Automated BOM Deduction | **Comission** | Duplex sheet math & "Dala ang Papel" bypass | **Figure 14:** Automated BOM Deduction Flow |
| |   10. Report Generation Process | **Comission** | Operational KPI & financial CSV export | **Figure 15:** Report Generation Workflow |
| | **10.3 User Interface (UI) Design** | **Comission** | Responsive screenshots with Livewire reactivity: | |
| | • Registration Interface | **Comission** | Account registration with validation alerts | **Figure 16:** Registration Interface |
| | • Login Interface | **Comission** | Authenticated login & role redirect | **Figure 17:** Login Interface |
| | • Owner Setup Wizard | **Comission** | Initial shop profile onboarding wizard | **Figure 18:** Owner Setup Wizard |
| | • Owner Master Dashboard | **Comission** | Central management & module launcher hub | **Figure 19:** Owner Master Dashboard |
| | • App & Service Management Panel | **Comission** | Modular app installer & service toggles | **Figure 20:** Service Management Interface |
| | • Dynamic Service Config Hub | **Comission** | Rate adjustments & BOM consumption rules | **Figure 21:** Service Config Hub |
| | • Customer Ordering Wizard | **Comission** | Accordion parameter selector & live pricing *(SPLIT)* | **Figure 22:** Customer Ordering Wizard |
| | • Artwork Upload & Payment | **Comission** | Document validator & QR payment proof *(SPLIT)* | **Figure 23:** Artwork Upload & Payment |
| | • Customer Progress Stepper | **Comission** | Live 5-stage milestone tracker *(SPLIT)* | **Figure 24:** Live Progress Stepper |
| | • Staff Production Kanban Console | **Comission** | 5-stage workshop board & digital job tickets | **Figure 25:** Staff Kanban Console |
| | • Report Spoilage Interface | **Comission** | Workshop material waste recording modal | **Figure 26:** Spoilage Reporting Interface |
| | • Inventory Hub | **Comission** | Stock balances & low-stock warning badges | **Figure 27:** Inventory Hub |
| | • Sales & Analytics Hub | **Comission** | Financial trends, product mix, & CSV export | **Figure 28:** Sales & Analytics Hub |
| **11** | **Project Timeline & Budget** | **Comission** | Project schedule and financial budget: | |
| | • Project Schedule (Gantt Chart) | **Comission** | Sprint allocation timeline (July–Dec 2026) | **Figure 29:** Project Gantt Chart |
| | • Cost Estimation (Budget Table) | **Comission** | Realistic development & testing budget | **Table 4:** Estimated Project Budget |

---

## 3. Detailed Audit, Action Items, and Ready-to-Paste Text (By Specific Assignee)

---

### Section 1: Methodology (Introductory Overview)
* **Assigned to:** **Author**
* **Status:** Verified & Aligned.
* **Terminology Update:** Replaced references to *"research"* with *"project"*.

#### Ready-to-Paste Text:
```markdown
METHODOLOGY

        This chapter outlines the engineering procedures and developmental techniques employed in designing, constructing, and evaluating the Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment (Printify). It details the developmental project design, operational characterization of project participants, hardware and software materials, empirical data collection techniques, statistical analysis procedures, system validity measures, and ethical safeguards. Furthermore, it presents the architectural specifications, unified modeling diagrams, user interface designs, and financial budget that guided the systematic implementation and quality assessment of the platform.
```

---

### Section 2: Project Design
* **Assigned to:** **Author**
* **Status:** Heading corrected from `Research Design` to `Project Design` to adhere to BSIS capstone standards.
* **Terminology Update:** Reframed around Developmental Project Design (Richey & Klein framework).

#### Ready-to-Paste Text:
```markdown
Project Design

        This capstone project employs a Developmental Project Design, a framework centered on the systematic analysis, design, development, testing, and evaluation of a technological solution engineered to resolve operational problems. This project design is suited for this undertaking because the primary objective is to engineer a functional, integrated web-based management system uniting dynamic order intake, workshop floor job scheduling, inventory tracking, and automated replenishment.

Through this developmental approach, the project developers systematically establish operational requirements from commercial printing workflows, translate these requirements into modular software components, conduct rigorous verification, and evaluate the final system against the ISO 25010 software quality standard. This ensures that the resulting software artifact is practically grounded, technically sound, and empirically validated.
```

---

### Section 3: Project Participants and Materials
* **Assigned to:** **Author**
* **Status:** Verified & Aligned with the three primary user roles and physical shop ledgers.

#### Ready-to-Paste Text:
```markdown
Project Participants and Materials

        The project participants encompass the business owners, production staff, and retail customers of the printing-service establishment. The business owners and administrators provide authoritative operational requirements concerning dynamic service definitions, pricing formulas, material attributes, baseline stock levels, and commercial policies, while evaluating administrative controls, inventory telemetry, and reporting modules during system assessment. The production staff and workshop employees supply operational insights regarding job queueing, prepress inspection, task bottlenecks, machinery capacity, and raw material waste, while testing the practical usability of the five-stage production Kanban console and digital job tickets. Furthermore, customers and clients—comprising university students, faculty members, institutional organizations, and walk-in patrons—provide essential qualitative feedback regarding counter ordering hurdles, file submission issues, and tracking difficulties, while evaluating the customer self-service portal, the dynamic ordering wizard, and the live visual progress stepper.

The materials required for the development and assessment of the system encompass both physical and digital resources. Hardware materials include development workstations, laptops, and mobile smartphones equipped with internet connectivity to evaluate responsive layouts across various screen viewports. Software tools include integrated development environments, local web servers, database management systems, and web application frameworks (PHP, Laravel, Livewire, and Tailwind CSS) necessary for designing, building, and deploying the platform. In addition, the project developers utilize operational documents from partner printing establishments—such as manual paper job tickets, physical order receipts, pricing tariffs, and inventory ledgers—as empirical reference materials to ensure that the system accurately models real-world business workflows.
```

---

### Section 4: Data Collection
* **Assigned to:** **Author**
* **Status:** Verified & Aligned.

#### Ready-to-Paste Text:
```markdown
Data Collection

        The project developers gathered operational data using semi-structured interviews, direct workplace observations, document reviews, and survey questionnaires to establish the functional requirements of the printing business.

First, semi-structured interviews were conducted with business owners and production staff to identify current operational challenges, service pricing rules, job scheduling workflows, and inventory restocking habits. Second, direct observations of daily shop activities were carried out to inspect counter order intake, task delegation across workstations, and material handling on the workshop floor. Third, the project developers examined existing business documents, including sample receipts, manual job order slips, supplier invoices, and price lists, to accurately model pricing formulas and material consumption in the system. Finally, survey questionnaires based on the ISO 25010 software quality standard will be distributed to end-users (owners, staff, and customers) and IT experts during the evaluation phase to assess the system's performance, usability, and functional quality.
```

---

### Section 5: Data Analysis Procedure
* **Assigned to:** **Author**
* **Status:** Aligned with Table 1 (ISO 25010 4-point Likert Scale).

#### Ready-to-Paste Text:
```markdown
Data Analysis Procedure

        The qualitative data gathered from interviews, workplace observations, and document examinations were synthesized to establish the system requirement specifications and workflow architecture of the platform.

For the system evaluation, responses collected through the evaluation questionnaires will be analyzed quantitatively using descriptive statistics, specifically frequency counts, percentage distributions, and the weighted mean. The computed weighted means across the ISO 25010 criteria—functional suitability, usability, performance efficiency, and security—will be interpreted using the four-point Likert scale shown in Table 1.

Table 1. Likert Scale for Evaluating System Quality (ISO 25010 Standard)

| Scale / Value | Range of Mean | Descriptive Rating | Interpretation |
| :---: | :---: | :--- | :--- |
| 4 | 3.50 – 4.00 | Strongly Agree (SA) | Excellent / Highly Acceptable |
| 3 | 2.50 – 3.49 | Agree (A) | Good / Acceptable |
| 2 | 1.50 – 2.49 | Disagree (D) | Fair / Needs Improvement |
| 1 | 1.00 – 1.49 | Strongly Disagree (SD) | Poor / Unacceptable |
```

---

### Section 6: Validity
* **Assigned to:** **Comission**
* **Current Gaps in Draft:** Generic text lacking specific participant counts, testing venues, and evaluation criteria breakdown.
* **Action Required by Comission:** Paste the formal text below directly into the Google Doc.

#### Ready-to-Paste Text:
```markdown
Validity

        The validity of the software artifact will be established through a dual-tier evaluation framework comprising IT expert review and end-user acceptance testing (UAT). The evaluation assesses the platform against four primary characteristics of the ISO 25010 software quality standard: functional suitability, usability, performance efficiency, and security.

The expert evaluation panel will consist of five (5) IT professionals and academic faculty specializing in web engineering, relational database management, and human-computer interaction to rigorously assess architectural integrity and code security. Concurrently, end-user acceptance testing will be conducted across three (3) representative commercial printing establishments in Kidapawan City and nearby municipalities. The user evaluation cohort will include three (3) business owners or shop managers, six (6) production workshop staff, and twenty (20) retail clients (composed of tertiary students, faculty researchers, and commercial walk-in patrons). Quantitative assessments obtained through the validated ISO 25010 questionnaire, combined with qualitative feedback gathered during structured post-testing debriefing sessions, will direct iterative code refinement prior to final institutional deployment.
```

---

### Section 7: Ethical Considerations
* **Assigned to:** **Comission**
* **Current Gaps in Draft:** Generic privacy statement lacking real-world print shop safeguards (student thesis copyright, GCash payment screenshots, owner trade secrets).
* **Action Required by Comission:** Paste the expanded narrative below into the Google Doc.

#### Ready-to-Paste Text:
```markdown
Ethical Considerations

        The project developers will maintain strict adherence to ethical protocols and statutory mandates throughout the undertaking, specifically complying with Republic Act No. 10173, otherwise known as the Data Privacy Act of 2012. Prior to field inquiries, formal administrative clearance and a certificate to conduct the project will be secured from the University Ethics Review Committee alongside written endorsements from proprietor management. Participation across all interviews, workflow observations, and usability trials will remain entirely voluntary, substantiated by executed informed consent agreements outlining participant rights and withdrawal procedures without institutional penalty.

Data confidentiality and digital intellectual property safeguards are rigorously enforced within the system architecture. Customer-uploaded documents—notably academic theses, capstone manuscripts, and proprietary creative artwork—are stored in access-restricted server directories utilizing secure tokenized hashes to prevent unauthorized access. Furthermore, transaction proofs and payment reference screenshots submitted through mobile wallet gateways are encrypted at rest and accessible exclusively to authenticated business owners and assigned billing personnel. All collected project telemetry will be anonymized, reported strictly in aggregated academic formats, and retained securely on encrypted media before scheduled disposal upon formal manuscript acceptance.
```

---

### Section 8: Project Developmental Approach
* **Assigned to:** **Comission**
* **Current Gaps in Draft:** Textbook definitions of Agile with zero mention of the actual *Printify* modules or sprint breakdowns.
* **Action Required by Comission:** Update the text to reflect the 4 developmental sprints below and verify that Figure 2 is labeled correctly.

#### Ready-to-Paste Text:
```markdown
Project Developmental Approach

        This capstone project adopts the Agile Software Development Methodology as its developmental framework. Agile is an iterative and incremental approach that emphasizes modular engineering, continuous stakeholder collaboration, and rapid adaptability. This methodology is suited for engineering the Printify platform, which integrates dynamic service configuration, production job scheduling, automated Bill of Materials (BOM) inventory deductions, and business analytics.

The development lifecycle was organized into four distinct iterative sprints, progressing systematically through the following phases:

Plan. Requirements elicitation was conducted through structured interviews and observational studies at partner printing shops to establish operational workflows, pricing algorithms, job scheduling bottlenecks, and inventory tracking constraints.

Design. Software modeling diagrams—specifically the Entity Relationship Diagram (ERD), Class Diagram, Use Case Diagram, and operational Activity Diagrams—were constructed alongside responsive user interface wireframes to define structural constraints and interaction flows.

Develop. The platform was built iteratively across four targeted development sprints:
  - Sprint 1: User authentication, role-based access controls (RBAC), and the Business Owner Onboarding Setup Wizard.
  - Sprint 2: The Dynamic Service Configuration Hub, universal ordering engine, artwork file upload handling, and manual payment verification.
  - Sprint 3: The 5-stage workshop production Kanban console, digital job ticketing, and the automated Bill of Materials (BOM) inventory deduction engine.
  - Sprint 4: The Material Spoilage Logging console, dynamic Reorder Point (ROP) burn-rate alerting, and the Sales and Financial Analytics Hub.

Test. Functional unit and integration testing were conducted continuously during each sprint, verifying database transactional integrity, reactive Livewire component states, file upload validation, and inventory deduction formulas.

Release. The stabilized web application was deployed to a cloud staging environment, allowing authenticated business owners, production personnel, and retail customers to interact with the platform during usability testing.

Feedback. Qualitative observations and structured evaluator assessments gathered during user acceptance testing were analyzed to guide final code refinements and optimize user interface responsiveness.

Figure 2. Agile Software Development Methodology Lifecycle
```

---

### Section 9: System Requirement Specifications (SRS)
* **Assigned to:** **Author**
* **Status:** Expanded from 9 to 18 Functional Requirements to achieve strict 1-to-1 parity with the 18 Use Cases modeled in the Use Case Diagram (Figure 5). Tables 2 and 3 are 100% verified against the Laravel 11/13 backend.

#### Functional Requirements to Use Case Traceability Matrix

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

#### Itemized Behavioral Specifications (Input - Process - Output):

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

* **Table 2. Software Requirements Specification:** Covers PHP 8.2+, Laravel 11/13, Livewire 4, Flux UI, Tailwind CSS v4, MySQL 8.0, and Vite.
* **Table 3. Hardware Requirements Specification:** Covers Development Workstations, Cloud VPS Hosting, and Client End-User Devices.

---

### Section 10: Design of the Project (System Architecture & Software Modeling)

#### 10.1 Structural Modeling
* **Assigned to:** **Author**
* **Figures:**
  * **Figure 3. Entity Relationship Diagram (ERD):** Normalized 8-table relational schema (`users`, `print_shops`, `shop_services`, `orders`, `order_items`, `inventory_items`, `service_boms`, `stock_movements`).
  * **Figure 4. Class Diagram:** 11-class object-oriented domain hierarchy rooted in the `User` superclass.

#### 10.2 Behavioral Modeling — Use Case Diagram
* **Assigned to:** **Author**
* **Figure:**
  * **Figure 5. Use Case Diagram:** Segregates actor boundaries across Business Owner, Production Staff, and Customer with explicit `<<include>>` and `<<extend>>` dependencies.

---

#### 10.2 Behavioral Modeling — Activity Diagrams
* **Assigned to:** **Comission**
* **Current Gaps in Draft:**
  * Figure 12 (formerly Fig 25) misidentified Kanban as having 6 stages; it has **5 active production columns** in the code (`Queue`, `Printing`, `Finishing/Assembly`, `Quality Check`, `Ready for Pickup`).
  * Figure 14 (formerly Fig 26) omitted the duplex sheet formula and the *"Dala ang Papel"* bypass.
  * Three (3) operational workflows were completely missing.
* **Action Required by Comission:**
  1. Draw the **3 missing Activity Diagrams** in StarUML or Draw.io using the swimlane guides below.
  2. Update the narratives for all 10 Activity Diagrams (Figures 6 through 15) using the text below.

---

##### Ready-to-Paste Narrative for Figure 8 (NEW Activity Diagram):
```markdown
Figure 8. Dynamic Service and Bill of Materials Configuration Workflow

        The activity diagram depicts the administrative procedure for dynamically configuring service offerings and their underlying Bill of Materials (BOM) recipes without modifying application source code. The business owner accesses the authenticated Dynamic Service Configuration Hub from the administrative console. The owner initiates the workflow by selecting an existing service category or establishing a new print service catalog entry.

The configuration workflow requires defining baseline commercial parameters, including service titles, base preparation fees, incremental per-page rates for monochrome and full-color print passes, paper dimension profiles (Short, A4, Long), paper thickness options (70, 80, 100 gsm), and binding style surcharges. Crucially, the owner links operational service parameters to physical inventory stock items through the ServiceBoms mapping interface. The owner assigns unit consumption coefficients—such as one plastic ring spine per bound volume, two PVC clear cover sheets per booklet, or duplex paper consumption formulas. Upon submission, the platform performs data validation against numeric boundaries and relational foreign key constraints before persisting the configuration parameters into the database. These updated rates and material recipes are instantaneously ingested by the client-facing ordering engine, dynamically recalibrating real-time price calculations and inventory deduction rules across the entire platform.
```

##### Ready-to-Paste Narrative for Figure 10 (NEW Activity Diagram):
```markdown
Figure 10. Customer-Supplied Substrate Intake and Counter Verification Workflow

        This activity diagram illustrates the operational workflow governing customer-supplied paper handling ("Dala ang Papel"). When a customer elects to supply their own pre-printed or blank paper stock during online order submission, the system flags the resulting digital job ticket with an "Awaiting Paper Delivery" alert and zeroes out the raw paper material cost from the transaction subtotal. The customer subsequently brings the physical paper bundle to the shop service counter. 

Upon arrival, production personnel inspect the physical stock to verify sheet quantity, standard paper grammage (GSM), and sheet physical condition against the specifications stated on the digital ticket. If the delivered stock is damaged, crumpled, or insufficient in quantity, staff reject the submission and prompt the client to supply supplemental sheets or switch to in-house shop paper. Once the paper is verified, the staff member marks the job ticket as "Paper Received" within the production console. This administrative verification unlocks the order in the Kanban queue, automatically transitioning its operational state from intake hold to the active printing and finishing queue.
```

##### Ready-to-Paste Narrative for Figure 12 (Updated Kanban Progression):
```markdown
Figure 12. Five-Stage Production Floor Kanban Progression

        The activity diagram illustrates how confirmed orders advance through workshop production. Upon payment verification, production personnel access the internal console to inspect job queues sorted by urgency and scheduled fulfillment windows. Staff members open the digital job ticket to inspect customer specifications, assign machinery, record prepress review notes, and register counter intake for customer-supplied paper when applicable. The order then transitions across five distinct production columns: (1) In Queue, (2) Printing Pages, (3) Finishing and Assembly (encompassing spiral binding, sliding covers, booklet folding, or hardbound foil stamping), (4) Quality Inspection, and (5) Ready for Pickup. During quality control, an order that fails verification triggers a documented step-back action, returning the job ticket to an earlier stage alongside recorded rework rationales. Conversely, successful quality verification advances the order to the pickup bay and logs stage completion timestamps.
```

##### Ready-to-Paste Narrative for Figure 13 (NEW Activity Diagram):
```markdown
Figure 13. Material Spoilage Logging and Dynamic Reorder Point Alert Triggering

        This activity diagram illustrates the process of recording workshop material waste and evaluating dynamic inventory reorder thresholds. During production execution, machine malfunctions, paper misfeeds, or binding errors may result in ruined materials. Rather than allowing unaccounted stock discrepancies, production personnel access the Report Spoilage utility directly from the Kanban operations console. 

The staff member selects the damaged inventory item from the active material catalog, specifies the exact quantity wasted, and provides a descriptive operational rationale (such as printer jam or foil stamping blister) before submitting the entry. The inventory deduction service executes a database transaction that decrements the current on-hand balance of the specified inventory item and appends an immutable audit record to the stock movements ledger, capturing the previous balance, adjusted balance, reporting user ID, and timestamp. Following the balance update, the system automatically evaluates the remaining stock against the dynamic Reorder Point (ROP) threshold. If the on-hand stock falls to or below the safety threshold, the system immediately generates a visual low-stock warning badge on the Inventory Hub and broadcasts a high-priority replenishment recommendation banner across the Business Owner Master Dashboard.
```

##### Ready-to-Paste Narrative for Figure 14 (Updated Automated BOM Deduction):
```markdown
Figure 14. Automated Bill of Materials (BOM) Inventory Deduction and Restock Alerting

        This activity diagram models the operational logic governing automated raw material depletion and inventory reorder evaluation. When production staff advance an order to the terminal processing stage, the inventory deduction engine executes a transactional inventory audit. The system first evaluates the fulfillment classification of each line item. If an order item is designated as "Dala ang Papel" (customer-supplied substrate), raw paper stock deduction is bypassed completely, preserving shop ream counts. For standard production jobs, the system calculates consumption based on configured service BOM recipes. For document printing, paper sheet usage is derived from duplex printing rules where physical sheet consumption equals the ceiling of total pages divided by two, multiplied by the copy count. Required finishing elements—such as plastic comb spines, clear PVC covers, and chipboard backers—are dynamically deducted according to unit multipliers. The engine decrements physical on-hand balances, writes immutable audit rows to the stock movement ledger, and compares remaining levels against dynamic Reorder Point (ROP) thresholds. If stock dips below critical thresholds, visual restock warnings are instantaneously broadcast to the managerial dashboard.
```

---

#### 10.3 User Interface (UI) Design
* **Assigned to:** **Comission**
* **Current Gaps in Draft:**
  * Duplicate figure numbering (starting at Figure 5).
  * Lumped caption: *"Figure 13, 14, 15 . Customer Ordering Wizard and Live Five-Stage Progress Stepper"*.
  * Omission of Livewire 4 dynamic reactive pricing explanation.
* **Action Required by Comission:**
  1. Decouple into three distinct figures (Figures 22, 23, and 24).
  2. Attach individual high-resolution screenshots for each interface.
  3. Apply the ready-to-paste narratives below across Figures 16 to 28.

---

##### Ready-to-Paste Decoupled Narratives for Customer Ordering Flow:
```markdown
Figure 22. Customer Dynamic Ordering and Parameter Selection Wizard

        Figure 22 illustrates the user interface of the Customer Ordering Wizard, which directs clients through the specification and customization of their print requirements. The interface adopts a structured accordion layout that organizes configuration parameters into discrete logical panels: service selection, document dimensions (Short, A4, Long), paper grammage (70 gsm, 80 gsm, 100 gsm), color mode (monochrome or colored), printing sides (simplex or duplex back-to-back), binding styles (plastic comb, sliding folder, softbound, or hardbound), and total copy counts. The interface leverages real-time reactive state management through Livewire 4, dynamically recalculating the itemized cost breakdown and estimated subtotal as the customer alters parameters, eliminating the need for manual page reloads. In addition, the interface incorporates the customer-supplied paper toggle ("Dala ang Papel"), which instantly deducts raw paper material expenses from the running price quote when activated, ensuring transparent pricing before order submission.

Figure 23. Artwork File Submission and Digital Payment Interface

        Figure 23 presents the user interface for digital file submission and payment reference processing. The file submission module incorporates client-side and server-side validation rules that verify file formats (accepting PDF, DOCX, and high-resolution raster images) and enforce file size constraints. A visual upload progress indicator displays upload completion, while an interactive file preview card allows the customer to verify document completeness prior to final submission. Following file attachment, the interface presents payment instructions for counter payment or mobile wallet options (GCash and Maya), alongside a dedicated QR code display. The customer inputs their alphanumeric transaction reference number and uploads a screenshot of the digital payment confirmation. Once submitted, the system bundles the document payload, payment references, and calculated quotations into an immutable pending order record, redirecting the user to their personal tracking dashboard.

Figure 24. Customer Live Five-Stage Production Progress Stepper

        Figure 24 illustrates the user interface of the Customer Live Production Progress Stepper. Designed to provide transparent operational telemetry and mitigate customer counter inquiries, the screen visualizes the order's real-time journey through five sequential production milestones: (1) In Queue, (2) Printing Pages, (3) Finishing and Assembly, (4) Quality Inspection, and (5) Ready for Pickup. The interface utilizes a color-coded stepper component where completed stages are highlighted with checkmark indicators and timestamps, the current active workstation stage is illuminated with an animated status badge, and pending milestones remain subdued. Alongside the visual stepper, the screen displays a summary card detailing assigned job ticket numbers, fulfillment method (pickup or counter handoff), verified payment status, and estimated completion timeframes. When workshop personnel advance the order stage on the floor Kanban console, the customer tracking stepper dynamically reflects the transition, providing clear visibility until the order is claimed.
```

---

### Section 11: Project Timeline & Budget

#### 11.1 Project Schedule (Gantt Chart)
* **Assigned to:** **Comission**
* **Action Required:** Remove the dual label *"Figure 3 & 4. Gantt Chart"*. Label as a single sequential diagram:
  ```markdown
  Figure 29. Project Development Gantt Chart (July–December 2026)
  ```

#### 11.2 Project Cost Estimation (Budget Table)
* **Assigned to:** **Comission**
* **Current Gaps in Draft:** Unrealistic entries (fictional GCash sandbox fees, gross underestimation of physical testing supplies).
* **Action Required:** Replace Table 3 with the revised software engineering budget below (Table 4).

#### Ready-to-Paste Budget Table:
```markdown
Table 4. Estimated Project Development Budget

| Category / Item | Detailed Description | Unit Cost (PHP) | Quantity | Total Cost (PHP) |
| :--- | :--- | :--- | :--- | :--- |
| **1. Requirements Analysis** | | | | |
| a. Field Travel & Site Visits | Transportation to partner printing establishments | 100.00 | 5 Trips | 500.00 |
| b. Instrument Reproduction | Printing of interview protocols and consent agreements | 100.00 | 3 Sets | 300.00 |
| **2. System Design & Modeling** | | | | |
| a. Architecture & UML Tools | Lucidchart / StarUML academic diagramming workspace | 0.00 | 1 Lot | 0.00 (Open/Academic) |
| b. UI/UX Prototyping Assets | Digital wireframing and vector graphic assets | 0.00 | 1 Lot | 0.00 (Open Source) |
| **3. Development & Staging** | | | | |
| a. Custom Domain Name | 1-Year .com/.ph domain registration with SSL certificate | 850.00 | 1 Year | 850.00 |
| b. Cloud VPS Hosting | Virtual Private Server (2 vCPUs, 2GB RAM) for staging | 550.00 | 2 Months | 1,100.00 |
| c. Local Server Infrastructure | Backup external storage media for database snapshots | 650.00 | 1 Unit | 650.00 |
| **4. System Testing & Consumables** | | | | |
| a. Paper Stock Consumables | Sample reams (Short, A4, Long; 70 & 80 gsm) for BOM testing | 280.00 | 4 Reams | 1,120.00 |
| b. Finishing Consumables | Plastic ring combs, sliding report covers, & PVC sheets | 350.00 | 1 Lot | 350.00 |
| c. Hardware Test Printing | Toner ink depletion trials for print engine verification | 800.00 | 1 Lot | 800.00 |
| **5. Connectivity & Utilities** | | | | |
| a. Broadband Internet | High-speed data connectivity for development & testing | 700.00 | 2 Months | 1,400.00 |
| **6. Evaluation & Administration** | | | | |
| a. Survey Instruments | Reproduction of ISO 25010 evaluation questionnaires | 150.00 | 34 Sets | 510.00 |
| b. Evaluator Honoraria | Token of appreciation for 5 expert IT evaluation panelists | 300.00 | 5 Panelists | 1,500.00 |
| c. Manuscript Review Copies | Draft printing and binding for capstone defense review | 234.00 | 4 Copies | 920.00 |
| **Total Estimated Budget** | | | | **PHP 10,000.00** |
```

---

## 4. Swimlane Guides for Comission (Drawing the 3 Missing Activity Diagrams)

Comission can use these exact step-by-step swimlane specifications to draw the diagrams in Draw.io or StarUML:

### A. Figure 8: Dynamic Service and BOM Configuration Workflow
* **Swimlane 1: Business Owner**
  1. *[Start]* $\rightarrow$ Logs in to Business Owner Portal.
  2. Opens **Service Configuration Hub**.
  3. Selects service offering to configure (e.g., *Document Printing* or *Thesis Hardbound*).
  4. Inputs pricing rules: base fee, per-page rates (B&W/Color), rush fee multiplier, duplex discount %.
  5. Selects linked raw inventory items (e.g., A4 80gsm ream, PVC sheets, ring spines) from dropdown.
  6. Sets unit consumption multipliers (e.g., 1 spine per copy, 2 PVC sheets per booklet).
  7. Clicks *"Save Service & BOM Configuration"*.
* **Swimlane 2: Configuration & Validation Engine (System)**
  1. Validates form inputs via Livewire server-side request.
  2. *Decision Diamond:* "Validation Passed? (Non-negative values, active inventory items exist)"
     - **[No]**: Returns inline validation error alerts highlighting erroneous input fields $\rightarrow$ Loops back to Owner step 4.
     - **[Yes]**: Initiates atomic database transaction.
  3. Writes/updates service record in `shop_services` table.
  4. Synchronizes linked material recipes in `service_boms` table.
  5. Clears cached pricing parameters in memory.
  6. Displays confirmation toast notification.
* **Swimlane 3: Customer Ordering Engine**
  1. Real-time pricing calculator immediately reflects updated rates and material requirements without code redeployment $\rightarrow$ *[End]*.

---

### B. Figure 10: Customer-Supplied Substrate Intake & Verification ("Dala ang Papel")
* **Swimlane 1: Customer**
  1. *[Start]* $\rightarrow$ Selects *"Dala ang Papel / Cover Only"* during checkout $\rightarrow$ Submits order online.
  2. Brings physical paper bundle to print shop service counter.
  3. Presents digital Order ID / QR code to counter staff.
  4. *(On Rejection)* Supplies replacement sheets or approves shift to shop paper.
  5. Leaves paper bundle with counter staff and monitors order progress online $\rightarrow$ *[End]*.
* **Swimlane 2: Production Staff (Counter / Workshop)**
  1. Retrieves Order ID on the internal console.
  2. Receives and physically inspects paper stock (counts sheets, checks GSM weight, checks edge condition).
  3. *Decision Diamond:* "Meets Specifications & Sufficient Count?"
     - **[No]**: Explains defects to customer; issues physical rejection note; requests replacement.
     - **[Yes]**: Clicks *"Mark Paper Received"* on digital job ticket.
  4. Places verified physical paper bundle into designated workshop staging bin labeled with Order ID.
* **Swimlane 3: System Engine**
  1. Sets order subtotal with zero-paper deduction; flags ticket: `awaiting_paper = true`.
  2. On staff confirmation: updates database attribute `is_paper_received = true` with timestamp.
  3. Transitions order status on Kanban board from *Hold/Pending Paper* to *In Queue*.
  4. Dispatches live status update toast/notification to Customer Dashboard.

---

### C. Figure 13: Material Spoilage Logging & Real-Time ROP Alert Triggering
* **Swimlane 1: Production Staff**
  1. *[Start]* $\rightarrow$ Encounters material damage during printing or binding (e.g., printer jam, misaligned cover).
  2. Clicks *"Report Spoilage"* on the Production Operations Console.
  3. Selects damaged supply item from drop-down (e.g., *70gsm Long Paper*, *Hardbound Chipboard*).
  4. Enters quantity damaged and types specific operational reason.
  5. Clicks *"Submit Spoilage Report"*.
* **Swimlane 2: Inventory Deduction Engine (System)**
  1. Validates inputs (`spoilageQty > 0`, item exists).
  2. Decrements `inventory_items.current_stock`.
  3. Inserts append-only record into `stock_movements` table with `movement_type = 'spoilage'`.
  4. *Decision Diamond:* "Is New Balance $\le$ Dynamic Reorder Point (ROP)?"
     - **[No]**: Closes modal; emits confirmation toast to staff $\rightarrow$ *[End: Routine Spoilage Recorded]*.
     - **[Yes]**: Flags item status as `low_stock`; logs replenishment recommendation trigger.
* **Swimlane 3: Business Owner Dashboard (Telemetry & Alerts)**
  1. Receives real-time alert trigger.
  2. Updates visual KPI card on **Inventory Hub** (item flagged with Amber/Red badge).
  3. Displays Restocking Banner on **Owner Master Dashboard** with recommended supplier reorder quantity $\rightarrow$ *[End: Restock Alert Active]*.
