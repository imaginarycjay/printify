# Customer Portal Product Backlog

**Target Role:** Customer / Client (`role: customer`)  
**Document Version:** 1.0 (Agile Draft)  
**Parent Document:** [lean-prd.md](file:///home/imaginarycjay/programming/capstone_system/capstone_paper/agile_development_draft/lean-prd.md)

---

## Overview
This backlog outlines all Epics, User Stories, and Acceptance Criteria for the Customer persona. The customer interacts with the storefront to browse available printing services, customize order specifications (binding, page counts, colors, dimensions), upload print-ready PDF files, submit GCash proof of payment, and track the live progress of their orders without needing physical visits or manual phone follow-ups.

---

## Epic 1: Storefront Browsing & Service Selection (`EPIC-CUS-1`)

### Story CUS-1.1: Visual Storefront & Service Catalog Browsing
- **User Story:** *As a Customer, I want to view a modern catalog of all printing services offered by the shop (e.g. Hardbound/Softbound Thesis Binding, Document Printing, Tarpaulin, Stickers), so that I can easily find and start the service I need.*
- **Priority:** `HIGH` | **Points:** 3 | **Status:** `IN PROGRESS`
- **Acceptance Criteria:**
  - [ ] Customer dashboard displays service tiles with custom branding, descriptions, starting prices, and turnaround times.
  - [ ] Clicking a service card navigates directly to the service's custom ordering wizard.

---

## Epic 2: Dynamic Customization & Instant Quotation (`EPIC-CUS-2`)

### Story CUS-2.1: Real-Time Price Estimator & Configuration
- **User Story:** *As a Customer, I want to configure my thesis binding options (Hardbound vs Softbound, number of B/W pages, number of colored pages, paper size, and cover color) and see an instant price breakdown, so that I know the exact cost before placing the order.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Real-time calculator updates dynamically as inputs change:
    $$\text{Subtotal} = \text{Base Price} + (\text{B/W Pages} \times \text{B/W Rate}) + (\text{Color Pages} \times \text{Color Rate})$$
  - [ ] Changing paper size, cover color, or foil color updates visual preview.
  - [ ] Selecting "Rush Delivery" adds the configured rush fee and recalculates the grand total immediately.

### Story CUS-2.2: Dynamic Cover Metadata Form Input
- **User Story:** *As a Customer, I want to fill in the required thesis cover fields (Thesis Title, Researchers, Course/Degree, School Year) directly on the form, so that the shop has accurate information for hot foil stamping without manual errors.*
- **Priority:** `HIGH` | **Points:** 3 | **Status:** `COMPLETED`
- **Acceptance Criteria:**
  - [x] Form dynamically displays all custom fields defined by the shop administrator.
  - [x] Inputs are validated before allowing the customer to proceed to file upload and checkout.

### Story CUS-2.3: Cover & Binding Only ("Dala ang Papel") Mode
- **User Story:** *As a Customer who already printed and collated my thesis pages, I want to order only the hardbound cover, foil stamping, and binding without paying for page printing charges, so that I can save money while getting a professional book cover.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `COMPLETED`
- **Acceptance Criteria:**
  - [x] Wizard provides a mode toggle: `📄 Full Package (Print & Bind)` vs `📦 Cover & Binding Only (Dala ang Papel)`.
  - [x] When Cover-Only is selected, page printing charges are set to **₱0.00**, applying only the base hardbound cover fee.
  - [x] Customer inputs total pre-printed page count, dynamically calculating estimated spine thickness ($\text{pages} \times 0.1\text{mm}$) for chipboard sizing.
  - [x] Foil stamping text fields and digital reference PDF upload remain active for double-checking and spine alignment.
  - [x] Checkout displays clear walk-in paper drop-off instructions with tracking code.

---

## Epic 3: Document Upload & Submission (`EPIC-CUS-3`)

### Story CUS-3.1: Print-Ready PDF File Upload
- **User Story:** *As a Customer, I want to upload my complete thesis manuscript as a PDF file, so that the shop receives the exact digital document ready for printing.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] File uploader supports `.pdf` format with client-side drag-and-drop.
  - [ ] File size limit enforced (e.g. up to 50MB).
  - [ ] Uploaded file displays file name, size, and a removal/replace button.
  - [ ] Server validates file MIME-type and stores it securely under private storage (`storage/app/orders/`).

---

## Epic 4: Manual Payment Submission (GCash Proof Upload) (`EPIC-CUS-4`)

### Story CUS-4.1: Payment Details & QR Code Display
- **User Story:** *As a Customer, I want to see the shop's GCash QR code, Account Name, Account Number, and exact payable amount at checkout, so that I can send my payment conveniently from my e-wallet app.*
- **Priority:** `CRITICAL` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Checkout screen displays the shop's GCash QR code image and account number with a 1-click "Copy Number" button.
  - [ ] Displays exact final order amount (including rush fee if applicable).

### Story CUS-4.2: Upload Payment Proof & Reference Number
- **User Story:** *As a Customer, I want to upload a screenshot of my GCash transaction receipt and enter the reference number, so that the shop owner can quickly verify my payment.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Image uploader accepts `.jpg`, `.jpeg`, `.png` receipts.
  - [ ] Input box captures the 13-digit GCash transaction reference number.
  - [ ] Submitting the form creates the `orders` record with `payment_status = pending_verification` and redirects the customer to their Live Order Tracking Page.

---

## Epic 5: Live Order Progress Tracking (`EPIC-CUS-5`)

### Story CUS-5.1: Real-Time Multi-Stage Tracking Timeline
- **User Story:** *As a Customer, I want to see a visual progress stepper showing my order's current status in real time, so that I know exactly when it is being printed, bound, and ready for pickup.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Tracking stepper displays 5 sequential stages:
    1. `Order Placed` (Awaiting Payment Verification)
    2. `Payment Verified` (Queued for Production)
    3. `In Production` (Printing & Binding Ongoing)
    4. `Quality Inspection` (Finishing & Cover Inspection)
    5. `Ready for Pickup` (Available at the Shop)
  - [ ] Active stage is highlighted with color-coded badges and timestamp.
  - [ ] Shows promised target completion date and time.

### Story CUS-5.2: Order History & Digital Job Receipt
- **User Story:** *As a Customer, I want to view my past orders, download a summary receipt, and check order details, so that I have a formal record of my transactions.*
- **Priority:** `MEDIUM` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Customer dashboard lists past completed and active orders.
  - [ ] Includes printable digital summary with order code, date, item specifications, total paid, and pickup verification QR/Code.
