# Capstone Adviser Consultation & Defense Preparation Guide
**Project Working Title:** Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment  
**Acronym / Working Name:** Printify  
**Target Event:** Adviser Manuscript Review & Progress Consultation (Post-Title Defense)  
**Authors / Pair Team:** Technical Architect & Development Team  
**Document Purpose:** Unified reference manual bridging Title Defense panel corrections, system architectural evolution, current codebase capabilities, and pair labor division.

---

## 1. Executive Summary: Objective of Tomorrow's Consultation

Bukas, magpapacheck kayo ng draft ng inyong Chapter 3 (Methodology) at System Architecture sa inyong capstone adviser. Ang pangunahing layunin ng meeting na ito ay:

1. **Patunayan na 100% nasunod ang mga pagwawasto (corrections) mula sa Title Defense panel** (partikular ang mga mabibigat na feedback nina **Ma'am Betg** at **Sir Ryan**).
2. **Ilatag ang pormal na System Title** at ipaliwanag kung bakit mas matatag, makatotohanan, at academically defensible ang kasalukuyang pamagat.
3. **Ipakita ang natapos na tatlong (3) core architectural diagrams**:
   - *Entity Relationship Diagram (ERD):* 8-table normalized schema (3NF) na walang redundant tables o crossing lines.
   - *Use Case Diagram:* UML 2.5 compliant na may tamang `<<include>>` at `<<extend>>` dependencies.
   - *Class Diagram:* 11-class object-oriented model na may formal inheritance hierarchy (`User` superclass).
4. **Ipakita ang malinaw na Division of Labor ng magkapares (Pair)** upang mapawi ang pangamba ng panel na baka iisang tao lang ang gumagawa ng buong proyekto.
5. **Patunayan na ang system ay hindi lamang isang "ordinary CRUD"** kundi may tunay na enterprise logic: automated Bill of Materials (BOM) deduction, dynamic Reorder Point (ROP) calculation, at 5-stage workshop Kanban scheduling.

---

## 2. System Title Evolution: Before vs. After

| Stage | Pamagat (Title) | Mga Kahinaan / Puna ng Panelists | Paano Naresolba sa Kasalukuyan |
| :--- | :--- | :--- | :--- |
| **Title Defense Draft 1** | *"Design and development of a web-based order and production management system for printing services"* | **Puna ni Ma'am Betg:** Bawal ilagay ang *"Design and development"* sa title dahil iyon ay automatic nang objective ng study. | Inalis ang *"Design and development"*. Naging mas direct at naka-focus sa core system capability. |
| **Title Defense Draft 2** | *"Web-based order and production management system for Purehandz printing and binding services with demand forecasting"* | **Puna ni Sir Ryan & Ma'am Betg:**<br>1. Alisin ang *"Purehandz"* (ilagay sa scope/delimitation lang).<br>2. Huwag mag-focus lang sa Hardbound dahil seasonal lang.<br>3. Ang *"Demand Forecasting"* ay nangangailangan ng 3-year historical dataset at formal mathematical methods (tulad ng RMSE/Moving Average). Kung walang data, magiging basyo ang AI/ML claim. | 1. Naging generalized SaaS-ready para sa printing establishments.<br>2. Pinalawak sa lahat ng printing services (document printing, binding, booklet, custom print).<br>3. Pinalitan ng **Automated Replenishment / Dynamic Reorder Point (ROP) Telemetry** na nakabatay sa aktwal na Bill of Materials (BOM) consumption burn rate. |
| **Final Approved Working Title** | **"Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment"** | **Status:** 100% compliant sa USM BSIS standards at sumasagot sa bawat puna ng panel. | Malinaw na naglalarawan ng 3 pillars: (1) Dynamic Ordering, (2) 5-Stage Kanban Scheduling, at (3) Automated Inventory Replenishment. |

### Talking Points para sa Adviser tungkol sa Title:
* *"Sir/Ma'am, inalis na po natin ang 'Design and Development' at ang shop name sa title ayon sa bilin ng panel. Nilipat po natin ang specific establishment (Purehandz) sa Scope and Delimitation."*
* *"Sa halip po na mag-claim tayo ng high-level Machine Learning Demand Forecasting na nangangailangan ng 3 taong sales history na wala naman ang printing shop, pinalitan po natin ito ng **Automated Replenishment through Bill of Materials (BOM) and Dynamic Reorder Point (ROP)**. Ito po ay mathematically exact at direktang lumulutas sa problema ng shop sa biglaang pagkaubos ng papel o tinta habang may rush orders."*

---

## 3. Title Defense Compliance Matrix (Panel Feedback vs. Solution)

Bawat puna sa inyong Title Defense minutes ay may kaukulang technical solution na nakapaloob na sa system at manuscript:

| # | Komento / Puna ng Panelist | Sino ang Nagpuna | Solusyon at Kasalukuyang Implementasyon sa System |
| :---: | :--- | :---: | :--- |
| **1** | *"Diagrams need to be specific, ayusin ang pag gamit ng mga symbols, make sure that we understand the diagram."* | Ma'am Betg | **Naresolba:** Lahat ng diagrams (ERD, Use Case, Class Diagram) ay sumusunod sa strict UML 2.5 standard. Ang Use Case Diagram ay may pabalik na arrow sa `<<extend>>`, at ang Class Diagram ay may 3 compartments na may formal data types at multiplicities. |
| **2** | *"Hindi pwede na isa lang ang mag work sa project dapat clear ang modules & functions ng bawat isa."* | Ma'am Betg | **Naresolba:** May pormal nang delineation: Si Partner 1 ang may hawak ng Backend Architecture, ERD, Class Diagram, at BOM Engine; Si Partner 2 ang may hawak ng Frontend Workflows, Activity Diagrams, SRS, at ISO 25010 Evaluation. |
| **3** | *"Include Inventory module for production but production module is different to inventory module."* | Ma'am Betg & Sir Ryan | **Naresolba:** Ganap na pinaghiwalay ang **Production Hub** (5-stage Kanban queue) at ang **Inventory Hub** (warehouse stock tracking). Ang nag-uugnay lamang sa kanila ay ang automated background service na `ServiceBom`. |
| **4** | *"Dapat hindi mag focus lang sa hardbound kasi seasonal lang... All printing and binding must be included."* | Sir Ryan | **Naresolba:** Inalis ang hardcoded hardbound schema. Pinalitan ng universal dynamic service architecture (`ShopServices` + `specifications` JSON) na sumusuporta sa Document Printing, Softbound, Ring Bind, Loose Leaf, at mga custom print products. |
| **5** | *"Predictions of the raw materials if ilan ang kaya nyang ma cater na orders... mag accept lang ng orders base sa raw materials sa inventory."* | Sir Ryan | **Naresolba:** Binuo ang real-time inventory deduction at feasibility calculation. Bago mag-advance ang isang job sa production, sinusuri ng system ang stock availability gamit ang `ServiceBom` recipes. |
| **6** | *"Alisin ang AI kasi may demand forecasting na... moving average or ano ba."* | Sir Ryan | **Naresolba:** Inalis ang vague AI buzzwords. Ipinalit ang **Rolling Daily Consumption Velocity (Burn Rate)** gamit ang 7-day at 30-day moving average mula sa `stock_movements` ledger para kalkulahin ang dynamic Reorder Point (ROP = Lead Time Demand + Safety Stock). |
| **7** | *"Production management dapat may job scheduling. Ilang tao ba meron sila, ilang matatapos this day."* | Sir Ryan | **Naresolba:** Ipinatupad ang visual **5-Stage Workshop Kanban Board** (*Queue $\rightarrow$ Printing $\rightarrow$ Finishing $\rightarrow$ Quality Check $\rightarrow$ Ready for Pickup*) na may field para sa `assigned_staff_id`, machine allocation, at timestamps. |
| **8** | *"Gcash recording lang dapat upload lang ng receipt and i-verify lang... mas i-priority nila ang full payment and rush order."* | Sir Ryan | **Naresolba:** Nagdagdag ng `payment_proof_path`, `payment_reference_no`, at manual staff verification workflow. Mayroon ding dynamic rush fee computation at `is_rush` priority flag. |
| **9** | *"Customer-supplied paper ('Dala ang Papel') consideration."* | Sir Ryan | **Naresolba:** Nagdagdag ng *"Dala ang Papel"* toggle sa ordering wizard. Awtomatikong binabawas ang presyo ng papel sa quotation, binabypass ang stock deduction, at naglalagay ng counter verification task sa job ticket ng staff. |
| **10**| *"Software as a Service na sya para sa mga printing shop."* | Sir Ryan | **Naresolba:** Ang `PrintShops` entity ay multi-tenant ready na may 1:1 ownership link sa `Users` (`owner_user_id`), kung saan bawat shop owner ay may sariling independent catalog at pricing rules. |

---

## 4. Architectural & Technical Overhauls Done Since Title Defense

### A. Database Optimization: From 14+ Anti-Pattern Tables to 8 Unified Entities
Dati, nagkaroon ng "table-per-service" anti-pattern kung saan bawat bagong serbisyo (hal. `document_printing_configs`, `thesis_binding_configs`) ay gumagawa ng bagong table na may tumatagos na anim (6) na foreign keys papuntang inventory.

**Ang Ginawang Normalization (3NF):**
1. **`Users`**: Central authentication at Role-Based Access Control (`business_owner`, `production_staff`, `customer`).
2. **`PrintShops`**: Multi-tenant anchor ng tindahan (1:1 sa Owner).
3. **`ShopServices`**: Isang generic catalog table na may structured `settings` (JSON) para sa dynamic pricing formulas at turnaround times.
4. **`ServiceBoms`**: Isang pinag-isang Bill of Materials repository na nagmamapa ng serbisyo patungo sa inventory consumables (`per_copy`, `per_page`, o `per_sheet` duplex rules).
5. **`InventoryItems`**: Central inventory ng raw materials at ready-to-sell assets na may unit costs at reorder levels.
6. **`StockMovements`**: Immutable audit ledger na nagtatala ng bawat bawas (production deduction), dagdag (supplier restock), o tapon (spoilage/waste).
7. **`Orders`**: Sentral na transaksyon na may 5-stage workshop Kanban tracking at payment verification.
8. **`OrderItems`**: Universal line-item table na may `specifications` (JSON) na kayang tumanggap ng kahit anong uri ng print job nang hindi binabago ang database structure.

### B. Strict Role-Based Access Control (RBAC) Security
Ipinatupad ang mahigpit na route middleware sa Laravel:
* `/owner/*` $\rightarrow$ Para lamang sa Business Owner (Admin, Settings, Financial Analytics).
* `/staff/*` $\rightarrow$ Para lamang sa Production Staff (Kanban board, Payment Verification, Stock Adjustments).
* `/customer/*` $\rightarrow$ Para lamang sa Customer (Catalog, Order Wizard, Receipt Upload, Live Order Tracking).
* Awtomatikong hinaharang at inililihis ang sinumang user na susubok pumasok sa URL ng ibang role.

---

## 5. Current System Feature Matrix (Working in Code)

Ito ang mga natapos at gumaganang modules sa Laravel application na pwede ninyong ipagmalaki sa adviser:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          PRINTIFY SYSTEM ARCHITECTURE                       │
├────────────────────────┬────────────────────────────┬───────────────────────┤
│  1. CUSTOMER PORTAL    │   2. PRODUCTION HUB        │  3. OWNER MANAGEMENT  │
├────────────────────────┼────────────────────────────┼───────────────────────┤
│ • Dynamic Instant      │ • 5-Stage Visual Kanban    │ • Multi-Service Setup │
│   Quotation Calculator │   Board (Drag/Click Move)  │   & Dynamic Pricing   │
│ • Custom Print Specs   │ • Operator Assignment &    │ • Bill of Materials   │
│   (GSM, Size, Binding) │   Machine Allocation       │   (BOM) Recipe Studio │
│ • "Dala ang Papel"     │ • GCash/Maya Receipt Proof │ • Inventory Balances  │
│   Discount Toggle      │   Verification Modal       │   & Reorder Alerts    │
│ • Artwork File Upload  │ • Counter Substrate Intake │ • Sales, Margins, and │
│ • Real-time Stage View │ • Automated BOM Deduction  │   Product Mix Charts  │
└────────────────────────┴────────────────────────────┴───────────────────────┘
```

1. **Customer Dynamic Quotation & Order Placement:**
   - Real-time price breakdown base sa bilang ng pahina, kulay (colored vs monochrome), binding type, at duplex discount.
   - Pagsusumite ng digital PDF o image files.
2. **Manual Payment Verification Queue:**
   - Uploading ng screenshot ng GCash/Maya/Bank reference.
   - May viewable receipt lightbox ang production staff para i-verify bago i-queue ang trabaho.
3. **5-Stage Kanban Production Board:**
   - Malinaw na visual columns: *Pending Queue $\rightarrow$ Printing $\rightarrow$ Assembly $\rightarrow$ Quality Check $\rightarrow$ Ready for Pickup*.
   - May operator tagging at audit timestamps sa bawat paglipat ng stage.
4. **Automated Inventory Deduction Engine (`InventoryDeductionService`):**
   - Kapag ang order ay lumipat sa printing/assembly, awtomatikong binabawas ng system ang eksaktong bilang ng sheets at toner base sa `ServiceBom` recipe.
   - Kung duplex (back-to-back), awtomatikong hinahati sa dalawa ang sheet requirement (`ceil(pages / 2)`).
5. **Dynamic Reorder Point (ROP) & Spoilage Tracking:**
   - Real-time warning badge kapag bumaba ang stock sa threshold.
   - May hiwalay na modal para magtala ng tapon o sirang materyales (*spoilage waste*) para laging tugma ang pisikal na bilang sa warehouse.

---

## 6. Division of Labor & Team Responsibilities

Upang masagot ang mahigpit na bilin ni **Ma'am Betg** (*"Hindi pwede na isa lang ang mag-work sa project, dapat clear ang modules & functions ng bawat isa"*), narito ang opisyal na hatian ng inyong pair team:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       CAPSTONE PAIR DIVISION OF LABOR                       │
├──────────────────────────────────────┬──────────────────────────────────────┤
│     PARTNER 1 (System Architect)     │  PARTNER 2 (Process & Quality Lead)  │
├──────────────────────────────────────┼──────────────────────────────────────┤
│ • Technical Architecture & Schema    │ • Business Process Workflow Modeling │
│   (3NF Normalized ERD Design)        │   (Activity & Swimlane Diagrams)     │
│ • Structural Domain Modeling         │ • System Requirement Specifications  │
│   (11-Class UML Class Diagram)       │   (SRS Functional Specs & Wireframes)│
│ • Automated BOM Deduction Engine     │ • Frontline Customer Order Portal &  │
│   & Inventory Telemetry Services     │   Tracking Experience Workflows      │
│ • Role-Based Security & Middleware   │ • ISO 25010 Software Quality         │
│ • Database Migrations & Seeders      │   Evaluation Framework & Survey Tool │
│ • Backend Test Suites (Pest/PHPStan) │ • Multi-Establishment Data Gathering │
└──────────────────────────────────────┴──────────────────────────────────────┘
```

### Detalyadong Paliwanag para kay Adviser:
* **Si Partner 1** ang namahala sa **Structural at Technical Foundations**:
  - Pagsasaayos ng database schema (ERD) mula sa magulong tables tungo sa normalized 8-table design.
  - Paggawa ng object-oriented UML Class Diagram kasama ang inheritance hierarchy.
  - Pagbuo ng backend logic para sa automated Bill of Materials (BOM) deduction at security authentication (RBAC).
* **Si Partner 2** ang namahala sa **Operational Workflows at System Evaluation**:
  - Pagmomodelo ng end-to-end business workflows gamit ang Activity Diagrams at swimlanes (mula customer submission hanggang counter pickup).
  - Pagsulat ng System Requirement Specifications (SRS) at input-process-output mappings.
  - Pagdidisenyo ng ISO 25010 evaluation instruments (survey questionnaires at testing protocols) at pakikipag-ugnayan sa 3 printing shops para sa empirical data gathering.

---

## 7. Anticipated Adviser Questions & Winning Responses

Ito ang mga inaasahang itatanong ng inyong adviser bukas at ang inihandang propesyonal na sagot ng inyong team:

### Q1: *"Bakit pinalitan ninyo ang Demand Forecasting sa title? Hindi ba 'yun ang naaprubahan sa title defense?"*
* **Sagot:** *"Sir/Ma'am, pinuna po kasi nina Sir Ryan at Ma'am Betg noong defense na ang true Demand Forecasting ay nangangailangan ng 3 taong empirical sales data para makabuo ng predictive mathematical model tulad ng moving averages o regression, na wala po ang local printing shops. Binalaan po kami ng panel na magiging 'ordinaryong CRUD' o pilit ang AI kung walang datos. Kaya iminungkahi po nilang gawin itong **Automated Replenishment based on Bill of Materials (BOM) and Dynamic Reorder Points (ROP)**. Mas makatotohanan po ito, gumagana agad kahit sa bagong shop, at direktang lumulutas sa problema ng stockouts habang may active jobs."*

### Q2: *"Paano ninyo siniguro na hindi seasonal at hindi lang sa Hardbound thesis nakatali ang system?"*
* **Sagot:** *"Sir/Ma'am, inayos po natin ang architecture gamit ang dynamic catalog model (`ShopServices`). Sinusuportahan na po ng system ang Document Printing (loose leaf, corner staple, sliding folder, ring bind, booklet) at kahit anong customized print services. Ginamitan po natin ng flexible JSON specifications ang mga order items para kahit magdagdag ang shop ng t-shirt printing o sticker labels sa hinaharap, hindi na kailangang baguhin o i-migrate ang database."*

### Q3: *"Bakit magkahiwalay ang Production Module at Inventory Module kung pareho namang gumagamit ng materyales?"*
* **Sagot:** *"Ayon din po sa mungkahi ni Ma'am Betg, magkaiba ang concern ng shop floor sa warehouse. Ang **Production Module** ay nakatuon sa time-sensitive workflow—pila ng mga makina, assignment ng operator, at 5-stage Kanban scheduling. Ang **Inventory Module** naman ay nakatuon sa stock valuation, unit acquisition costs, supplier replenishment, at spoilage waste. Ang nagdudugtong lamang po sa kanila sa background ay ang `ServiceBom`, kung saan awtomatikong nababawasan ang warehouse stocks kapag pumasok sa production stage ang isang job order nang hindi nagkakagulo ang data."*

### Q4: *"Paano kung magdala ang estudyante ng sarili nilang papel ('Dala ang Papel')? Mababawasan ba ang inventory ng tindahan?"*
* **Sagot:** *"Mayroon po tayong dedicated toggle na 'Customer-Supplied Substrate'. Kapag pinili ito ng customer, awtomatikong binabawas ng pricing formula ang cost ng papel sa quotation, binabypass ng system ang deduction ng shop paper sa warehouse, at tinatatakan ang digital job ticket upang beripikahin ng staff sa counter ang physical paper bago simulan ang pag-imprenta."*

### Q5: *"Kumusta ang hatian ninyong dalawa sa paggawa ng capstone?"*
* **Sagot:** *"Napakalinaw po ng aming division of labor. Si [Partner 1] po ang nakatutok sa System Architecture, Database Normalization (ERD), Class Diagram, at ang backend logic ng automated BOM deductions. Si [Partner 2] naman po ang nangunguna sa Business Process Modeling (Activity Diagrams), Customer Experience Workflows, System Requirements Specification, at ang ISO 25010 Quality Evaluation framework kasama ang field testing sa tatlong partner printing establishments."*

---

## 8. Checklist para sa Consultation Bukas

- [ ] I-print o i-handa sa tablet/laptop ang PDF version nitong Guide (`adviser_consultation_guide.pdf`).
- [ ] Buksan ang rendered diagrams:
  - `capstone_paper/capstone_draft_folder/diagrams/final_diagrams/ERD-draft.png`
  - `capstone_paper/capstone_draft_folder/diagrams/final_diagrams/use_case_diagram_final.png`
  - `capstone_paper/capstone_draft_folder/diagrams/class_diagram_draft.png` (o ang na-export na final image)
- [ ] Buksan ang draft ng Chapter 3 sa [`chapter_3_methodology_draft.md`](file:///home/imaginarycjay/printify/capstone_paper/capstone_draft_folder/outline_draft/chapter_3_methodology_draft.md) para ipakita ang pormal na academic narratives sa bawat diagram.
- [ ] Mag-usap kayong magkapares bago pumasok sa faculty room at i-divide kung sino ang magpapaliwanag ng bawat slide o section.
