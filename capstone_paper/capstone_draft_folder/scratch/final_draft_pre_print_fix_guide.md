# Final Pre-Print Fix Guide (`outline_capstone_v1_FinalDraft.pdf`)

This guide lists every remaining issue found across all **164 PDF pages** of `outline_capstone_v1_FinalDraft.pdf`, organized into three categories so you know exactly what to fix right now before printing versus what can wait until after your Outline Defense.

---

## Category 1: 🚨 Panel-Triggering Errors *(Must Fix Before Printing)*

These are high-visibility errors in your Functional Requirements and Appendix F that a panelist flipping through your manuscript will immediately spot and question.

---

### Fix 1.1: Remove Double Numbering on Functional Requirements `6` to `19` (Printed pp. 47–56 / PDF pp. 60–69)

* **Where:** Chapter IV $\rightarrow$ `Functional Requirements`, starting at Requirement `6` on **Printed p. 47 (PDF p. 60)** down to Requirement `19` on **Printed p. 56 (PDF p. 69)**.
* **What Happened:** After fixing `5. Service Catalog Browsing and Quotation Calculation`, Microsoft Word restarted its automatic list numbering at `1.` next to your manual numbers `6.` through `19.`.
* **What It Currently Looks Like in the PDF:**
  * **Printed p. 47 (PDF p. 60):** `1. 6. Print Order Placement and Document Upload`
  * **Printed p. 48 (PDF p. 61):** `2. 7. Print Attribute and Finishing Specification`
  * **Printed p. 49 (PDF p. 62):** `3. 8. Customer-Supplied Substrate Declaration`
  * **Printed p. 49 (PDF p. 62):** `4. 9. Proof of Payment Submission`
  * **Printed p. 50 (PDF p. 63):** `10. Live Order Progress and Status Tracking` *(no double number on 10, which broke the auto-list)*
  * **Printed p. 50 (PDF p. 63):** `5. 11. Payment Transaction Auditing and Verification`
  * **Printed p. 51 (PDF p. 64):** `6. 12. Production Floor 5-Stage Kanban Queue Management`
  * **Printed p. 52 (PDF p. 65):** `7. 13. Automated Bill of Materials (BOM) Inventory Deduction`
  * **Printed p. 53 (PDF p. 66):** `14. Customer-Supplied Substrate Counter Inspection` *(no double number on 14)*
  * **Printed p. 53 (PDF p. 66):** `8. 15. Material Spoilage Logging and Stock Adjustments`
  * **Printed p. 54 (PDF p. 67):** `9. 16. Dynamic Service and Rate Configuration`
  * **Printed p. 54 (PDF p. 67):** `10. 17. Bill of Materials (BOM) Recipe Definition`
  * **Printed p. 55 (PDF p. 68):** `11. 18. Inventory Oversight and Dynamic Reorder Point (ROP) Alerting`
  * **Printed p. 56 (PDF p. 69):** `12. 19. Sales, Cashflow, and Operational Analytics Generation`
* **How to Fix in Word:**
  Click right in front of `6.`, `7.`, `8.`, `9.`, `11.`, `12.`, `13.`, `15.`, `16.`, `17.`, `18.`, and `19.` and turn off the automatic numbering button (or press `Backspace` on the auto-number `1.`–`12.`) so only `6.` through `19.` remain.

---

### Fix 1.2: Fix `TC-MOD5-04` (`FR-18`) and Add Missing `TC-MOD5-05` (`FR-19`) in `Appendix F` (PDF p. 128)

* **Where:** `Appendix F: Master Functional Test Cases` (**PDF p. 128**, last page of Appendix F).
* **Problem A (`TC-MOD5-04` has the wrong FR title):**
  * Currently says: `FR-18: Inventory Oversight and Dynamic Rate Configuration Workflow` *(this accidentally combined `FR-18` and `FR-16`)*.
  * **Change to:** `FR-18: Inventory Oversight and Dynamic Reorder Point (ROP) Alerting`
* **Problem B (`TC-MOD5-05` / `FR-19` is completely missing from the table):**
  * The intro paragraph of Appendix F (PDF p. 125) says **"26 discrete Test Cases"**, and `TC-MOD5-05` exists in Appendix G (PDF p. 154), but the table in Appendix F ends at `TC-MOD5-04` (only 25 rows).
  * **Add Row 26 at the bottom of the Appendix F table (PDF p. 128):**

| Test Case ID | Module | Linked Requirement | Test Case Title / Scenario | Test Type |
| :--- | :--- | :--- | :--- | :--- |
| **TC-MOD5-04** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-18:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting | Low-Stock Threshold Triggering Automated ROP Warning Alert | Positive |
| **TC-MOD5-05** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-19:** Sales, Cashflow, and Operational Analytics Generation | Generation of Date-Filtered Sales, Spoilage & Material Reports | Positive |

---

### Fix 1.3: Fix the 4 Remaining Non-1:1 FR Names & Duplicate Column in `Appendix F` (PDF pp. 125–128)

* **Where:** `Appendix F: Master Functional Test Cases` (**PDF pp. 125–128**).
* **4 Rows Where `Linked Requirement` Is Not 100% 1:1 Verbatim With Chapter IV & `Table 1`:**
  1. **`TC-MOD2-04` (PDF p. 126):**
     * Currently says: `FR-07: Print Attribute Finishing Specification`
     * **Change to:** `FR-07: Print Attribute and Finishing Specification` *(add **"and"**)*
  2. **`TC-MOD3-03` (PDF p. 126):**
     * Currently says: `FR-07: Print Attribute Finishing Specification`
     * **Change to:** `FR-07: Print Attribute and Finishing Specification` *(add **"and"**)*
  3. **`TC-MOD4-02` (PDF p. 127):**
     * Currently says: `FR-11: Payment Auditing and Verification`
     * **Change to:** `FR-11: Payment Transaction Auditing and Verification` *(add **"Transaction"** to match `TC-MOD4-01` right above it)*
  4. **`TC-MOD4-05` (PDF p. 127):**
     * Currently says: `FR-14: Customer-Supplied Substrate Intake and Counter Inspection`
     * **Change to:** `FR-14: Customer-Supplied Substrate Counter Inspection` *(remove **"Intake and"** to match `FR-14` on Printed p. 53 and `Table 1`)*
* **Duplicate 6th Column in `Appendix F`:**
  * The table in Appendix F has `Test Case ID` as Column 1 **and** duplicated again as Column 6 on the far right (`Test Case ID | Module | Linked Requirement | Test Case Title / Scenario | Test Type | Test Case ID`).
  * **Fix:** Right-click the rightmost `Test Case ID` column in Word $\rightarrow$ **Delete Columns**.

#### Complete Ready-to-Paste `Appendix F` Table (All 26 Rows, 100% 1:1 Verbatim FR & Module Names, 5 Columns)

If it is faster for you to replace the whole Appendix F table at once, copy and paste this clean 5-column, 26-row table:

| Test Case ID | Module | Linked Requirement | Test Case Title / Scenario | Test Type |
| :--- | :--- | :--- | :--- | :--- |
| **TC-MOD1-01** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-01:** User Account Registration | Customer Registration with Valid Inputs | Positive |
| **TC-MOD1-02** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-01:** User Account Registration | Customer Registration with Duplicate Email | Negative |
| **TC-MOD1-03** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-02:** User Authentication (Login) | Multi-Role Login & RBAC Route Guard | Positive / Security |
| **TC-MOD1-04** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-02:** User Authentication (Login) | Login with Invalid Credentials & Rate Limiting | Negative / Security |
| **TC-MOD1-05** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-04:** Session Termination and Logout | User Logout & Session Invalidation | Positive / Security |
| **TC-MOD1-06** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-03:** User Profile Management | Customer Profile Information & Password Update | Positive |
| **TC-MOD2-01** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-05:** Service Catalog Browsing and Quotation Calculation | Interactive Price Quotation Calculation | Positive |
| **TC-MOD2-02** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-16:** Dynamic Service and Rate Configuration | Admin Creation of New Print Service & Pricing Matrix | Positive |
| **TC-MOD2-03** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-17:** Bill of Materials (BOM) Recipe Definition | Admin Mapping of BOM Raw Material Deduction Ratios | Positive |
| **TC-MOD2-04** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-07:** Print Attribute and Finishing Specification | Dynamic Filtering of Incompatible Material/Size Attributes | Negative / Validation |
| **TC-MOD3-01** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-06:** Print Order Placement and Document Upload | Valid Artwork File Upload (`PDF`/`PNG`/`JPG`/`TIFF`) | Positive |
| **TC-MOD3-02** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-06:** Print Order Placement and Document Upload | Artwork Upload with Invalid Format (`.exe`/`.docx`) or `> 50 MB` | Negative / Security |
| **TC-MOD3-03** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-07:** Print Attribute and Finishing Specification | Complete Job Order Placement with Custom Specifications | Positive |
| **TC-MOD3-04** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-08:** Customer-Supplied Substrate Declaration | Order Placement with Customer-Supplied Substrate Toggle | Positive |
| **TC-MOD3-05** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-09:** Proof of Payment Submission | Digital Payment Receipt Upload & Reference Number Logging | Positive |
| **TC-MOD3-06** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-10:** Live Order Progress and Status Tracking | Real-Time Customer Order Status Tracker Updates | Positive |
| **TC-MOD4-01** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-11:** Payment Transaction Auditing and Verification | Staff Approval of Valid Proof of Payment | Positive |
| **TC-MOD4-02** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-11:** Payment Transaction Auditing and Verification | Staff Rejection of Fraudulent/Blurry Payment Receipt | Negative |
| **TC-MOD4-03** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-12:** Production Floor 5-Stage Kanban Queue Management | Sequential Kanban Stage Progression (*Pending* to *Completed*) | Positive |
| **TC-MOD4-04** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-12:** Production Floor 5-Stage Kanban Queue Management | Restricted Transition to *In Production* Without Verified Payment | Negative / Business Rule |
| **TC-MOD4-05** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-14:** Customer-Supplied Substrate Counter Inspection | Staff Acceptance & Rejection of Customer-Supplied Media | Positive / Negative |
| **TC-MOD5-01** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction | Automatic Raw Material Deduction Upon *In Production* Trigger | Positive |
| **TC-MOD5-02** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction | Zero Base-Substrate Deduction for Customer-Supplied Orders | Positive / Business Rule |
| **TC-MOD5-03** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-15:** Material Spoilage Logging and Stock Adjustments | Staff Logging of Damaged Materials & Reason Code Deduction | Positive |
| **TC-MOD5-04** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-18:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting | Low-Stock Threshold Triggering Automated ROP Warning Alert | Positive |
| **TC-MOD5-05** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-19:** Sales, Cashflow, and Operational Analytics Generation | Generation of Date-Filtered Sales, Spoilage & Material Reports | Positive |

---

## Category 2: ⚠️ Traceability & Structural Glitches *(Quick 2-Minute Fixes)*

These are structural/layout glitches that are very fast to fix if you have 2 extra minutes before printing:

### Fix 2.1: Extra Blank "Approved by the Guidance Committee" Page (PDF p. 5)
* **Where:** **PDF p. 4** has your filled-in `APPROVAL OF THESIS OUTLINE` page (`DANILYN A. FLORES`, `CATHERINE C. DAFFON`, `RYAN Z. GONZAGA`), and **PDF p. 5** has a second, blank copy of `APPROVED BY THE GUIDANCE COMMITTEE`.
* **Fix:** Delete the blank duplicate page on **PDF p. 5**.

### Fix 2.2: Table of Contents (PDF p. 8) vs. List of Appendices (PDF p. 13) Page Numbers for `G, H, I`
* **Where:** **PDF p. 8 (Table of Contents)** vs. **PDF p. 13 (List of Appendices)**.
* **What Happened:** Because `Appendix F` now takes 4 pages (pp. 112–115), you updated the **Table of Contents (PDF p. 8)** to:
  * `Appendix G. Detailed Test Case Specification` $\rightarrow$ **`116`** *(also fix the typo **`Deltailed`** $\rightarrow$ **`Detailed`** on PDF p. 8!)*
  * `Appendix H. Task Allocation Matrix (TAM)` $\rightarrow$ **`142`**
  * `Appendix I. Curriculum Vitae` $\rightarrow$ **`150`**
* However, the **List of Appendices (PDF p. 13)** still lists `G = 115`, `H = 141`, and `I = 149`.
* **Fix:** Update `G`, `H`, and `I` on **PDF p. 13 (List of Appendices)** to **`116`**, **`142`**, and **`150`** (and change `Test Case Specification` to `Detailed Test Case Specification` so both lists match). *(Note: If deleting the 6th column in Appendix F brings Appendix F back down to 3 pages [pp. 112–114], then `G = 115`, `H = 141`, `I = 149` will be the exact page numbers for both TOC and List of Appendices!)*

### Fix 2.3: Missing Top-Left Page Numbers on Appendices (PDF pp. 119–164)
* **Where:** Starting at the `APPENDICES` divider page (**PDF p. 119**) through the end of the file (**PDF p. 164**).
* **What Happened:** Printed page numbers (`1` to `105`) appear in the header up to the end of `Literature Cited` (PDF p. 118), but disappeared starting at `APPENDICES` (PDF p. 119) because the Word Section Break header was unlinked or cleared.
* **Fix:** Double-click the header of **PDF p. 120 (Appendix A)** in Word and turn on **Link to Previous** (or insert Page Number $\rightarrow$ Top of Page $\rightarrow$ Plain Number 1, continuing from previous section).

### Fix 2.4: Stray `"Appendix D. Application for Thesis Outline Defense"` Caption at the Bottom of Appendices F & G
* **Where:** Bottom of **PDF p. 125 (Appendix F first page)** and bottom of **PDF p. 129 (Appendix G first page)**.
* **What Happened:** Right below `Appendix F. Master Functional Test Cases` (PDF p. 125) and `Appendix G. Test Case Specification` (PDF p. 129), there is a stray line that says: `Appendix D. Application for Thesis Outline Defense`.
* **Fix:** Click and delete that stray `Appendix D...` line at the bottom of those two pages.

---

## Category 3: 🟢 Post-Defense / Final Manuscript Polish *(Safe to Ignore for Outline Defense)*

You can safely leave these alone for now and clean them up after your Outline Defense during manuscript revisions:

| Location | Current Text in `FinalDraft.pdf` | Post-Defense Polish |
| :--- | :--- | :--- |
| **Appendix G (`TC-MOD1-01` to `TC-MOD5-05`, PDF pp. 129–154)** | `Actual Result:` has pre-filled text while `Status: [ ] PASS [ ] FAIL` is blank. | Safe to keep as-is for defense; if asked by a panelist, explain that `Actual Result` reflects initial prototype unit-test runs while `Status` and `Tester/Date` are reserved for formal QA/UAT sign-off. |
| **PDF p. 8 (Table of Contents)** | `Appendix G. Deltailed Test Case Specification` | Fix typo: `Deltailed` $\rightarrow$ **`Detailed`**. |
| **PDF p. 11 (List of Figures, `Figures 22 & 23`)** | Lists `Figure 22` on p. `84` and `Figure 23` on p. `85`. | Their captions actually land on Printed pp. **`85`** and **`86`**. |
| **PDF p. 30 (Printed p. 17, Chapter II Heading)** | `REVIEW RELATED LITERATURE` | Change to **`REVIEW OF RELATED LITERATURE`**. |
| **PDF p. 118 (Printed p. 105, Literature Cited)** | *Susila et al. (2025)* ends with `...2832–2839. Retrieved from` | Delete the hanging words **`Retrieved from`**. |
| **PDF p. 122 (Appendix C Header)** | `Kabacan, Cotabato z` | Remove the stray **`z`**. |
| **PDF p. 131 (`TC-MOD1-03` in Appendix G)** | `Requirement Reference: FR-02: User Authentication` | Add **`(Login)`** so it reads `FR-02: User Authentication (Login)`. |
| **PDF p. 144 (`TC-MOD3-06` in Appendix G)** | `Requirement Reference: FR-010: Live Order...` | Remove extra zero: **`FR-10:`**. |
| **PDF p. 161 (Appendix H TAM — *System Testing & QA*)** | `"25 exhaustive scenario test cases"` and `"all 25 test cases"` | Change both **`25`**s to **`26`**. |
