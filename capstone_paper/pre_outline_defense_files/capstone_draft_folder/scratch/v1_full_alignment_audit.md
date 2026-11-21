# Complete Alignment & Minute-Detail Audit (`outline_capstone_v1.pdf`)

**Audited File:** `capstone_paper/capstone_draft_folder/outline_capstone_v1.pdf` (165 PDF pages)  
**Note on Page Numbers:** Below, **Printed Page** is the number printed at the top-left of your manuscript pages; **PDF Page** is the page number in the PDF viewer.

---

## 1. What Is Now Properly Aligned ✅

1. **Statement of the Problem (Printed p. 5 / PDF p. 18):** Properly includes customer self-service ordering, material spoilage recording, and role-based access control (RBAC).
2. **Objectives of the Project (Printed pp. 6–7 / PDF pp. 19–20):** Objectives 1, 2, 3, 4, and 5 are cleanly aligned with the 5 modules and updated to **19 functional requirements**.
3. **Limitations of the Project (Printed p. 9 / PDF p. 22):** Cleanly clarifies that sales and consumption reports are operational summaries and not formal financial accounting/tax statements.
4. **System Modules Subsection (Printed pp. 43–44 / PDF pp. 56–57):** All 5 modules are now formally introduced in the main body before the Functional Requirements.
5. **19 Functional Requirements ↔ 19 Activity Diagrams (Printed pp. 45–57 & 69–89):** Splitting *User Authentication (Login)* and *User Profile Management* into two separate FRs now gives you **19 FRs** matching your **19 Activity Diagrams (`Figures 7 to 25`)**.

---

## 2. Critical Alignment Bugs Found (Must Fix) 🔴

### A. The 18-FR vs. 19-FR Number Shift Collision
Because you inserted **`3. User Profile Management`** into the Functional Requirements list (making old FR-03 Logout become **FR-04**, old FR-04 become **FR-05**, ..., and old FR-18 become **FR-19**), four tables/appendices are now **off by 1**:

#### 1. `Table 1. Functional Test Plan and Requirements Traceability Matrix` (Printed pp. 33–35 / PDF pp. 46–48)
* **Duplicate `FR-03` Bug:**
  * `TS-03` says `FR-03: User Profile Management`
  * `TS-04` **also** says `FR-03: Session Termination & Logout`!
  * Because `TS-04` was not changed to `FR-04`, **every single row from `TS-04` to `TS-19` has the wrong FR number** (`TS-05` says `FR-04` instead of `FR-05`, ..., `TS-19` says `FR-18` instead of `FR-19`).
* **`Module Scope` Column in Table 1:** Still uses 19 random labels (*Customer Onboarding, Security & Access Control, User Profile & Settings, Session Management, Pricing Engine...*) instead of **`Module 1`** to **`Module 5`**.
* **Text Right Above Table 1 (Printed p. 33 / PDF p. 46):** Still says *"across all 19 core functional **modules**"* $\rightarrow$ Change **`modules`** to **`requirements`**.

#### 2. `Table 3. Mapping of Project Objectives, System Modules, and Functional Requirements` (Printed p. 44 / PDF p. 57)
* The last row (`Objective 5`) says `FR-01 to FR-19`, **but the upper 5 rows still use the old 18-FR numbers (`FR-01 to FR-18`) and leave out `FR-19`!**
* **Replace the `Functional Requirements` column in Table 3 with these exact 19-FR numbers:**

| Objective | System Module | Functional Requirements (Corrected for 19 FRs) |
| :--- | :--- | :--- |
| **Objective 1** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-05, FR-07, FR-16, FR-17** |
| **Objective 1** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-06, FR-08, FR-09, FR-10** |
| **Objective 2** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-11, FR-12, FR-14** |
| **Objective 3** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-13, FR-15, FR-18, FR-19** |
| **Objective 4** | Module 1: User Onboarding, Authentication, and RBAC | **FR-01, FR-02, FR-03, FR-04** |
| **Objective 5** | All Modules | **FR-01 to FR-19** |

#### 3. Double-Numbering & Stray Text in the `Functional Requirements` Section (Printed pp. 45–57 / PDF pp. 58–70)
* **Stray Floating Line (Printed p. 46 / PDF p. 59):** Right above `3. User Profile Management`, the words `Session Termination and Logout` are floating on a line by themselves. Delete that stray line.
* **Double-Numbering Glitch (Printed pp. 48–57 / PDF pp. 61–70):** Starting at item 5, Word started a second auto-numbered list (`1.` to `14.`) in front of your typed numbers (`5.` to `19.`), and skipped `10.`:
  * `1. 5. Service Catalog Browsing and Quotation Calculation` (p. 48)
  * `2. 6. Print Order Placement and Document Upload` (p. 48)
  * `3. 7. Print Attribute and Finishing Specification` (p. 49)
  * `4. 8. Customer-Supplied Substrate Declaration` (p. 50)
  * `5. 9. Proof of Payment Submission` (p. 50)
  * `10. Live Order Progress and Status Tracking` (p. 51)
  * `6. 11. Payment Transaction Auditing and Verification` (p. 52)
  * `7. 12. Production Floor 5-Stage Kanban Queue Management` (p. 52)
  * `8. 13. Automated Bill of Materials (BOM) Inventory Deduction` (p. 53)
  * `9. 14. Customer-Supplied Substrate Counter Inspection` (p. 54)
  * `10. 15. Material Spoilage Logging and Stock Adjustments` (p. 54)
  * `11. 16. Dynamic Service and Rate Configuration` (p. 55)
  * `12. 17. Bill of Materials (BOM) Recipe Definition` (p. 55)
  * `13. 18. Inventory Oversight and Dynamic Reorder Point (ROP) Alerting` (p. 56)
  * `14. 19. Sales, Cashflow, and Operational Analytics Generation` (p. 57)
  * **Fix:** Remove the outer `1.`–`14.` auto-numbering so they read cleanly as `1.` through `19.` (or `FR-01` through `FR-19`).

#### 4. `Appendix F` (Printed pp. 113–115 / PDF pp. 126–128) vs. `Appendix G` (Printed pp. 116–142 / PDF pp. 129–155)
* **25 vs. 26 Test Cases Mismatch:**
  * In **Appendix G** (Printed p. 121 / PDF p. 134), you added **`TC-MOD1-06` (`User Profile Management`)**, so Appendix G now has **26 test cases**.
  * **However**, **Appendix F** (Printed p. 113 / PDF p. 126) still says *"25 discrete Test Cases"* and **is missing `TC-MOD1-06` from the table!**
  * **Appendix H (TAM, Printed pp. 149–150 / PDF pp. 162–163)** also still says *"25 exhaustive scenario test cases (TC-MOD1-01 to TC-MOD5-05)"* and *"all 25 test cases"* instead of **26**.
* **Wrong FR Numbers in Appendix F & Appendix G:**
  * In **Appendix G** (pp. 120–121), both `TC-MOD1-05` (Logout) and `TC-MOD1-06` (Profile Management) say `Requirement Reference: FR-03`. `TC-MOD1-05` (Logout) must be **`FR-04`**, and all test cases in Modules 2–5 (`TC-MOD2-01` to `TC-MOD5-05`) in both Appendix F and Appendix G must shift their `FR-XX` reference up by +1 (`FR-04` $\rightarrow$ `FR-05`, ..., `FR-18` $\rightarrow$ `FR-19`).
* **Module 5 Title in Appendix F & Appendix G:**
  * In **Appendix G (Printed p. 138 / PDF p. 151)**, the heading says: `Module 5: Automated Inventory Replenishment, Spoilage & Financial Sales Reporting` $\rightarrow$ Delete the leftover word **`Financial`**.
  * In **Appendix F (Printed p. 115 / PDF p. 128)**, the column `Core System Module` still says `Module 5: Inventory Telemetry` $\rightarrow$ Change to `Module 5: Inventory Replenishment & Reporting`.

---

## 3. Minute Formatting, Caption, & Typo Details (Page-by-Page) 🟡

### Preliminaries & Front Matter (PDF pp. 3–13)
1. **PDF p. 3 (Academic Integrity Statement):** Turn off Word Track Changes / balloons before exporting to PDF (`Formatted: Font: Corbel...` is showing on the right margin).
2. **PDF pp. 4–5 (Approval of Thesis Outline):**
   * Page 5 is an empty duplicate of Page 4—delete Page 5.
   * On Page 4, the Department Chairperson is typed as `RYAN G. GONZAGA`, whereas in Appendices B, C, and D (PDF pp. 122–124) it is `RYAN Z. GONZAGA`. Make the middle initial consistent (`Z.`).
3. **PDF pp. 6–8 (Table of Contents):** Update page numbers after final formatting (e.g., `System Requirement Specifications` is at p. 45, not 41; `Literature Cited` is at p. 104, not 100; Appendices A–I shifted by 4–5 pages).
4. **PDF p. 9 (List of Dummy Tables):**
   * Missing the new `Table 3: Mapping of Project Objectives, System Modules, and Functional Requirements (p. 44)`.
   * Renumber `Software Requirements Specification` to **Table 4** and `Hardware Requirements Specification` to **Table 5** (both in the List of Dummy Tables and on Printed pp. 58–59 / PDF pp. 71–72).
5. **PDF pp. 10–12 (List of Figures):**
   * **Figure 1:** `Conceptual Frame Work` $\rightarrow$ `Conceptual Framework` (one word).
   * **Figure 19:** `Inve n tory` $\rightarrow$ `Inventory`.
   * **Figure 20:** `Customer-Supplied Substrate Intake and counter verification` $\rightarrow$ Capitalize `Counter Verification` and match the exact caption on Printed p. 83 (`Customer-Supplied Substrate Counter Inspection`).
   * **Figure 40:** `Inventory Hun with Automated Restock Alerts` $\rightarrow$ Fix typo **`Hun`** to **`Hub`**.
   * Update the page numbers in the right column (Figures 4 to 41 shifted by ~4 pages).

### Chapter 1 & Chapter 3 Body Text
6. **Printed p. 7 (PDF p. 20) — Expected Outputs Intro Line:**
   * Reads: *"The expected output of the project will include the following to:"* $\rightarrow$ Remove the stray word **`to`** at the end (`"...will include the following:"`).
7. **Printed p. 8 (PDF p. 21) — Expected Outputs Item 3 & Item 5:**
   * **Item 3:** Add a missing period `.` at the end of `...physical follow-ups.`
   * **Item 5:** Add spoilage and sales reporting to match Objective 3: *"...tracks material consumption **and spoilage** and calculates real-time burn rates based on active sales, providing management with data-driven alerts before essential printing supplies reach critical depletion levels**, along with sales and material consumption summary reports**."*
8. **Printed p. 42 (PDF p. 55) — Table 2 (Budget Item 8b):**
   * Item `8b` still reads `b. Manuscript Defense Copies` (in our earlier budget guide, you noted changing `8b` to `Outline Defense Copies`).
9. **Printed p. 43 (PDF p. 56) — Word Comment `[CP1]`:**
   * Delete or hide the comment `[CP1]` before exporting the final PDF.
10. **Printed p. 62 (PDF p. 75) — Above Figure 4 (Use Case Diagram):**
    * Delete the stray letter **`z`** sitting on its own line right above `Figure 4: Use Case Diagram of Printify`.
11. **Printed p. 70 (PDF p. 83) — Above Figure 8 (User Authentication):**
    * Delete the stray quotation mark **`"`** at the very end of the paragraph (`...or Owner Master Hub."`).
12. **Printed p. 88 (PDF p. 101) — Paragraph Above Figure 24:**
    * Reads: *"Figure 24 The activity diagram for Inventory Oversight..."* $\rightarrow$ Add *"shows"* or *"illustrates"* (`"Figure 24 illustrates the activity diagram..."`).

### Appendices (PDF pp. 121–165)
13. **PDF pp. 121, 123, 125 (Appendices A, C, E):**
    * Visible Word Track Changes boxes at the bottom (`Formatted: Line spacing: Double...`).
14. **PDF pp. 121–122 (Appendices A & B):**
    * Only `CHRISTIAN JAMES J. PEREZ` is listed in the student signature block—add `KAYE P. COMISSION` so both project developers are on all forms.
    * In **Appendix B (Application for Research Title, PDF p. 122)**: Objectives 1, 3, and 4 still have the old text while Objective 5 has `19 functional requirements`. Sync Objectives 1, 3, and 4 with Printed pp. 6–7.
15. **PDF pp. 123, 124, 126, 129 (Appendices C, D, F, G Footers):**
    * Duplicate/mismatched footer labels at the bottom of the pages:
      * PDF p. 126 (Appendix F) has `Appendix D. Application for Thesis Outline Defense` printed right below `Appendix F. Master Functional Test Cases`.
      * PDF p. 129 (Appendix G) also has `Appendix D. Application for Thesis Outline Defense` printed right below `Appendix G. Test Case Specification`.
16. **PDF p. 125 (Appendix E: Application for Group Research):**
    * Date at the top says **`October 195, 2026`** $\rightarrow$ Change to **`October 19, 2026`**.
    * Grammar in paragraph 2: *"Christian James J. Perez and Kaye P. Comission **is** duly authorized..."* $\rightarrow$ Change **`is`** to **`are`**, and *"member's name and signature"* $\rightarrow$ *"members' names and signatures"*.
17. **PDF p. 156 (Appendix H: Task Allocation Matrix Header):**
    * Adviser is typed as `DANILYN P. FLORES` $\rightarrow$ Change middle initial to **`DANILYN A. FLORES`**.
18. **PDF p. 161 (Appendix H: TAM — Methodology Phase 2):**
    * Bullet 1 says: *"Drafted, illustrated, and finalized all **eighteen (19)** UML Activity Diagrams..."* $\rightarrow$ Change **`eighteen (19)`** to **`nineteen (19)`**.
19. **PDF p. 164 (Appendix I: Curriculum Vitae):**
    * Typo in Kaye's email address: `s.kapecomission@usam.edu.ph` $\rightarrow$ **`@usm.edu.ph`** (and verify if `s.kapecomission` vs `s.kpcomission`).
    * **Christian James J. Perez's Curriculum Vitae is missing** (only Kaye's CV is in Appendix I).
