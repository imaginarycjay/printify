**Critique of the Revised Review of Related Literature — PressQueue**

Review date: November 16, 2026.

**This is a useful starting draft, but it needs substantial revision before it can support the revised PressQueue proposal.** Its strongest improvements are placing scheduling first, introducing multi-tenancy, and including printing-specific ordering systems. Its main weakness is that several requirements added after the defense still have no literature foundation.

The [RRL](../REVISED_REVIEW_RELATED_LITERATURE.docx) was compared with the [defense corrections](outline_defense_results.pdf), [Chapter 1 v2](PressQueue_Chapter_1_v2.docx), revision guides, and relevant system implementation. Selected cited sources were also checked online. This critique does not modify the original DOCX.

**1. The biggest problem is incomplete alignment with the revised objectives.**

| Requirement from the revised paper and panel feedback | Coverage in this RRL | Revision needed |
|---|---|---|
| Multiple printers and staff workloads | General discussion | Explain machine availability, operator workload, and assignment constraints. |
| Item-level scheduling and split fulfillment | Missing | Ground independent progress of a quick printing item and a longer binding item within one order. |
| Customer-visible shop workload | Missing | Discuss workload visibility before ordering and distinguish workload indicators from completion-time estimates. |
| Direct printing from the system | Missing | Review browser/local printing integration and its limitations. |
| Tenant isolation | Introduced | Connect isolation to shop-specific orders, files, inventories, staff permissions, and reports. |
| Prepress approval, specification locking, and price adjustments | Missing | Explain why approved specifications require controlled revisions. |
| Downpayment verification | Weak | Discuss the actual payment-verification workflow and production-release rule. |
| Automatic file retention and cleanup | Missing | Cover deletion of completed-order files while preserving transaction records. |
| BOM consumption, concurrent reservations, and spoilage | Mostly missing | Distinguish normal consumption, reserved stock, and additional waste from reprints. |
| Printable/downloadable inventory, sales, and job-status reports | Mentioned generally | Address the three required report categories explicitly. |
| Smoke, functional, and regression testing | Missing | Support the revised verification objective. |

These topics do not each need a separate heading or paper. They need enough credible support to explain the system's major design decisions. Detailed database fields and algorithms belong in Chapter 3.

**2. The scheduling subsection is relevant, but too repetitive and general.**

The paragraphs repeatedly conclude that scheduling literature supports organizing queues, tracking progress, and allocating staff and machinery. This establishes relevance, but offers little comparison between the sources.

For each source, explain what it actually contributes: resource allocation, scheduling constraints, production sequencing, or handling changing workloads. Then identify which principle applies to PressQueue and where the contexts differ.

For example, **Smagowicz et al. (2024)** examines simulation and optimization for printing on clothing. It supports resource-aware production planning, but its production environment differs from document printing and thesis binding. State that distinction rather than treating it as direct evidence for your entire scheduling workflow. [Original article](https://www.mdpi.com/2071-1050/16/4/1693)

The subsection should ultimately explain why PressQueue needs **printer assignment, staff workload visibility, and independently progressing order items**. Those are the scheduling issues the panel specifically raised.

**3. The multi-tenant/order/billing subsection combines too many loosely connected ideas.**

The Pushpan discussion provides a useful starting point. However, the later discussion of AI optimization, emerging technologies, and long-term innovation does little to explain your actual tenant architecture. Adelusi's data-mart discussion also needs a clearer connection to operational tenant isolation.

The other sources need tighter treatment:

- **Dhiman's food-ordering system:** usable as a cross-domain comparison, but explain the transferable mechanism—centralized intake and order status. Your printing-specific sources should receive more attention.
- **Moon's payment-security discussion:** too broad to justify your payment workflow. Cybersecurity concerns alone do not explain payment-proof verification, a downpayment threshold, or authorization to release a job into production.
- **Anisa's interactive design editor:** distinguish that feature from PressQueue. Your latest Chapter 1 explicitly excludes built-in design editing.
- **Anisa, Yuniardani, and Mellati:** separate their contributions. The current paragraph combines ordering, design editing, payment visibility, and database performance into one long discussion.

**The Mellati interpretation needs particular correction.** The reported 80.9%, 72.3%, and average 76.6% improvements concern software response times associated with Redis integration and simulated concurrent-user testing. They do not establish equivalent improvements in physical printing turnaround or overall shop operations. Your figures are traceable, but their meaning needs to remain precise. [Original article](https://jurnal.polgan.ac.id/index.php/sinkron/article/view/15502)

You can retain this source as evidence about application responsiveness without suggesting that PressQueue implements Redis or will achieve the same gains.

**4. The inventory subsection currently emphasizes methods beyond your declared system.**

Tanaman and Asim and Arbaiy are useful for explaining problems with manual stock records. However, much of the remaining discussion concerns AI, forecasting, lot sizing, and predictive replenishment.

Your revised project primarily needs support for:

- Service specifications translated into material requirements through a BOM.
- Stock availability and reservation when orders compete for limited materials.
- Normal production consumption versus spoilage and reprint consumption.
- Reorder-point alerts and owner-directed replenishment.
- Stock-movement records and standardized operational reports.

**BOM-based deductions do not, by themselves, establish that the system implements full MRP.** Likewise, a low-stock notification does not establish predictive replenishment.

Retain AI and forecasting literature only where it provides a useful comparison or explains an excluded approach. Otherwise, it creates expectations the system and functional evaluation may not satisfy.

The heading would better reflect your scope as **“Inventory Control, BOM-Based Material Accounting, Reorder Alerts, and Operational Reporting.”**

**5. The RRL needs synthesis and a defensible project gap.**

Most paragraphs follow this pattern:

*Author A discussed something → Author B discussed something → these support the proposed module.*

A stronger review compares the sources:

*What they address → how their approaches differ → what remains insufficiently addressed → why that matters to PressQueue.*

At present, the document ends after Bari's inventory discussion. It does not bring scheduling, ordering, tenancy, and inventory together.

Add a closing synthesis supported by the reviewed systems. Explain which combinations of capabilities are absent or insufficiently documented **among those systems**. Avoid claiming that no existing platform offers them unless you have evidence from a sufficiently broad comparison.

A compact working comparison matrix would help: source, operating context, scheduling, tenancy, inventory integration, reporting, evaluation, and relevant limitation. Features that a publication does not discuss should be marked **“not reported,”** rather than assumed absent.

**6. Add the functional-testing foundation, with carefully bounded claims.**

Chapter 1 now includes developer-conducted smoke, role-based functional, and regression testing. Chapter 2 currently provides no foundation for that objective.

The ISO/IEC/IEEE 29119 sources in your revision guide are relevant: Part 1 covers testing concepts, Part 2 covers testing processes, and Part 3 covers testing documentation. [Part 1](https://www.iso.org/standard/81291.html), [Part 2](https://www.iso.org/standard/79428.html), [Part 3](https://www.iso.org/standard/79429.html)

Explain how your chosen tests provide evidence of requirement conformance. Avoid saying that ISO mandates your exact three-stage arrangement without checking the relevant clauses.

Also keep the conclusions within the evaluation: successful functional tests can support correct workflow behavior under tested conditions. Customer satisfaction, usability, and commercial improvement require their own evidence.

**7. Resolve contradictions between the revision guides, Chapter 1, and the current system.**

These affect what the RRL should justify. The implementation observations below describe the code inspected during the November 16, 2026 review.

- **Payments:** Chapter 1 v2 specifies manual payment-proof verification and excludes live automated banking settlement. The older master guide includes webhooks and polling. Follow the settled scope and reconcile the conflicting documents.
- **Workload:** the current [workload calculation](../../../app/Models/ShopService.php) uses active orders per service against a configured threshold. It does not calculate a completion-time estimate or actual printer/staff utilization.
- **Scheduling:** the inspected [Order model](../../../app/Models/Order.php) currently holds staff assignment, machine assignment, and production stage. Item-level scheduling should remain a proposed requirement until implemented and verified.
- **Direct printing:** the inspected staff interface, `resources/views/pages/staff/⚡staff-dashboard.blade.php` (around line 1150), still provides file download and preview. Also, calling `window.print()` opens a print dialog; it does not return confirmed physical sheet consumption. Any claim about exact consumption needs a separate accounting or confirmation mechanism. [API documentation](https://developer.mozilla.org/en-US/docs/Web/API/Window/print)

**The final editing priorities are straightforward.**

1. Add the missing literature coverage and closing synthesis.
2. Correct interpretations such as the Mellati performance claim.
3. Reconcile the payment and printing scope across chapters.
4. Match every citation to a complete reference. This DOCX contains no bibliography, so full reconciliation remains outstanding; Bari (2026), including the “119 studies” claim, needs an identifiable source.
5. Polish terminology and grammar: change the title to **“Review of Related Literature,”** use **“present capstone project,”** remove the comma in **“Moon et al. (2022),”** and replace **“Saptadi et al. (2023), Says”** with an appropriate reporting verb.

A suitable final organization is: scheduling and production queues; multi-tenant ordering, prepress, billing, and retention; BOM inventory and reporting; functional testing; and synthesis/project gap.
