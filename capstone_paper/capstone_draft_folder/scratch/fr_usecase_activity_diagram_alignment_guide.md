# Comprehensive Revision Guide: Aligning Functional Requirements, Use Case Diagram, and Activity Diagrams (19 FR Standard)

**Document Target:** Printify Capstone Outline Manuscript & Diagrams  
**Destination Folder:** `capstone_paper/capstone_draft_folder/scratch/`  
**Purpose:** Actionable, pinpoint instructions for project developers to implement your adviser's feedback—resolving all naming discrepancies and standardizing the project on a unified **19-item traceability framework** (19 Functional Requirements = 19 Use Case Ovals = 19 Activity Diagrams).

> [!NOTE]
> **Scope of this Guide:** This guide contains **ONLY** the specific sections, captions, tables, and narrative paragraphs that need to be edited or added. Parts of your paper that are already correct and unaffected are omitted.

---

## Quick Summary of the Core Changes

| Component | Status Before Revision | Status After Revision | Action Required |
| :--- | :---: | :---: | :--- |
| **Functional Requirements (FR)** | 18 FRs (`FR-02` merged Login + Profile) | **19 FRs** | Split `FR-02` into `FR-02: User Authentication (Login)` and `FR-03: User Profile Management`. Shift former `FR-03` to `FR-18` up by +1 to become `FR-04` through `FR-19`. |
| **Use Case Diagram (Figure 4)** | 19 Ovals (Already had Login & Profile separated, but with wording mismatches) | **19 Ovals** | **NO new ovals needed.** Simply update the labels inside existing ovals to match the FR names verbatim (e.g., change *"Register Customer Account"* $\rightarrow$ *"User Account Registration"*). |
| **Activity Diagrams** | 18 Diagrams (Figures 7 to 24) | **19 Diagrams** (Figures 7 to 25) | Add **one new activity diagram** for *User Profile Management* (Figure 9). Renumber subsequent activity diagrams by +1 (Figures 10 to 25). |
| **Table 1 (Traceability Matrix)** | 18 Test Suites (`TS-01` to `TS-18`) | **19 Test Suites** (`TS-01` to `TS-19`) | Insert row for `TS-03: User Profile Management` and renumber subsequent rows. |
| **Global Narrative Text** | References to "18 functional requirements" | References to "19 functional requirements" | Update specific sentences on Pages 19, 20, 41, 42, 44, 47, 116, 153, 154. |

---

## Part 1: Revisions to Functional Requirements (Chapter 3)

### 1.1 The Split of FR-02 (Insert into Chapter 3 Functional Requirements List)

In your itemized D-IPO (Description, Input, Process, Output) list under Chapter 3, replace the existing combined `FR-02` with the following two distinct requirements:

```markdown
2. User Authentication (Login)
   * Description: Authenticates registered credentials, establishes secure stateful sessions, enforces role-based access control (RBAC), and directs actors to their designated portal interfaces.
   * Input: Registered email address, password, optional "remember me" flag, and optional two-factor authentication (2FA) one-time token.
   * Process: Cross-references submitted credentials against bcrypt-hashed database records, evaluates account active status, resolves the user role (Customer, Production Staff, or Business Owner), issues session tokens, and executes route redirection to authorized dashboard hubs.
   * Output: Authenticated user session, role-gated interface routing, or invalid credential error prompts.

3. User Profile Management
   * Description: Allows authenticated users across all roles to inspect, modify, and maintain personal account information, security credentials, and communication preferences.
   * Input: Updated full name, mobile contact number, profile avatar file, current password, and new password with confirmation.
   * Process: Validates form syntax, enforces uniqueness on contact identifiers, verifies current password authenticity prior to credential modification, re-hashes new passwords using bcrypt, updates user entity records in the database, and dispatches status feedback.
   * Output: Updated database profile entity, success flash alerts, and updated session profile display.
```

---

### 1.2 Master Standardized List of All 19 Functional Requirements

Use this exact naming convention across your **Functional Requirements List**, **Table 1**, **Use Case Diagram**, and **Activity Diagrams** to eliminate any discrepancy:

1. **FR-01:** User Account Registration
2. **FR-02:** User Authentication (Login)
3. **FR-03:** User Profile Management
4. **FR-04:** Session Termination and Logout *(formerly FR-03)*
5. **FR-05:** Service Catalog Browsing and Quotation Calculation *(formerly FR-04)*
6. **FR-06:** Print Order Placement and Document Upload *(formerly FR-05)*
7. **FR-07:** Print Attribute and Finishing Specification *(formerly FR-06, <<include>>)*
8. **FR-08:** Customer-Supplied Substrate Declaration *(formerly FR-07, <<extend>>)*
9. **FR-09:** Proof of Payment Submission *(formerly FR-08)*
10. **FR-10:** Live Order Progress and Status Tracking *(formerly FR-09)*
11. **FR-11:** Payment Transaction Auditing and Verification *(formerly FR-10)*
12. **FR-12:** Production Floor 5-Stage Kanban Queue Management *(formerly FR-11)*
13. **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction *(formerly FR-12, <<include>>)*
14. **FR-14:** Customer-Supplied Substrate Counter Inspection *(formerly FR-13, <<extend>>)*
15. **FR-15:** Material Spoilage Logging and Stock Adjustments *(formerly FR-14)*
16. **FR-16:** Dynamic Service and Rate Configuration *(formerly FR-15)*
17. **FR-17:** Bill of Materials (BOM) Recipe Definition *(formerly FR-16, <<include>>)*
18. **FR-18:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting *(formerly FR-17)*
19. **FR-19:** Sales, Cashflow, and Operational Analytics Generation *(formerly FR-18)*

---

## Part 2: Revisions to Use Case Diagram (Figure 4)

Your Use Case Diagram in Draw.io / Figure 4 **already has 19 ovals**. You do not need to add any new shapes or change any relationship arrows (`<<include>>` and `<<extend>>` remain untouched). 

Simply open your diagram in Draw.io and update the text inside the ovals to match the FR titles:

| Current Oval Label in Figure 4 | Standardized New Label (Exact FR Match) | Relationship Type | Actor Connected |
| :--- | :--- | :---: | :--- |
| `Register Customer Account` | **User Account Registration** | Base | Customer |
| `Log-in` | **User Authentication (Login)** | Base | All Actors |
| `Manage Profile` | **User Profile Management** | Base | All Actors |
| `Log Out & Terminate Session` | **Session Termination and Logout** | Base | All Actors |
| `Browse Catalog & Calculate Quotation` | **Service Catalog Browsing and Quotation Calculation** | Base | Customer |
| `Place Order & Upload Documents` | **Print Order Placement and Document Upload** | Base | Customer |
| `Specify Print Attributes & Finishing` | **Print Attribute and Finishing Specification** | `<<include>>` | Sub-case of Place Order |
| `Declare Customer-Supplied Substrates` | **Customer-Supplied Substrate Declaration** | `<<extend>>` | Sub-case of Place Order |
| `Submit Proof of Payment` | **Proof of Payment Submission** | Base | Customer |
| `Track Live Order Progress & Status` | **Live Order Progress and Status Tracking** | Base | Customer |
| `Verify Payment Transactions` | **Payment Transaction Auditing and Verification** | Base | Production Staff |
| `Manage 5-Stage Kanban Production Queue` | **Production Floor 5-Stage Kanban Queue Management** | Base | Production Staff |
| `Deduct Materials via Automated BOM` | **Automated Bill of Materials (BOM) Inventory Deduction** | `<<include>>` | Sub-case of Kanban Queue |
| `Inspect Customer-Supplied Substrates` | **Customer-Supplied Substrate Counter Inspection** | `<<extend>>` | Sub-case of Kanban Queue |
| `Record Spoilage & Inventory Adjustments` | **Material Spoilage Logging and Stock Adjustments** | Base | Production Staff |
| `Configure Services & Dynamic Rates` | **Dynamic Service and Rate Configuration** | Base | Business Owner |
| `Define Bill of Materials (BOM) Recipes` | **Bill of Materials (BOM) Recipe Definition** | `<<include>>` | Sub-case of Configure Services |
| `Manage Inventory & Reorder Thresholds` | **Inventory Oversight and Dynamic Reorder Point (ROP) Alerting** | Base | Business Owner |
| `Generate Sales & Cashflow Analytics` | **Sales, Cashflow, and Operational Analytics Generation** | Base | Business Owner |

---

## Part 3: Revisions to Activity Diagrams (Chapter 3 / Appendix)

### 3.1 Figure Numbering Shift

Because we are inserting a dedicated diagram for **User Profile Management**, the captions in Chapter 3 shift as follows:

* **Figure 7:** *Activity Diagram: User Account Registration* *(Unchanged)*
* **Figure 8:** *Activity Diagram: User Authentication (Login)* *(Title updated)*
* **Figure 9 (NEW):** *Activity Diagram: User Profile Management* $\leftarrow$ **INSERT HERE**
* **Figure 10:** *Activity Diagram: Session Termination and Logout* *(formerly Figure 9)*
* **Figure 11:** *Activity Diagram: Service Catalog Browsing and Quotation Calculation* *(formerly Figure 10)*
* **Figure 12:** *Activity Diagram: Print Order Placement and Document Upload* *(formerly Figure 11)*
* **Figure 13:** *Activity Diagram: Print Attribute and Finishing Specification* *(formerly Figure 12)*
* **Figure 14:** *Activity Diagram: Customer-Supplied Substrate Declaration* *(formerly Figure 13)*
* **Figure 15:** *Activity Diagram: Proof of Payment Submission* *(formerly Figure 14)*
* **Figure 16:** *Activity Diagram: Live Order Progress and Status Tracking* *(formerly Figure 15)*
* **Figure 17:** *Activity Diagram: Payment Transaction Auditing and Verification* *(formerly Figure 16)*
* **Figure 18:** *Activity Diagram: Production Floor 5-Stage Kanban Queue Management* *(formerly Figure 17)*
* **Figure 19:** *Activity Diagram: Automated Bill of Materials (BOM) Inventory Deduction* *(formerly Figure 18)*
* **Figure 20:** *Activity Diagram: Customer-Supplied Substrate Counter Inspection* *(formerly Figure 19)*
* **Figure 21:** *Activity Diagram: Material Spoilage Logging and Stock Adjustments* *(formerly Figure 20)*
* **Figure 22:** *Activity Diagram: Dynamic Service and Rate Configuration* *(formerly Figure 21)*
* **Figure 23:** *Activity Diagram: Bill of Materials (BOM) Recipe Definition* *(formerly Figure 22)*
* **Figure 24:** *Activity Diagram: Inventory Oversight and Dynamic Reorder Point (ROP) Alerting* *(formerly Figure 23)*
* **Figure 25:** *Activity Diagram: Sales, Cashflow, and Operational Analytics Generation* *(formerly Figure 24)*

---

### 3.2 Revised Activity Diagrams: Visual ASCII Swimlane Diagrams & Text Guides

#### A. Activity Diagram 2: User Authentication (Login) (FR-02)

##### Narrative Paragraph for Chapter 3:
> *"Figure 8 illustrates the operational activity diagram for User Authentication (Login). The process begins when an actor (Customer, Production Staff, or Business Owner) submits their registered email address and password into the authentication portal. The authentication guard cross-references the submitted credentials against bcrypt password hashes stored in the database. If the credentials fail validation or the account is inactive, the system increments the failed login counter and displays an authentication error alert. Upon successful verification, the system initializes an authenticated session token, determines the actor's role-based access level, and redirects the user to their designated portal: the Customer Dashboard, Staff Production Console, or Owner Master Hub."*

##### Visual Swimlane Flowchart (ASCII Text Format):
```text
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: USER - ALL ROLES (LEFT)      | SWIMLANE 2: AUTH ENGINE & DB (RIGHT)     |
+------------------------------------------+------------------------------------------+
|  (● Start)                               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Enter Email & Password in Login Form]   |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Query 'users' Table & Cross-Reference   |
|                                          |  Submitted Password with Bcrypt Hash]    |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Credentials Valid & Account Active? > |
|                                          |     │                 │                  |
| [View Error Prompt & Retry] <────────────┼─────┘ [No]            │ [Yes]            |
|     │                                    |                       ▼                  |
|     ▼                                    | [Initialize Authenticated Session Token] |
|  (Retry Form Input)                      |     │                                    |
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
|  (◉ End: Session Authenticated)          |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 2: User Authentication (Login)**

##### Step-by-Step Drawing Instructions for Draw.io:
1. **Swimlanes:** Draw two vertical swimlanes:
   - Left Swimlane: `User (Customer / Staff / Owner)`
   - Right Swimlane: `Authentication Engine & Database`
2. **Start Node:** Place a filled black circle `(● Start)` at the top of the Left Swimlane.
3. **Action Box 1 (Left):** Connect Start to a rounded rectangle: `[Enter Email & Password in Login Form]`.
4. **Action Box 2 (Right):** Draw an arrow crossing to the Right Swimlane: `[Query 'users' Table & Verify Bcrypt Hash]`.
5. **Decision Diamond 1 (Right):** Connect to a decision diamond: `< Credentials Valid? >`.
   - Branch `[No]`: Draw an arrow back to the Left Swimlane to a box: `[Display Error Prompt & Re-enter Credentials]`, looping back to Action Box 1.
   - Branch `[Yes]`: Connect downwards to Action Box: `[Initialize Authenticated Session Token]`.
6. **Decision Diamond 2 (Right):** Connect downwards to a decision diamond: `< Evaluate User Role >`.
   - Branch `[Customer]`: Draw an arrow pointing to `[Redirect to Customer Dashboard]` in the Left Swimlane.
   - Branch `[Staff]`: Draw an arrow pointing to `[Redirect to Staff Production Console]` in the Left Swimlane.
   - Branch `[Owner]`: Draw an arrow pointing to `[Redirect to Owner Master Hub]` in the Left Swimlane.
7. **End Node:** Connect all three dashboard boxes to a bullseye circle `(◉ End: Session Authenticated)`.

---

#### B. Activity Diagram 3: User Profile Management (FR-03) — NEW DIAGRAM

##### Narrative Paragraph for Chapter 3:
> *"Figure 9 illustrates the operational activity diagram for User Profile Management. The workflow begins when an authenticated user accesses the account settings interface to modify personal details, contact information, or security credentials. The user inputs their updated attributes, providing current password verification if a password change is requested. The system validates syntactic constraints and checks mobile number uniqueness. If password alteration is submitted, the backend verifies the current password hash before encrypting the new password via bcrypt. Upon successful validation, the system executes an atomic database transaction updating the user entity, flushes the cached session state, and renders an update confirmation notification."*

##### Visual Swimlane Flowchart (ASCII Text Format):
```text
+------------------------------------------+------------------------------------------+
| SWIMLANE 1: AUTHENTICATED USER (LEFT)    | SWIMLANE 2: PROFILE CONTROLLER & DB (RT) |
+------------------------------------------+------------------------------------------+
|  (● Start: User Logged In)               |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Navigate to Profile / Settings Menu]    |                                          |
|     │                                    |                                          |
|     ▼                                    |                                          |
| [Input Profile Changes: Name, Mobile,    |                                          |
|  Avatar, or New Password]                |                                          |
|     │                                    |                                          |
|     └───────────────────────────────────>│ [Validate Form Syntax, Formats,          |
|                                          |  & Mobile Number Uniqueness]             |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |  < Form Input Syntactically Valid? >     |
|                                          |     │                 │                  |
| [Display Validation Errors] <────────────┼─────┘ [No]            │ [Yes]            |
|     │                                    |                       ▼                  |
|     ▼                                    |  < Password Change Requested? >          |
|  (Correct Input Fields)                  |     │                 │                  |
|                                          |     │ [No]            │ [Yes]            |
|                                          |     │                 ▼                  |
|                                          |     │              < Current Password    |
|                                          |     │                Hash Matches? >     |
|                                          |     │                 │        │         |
| [Display Current Password Error] <───────┼─────┼─────────────────┘ [No]   │ [Yes]   |
|                                          |     │                          ▼         |
|                                          |     │              [Bcrypt Hash New Pwd] |
|                                          |     │                          │         |
|                                          |     ▼                          ▼         |
|                                          |   [Execute Update in 'users' Table]      |
|                                          |     │                                    |
|                                          |     ▼                                    |
|                                          |   [Refresh Authenticated User Session]   |
|                                          |     │                                    |
| [Render 'Profile Updated' Success Toast] <─────┘                                    |
|     │                                    |                                          |
|     ▼                                    |                                          |
|  (◉ End: Profile Saved)                  |                                          |
+------------------------------------------+------------------------------------------+
```
**Activity Diagram 3: User Profile Management**

##### Step-by-Step Drawing Instructions for Draw.io:
1. **Swimlanes:** Draw two vertical swimlanes:
   - Left Swimlane: `Authenticated User`
   - Right Swimlane: `Profile Controller & Database`
2. **Start Node:** Place a filled black circle `(● Start)` in the Left Swimlane.
3. **Action Box 1 (Left):** Connect to `[Navigate to Profile / Settings Menu]`.
4. **Action Box 2 (Left):** Connect to `[Input Profile Changes: Name, Mobile, Avatar, or New Password]`.
5. **Action Box 3 (Right):** Draw an arrow to the Right Swimlane: `[Validate Form Syntax & Unique Mobile]`.
6. **Decision Diamond 1 (Right):** Connect to `< Form Inputs Valid? >`.
   - Branch `[No]`: Draw an arrow to `[Display Validation Errors]` in the Left Swimlane (loop back to input).
   - Branch `[Yes]`: Connect downwards to Decision Diamond 2: `< Password Change Requested? >`.
7. **Decision Diamond 2 (Right):**
   - Branch `[No]`: Skip directly to Action Box: `[Execute Update in 'users' Table]`.
   - Branch `[Yes]`: Connect to Decision Diamond 3: `< Current Password Hash Matches? >`.
8. **Decision Diamond 3 (Right):**
   - Branch `[No]`: Draw an arrow to `[Display Current Password Error]` in the Left Swimlane.
   - Branch `[Yes]`: Connect to `[Bcrypt Hash New Password]`, and then connect downwards to `[Execute Update in 'users' Table]`.
9. **Action Box 4 (Right):** Connect from the database update to `[Refresh Authenticated User Session]`.
10. **Action Box 5 (Left):** Draw an arrow to the Left Swimlane: `[Render 'Profile Updated' Success Toast]`.
11. **End Node:** Connect to bullseye circle `(◉ End: Profile Saved)`.

---

## Part 4: Revisions to Table 1: Traceability Matrix (Chapter 3)

In **Table 1: Functional Test Plan and Requirements Traceability Matrix**, update the top rows to include the dedicated test suite for User Profile Management:

| Test Suite ID | Linked Functional Requirement | Module Scope | Verification Method | Evaluation Metric |
| :---: | :--- | :--- | :--- | :---: |
| **TS-01** | **FR-01:** User Account Registration | Customer Onboarding | Automated Feature Test & Boundary Form Validation | Pass / Fail |
| **TS-02** | **FR-02:** User Authentication (Login) | Security & Access Control | Credential Validation & Role-Based Route Gate | Pass / Fail |
| **TS-03** | **FR-03:** User Profile Management | User Profile & Settings | Field Validation, Bcrypt Hash Check & Profile Mutation | Pass / Fail |
| **TS-04** | **FR-04:** Session Termination and Logout | Session Security | Session Token Flush & Cache Cookie Invalidation | Pass / Fail |
| ... | *(Rows TS-05 to TS-19 follow sequential renumbering)* | ... | ... | Pass / Fail |

---

## Part 5: Pinpoint Global Text Find & Replace (Pages 19 to 154)

Perform these exact text updates in your manuscript to ensure total numeric consistency:

### 1. Page 19 (Chapter 1 / Scope and Delimitation)
* **Change from:**
  > "...Test Cases to validate that all **18 functional requirements** satisfy their..."
* **Change to:**
  > "...Test Cases to validate that all **19 functional requirements** satisfy their..."

### 2. Page 20 (Chapter 1 / Operational Definition / Scope)
* **Change from:**
  > "...verification across all **18 functional modules** on a pass-or-fail basis."
* **Change to:**
  > "...verification across all **19 functional modules** on a pass-or-fail basis."

### 3. Page 41 (Chapter 3 / Software Testing Methodology)
* **Change from:**
  > "...mapped directly to the **18 functional requirements**, recording binary evaluation..."
* **Change to:**
  > "...mapped directly to the **19 functional requirements**, recording binary evaluation..."

### 4. Page 42 (Chapter 3 / Functional Test Plan Header)
* **Change from:**
  > "...conditions across all **18 Functional Requirements (FR-01 to FR-18)**. In..."
* **Change to:**
  > "...conditions across all **19 Functional Requirements (FR-01 to FR-19)**. In..."

### 5. Page 44 (Chapter 3 / Table 1 Introduction)
* **Change from:**
  > "...rate across all **18 core functional modules**, ensuring zero critical defects prior to..."
* **Change to:**
  > "...rate across all **19 core functional modules**, ensuring zero critical defects prior to..."

### 6. Page 47 (Chapter 3 / Functional Verification Design)
* **Change from:**
  > "...formal test cases mapped to the **18 functional requirements** to verify algorithmic..."
* **Change to:**
  > "...formal test cases mapped to the **19 functional requirements** to verify algorithmic..."

### 7. Page 116 (Chapter 4 / Functional Testing Results)
* **Change from:**
  > "...structured Test Plan and discrete Test Cases to validate that all **18 functional requirements** satisfy their..."
* **Change to:**
  > "...structured Test Plan and discrete Test Cases to validate that all **19 functional requirements** satisfy their..."

### 8. Pages 153–154 (Appendix F / Task Allocation Matrix)
* **Change from:**
  > "...UML Activity Diagrams corresponding to all **18 functional requirements (FR-01 to FR-18)**."  
  > "...Matrix linking all **18 functional requirements** to specific..."
* **Change to:**
  > "...UML Activity Diagrams corresponding to all **19 functional requirements (FR-01 to FR-19)**."  
  > "...Matrix linking all **19 functional requirements** to specific..."

---

---

## Part 6: Master Test Cases & Test Suites Revisions (Addressing the 18 vs. 19 Question)

Your intuition is 100% correct: **Yes, the test cases must be modified.**

In your manuscript, testing is presented at two levels:
1. **High-Level Test Suites in Table 1 (Chapter 3)**: Requirements Traceability Matrix (`TS-01` to `TS-18`).
2. **Detailed Black-Box Scenario Test Cases (Chapter 4 & Appendix)**: Discrete execution test cases (`TC-MOD1-01` to `TC-MOD5-05`).

Here is exactly how both levels must be updated:

---

### 6.1 Revisions to Table 1: Functional Test Plan & Traceability Matrix (Chapter 3)

In Chapter 3, **Table 1** previously contained 18 rows. It must now contain **19 Test Suites (`TS-01` to `TS-19`)** to provide 1:1 coverage for `FR-01` through `FR-19`.

| Test Suite ID | Linked Functional Requirement | Module Scope | Verification Method | Evaluation Metric |
| :---: | :--- | :--- | :--- | :---: |
| **TS-01** | **FR-01:** User Account Registration | Customer Onboarding | Automated Feature Test & Boundary Form Validation | Pass / Fail |
| **TS-02** | **FR-02:** User Authentication (Login) | Security & Access Control | Credential Validation & Role-Based Route Gate | Pass / Fail |
| **TS-03** | **FR-03:** User Profile Management *(NEW)* | User Profile & Settings | Field Validation, Bcrypt Hash Check & Profile Mutation | Pass / Fail |
| **TS-04** | **FR-04:** Session Termination and Logout | Session Security | Session Token Flush & Cache Cookie Invalidation | Pass / Fail |
| **TS-05** | **FR-05:** Service Catalog Browsing & Quotation | Pricing Engine | Reactive Component State & Livewire Formula Audit | Pass / Fail |
| **TS-06** | **FR-06:** Print Order Placement & Document Upload | Customer Ordering | MIME/Size Validation, File Tokenization & DB Persistence | Pass / Fail |
| **TS-07** | **FR-07:** Print Attribute & Finishing Specification | Technical Specs | Sheet Calculation Math & Boundary Rule Constraint | Pass / Fail |
| **TS-08** | **FR-08:** Customer-Supplied Substrate Declaration | Order Extension | Cover-Only Flagging & Paper Fee Exemption Logic | Pass / Fail |
| **TS-09** | **FR-09:** Proof of Payment Submission | Billing Intake | Payment Receipt Upload & Status Transition to Pending | Pass / Fail |
| **TS-10** | **FR-10:** Live Order Progress and Status Tracking | Customer Telemetry | Real-Time Milestone Stepper Query & Status Sync | Pass / Fail |
| **TS-11** | **FR-11:** Payment Transaction Auditing & Verification | Billing Audit | Staff Ledger Cross-Reference & Paid Status Transition | Pass / Fail |
| **TS-12** | **FR-12:** Production Floor 5-Stage Kanban Queue | Floor Operations | Sequential Kanban Stage Machine & QC Step-Back Loop | Pass / Fail |
| **TS-13** | **FR-13:** Automated BOM Inventory Deduction | Inventory Telemetry | Stage Advance Trigger & Duplex Sheet Stock Deduction | Pass / Fail |
| **TS-14** | **FR-14:** Customer Substrate Counter Inspection | Floor Intake | Physical Inspection Hold Release & Production Tagging | Pass / Fail |
| **TS-15** | **FR-15:** Material Spoilage Logging & Stock Adjust | Inventory Control | Waste Entry Recording & Stock Movement Ledger Entry | Pass / Fail |
| **TS-16** | **FR-16:** Dynamic Service and Rate Configuration | Admin Configuration | Dynamic Parameter Mutation & Rate Persistence | Pass / Fail |
| **TS-17** | **FR-17:** Bill of Materials (BOM) Recipe Definition | Inventory Mapping | Service-to-Item Consumable Mapping & Recipe Setup | Pass / Fail |
| **TS-18** | **FR-18:** Inventory Oversight & Dynamic ROP Alerting | Inventory Analytics | Rolling Burn Rate Derivation & Low-Stock Trigger | Pass / Fail |
| **TS-19** | **FR-19:** Sales, Cashflow & Operational Analytics | Business Intelligence | Multi-Factor Financial Aggregation & CSV Export | Pass / Fail |

---

### 6.2 Revisions to Detailed Black-Box Scenario Test Cases (Chapter 4 & Appendix)

In Chapter 4 and Appendix, you previously had **25 test cases** (`TC-MOD1-01` to `TC-MOD5-05`). 

Because `FR-03: User Profile Management` is now its own functional requirement, you should add **one test case (`TC-MOD1-06`)** to explicitly verify profile updates and password changes, bringing your total to **26 Test Cases**.

#### A. The New Test Case Specification to Insert into Module 1:

```markdown
Test Case ID: TC-MOD1-06
Requirement Reference: FR-03: User Profile Management
Test Description: Verify user profile attribute modification, mobile number uniqueness enforcement, and current password verification during password update.
Preconditions: Authenticated user session active (Customer, Staff, or Owner).
Test Input Data: 
  * Updated Name = "Christian J. Perez (Updated)"
  * Updated Mobile = "09123456789"
  * Current Password = "CorrectOldPassword123"
  * New Password = "NewSecurePassword456"
  * New Password Confirmation = "NewSecurePassword456"
Execution Steps:
  1. Access user settings interface via /user/profile.
  2. Modify full name and contact number fields.
  3. Enter current password and matching new password in credential update section.
  4. Submit profile update form.
Expected Output: Form passes validation; database entity updated in 'users' table with re-hashed bcrypt password; session state refreshed; visual success notification "Profile Updated Successfully" rendered.
Actual Result: User record mutated cleanly; password re-hashed via bcrypt; success toast displayed.
Status: PASS
```

#### B. Updated FR Cross-Reference Mapping for All 26 Test Cases:

Notice how the `Requirement Reference` column updates to reflect the new 19 FR numbering:

| Test Case ID | Module Scope | Linked FR (Updated) | Test Description Scope | Status |
| :---: | :--- | :--- | :--- | :---: |
| **TC-MOD1-01** | Module 1: Auth & RBAC | **FR-01:** User Account Registration | Positive: Valid Customer Registration | **PASS** |
| **TC-MOD1-02** | Module 1: Auth & RBAC | **FR-01:** User Account Registration | Negative: Duplicate Email Rejection | **PASS** |
| **TC-MOD1-03** | Module 1: Auth & RBAC | **FR-02:** User Authentication (Login) | Positive: Role-Based Dashboard Routing | **PASS** |
| **TC-MOD1-04** | Module 1: Auth & RBAC | **FR-02:** User Authentication (Login) | Negative: Invalid Password Rejection | **PASS** |
| **TC-MOD1-05** | Module 1: Auth & RBAC | **FR-04:** Session Termination & Logout | Security: Token Invalidation & Cookie Flush | **PASS** |
| **TC-MOD1-06** | Module 1: Auth & RBAC | **FR-03:** User Profile Management *(NEW)* | Mutation: Name, Mobile, & Bcrypt Password Update | **PASS** |
| **TC-MOD2-01** | Module 2: Pricing Engine | **FR-05:** Service Catalog & Quotation | Dynamic Pricing: Reactive Subtotal Calculation | **PASS** |
| **TC-MOD2-02** | Module 2: Pricing Engine | **FR-16:** Dynamic Service Configuration | Dynamic Service: Dynamic Parameter Mutation | **PASS** |
| **TC-MOD2-03** | Module 2: Pricing Engine | **FR-17:** BOM Recipe Definition | Relational BOM: Consumable Inventory Mapping | **PASS** |
| **TC-MOD2-04** | Module 2: Pricing Engine | **FR-07:** Print Attribute Specification | Boundary: Page Limit Exceedance Handling | **PASS** |
| **TC-MOD3-01** | Module 3: Customer Ordering | **FR-06:** Print Order Placement & Upload | File Intake: Valid PDF Upload & Tokenization | **PASS** |
| **TC-MOD3-02** | Module 3: Customer Ordering | **FR-06:** Print Order Placement & Upload | File Intake: Disallowed MIME Extension Rejection | **PASS** |
| **TC-MOD3-03** | Module 3: Customer Ordering | **FR-07:** Print Attribute Specification | Duplex Math: Sheet Count Calculation ($\lceil P/2 \rceil \times C$) | **PASS** |
| **TC-MOD3-04** | Module 3: Customer Ordering | **FR-08:** Substrate Declaration | Business Rule: "Dala ang Papel" Fee Deduction | **PASS** |
| **TC-MOD3-05** | Module 3: Customer Ordering | **FR-09:** Proof of Payment Submission | Billing Intake: Digital Remittance Proof Upload | **PASS** |
| **TC-MOD3-06** | Module 3: Customer Ordering | **FR-10:** Live Order Progress Tracking | Telemetry: 5-Stage Stepper Synchronization | **PASS** |
| **TC-MOD4-01** | Module 4: Floor Scheduling | **FR-11:** Payment Auditing & Verification | Billing Audit: Staff Verification Approval | **PASS** |
| **TC-MOD4-02** | Module 4: Floor Scheduling | **FR-11:** Payment Auditing & Verification | Billing Audit: Staff Verification Rejection | **PASS** |
| **TC-MOD4-03** | Module 4: Floor Scheduling | **FR-12:** 5-Stage Kanban Floor Queue | Workflow: Sequential Stage Progression | **PASS** |
| **TC-MOD4-04** | Module 4: Floor Scheduling | **FR-12:** 5-Stage Kanban Floor Queue | QC Loop: Stage Step-Back & Rework Tracking | **PASS** |
| **TC-MOD4-05** | Module 4: Floor Scheduling | **FR-14:** Substrate Counter Inspection | Floor Intake: Physical Paper Audit & Release | **PASS** |
| **TC-MOD5-01** | Module 5: Inventory Telemetry | **FR-13:** Automated BOM Stock Deduction | Telemetry: Stage-Triggered Material Decrement | **PASS** |
| **TC-MOD5-02** | Module 5: Inventory Telemetry | **FR-13:** Automated BOM Stock Deduction | Exception: Customer Paper Stock Bypass | **PASS** |
| **TC-MOD5-03** | Module 5: Inventory Telemetry | **FR-15:** Material Spoilage Logging | Stock Adjustment: Scrap Recording & Audit Log | **PASS** |
| **TC-MOD5-04** | Module 5: Inventory Telemetry | **FR-18:** Inventory Oversight & ROP Alert | Alerting: Rolling Burn Rate & Dynamic ROP | **PASS** |
| **TC-MOD5-05** | Module 5: Inventory Telemetry | **FR-19:** Analytics & Financial Reporting | Intelligence: Multi-Factor BI & CSV Export | **PASS** |

---

## Action Checklist for Authors

- [ ] In Chapter 3, split `FR-02` into `FR-02` and `FR-03`, and renumber `FR-04` through `FR-19`.
- [ ] In Figure 4 (Draw.io), rename the 19 existing ovals to match the standardized list verbatim.
- [ ] In Draw.io / Manuscript, insert **Figure 9** (*User Profile Management Activity Diagram*) following the ASCII swimlane guide, and renumber subsequent figures up to Figure 25.
- [ ] Update **Table 1** (Chapter 3) to contain **19 Test Suites** (`TS-01` to `TS-19`).
- [ ] In Chapter 4 and Appendix, insert **`TC-MOD1-06`** and update the `Requirement Reference` numbers in the test cases table (bringing total test cases to 26).
- [ ] Run a Find & Replace for the 8 identified sentences in the text changing "18" to "19".
