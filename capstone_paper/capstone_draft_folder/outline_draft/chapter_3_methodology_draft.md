# CHAPTER 3: METHODOLOGY

This chapter presents the methodology used in conducting the study. It describes the research design, the participants involved, data collection procedures, system development approach, and evaluation methods applied in developing and assessing the proposed system. It also outlines the procedures followed by the researchers to ensure that the system meets its objectives and satisfies the operational needs of the printing shop.

### Project Design
This study adopts a Developmental Research Design, which focuses on the design, development, implementation, and evaluation of an information system to solve a practical problem. This design is appropriate because the primary goal of the study is to create an integrated web-based system that combines dynamic order management, job scheduling, inventory management, and automated replenishment for printing services. 

Through this design, the researchers systematically identify operational requirements, design and build the system modules, test for functional errors, and evaluate the platform using the ISO 25010 software quality standard to ensure its practical usability and effectiveness.

### Project Participants and Materials
The project participants include the business owners, production staff, and customers of the printing-service establishment. The business owners provide essential information regarding shop operations, printing services, pricing rules, and inventory management, while evaluating the administrative tools of the system. The production staff provide details on actual shop workflows, daily tasks, machinery use, and material consumption, and test the job scheduling and production tracking features. The customers, including students and local clients, share feedback on the ordering process and order tracking to help assess the usability and convenience of the customer portal.

The materials used in developing the system include computers and laptops for programming, mobile phones for testing responsiveness, a stable internet connection, and development tools such as Visual Studio Code, database servers, and web development frameworks. In addition, existing shop records, such as sample receipts, job orders, and price lists, serve as reference materials to ensure that the system reflects the actual daily transactions of the printing business.

### Data Collection
The researchers will gather data using interviews, direct workplace observations, document reviews, and survey questionnaires to understand the operational requirements of the printing business.

First, semi-structured interviews will be conducted with the business owners and production staff to identify current operational challenges, service pricing rules, job scheduling workflows, and inventory restocking habits. Second, direct observations of daily shop activities will be carried out to observe counter order intake, task delegation across workstations, and material handling on the workshop floor. Third, the researchers will examine existing business documents, including sample receipts, manual job order slips, supplier invoices, and price lists, to accurately model pricing formulas and material consumption in the system. Finally, survey questionnaires based on the ISO 25010 software quality standard will be distributed to end-users (owners, staff, and customers) and IT experts during the evaluation phase to assess the system's performance, usability, and functional quality.

### Data Analysis Procedure
The data gathered from interviews and observations will be organized and analyzed qualitatively to determine the core functional requirements and workflow design of the system. 

For the system evaluation, responses collected from the evaluation questionnaires will be analyzed quantitatively using descriptive statistics, specifically frequency counts, percentage distributions, and the weighted mean. The computed weighted means for the ISO 25010 criteria—functional suitability, usability, performance efficiency, and security—will be interpreted using the four-point Likert scale shown in Table 1.

**Table 1. Likert Scale for Evaluating System Quality (ISO 25010 Standard)**

| Scale / Value | Range of Mean | Descriptive Rating | Interpretation |
| :---: | :---: | :--- | :--- |
| **4** | 3.50 – 4.00 | Strongly Agree (SA) | Excellent / Highly Acceptable |
| **3** | 2.50 – 3.49 | Agree (A) | Good / Acceptable |
| **2** | 1.50 – 2.49 | Disagree (D) | Fair / Needs Improvement |
| **1** | 1.00 – 1.49 | Strongly Disagree (SD) | Poor / Unacceptable |

### System Requirement Specifications

This section defines the functional and technical requirements of the Integrated Dynamic Order, Job Scheduling, and Inventory Management System. It outlines the specific behavioral functions of the platform alongside the minimum and recommended software and hardware environments required for development, hosting, and end-user access.

#### Functional Requirements

1. **User Account Registration and Authentication**
   * **Description:** Provides secure user registration, role-based login, and session access control tailored for business owners, production staff, and customers.
   * **Input:** Full name, email address, contact number, password, and designated user role.
   * **Process:** Validates input data, hashes passwords securely, verifies user credentials, assigns role permissions, and redirects the user to their designated dashboard.
   * **Output:** User account confirmation message and access to the corresponding role-specific dashboard.

2. **Dynamic Service and Rate Configuration (Admin)**
   * **Description:** Allows business owners to dynamically define and update print service offerings, page rates, paper stocks, finishing options, and pricing formulas without modifying the source code.
   * **Input:** Service name, base prices, per-page rates for monochrome and color, paper sizes (Short, A4, Long), paper thickness options (70, 80, 100 gsm), duplex discount percentages, rush fee amounts, and finishing add-on rates.
   * **Process:** Stores and updates configuration parameters in the database and applies dynamic pricing rules to the customer ordering calculations.
   * **Output:** Updated service catalog, active pricing formulas, and instant confirmation alerts.

3. **Customer Self-Service Ordering and File Upload**
   * **Description:** Enables customers to select printing services, specify customized attributes, upload document or artwork files, and receive instant price quotations.
   * **Input:** Selected print service, page specifications, color mode, paper stock, binding and finishing selections, document files (PDF/images), quantity, and delivery or pickup preferences.
   * **Process:** Computes the total order cost dynamically based on active pricing rules, validates uploaded files, creates a pending order record, and generates a unique Order ID.
   * **Output:** Itemized price quotation, generated order summary, and order submission confirmation.

4. **Payment Proof Submission and Verification**
   * **Description:** Facilitates manual payment verification where customers submit payment references and receipts for staff confirmation.
   * **Input:** Selected payment method (GCash, Maya, or Counter Cash), transaction reference number, and payment receipt screenshot.
   * **Process:** Records the payment proof, notifies production staff of pending payments, allows staff to cross-reference transactions, and updates the payment status upon approval.
   * **Output:** Updated payment status (e.g., Pending, Paid, or Rejected) and automated order status update.

5. **Production Floor Job Scheduling (5-Stage Kanban Queue)**
   * **Description:** Organizes active print jobs into a visual 5-stage production queue, allowing staff to track job progression, manage machine allocation, and meet fulfillment deadlines.
   * **Input:** Confirmed customer orders, assigned operator names, and production stage transitions.
   * **Process:** Routes confirmed orders into the production queue (Pending Queue → Printing → Assembly/Binding → Quality Check → Ready for Pickup), logs stage timestamps, and updates progress indicators.
   * **Output:** Real-time visual Kanban board, digital job tickets, and synchronized progress updates on customer dashboards.

6. **Customer-Supplied Substrate Tracking ("Dala ang Papel")**
   * **Description:** Tracks whether paper or substrates are supplied by the customer, adjusting pricing calculations and preventing unnecessary inventory deductions.
   * **Input:** Customer-supplied paper selection toggle, sheet quantity, and paper description.
   * **Process:** Deducts the shop paper cost from the total quotation, flags the order ticket for staff verification upon paper receipt, and bypasses shop raw paper stock deduction.
   * **Output:** Adjusted order total, tagged digital job ticket, and updated material requirement logs.

7. **Automated Bill of Materials (BOM) Inventory Deduction**
   * **Description:** Automatically deducts raw printing supplies from the inventory based on predefined material recipes as jobs advance through production stages.
   * **Input:** Active order specifications (page count, paper type, binding rings, cover boards) and production stage completions.
   * **Process:** Multiplies job quantities by the linked BOM recipe and automatically decrements stock levels in the inventory database upon job execution.
   * **Output:** Updated real-time inventory balances and recorded stock movement audit logs.

8. **Real-Time Burn Rate Calculation and Dynamic Reorder Point (ROP) Alerts**
   * **Description:** Monitors daily material consumption velocity across rolling 7-day and 30-day windows, evaluates dynamic reorder points, and alerts management of low stock levels.
   * **Input:** Historical stock movement logs, daily consumption records, supplier lead times, and safety stock baselines.
   * **Process:** Computes the daily material burn rate, calculates the dynamic reorder point threshold, and evaluates current stock levels against the threshold.
   * **Output:** Visual low-stock warning badges, restocking recommendation alerts, and inventory health summaries.

9. **Sales, Cashflow, and Inventory Reporting**
   * **Description:** Compiles and visualizes business transaction data to generate comprehensive sales, material consumption, and financial reports.
   * **Input:** Date range filters, service category selections, and report type parameters.
   * **Process:** Aggregates transaction records, calculates gross revenue, net margins, and service volume distribution, and formats data into printable summaries and charts.
   * **Output:** Interactive visual charts, service product mix reports, and exportable financial summary tables.

#### Software Requirements Specification

Table 2 outlines the software environment and developer toolchains utilized in designing, developing, and operating the web-based system.

**Table 2. Software Requirements Specification**

| Component | Minimum Requirement | Recommended Requirement |
| :--- | :--- | :--- |
| **Operating System** | Windows 10 / Ubuntu 20.04 LTS / macOS 12 | Windows 11 / Ubuntu 22.04 LTS / macOS 14 |
| **Web Server** | Apache 2.4 / Nginx 1.18+ / PHP Built-in Server | Nginx 1.24+ / Apache 2.4+ |
| **Database Server** | MySQL 8.0 / SQLite 3 | MySQL 8.0+ / MariaDB 10.6+ |
| **Programming Language / Runtime** | PHP 8.2 | PHP 8.3 or higher |
| **Backend Framework** | Laravel 11.x | Laravel 11.x / 13.x |
| **Frontend Reactive Stack** | Livewire 3.x / Alpine.js | Livewire 4.x / Flux UI |
| **CSS Framework & Bundler** | Tailwind CSS v3 / Vite | Tailwind CSS v4 via Vite |
| **Web Browser** | Modern Browser (Chrome, Firefox, Edge, Safari) | Latest Google Chrome / Brave / Microsoft Edge |

#### Hardware Requirements Specification

Table 3 details the minimum and recommended hardware specifications for the development workstations, hosting servers, and client access devices.

**Table 3. Hardware Requirements Specification**

| Category | Specification | Minimum Requirement | Recommended Requirement |
| :--- | :--- | :--- | :--- |
| **Development Workstation** | Processor<br>Memory (RAM)<br>Storage<br>Display | Dual-Core 2.0 GHz Processor<br>8 GB RAM<br>256 GB SSD<br>1366 × 768 Resolution | Quad-Core 3.0 GHz or higher<br>16 GB RAM or higher<br>512 GB NVMe SSD<br>1920 × 1080 Full HD Display |
| **Web Server (Hosting)** | Processor<br>Memory (RAM)<br>Storage<br>Bandwidth | 1 vCPU (Cloud VM or Dedicated)<br>1 GB RAM<br>20 GB Storage<br>10 Mbps Internet Connection | 2 vCPUs or higher<br>2 GB – 4 GB RAM<br>50 GB SSD Storage<br>50 Mbps+ High-Speed Internet |
| **Client Devices (Users)** | Device Type<br>Memory (RAM)<br>Connectivity | Any Web-Capable Device (PC, Smartphone)<br>2 GB RAM<br>Standard 3G/4G/Wi-Fi Connection | Smartphone / Laptop / Desktop PC<br>4 GB RAM or higher<br>Stable 4G/5G/Broadband Internet |

### Project Design

The project design outlines the technical architecture, data models, and system structure of the web-based management platform. It translates the identified operational and business requirements into concrete architectural blueprints. This section details the database schema through an Entity Relationship Diagram (ERD) to demonstrate how data entities interact, followed by behavioral modeling diagrams that define user roles, system interactions, and core operational workflows.

#### Database Schema / Entity Relationship Diagram (ERD)

Figure 3 illustrates the Entity Relationship Diagram (ERD) of the system, showing the logical relationships, primary keys, and foreign key constraints across the core database tables. At the center of the architecture is the **Users** entity, which manages authentication and role assignments for business owners, production staff, and customers. A business owner owns a **PrintShop** entity (1:1), which acts as the organizational root for all shop-level operations. Each print shop maintains its active service catalog through **ShopServices** (1:N) and manages its dynamic service configuration parameters through dedicated service models, including **ThesisBindingConfig** (1:1) and **DocumentPrintingConfig** (1:1).

Customer transactions are captured in the **Orders** entity (1:N from Users and PrintShop), which records the unique order number, transaction totals, payment status, staff assignment, payment proof, and active five-stage production status (*Pending Queue, Printing, Binding/Assembly, Quality Check, and Ready for Pickup*). Each order contains one or more line items in the **OrderItems** entity (1:N), storing detailed print specifications, page breakdowns (monochrome vs. color), paper dimensions, uploaded document file paths, custom cover text, and customer-supplied paper indicators (*"Dala ang Papel"*). 

Material resources are managed through the **InventoryItems** entity (1:N from PrintShop), which records current stock balances, units of measurement, unit costs, and reorder levels. Inventory tracking is integrated with production through two mechanisms: (1) dynamic Bill of Materials recipes in **ThesisBindingBomItems** (1:N from ThesisBindingConfig and InventoryItems) and direct foreign key BOM linkages in **DocumentPrintingConfig**, and (2) an automated audit ledger in the **StockMovements** entity (1:N from InventoryItems), which records every manual stock-in, adjustment, and stage-based production deduction alongside the responsible user ID and timestamp.

*(Figure 3. Entity Relationship Diagram will be placed here)*





