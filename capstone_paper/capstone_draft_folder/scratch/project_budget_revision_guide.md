# Table 2 Project Development Budget Revision Guide (Outline Defense Stage)

**Document Target:** Printify Capstone Outline Manuscript — Chapter 3 (Methodology)  
**Destination Folder:** `capstone_paper/capstone_draft_folder/scratch/`  
**Purpose:** Replace the unrealistic, AI-generated draft of Table 2 with an accurate, defensible, and institutionally formatted Project Development Budget grounded in actual developer tools and local outline requirements.

---

## 1. Executive Critique: Why the Previous Draft Was Flawed

The previous budget draft drafted by your co-author exhibited telltale signs of generic, ungrounded AI hallucination:

1. **Fabricated Fee for Free Services:** Listed *"GCash sandbox setup and integration testing fees = ₱800.00"*. In reality, GCash and PayMongo developer sandbox portals are **100% free** and provide instant testing API keys without setup charges.
2. **Premature Cloud Infrastructure:** Listed *"Cloud VPS Hosting (₱1,000.00)"* and *"Domain Name & SSL (₱800.00)"*. The project is currently running in a local development environment (Laravel Chisel, Vite, and SQLite). Cloud deployment only takes place during Phase 2 (final manuscript and production deployment).
3. **Artifacts of Purged Methodologies:** Listed *"Printed Survey Questionnaires (₱300.00)"* and *"Evaluator Participation Tokens (₱700.00)"*. These belong to the discarded ISO 25010 survey methodology. The project now uses multi-tier automated SQA testing and functional verification.
4. **Milestone Nomenclature Error:** Labeled item 8.b as *"Manuscript Defense Copies"*. This submission is for the **Outline Defense**, not the Final Manuscript Defense.
5. **Artificial Rounding:** Reverse-engineered to hit an exact arbitrary sum of *"₱10,000.00"*.

---

## 2. Revised Table 2: Estimated Project Development Budget

Below is the updated, realistic, and defensible budget formatted in strict accordance with the **USM Institutional Table Standard (APA 7th Edition style)**—featuring clean horizontal rules, no vertical borders, and right-aligned financial values.

### Table 2. Estimated Project Development Budget

| Budget Category / Item | Detailed Description | Estimated Total Cost (PHP) |
| :--- | :--- | :---: |
| **1. Requirements Analysis** | | |
| a. Transportation & Site Visits | Field travel and local transportation to partner commercial print shops in Kabacan, Cotabato | ₱500.00 |
| **2. Design** | | |
| a. UI/UX Prototyping Software | Figma Professional subscription ($20.00 USD @ ~₱58.00/USD) for collaborative UI wireframing and design token specification | 1,160.00 |
| **3. Development** | | |
| a. AI Coding Assistance & Tooling | Google Gemini Advanced & OpenCode developer subscription for rapid code scaffolding, refactoring, and query optimization | 1,150.00 |
| b. Development Software & IDE License | JetBrains PhpStorm / Pro developer tool license for full-stack Laravel, Livewire, and database schema management | 1,000.00 |
| c. SMS Gateway API Integration | Prepaid developer credit bundle (Semaphore / PhilSMS API) for automated customer SMS order notifications and status alerts | 500.00 |
| **4. Integration** | | |
| a. Payment Gateway Sandbox Integration | GCash Developer Sandbox & PayMongo API for downpayment receipt auditing and sandbox testing | 0.00 *(Free Tier)* |
| **5. Testing** | | |
| a. Multi-Tier Automated SQA Testing | Pest PHP, PHPUnit, and in-memory SQLite automated test execution suite | 0.00 *(Open Source)* |
| **6. Connectivity** | | |
| a. Internet Broadband Connectivity | Monthly high-speed fiber broadband subscription for repository version control, dependency updates, and documentation lookup | 1,499.00 |
| **7. Paper Supplies** | | |
| a. Documentation Printing Materials | Bond paper reams and printer ink refills for working drafts, requirement sheets, and progress documentation | 800.00 |
| **8. Administrative Costs** | | |
| a. Evaluator / Panel Honoraria | Institutional token of appreciation for capstone outline defense panel members and adviser | 1,000.00 |
| b. Outline Defense Manuscript Copies | Hardcopy reproduction, high-resolution laser printing, and soft/ring binding of outline manuscripts for panel evaluation | 1,900.00 |
| **Total Estimated Budget** | | **₱9,509.00** |

*Note: USD to PHP conversion computed at current prevailing exchange rate of $1.00\text{ USD} \approx \text{₱}58.00$. Software development, automated testing, and sandbox payment integrations leverage local toolchains and free open-source frameworks during the outline development stage.*

---

## 3. Line-Item Justification & Defense Talking Points

Should the defense panel or your adviser inquire about specific items in Table 2, use the following explanations:

### 1. Requirements Analysis (₱500.00)
* **Transportation & Site Visits (₱500.00):** Covers fuel and local travel expenses incurred while visiting commercial printing establishments in Kabacan to observe operational shop-floor workflows and intake bottlenecks.
* *Why item 1.b was removed:* Formal stakeholder survey questionnaires were not conducted during initial technical reconnaissance; field inquiries relied on direct observational walkthroughs.

### 2. Design (₱1,160.00)
* **Figma Professional Subscription ($20.00 USD $\approx$ ₱1,160.00):** Figma is the industry-standard collaborative vector design tool. The single-seat paid license allows exporting production-ready CSS variables, UI layout specifications, and high-fidelity screen mockups for client portals and staff Kanban boards.

### 3. Development (₱2,650.00)
* **AI Coding Assistance & Tooling (₱1,150.00 / ~$20 USD):** Google Gemini and developer code models are utilized as pair-programming assistants for backend query optimization, formula validation ($ROP = d \times L + SS$), and complex Livewire reactivity debugging.
* **JetBrains PhpStorm Pro License (₱1,000.00):** Provides professional-grade PHP static analysis, database introspection, and automated refactoring tools tailored specifically for modern Laravel ecosystems.
* **SMS Gateway API Prepaid Credits (₱500.00):** An initial credit bundle for SMS gateways (such as Semaphore or PhilSMS) budgeted early to prepare automated SMS dispatching when customer orders transition stages (e.g., *"Your order ORD-2026-001 is now printing"*).
* *Why Cloud VPS & Domain were excluded:* Development is currently staged on local developer workstations using Laravel Chisel and SQLite. Domain registration and production VPS deployment will be budgeted during the Final Manuscript stage.

### 4. Integration (₱0.00 — Free Developer Sandbox)
* **Payment Gateway Sandbox Integration:** GCash and PayMongo developer sandbox environments do not charge setup fees or require live deposit balances. Test API keys are issued free of charge for staging and academic research.

### 5. Testing (₱0.00 — Open Source Frameworks)
* **Multi-Tier Automated SQA Testing:** Testing is executed using open-source tools (Pest PHP 5, PHPUnit, and in-memory SQLite). Because functional verification is automated programmatically rather than distributed via physical survey questionnaires, no consumable survey expenses are incurred.

### 6. Connectivity (₱1,499.00)
* **Internet Broadband Connectivity:** Reflects the standard monthly billing plan for a home/dorm fiber internet connection required for pulling Composer packages, synchronizing GitHub repositories, and accessing technical documentation.

### 7. Paper Supplies (₱800.00)
* **Documentation Printing Materials (₱800.00):** Covers 2 reams of high-grade 80gsm paper and printer refill inks for printing developer notes, architecture drafts, and revision iterations.
* *Why item 7.b was removed:* Printed survey questionnaires are eliminated because the project abandoned the ISO 25010 survey model in favor of automated SQA test verification.

### 8. Administrative Costs (₱2,900.00)
* **Evaluator / Panel Honoraria (₱1,000.00):** Standard token of appreciation allocated for the expert examination committee.
* **Outline Defense Manuscript Copies (₱1,900.00):** Covers color laser printing and binding for multiple complete copies of the 100+ page outline manuscript distributed to panel members and the project adviser.

---

## 4. Proper Table Formatting Guide (Addressing Comment KC4)

Your manuscript includes a comment from your co-author/adviser: `Commented [KC4]: Follow proper Table format`. 

In university capstones adhering to APA 7th style and USM thesis manuals, tables must strictly follow these layout rules:

1. **Table Title Format:**
   * Above the table, place:  
     `Table 2`  
     *Estimated Project Development Budget* (italicized title on the next line).
2. **Three Horizontal Border Lines Only:**
   * **Top Border:** A single horizontal rule (1 pt or 1.5 pt) directly above the header row.
   * **Header Rule:** A single horizontal rule (0.75 pt) separating the header row from the data rows.
   * **Bottom Rule:** A single horizontal rule (1 pt or 1.5 pt) directly below the summary/Total row.
   * **NO Vertical Borders:** Disable all left, right, and interior vertical grid lines.
3. **Text & Number Alignment:**
   * Category and Description columns: **Left-aligned**.
   * Cost / Currency column: **Right-aligned** so decimal places align vertically.
4. **Table Note:**
   * Place an explanatory note italicized directly below the bottom rule (`Note. USD exchange rate computed at...`).

---

## Action Checklist for Updating Table 2 in Word / Docs

- [ ] Delete the previous Table 2 rows on pages 40–41 of your manuscript.
- [ ] Paste the revised items above with the new total of **₱9,509.00**.
- [ ] Remove vertical grid lines in Word/Docs table properties.
- [ ] Right-align the cost figures and format them with two decimal places.
- [ ] Delete Comment KC4 once the formatting is complete.
