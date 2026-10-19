# Appendix F. Task Allocation Matrix (TAM)

**University of Southern Mindanao**  
College of Science and Mathematics — Department of Information Technology  
Kabacan, Cotabato, Philippines  

---

## 1. Document Overview & Administrative Header

| Parameter | Details |
| :--- | :--- |
| **Project Title** | **Printify: Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment** |
| **Degree Program** | Bachelor of Science in Information Systems (BSIS) |
| **Institutional Form Code** | `USM-EDR-F20-Rev.0.2025.09.08` |
| **Adviser** | **DANILYN P. FLORES** |
| **Project Developers** | **CHRISTIAN JAMES J. PEREZ** (Lead Systems Architect & Full-Stack/Backend Engineer)<br>**KAYE P. COMISSION** (Systems Analyst, UI/UX Frontend Developer & Technical Documentation Lead) |
| **Overall Allocation Ratio** | **50.0% (Christian James J. Perez) : 50.0% (Kaye P. Comission)** |

---

## 2. Comprehensive Task Allocation Matrix (Expanded Formulation)

This detailed formulation breaks down each project component—including the five (5) core system modules, the three (3) literature review domains, and the three (3) methodology phases—into explicit, defensible responsibilities with verifiable deliverables.

| Project Component | Member/s Assigned & % Contribution | Specific Action Items & Deliverables |
| :--- | :--- | :--- |
| **1. Problem Statement and Objectives** | **Perez: 60%**<br>**Comission: 40%** | **Member A (Christian James J. Perez):**<br>• Identified and formulated the core technical challenges: manual order calculation overhead, unmonitored shop floor queue delays, and decoupled inventory stockouts.<br>• Authored the General Objective and the five (5) Specific Objectives aligning system engineering with empirical printing shop constraints.<br>• Defined the architectural boundaries and operational scope of the automated replenishment and scheduling engines.<br><br>**Member B (Kaye P. Comission):**<br>• Conducted initial field inquiries and stakeholder interviews at commercial print shops in Kabacan, Cotabato to document baseline operational bottlenecks.<br>• Synthesized end-user pain points into cohesive narrative paragraphs conforming to USM BSIS non-itemized Statement of the Problem conventions.<br>• Drafted the Significance of the Project section for shop owners, production employees, customers, and future developers. |
| **2. Related Literature — Topic A:**<br>*Dynamic Order Management & Web-to-Print Systems* | **Perez: 30%**<br>**Comission: 70%** | **Member B (Kaye P. Comission):**<br>• Conducted comprehensive academic literature searches across Google Scholar, IEEE, and DOAJ for modern Web-to-Print paradigms and self-service ordering portals.<br>• Authored in-depth critical evaluations and summaries for cited authors: *Apriadi & Dewi (2026)*, *Susila et al. (2025)*, *Saragih et al. (2026)*, *Kurniawan et al. (2025)*, *Joshi et al. (2026)*, *Bhaktha et al. (2023)*, and *Dhiman (2021)*.<br>• Synthesized how digital quoting and structured prepress intake reduce counter customer wait times and prevent intake specification errors.<br><br>**Member A (Christian James J. Perez):**<br>• Evaluated the technical architectures in reviewed literature against multi-variable pricing constraints (dimensional sizing, substrate weight, duplex print logic).<br>• Identified the technical research gap regarding dynamic quotation engines capable of instant mathematical recalculation upon asset configuration. |
| **3. Related Literature — Topic B:**<br>*Job Scheduling & Shop Floor Queue Control* | **Perez: 60%**<br>**Comission: 40%** | **Member A (Christian James J. Perez):**<br>• Investigated operational scheduling algorithms, state-machine queue transitions, and production queue visibility frameworks in industrial environments.<br>• Analyzed foundational studies: *Smagowicz et al. (2024)* on operational scheduling, *Zonta et al. (2022)* on data availability in operational decisions, and *Destouet et al. (2023)* on scheduling decision support.<br>• Formulated the theoretical rationale for establishing a 5-stage sequential Kanban state machine (Pending, Prepress, Printing, Finishing, Ready for Pickup) to replace static paper job tickets.<br><br>**Member B (Kaye P. Comission):**<br>• Gathered and synthesized empirical studies on shop-floor tracking and worker communication: *Hernada et al. (2024)* and *Jiang et al. (2022)*.<br>• Documented the operational delays caused by verbal job handoffs and uncoordinated prepress revisions in physical print shops.<br>• Encoded citations, standardized paragraph formatting, and verified publication metrics. |
| **4. Related Literature — Topic C:**<br>*Inventory Management with Automated Replenishment & Dynamic ROP* | **Perez: 60%**<br>**Comission: 40%** | **Member A (Christian James J. Perez):**<br>• Researched quantitative inventory control models, automated replenishment triggers, and rolling burn rate formulations: *Rolf et al. (2023)* on data-driven supply chain decision-making and *Munyaka & Yadavalli (2022)* on inventory optimization.<br>• Derived the mathematical framework for the Dynamic Reorder Point ($ROP = d \times L + SS$) and evaluated real-time Bill of Materials (BOM) stock deduction mechanics.<br>• Analyzed algorithmic supply chain automation literature: *Ugbebor et al. (2024)*, *Jebbor et al. (2023)*, and *Albayrak Ünal et al. (2023)*.<br><br>**Member B (Kaye P. Comission):**<br>• Surveyed empirical SME inventory studies: *Tanaman et al. (2023)* on retail inventory challenges and *Asim & Arbaiy (2026)* on computerized stock control.<br>• Researched literature on material spoilage overhead, carrying costs, and the operational risks of paper substrate stockouts.<br>• Encoded literature citations, compiled publication metadata, and cross-referenced author years. |
| **5. Synthesis of Related Literature** | **Perez: 40%**<br>**Comission: 60%** | **Member B (Kaye P. Comission):**<br>• Authored the comprehensive literature synthesis integrating findings from all twenty (20) empirical and theoretical studies.<br>• Articulated the operational evolution of the printing industry from disparate, manual bookkeeping to unified, cloud-based workflow platforms.<br>• Structured the narrative bridging user interaction, floor scheduling, and material availability.<br><br>**Member A (Christian James J. Perez):**<br>• Articulated the primary research and engineering gap: the persistent technical decoupling between customer ordering interfaces, shop floor Kanban queues, and automated inventory replenishment.<br>• Grounded the design of Printify as an integrated architectural solution resolving this identified industry gap. |
| **6. Conceptual Framework (5-Stage IPO Model)** | **Perez: 50%**<br>**Comission: 50%** | **Collaborative Effort:**<br>• Jointly structured the 5-Stage Input-Process-Output-Outcome-Impact (IPO) model conforming to the USM institutional standard.<br><br>**Member A (Christian James J. Perez):**<br>• Defined the technical parameters for the **Input Stage** (system architecture, Laravel/Livewire stack, relational database schema, dynamic formulas) and **Process Stage** (multi-tier verification, Kanban state transitions, dynamic burn rate calculation, automated Pest testing).<br>• Specified the verifiable technical deliverables under the **Output Stage** (Printify platform, Functional Test Summary Report, Requirements Traceability Matrix).<br><br>**Member B (Kaye P. Comission):**<br>• Formulated the **Outcome Stage** (streamlined order processing, reduction in prepress misprints, elimination of paper stockouts) and **Impact Stage** (long-term operational resilience, maximized profit margins, scalable micro-enterprise capacity).<br>• Designed and illustrated the formal conceptual framework diagram (*Figure 1: Conceptual Framework of Printify*). |
| **7. Technology Design — Module 1:**<br>*User Onboarding, Authentication & Role-Based Access Control (RBAC)* | **Perez: 40%**<br>**Comission: 60% (Lead)** | **Member B (Kaye P. Comission — UI/UX & Frontend Lead):**<br>• Prototyped and built the user onboarding views, registration forms, login layouts, and password recovery interfaces.<br>• Designed the responsive customer profile management interface, navigation bars, and role-differentiated menu sidebars.<br>• Structured user experience flows and visual access indicators for Customers, Production Staff, and Business Owners.<br><br>**Member A (Christian James J. Perez — Backend & Security Support):**<br>• Integrated Fortify authentication backend services, session security, and bcrypt credential hashing.<br>• Implemented role authorization middleware guards and route policies enforcing strict boundary separation across user tiers.<br>• Structured database migrations for `users`, role enumerations, and authentication audit logs. |
| **8. Technology Design — Module 2:**<br>*Dynamic Service Configuration & Catalog Pricing Engine* | **Perez: 70% (Lead)**<br>**Comission: 30%** | **Member A (Christian James J. Perez — Lead Backend Architect):**<br>• Engineered the multi-variable dynamic pricing calculation engine handling dimensional matrices, substrate weights, and duplex multipliers.<br>• Designed the relational database architecture linking configurable service options to physical inventory Bill of Materials (BOM) items.<br>• Implemented administrative controllers and Eloquent models for dynamic service provisioning, price updates, and option toggling.<br><br>**Member B (Kaye P. Comission — UI/UX Layout Support):**<br>• Designed the service catalog management dashboard views, pricing tier modals, and substrate specification cards.<br>• Structured the visual hierarchy for service categories, add-on finishes, and turnaround time options.<br>• Documented dynamic pricing matrices and captured administrative catalog interface screenshots. |
| **9. Technology Design — Module 3:**<br>*Customer Self-Service Ordering & Prepress Intake* | **Perez: 40%**<br>**Comission: 60% (Lead)** | **Member B (Kaye P. Comission — UI/UX & Interaction Lead):**<br>• Prototyped and developed the multi-step customer ordering wizard, digital asset upload dropzone, and custom specification selectors.<br>• Built the real-time quote preview component displaying price updates synchronously upon user option selections.<br>• Designed the customer job tracking portal featuring a visual 5-stage progress stepper and digital downpayment receipt upload modal.<br><br>**Member A (Christian James J. Perez — Backend Pipeline Support):**<br>• Engineered the secure file upload pipeline with MIME-type verification, storage disk isolation, and prepress asset validation.<br>• Implemented transactional order persistence, reference tracking code generation, and payment proof handling.<br>• Coded duplex calculation logic and page-count verification routines. |
| **10. Technology Design — Module 4:**<br>*Production Floor 5-Stage Kanban Scheduling & Counter Intake* | **Perez: 70% (Lead)**<br>**Comission: 30%** | **Member A (Christian James J. Perez — Lead Production Systems Engineer):**<br>• Engineered the 5-stage Kanban state machine governing order progression: *Pending*, *Prepress*, *Printing*, *Finishing*, and *Ready for Pickup/Claimed*.<br>• Programmed database transaction locks, concurrency controls, and automated timestamp logging across stage transitions.<br>• Implemented the quality control rework loop, rejection reason tracking, payment verification workflows, and staff task assignment logic.<br><br>**Member B (Kaye P. Comission — UI/UX & Flow Prototyping):**<br>• Prototyped the visual drag-and-drop Kanban board layout with responsive swimlanes, job detail cards, and urgency color tags.<br>• Designed the walk-in counter order intake interface allowing staff to rapidly log on-site customer orders.<br>• Captured production floor UI screenshots and documented operator interaction flows. |
| **11. Technology Design — Module 5:**<br>*Automated Inventory Telemetry, Spoilage & Financial Analytics* | **Perez: 70% (Lead)**<br>**Comission: 30%** | **Member A (Christian James J. Perez — Lead Telemetry & Algorithms Engineer):**<br>• Engineered the real-time Bill of Materials (BOM) inventory consumption deduction triggered synchronously as jobs advance through Kanban stages.<br>• Implemented the Dynamic Reorder Point algorithm ($ROP = d \times L + SS$) using rolling 30-day historical material burn rates.<br>• Programmed automated low-stock banner trigger logic, material spoilage audit log tracking, and financial metric aggregation routines (Gross Revenue, COGS, Net Margin, Spoilage Loss) with CSV ledger exports.<br><br>**Member B (Kaye P. Comission — UI/UX Dashboard Support):**<br>• Designed the owner executive analytics dashboard layout featuring key performance indicator (KPI) metric cards and restock alerts.<br>• Built the inventory ledger data tables, material movement history views, and staff spoilage submission modal.<br>• Formatted analytical reporting views and verified filter controls. |
| **12. Methodology — Phase 1:**<br>*Developmental Research Design & Operational Data Gathering* | **Perez: 40%**<br>**Comission: 60%** | **Member B (Kaye P. Comission):**<br>• Prepared qualitative interview guides and conducted shop floor observations at local commercial print shops.<br>• Transcribed operational workflow procedures, recorded machine operating bottlenecks, and documented material waste patterns.<br>• Synthesized qualitative field data into formal operational requirements and user persona profiles.<br><br>**Member A (Christian James J. Perez):**<br>• Analyzed gathered operational data to establish technical software specifications, performance criteria, and hardware/software design constraints.<br>• Structured the developmental research design and mapped operational problems directly to system architectural features. |
| **13. Methodology — Phase 2:**<br>*System Modeling & Activity Diagrams (18 UML Diagrams)* | **Perez: 30%**<br>**Comission: 70%** | **Member B (Kaye P. Comission — Lead System Modeler):**<br>• Drafted, illustrated, and finalized all eighteen (18) UML Activity Diagrams corresponding to all 18 functional requirements (`FR-01` to `FR-18`).<br>• Structured multi-lane swimlanes clearly delineating responsibilities among Customer, Counter/Production Staff, Business Owner, and System.<br>• Ensured visual clarity, standard UML syntax compliance, and cohesive layout across all diagrams.<br><br>**Member A (Christian James J. Perez — Technical Logic Verification):**<br>• Audited all decision nodes, guard conditions, and alternate branch paths in the activity diagrams against backend database transactions and route controllers.<br>• Validated error handling paths (e.g., payment rejection, material stockout, rework loops) against server-side logic. |
| **14. Methodology — Phase 3:**<br>*Multi-Tier Software Verification & Black-Box Functional Testing* | **Perez: 70%**<br>**Comission: 30%** | **Member A (Christian James J. Perez — Lead SQA Engineer):**<br>• Authored and executed the automated test suite comprising 28 Pest feature test cases with 90 programmatic assertions across all five modules.<br>• Formulated the Black-Box Functional Test Plan detailing 25 exhaustive scenario test cases (`TC-MOD1-01` to `TC-MOD5-05`) with rigorous preconditions, test inputs, step-by-step procedures, and expected outputs.<br>• Programmed automated edge case simulations (zero inventory, corrupt file uploads, invalid discount rules, concurrent Kanban status updates).<br><br>**Member B (Kaye P. Comission — Functional Verification Support):**<br>• Participated in manual functional scenario walkthroughs across all 25 test cases, recording actual outputs and UI behavioral responses.<br>• Audited and compiled the Requirements Traceability Matrix linking all 18 functional requirements to specific test cases and outcomes.<br>• Authored the Functional Test Summary Report and test execution documentation in Chapter 4 and Appendix. |
| **15. Formatting & Referencing (APA 7th Edition)** | **Perez: 30%**<br>**Comission: 70%** | **Member B (Kaye P. Comission — Lead Formatting Specialist):**<br>• Standardized manuscript margins (1.5 in left, 1.0 in top/bottom/right), font hierarchies (Times New Roman 12 pt), line spacing (2.0), and paragraph indentation per USM guidelines.<br>• Verified all in-text citations and compiled the complete Literature Cited section in strict compliance with APA 7th edition formatting.<br>• Formatted all formal tables, captions, and appendices according to institutional standards.<br><br>**Member A (Christian James J. Perez — Technical Formatting Support):**<br>• Verified mathematical equation formatting ($ROP = d \times L + SS$), source code snippets, and database entity naming conventions.<br>• Formatted automated test execution logs, assertion summary tables, and technical schema diagrams. |
| **16. Final Editing & Proofreading** | **Perez: 40%**<br>**Comission: 60%** | **Member B (Kaye P. Comission — Editorial Lead):**<br>• Conducted thorough grammatical reviews, eliminated typographical errors, and enhanced scholarly sentence flow across Chapters 1 to 5.<br>• Ensured strict institutional adherence to BSIS capstone guidelines (e.g., replacing "researchers" with "project developers" and "study" with "capstone project").<br>• Standardized table numbering, figure captions, and cross-references throughout the entire manuscript.<br><br>**Member A (Christian James J. Perez — Technical Review):**<br>• Audited the entire paper to ensure absolute consistency across functional requirements (`FR-01` to `FR-18`), test case IDs (`TC-MOD1-01` to `TC-MOD5-05`), and database entities.<br>• Verified technical accuracy and mathematical precision in all formulas and algorithmic descriptions. |
| **17. Oral Presentation Preparation** | **Perez: 50%**<br>**Comission: 50%** | **Collaborative Preparation:**<br>• Co-developed the oral defense presentation slide deck.<br><br>**Member A (Christian James J. Perez):**<br>• Prepared technical slides detailing system architecture, dynamic pricing formulas, Kanban state machine logic, Dynamic ROP calculations, and automated test suite results.<br>• Configured the live technical demonstration environment for interactive system walkthroughs during the panel examination.<br><br>**Member B (Kaye P. Comission):**<br>• Designed the visual presentation theme, structured the problem statement and literature synthesis slides, and prepared UI/UX workflow walkthroughs.<br>• Organized the live demonstration storyboard illustrating real-time order tracking and counter intake workflows. |

---

## 3. Mathematical Verification of 50:50 Contribution Balance

The table below demonstrates the exact mathematical derivation showing that both project developers achieved an identical cumulative contribution of **50.0%**.

| Item # | Evaluated Project Component | Member A (Perez) | Member B (Comission) | Variance from 50% Base |
| :---: | :--- | :---: | :---: | :---: |
| 1 | Problem statement and objectives | **60%** | **40%** | +10% / -10% |
| 2 | Related literature — Topic A (Dynamic Order) | **30%** | **70%** | -20% / +20% |
| 3 | Related literature — Topic B (Job Scheduling) | **60%** | **40%** | +10% / -10% |
| 4 | Related literature — Topic C (Inventory Management & ROP) | **60%** | **40%** | +10% / -10% |
| 5 | Synthesis of related literature | **40%** | **60%** | -10% / +10% |
| 6 | Theoretical / Conceptual Framework (5-Stage IPO) | **50%** | **50%** | 0% / 0% |
| 7 | Technology Design — Module 1 (Auth & RBAC) | **40%** | **60%** | -10% / +10% |
| 8 | Technology Design — Module 2 (Catalog & Dynamic Pricing) | **70%** | **30%** | +20% / -20% |
| 9 | Technology Design — Module 3 (Customer Ordering Wizard) | **40%** | **60%** | -10% / +10% |
| 10 | Technology Design — Module 4 (5-Stage Kanban Scheduling) | **70%** | **30%** | +20% / -20% |
| 11 | Technology Design — Module 5 (Inventory Telemetry & Analytics) | **70%** | **30%** | +20% / -20% |
| 12 | Methodology — Phase 1 (Research Design & Field Data) | **40%** | **60%** | -10% / +10% |
| 13 | Methodology — Phase 2 (18 UML Activity Diagrams) | **30%** | **70%** | -20% / +20% |
| 14 | Methodology — Phase 3 (Software Verification & 25 TCs) | **70%** | **30%** | +20% / -20% |
| 15 | Formatting & Referencing (APA 7th Edition) | **30%** | **70%** | -20% / +20% |
| 16 | Final editing & proofreading | **40%** | **60%** | -10% / +10% |
| 17 | Oral presentation preparation | **50%** | **50%** | 0% / 0% |
| **TOTAL** | **Cumulative Sum Across 17 Components** | **850%** | **850%** | **Net Sum = 0%** |
| **MEAN** | **Overall Project Contribution Ratio** | **50.00%** | **50.00%** | **EXACT 50:50 SPLIT** |

$$\text{Overall Contribution (Member A)} = \frac{850\%}{17} = 50.00\%$$
$$\text{Overall Contribution (Member B)} = \frac{850\%}{17} = 50.00\%$$

---

## 4. Default 13-Row Template Formulation (Direct Word Document Alignment)

If the panel or adviser requires strict adherence to the default 13-row table structure found in the original institutional template without adding separate sub-rows for individual modules, the table below provides the consolidated version.

| Thesis components *(these can be revised, as appropriate)* | Member/s Assigned *(For shared tasks, indicate % contribution)* | Description |
| :--- | :--- | :--- |
| **Problem statement and objective** | Member A — 70%<br>Member B — 30% | **Member A:** Formulated core technical problems (manual pricing overhead, unmonitored queue delays, decoupled inventory stockouts), authored general objective and 5 specific objectives, defined automated replenishment and scheduling scope.<br>**Member B:** Conducted shop floor field inquiries, documented customer pain points, drafted cohesive non-itemized problem narrative, authored significance of the project. |
| **Related literature** | Member A — 30%<br>Member B — 70% | **Read about [Topic A: Dynamic Order & Web-to-Print Systems]:**<br>**Member B:** Conducted literature searches, reviewed key studies (*Apriadi & Dewi, Susila et al., Saragih et al., Bhaktha et al., Dhiman*), authored detailed summaries on self-service portals and digital prepress intake.<br>**Member A:** Evaluated architectural viability for multi-variable print pricing, identified technical research gaps regarding dynamic recalculation engines. |
| **Related literature** | Member A — 60%<br>Member B — 40% | **Read about [Topic B: Job Scheduling & Shop Floor Queue Control]:**<br>**Member A:** Analyzed advanced production scheduling models and queue optimization (*Smagowicz et al., Zonta et al., Destouet et al.*), derived 5-stage sequential Kanban framework to replace physical job tickets.<br>**Member B:** Gathered and reviewed shop floor tracking studies (*Hernada et al., Jiang et al.*), documented turnaround delays from verbal handoffs, encoded citations and formatting. |
| **Related literature** | Member A — 60%<br>Member B — 40% | **Read about [Topic C: Inventory Management with Automated Replenishment]:**<br>**Member A:** Reviewed quantitative inventory models (*Rolf et al., Munyaka & Yadavalli, Albayrak Ünal et al.*), derived Dynamic Reorder Point ($ROP = d \times L + SS$) and real-time BOM stock deduction logic.<br>**Member B:** Surveyed SME empirical inventory studies (*Tanaman et al., Asim & Arbaiy*), evaluated paper stockout impacts and carrying cost risks, organized citations. |
| **Synthesis of literature** | Member A — 50%<br>Member B — 50% | **Collaborative Synthesis:**<br>**Member B:** Synthesized overarching operational themes across all 20 reviewed studies, highlighting the transition from fragmented manual operations to integrated cloud workflows.<br>**Member A:** Formulated the primary technical research gap (lack of unified platforms synchronizing dynamic pricing, Kanban queues, and inventory burn rates), aligning theoretical insights with Printify's architecture. |
| **Theoretical/Conceptual Framework** | Member A — 50%<br>Member B — 50% | **Collaboratively** formulated the 5-Stage IPO model (Input-Process-Output-Outcome-Impact) conforming to USM institutional standards.<br>**Member A:** Defined technical inputs (Laravel/Livewire architecture, dynamic formulas, relational schema), process verification methods, and verifiable outputs (Printify system, Test Summary Report, Traceability Matrix).<br>**Member B:** Formulated operational outcomes, organizational impacts (resilience, waste reduction), and illustrated *Figure 1: Conceptual Framework*. |
| **Intervention/Technology Design** | Member A — 70%<br>Member B — 30% | **Collaboratively** designed and developed the five (5) core system modules:<br>• **Member A (Lead Systems Architect & Full-Stack Developer — 70%):** Programmed the core backend logic, database migrations, Fortify authentication, multi-variable dynamic pricing engine, 5-stage Kanban state machine, transactional queue transitions, automated BOM stage deduction, rolling Dynamic ROP algorithm, and financial export ledgers across all 5 modules.<br>• **Member B (UI/UX Frontend Developer & Prototyping Specialist — 30%):** Designed and built the user-facing interfaces, responsive Blade layouts, Flux UI component wireframes, multi-step customer ordering wizard, live quotation card, 5-stage visual progress stepper, drag-and-drop Kanban board layout, counter order intake forms, and owner KPI dashboard views. |
| **Methodology** *(Task 1: Research Design & Data Gathering)* | Member A — 40%<br>Member B — 60% | **Member B:** Prepared interview guides, conducted on-site field observations at local print shops, transcribed operational procedures, compiled user requirements.<br>**Member A:** Formulated developmental research design, translated observational data into software specifications and system constraints. |
| **Methodology** *(Task 2: System Modeling & Activity Diagrams)* | Member A — 30%<br>Member B — 70% | **Member B (Lead):** Illustrated and finalized all 18 UML Activity Diagrams for requirements `FR-01` to `FR-18` across Customer, Staff, Owner, and System swimlanes.<br>**Member A:** Audited decision nodes, state transitions, and exception paths against backend database transactions and route controllers. |
| **Methodology** *(Task 3: Multi-Tier Verification & Software Testing)* | Member A — 70%<br>Member B — 30% | **Member A (Lead):** Programmed 28 automated Pest feature test cases (90 assertions), authored Black-Box Functional Test Plan with 25 comprehensive test cases (`TC-MOD1-01` to `TC-MOD5-05`).<br>**Member B:** Conducted black-box walkthroughs, recorded actual results, compiled Functional Test Summary Report and Requirements Traceability Matrix. |
| **Formatting & Referencing** | Member A — 30%<br>Member B — 70% | **Member B (Lead):** Standardized margins, typography, spacing, and headings per USM guidelines; formatted complete Literature Cited list per APA 7th edition.<br>**Member A:** Formatted mathematical formulas ($ROP = d \times L + SS$), code snippets, and automated test tables. |
| **Final editing & proofreading** | Member A — 40%<br>Member B — 60% | **Member B (Lead):** Performed comprehensive editorial proofreading, eliminated typographical errors, ensured scholarly tone and USM BSIS conventions.<br>**Member A:** Verified consistency of functional requirement tags (`FR-01` to `FR-18`), test IDs (`TC-MOD1-01` to `TC-MOD5-05`), and database schema naming. |
| **Oral presentation preparation** | Member A — 50%<br>Member B — 50% | **Member A:** Prepared technical architecture slides, algorithmic formulas, Kanban state logic, and live demonstration environment.<br>**Member B:** Designed presentation visual theme, created problem context and RRL slides, and structured live walkthrough storyboard. |

### Mathematical Balance of the 13-Row Consolidated Formulation

$$\text{Sum of Member A Contributions} = 70 + 30 + 60 + 60 + 50 + 50 + 70 + 40 + 30 + 70 + 30 + 40 + 50 = 650\%$$
$$\text{Sum of Member B Contributions} = 30 + 70 + 40 + 40 + 50 + 50 + 30 + 60 + 70 + 30 + 70 + 60 + 50 = 650\%$$

$$\text{Overall Contribution (Member A)} = \frac{650\%}{13} = 50.00\%$$
$$\text{Overall Contribution (Member B)} = \frac{650\%}{13} = 50.00\%$$

---

## 5. Defense Rationale & Oral Examination Guide

During the capstone defense, panels frequently scrutinize the Task Allocation Matrix to determine whether one student performed all the work while the other acted as a passive participant. The distribution established here is structured specifically to withstand deep panel interrogation.

### Core Strategic Division

1. **Member A (Christian James J. Perez): Lead Systems Architect & Backend Developer**
   - **Defense Identity:** The algorithmic and backend powerhouse who engineered the business logic, database migrations, dynamic pricing math, Kanban state engine, Dynamic ROP formula, and automated SQA test suites.
   - **Primary Panel Questions to Field:**
     - *"How does the Dynamic ROP formula calculate reorder points from rolling daily consumption?"*
     - *"How does the system ensure ACID compliance during Kanban stage transitions?"*
     - *"How are Bill of Materials (BOM) items linked relationally to service configuration options?"*
     - *"Explain the automated Pest test suite and how 28 feature tests validate system integrity."*

2. **Member B (Kaye P. Comission): Systems Analyst, UI/UX Frontend Developer & Technical Documentation Lead**
   - **Defense Identity:** The systems analyst and frontend engineer who conducted field requirements elicitation, modeled all 18 UML activity diagrams, prototyped the user interfaces (Blade/Flux views, customer ordering wizard, tracking stepper), authored the literature review, and managed verification walkthroughs.
   - **Primary Panel Questions to Field:**
     - *"How did you capture user requirements during your field inquiries at local printing shops?"*
     - *"Explain the decision paths and swimlanes in the UML Activity Diagrams for order intake and rework."*
     - *"How does the customer ordering wizard guide users through complex print option configurations?"*
     - *"What insights did you derive from the synthesis of related literature across the 20 studies?"*
     - *"Walk us through the black-box functional testing execution and the Requirements Traceability Matrix."*

### Key Tactical Advice for the Panel Presentation

- **Never contradict each other**: When a question touches both interface and backend (e.g., *"How does the customer know their order is printing?"*), Member B should explain the visual 5-stage progress stepper and notification UI, then hand off to Member A to explain the underlying database status update and event trigger.
- **Own your modules with confidence**:
  - If asked about Module 1 (Auth) or Module 3 (Customer Wizard), Member B takes the lead on user workflows, while Member A supports on security/storage.
  - If asked about Module 2 (Pricing), Module 4 (Kanban), or Module 5 (Inventory), Member A takes the lead on algorithms and state machines, while Member B explains the operator dashboard and form controls.

---

## 6. Official Submission & Sign-off Block

```
Submitted by:

________________________________________         ________________________________________
       CHRISTIAN JAMES J. PEREZ                             KAYE P. COMISSION
     Name & Signature of Member A                         Name & Signature of Member B
Lead Systems Architect & Backend Engineer         Systems Analyst & UI/UX Frontend Lead

Date Submitted: October ____, 2026               Date Submitted: October ____, 2026
```

```
Endorsed by:

________________________________________
           DANILYN P. FLORES
            Capstone Adviser

Date Approved:  ________________________
```
