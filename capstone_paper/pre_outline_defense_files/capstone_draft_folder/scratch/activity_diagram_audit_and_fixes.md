# Activity Diagram Revision Audit and Fixes Guide

**Document Target:** Google Docs Live Manuscript (`Real Capstone Paper` tab)  
**Section in Scope:** Chapter 3 – Methodology $\rightarrow$ Activity Diagrams (Figures 7 to 25)  
**Purpose:** Precise, step-by-step diagnostic and remediation guide for project developers (Comission & Partner) to resolve all structural, grammatical, and numbering errors in the draft.

---

## 1. Deep Dive into Issue 1: Clarifying the "Figure 7 Duplicate"

### Why You Couldn't Find the Duplicate under Figure 7
If you looked directly under **Figure 7**, you only saw one caption: `Figure 7. User Account Registration`. That is correct—Figure 7 itself looks normal.

The duplicate **is NOT under Figure 7**. The duplicate caption was accidentally pasted **under the diagram of Figure 8**!

---

### Exactly What the Document Contains Right Now (Raw Extraction)

Here is the exact sequential text and image order extracted directly from the `Real Capstone Paper` tab:

```text
[PARAGRAPH 1 - Figure 7 Narrative]
Figure 7 illustrates the User Account Registration process, where prospective customers provide their name, email, mobile number, and password...

[IMAGE 1 - Registration Activity Diagram]

[CAPTION 1]
Figure 7. User Account Registration

-------------------------------------------------------------------------

[PARAGRAPH 2 - Figure 8 Narrative]
Figure 8 shows the activity diagram for the User Authentication and Profile Management. It presents the user entering their credentials...

[IMAGE 2 - Authentication Activity Diagram]

[BLANK SPACER LINES / ENTERS (7 blank lines)]

[STRAY DUPLICATE CAPTION]  <--- HERE IT IS!
Figure 7. User Account Registration

[ACTUAL CAPTION FOR FIGURE 8]
Figure 8. User Authentication and Profile Management

-------------------------------------------------------------------------

[PARAGRAPH 3 - Figure 9 Narrative]
Figure 9 illustrates the activity diagram for Session Termination and Logout process...
```

---

### Why Did This Happen?
When drafting Figure 8, Comission likely copied the entire caption block from Figure 7 (`Figure 7. User Account Registration`) to reuse the centered font formatting for Figure 8. After typing `Figure 8. User Authentication and Profile Management` on the next line, the original copied `Figure 7` line was accidentally left in place right above it.

Because there are multiple blank lines (`Enter` presses) between the Figure 8 diagram and the caption, the duplicate line may sit at the bottom of the page or directly above Figure 8's title in Google Docs.

### How to Fix Issue 1 in Google Docs (10 Seconds)
1. In the `Real Capstone Paper` tab, scroll down to the **User Authentication and Profile Management** diagram (Figure 8).
2. Look directly below the Figure 8 diagram image.
3. You will see:
   ```text
   Figure 7. User Account Registration
   Figure 8. User Authentication and Profile Management
   ```
4. **Highlight and delete** the line that says `Figure 7. User Account Registration`.
5. Remove any excessive empty blank lines between the image and the caption.

---

## 2. Complete Punch List of All Activity Diagram Issues & Fixes

Below is the complete list of all 8 issues found across the 18 Activity Diagrams, accompanied by exact copy-paste replacements.

---

### Issue 1: Duplicate Figure 7 Caption under Figure 8
* **Status:** Actionable deletion.
* **Location:** Directly below the Figure 8 diagram image.
* **Action:** Delete the stray line `Figure 7. User Account Registration`.

---

### Issue 2 & 3: Figure 22 Caption Numbering Mismatch & Stray Duplicate Image
* **Status:** Critical structural defect.
* **Location:** Between Figure 21 (Dynamic Service Configuration) and Figure 24 (Inventory Oversight).
* **The Glitch:**
  1. The narrative text says:
     > *"Figure 22 shows the activity diagram for Bill of Materials (BOM) Recipe Definition depicts the administrative linking..."*
  2. The caption directly below the diagram says:
     > `Figure 23 Bill of Materials (BOM) Recipe Definition` *(It skipped Figure 22!)*
  3. Right below this caption, there is an **unlabeled second diagram image**. An MD5 hash inspection confirms this image is an exact duplicate of the Analytics diagram (Figure 25).
* **Step-by-Step Fix:**
  1. **Delete** the extra diagram image sitting below the BOM Recipe caption.
  2. **Change the caption** from `Figure 23 Bill of Materials (BOM) Recipe Definition` to:
     ```text
     Figure 22. Bill of Materials (BOM) Recipe Definition
     ```
  3. **Fix the double-verb** in the narrative paragraph (see Issue 6 below).

---

### Issue 4: Downstream Numbering Alignment (Figures 22 to 24 vs. UI Figures)
Because the BOM Recipe caption was accidentally labeled as `Figure 23`, Comission numbered:
* Inventory Oversight as **Figure 24**
* Analytics as **Figure 25**
* User Interface Landing Page as **Figure 26**

There are strictly **18 Functional Requirements** (FR-01 to FR-18). Since Activity Diagrams begin at **Figure 7**, the 18th diagram must mathematically be **Figure 24** ($7 + 17 = 24$). Currently, Figure 22 has no caption in the document.

#### Standard Recommendation (Strict Sequential Alignment):
Renumber the final two activity diagrams so there are no skipped figure numbers:
1. **Figure 22:** Bill of Materials (BOM) Recipe Definition (FR-16)
2. **Figure 23:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting (FR-17)
3. **Figure 24:** Sales, Cashflow, and Operational Analytics Generation (FR-18)
4. **Figure 25:** User Interface – Landing Page (adjust downstream UI Figures from 26–41 to 25–40).

> [!TIP]
> If your group decides **not** to renumber the 16 User Interface figures (Figures 26–41), you must still resolve why Figure 22 has no caption. An external panelist or adviser reading "Figure 21... Figure 23... Figure 24..." will immediately flag the missing Figure 22. Renumbering ROP to 23 and Analytics to 24 produces a flawless, defensible manuscript.

---

### Issue 5: Severed Text Glitch at Figure 18 (FR-12)
* **Status:** Text truncation error.
* **Location:** At the very end of the paragraph for Figure 18 (Automated BOM Inventory Deduction).
* **Current Text:**
  > `...The system then updates inventory levels and records each deduction for tracking and auditing. es restocking threshold evaluation.`
  *(Notice the severed fragment "es restocking threshold evaluation.")*
* **Current Caption:**
  > `Figure 18 Automated Bill of Materials (BOM) Inventory Deduction` *(Missing period after 18)*
* **Copy-Paste Replacement for Narrative:**
  > Figure 18 illustrates the Automated Bill of Materials (BOM) Inventory Deduction process, where the system automatically calculates and deducts materials as an order enters production. For customer-supplied paper, paper deduction is skipped, while standard orders calculate paper usage based on pages and copies. Finishing materials such as ring spines, PVC covers, and backings are deducted based on their defined quantities. The system then updates inventory levels and records each deduction for tracking and auditing, followed by automated restocking threshold evaluation.
* **Copy-Paste Replacement for Caption:**
  ```text
  Figure 18. Automated Bill of Materials (BOM) Inventory Deduction
  ```

---

### Issue 6: Grammatical Double-Verb Polish
In several introductory sentences, two main active verbs were combined (e.g., *"shows... depicts"* or *"shows... models"*). Use these copy-paste sentences to ensure clean academic phrasing:

#### Figure 10 (FR-04): Service Catalog Browsing and Quotation Calculation
* **Current:** *"The figure 10 shows the activity diagram for Service Catalog Browsing and Quotation Calculation depicts the interactive estimation process..."*
* **Copy-Paste Replacement:**
  > Figure 10 illustrates the activity diagram for Service Catalog Browsing and Quotation Calculation, depicting the interactive estimation process on the customer storefront. The client navigates through available service offerings (e.g., document printing, thesis binding, merchandise) and adjusts operational parameters, including page quantities, color profiles (monochrome or full color), paper grammage, and copy volume. The reactive Livewire component intercepts each parameter change without triggering a manual browser reload. The engine interrogates database pricing formulas, computes unit sheet rates, incorporates finishing surcharges, and renders an illuminated, itemized cost subtotal in real time, providing transparent pricing before order commitment.

#### Figure 12 (FR-06): Print Attribute and Finishing Specification
* **Current:** *"Figure 12 shows the activity diagram for Print Attribute and Finishing Specification models the structural parameterization..."*
* **Copy-Paste Replacement:**
  > Figure 12 presents the activity diagram for Print Attribute and Finishing Specification, modeling the structural parameterization of custom print jobs. The customer configures exact technical attributes: paper dimensions (Short, A4, Long), paper thickness (70, 80, 100 gsm), print orientation (simplex single-sided or duplex double-sided), and binding finishes (plastic comb, sliding folder, softbound, or hardbound foil stamping). The specification engine inspects page counts against physical binding thresholds (e.g., maximum page limits for sliding folders) and executes duplex mathematical calculations where physical sheet requirements equal $\lceil \text{Pages}/2 \rceil \times \text{Copies}$. The system encapsulates these attributes into structured JSON specifications within the order_items entity.
* **Caption Fix:** Add a period after 12 $\rightarrow$ `Figure 12. Print Attribute and Finishing Specification`.

#### Figure 13 (FR-07): Customer-Supplied Substrate Declaration
* **Current:** *"Figure 13 shows activity diagram for Customer-Supplied Substrate Declaration illustrates the client checkout extension..."*
* **Copy-Paste Replacement:**
  > Figure 13 illustrates the activity diagram for Customer-Supplied Substrate Declaration, outlining the client checkout extension for "Dala ang Papel" orders. When placing an order, the customer activates the substrate declaration toggle, specifying their paper brand, type, and sheet volume. The pricing engine detects the flag, zeroes out shop raw paper material fees from the running subtotal, and designates the line item as cover-only fulfillment. Upon order submission, the system attaches an "Awaiting Paper Delivery" intake flag to the digital job ticket, ensuring that shop paper is not billed while alerting the client to deliver their paper bundle to the counter.
* **Caption Fix:** Add a period after 13 $\rightarrow$ `Figure 13. Customer-Supplied Substrate Declaration`.

#### Figure 14 (FR-08): Proof of Payment Submission
* **Current:** *"Figure 14 shows the activity diagram for Proof of Payment Submission models client remittance submission..."* (also contains an accidental line break).
* **Copy-Paste Replacement:**
  > Figure 14 depicts the activity diagram for Proof of Payment Submission, detailing client remittance submission workflows. Following order placement, the customer navigates to the payment step, selecting either counter cash settlement or mobile wallet options (GCash or Maya). For digital transactions, the customer scans the shop payment QR code, inputs the transaction reference number, and uploads a screenshot of the remittance receipt. The server validates image dimensions and file formats, stores the receipt file on a private disk, updates the order payment status to "pending_verification", and alerts workshop staff for audit.

#### Figure 15 (FR-09): Live Order Progress and Status Tracking
* **Current:** *"Figure 15 shows the Live Order Progress and Status Tracking depicts real-time operational telemetry..."*
* **Copy-Paste Replacement:**
  > Figure 15 illustrates the activity diagram for Live Order Progress and Status Tracking, depicting real-time operational telemetry available to clients. The customer accesses their dashboard or inputs their unique tracking code. The tracking subsystem queries active order records from the database, extracts current production flags, and checks stage transition timestamps. The system renders an illuminated, color-coded five-stage stepper component (Queue, Printing, Finishing/Assembly, Quality Inspection, Ready for Pickup). Completed milestones display checkmarks and recorded timestamps, the active milestone displays an illuminated pulse badge, and estimated fulfillment times are updated dynamically.

#### Figure 22 (FR-16): Bill of Materials (BOM) Recipe Definition
* **Current:** *"Figure 22 shows the activity diagram for Bill of Materials (BOM) Recipe Definition depicts the administrative linking..."*
* **Copy-Paste Replacement:**
  > Figure 22 illustrates the activity diagram for Bill of Materials (BOM) Recipe Definition, depicting the administrative linking between commercial services and inventory consumables. The business owner accesses the BOM Recipe Manager, selects a target service (e.g., Thesis Hardbound), and maps required raw stock items (e.g., chipboard, book cloth, foil ribbon, end-sheets) from the inventory catalog. The owner defines consumption coefficients (such as 2 sheets per book or 1 coil per booklet). The system validates foreign key relationships, writes recipe mappings to the service_boms configuration table, and binds the operational consumption rules to automated floor deduction routines.

---

### Issue 7: Minor Formatting & Caption Consistency
1. **Figure 11:** Join the two separate lines of the caption into one line: `Figure 11. Print Order Placement and Document Upload`.
2. **Figure 20:** Capitalize "logging" in the caption: `Figure 20. Material Spoilage Logging and Stock Adjustments`.

---

### Issue 8: Preliminary "List of Figures" Synchronization
* **Location:** Page 4 of the manuscript (`LIST OF FIGURES` preliminary table).
* **Current State:** The table on page 4 still contains the outdated 10-diagram structure from earlier drafts.
* **Action:** Once the figure numbers in Chapter 3 are finalized, synchronize the `LIST OF FIGURES` table on page 4 with the exact titles and page numbers.

---

## 3. Quick Checklist for Comission (5-Minute Cleanup)

- [ ] **Figure 8:** Delete the stray `Figure 7. User Account Registration` line under the Figure 8 diagram.
- [ ] **Figure 10:** Update the first sentence to remove *"shows... depicts"*.
- [ ] **Figure 11:** Rejoin the caption onto a single line.
- [ ] **Figure 12:** Add a period after `Figure 12.` and update opening sentence.
- [ ] **Figure 13:** Add a period after `Figure 13.` and update opening sentence.
- [ ] **Figure 14:** Update opening sentence and remove stray line break.
- [ ] **Figure 15:** Update opening sentence to include *"activity diagram for"*.
- [ ] **Figure 18:** Replace the ending sentence fragment (`es restocking...`) and add period to `Figure 18.`.
- [ ] **Figure 20:** Capitalize `Logging` in caption.
- [ ] **Figure 22:**
  - Change caption from `Figure 23` to `Figure 22. Bill of Materials (BOM) Recipe Definition`.
  - Delete the duplicate diagram image directly below this caption.
  - Update narrative opening sentence.
- [ ] **Figure 23 & 24:** Align figure numbers for ROP Alerting and Analytics.
