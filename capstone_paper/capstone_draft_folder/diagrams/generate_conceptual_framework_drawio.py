#!/usr/bin/env python3
"""
generate_conceptual_framework_drawio.py
Generates a professional, fully-styled Draw.io (.drawio) XML diagram
for the Conceptual Framework (IPO-Outcome-Impact Model) of the Capstone System.

Usage:
    python3 generate_conceptual_framework_drawio.py
Output:
    conceptual_framework.drawio (in the same directory)
"""

import os
import xml.sax.saxutils as saxutils

def generate_drawio():
    output_dir = os.path.dirname(os.path.abspath(__file__))
    output_file = os.path.join(output_dir, "conceptual_framework.drawio")

    # Content for the 5 Stages
    input_html = (
        "<b>1. INPUT</b><hr style='margin: 4px 0; border: 0; border-top: 1px solid #94a3b8;'>"
        "<b>Stakeholder Profiles:</b><br>"
        "• Business Owners (Pricing & Config)<br>"
        "• Production Staff (Task Allocation)<br>"
        "• Customers (Self-Service Ordering)<br><br>"
        "<b>Modular Service Parameters:</b><br>"
        "• Dynamic Pricing Formulas (Page/SqFt/Unit)<br>"
        "• Media Specs (Short, A4, Long; 70-100 gsm)<br>"
        "• Substrates (Paper, Vinyl, Garment, PVC)<br>"
        "• Finishing Rules (Foil, Ring Bind, Eyelets)<br><br>"
        "<b>Customer Assets & Proofs:</b><br>"
        "• PDF Manuscripts & Vector Artwork<br>"
        "• GCash / Maya Receipts & Cash Proofs<br><br>"
        "<b>Raw Consumables & BOM:</b><br>"
        "• Paper Reams, Vinyl Rolls, Inks, Boards<br>"
        "• Stock Baselines & Safety Buffers"
    )

    process_html = (
        "<b>2. PROCESS</b><hr style='margin: 4px 0; border: 0; border-top: 1px solid #94a3b8;'>"
        "<b>Order Intake & Dynamic Quotation:</b><br>"
        "• Instant multi-attribute cost computation<br>"
        "• Duplex discounts, rush fees & add-ons<br>"
        "• Manual payment verification & routing<br><br>"
        "<b>Workshop Floor Scheduling:</b><br>"
        "• 5-Stage Kanban Queue Tracking<br>"
        "  (Queue → Print → Assembly → QC → Ready)<br>"
        "• Customer paper tracking ('Dala ang Papel')<br>"
        "• Machine & operator task assignment<br><br>"
        "<b>Automated Inventory Telemetry:</b><br>"
        "• Stage-based Bill of Materials (BOM) burn<br>"
        "• 7-day & 30-day velocity (Burn Rate) calculation<br>"
        "• Dynamic Reorder Point (ROP) evaluation<br><br>"
        "<b>Business Intelligence:</b><br>"
        "• Sales, product mix & margin aggregation"
    )

    output_html = (
        "<b>3. OUTPUT</b><hr style='margin: 4px 0; border: 0; border-top: 1px solid #94a3b8;'>"
        "<b>Integrated Web-to-Print Platform:</b><br>"
        "• Role-segregated cloud portals<br><br>"
        "<b>Customer Experience:</b><br>"
        "• Interactive multi-service order wizards<br>"
        "• Live 5-stage visual progress stepper<br><br>"
        "<b>Workshop Floor Hub:</b><br>"
        "• Digital job tickets & floor Kanban console<br>"
        "• Transparent machine & operator queues<br><br>"
        "<b>Automated Inventory Telemetry:</b><br>"
        "• Automated low-stock & restock alerts<br>"
        "• Real-time stock ledgers & audit trails<br><br>"
        "<b>Financial & Operational BI:</b><br>"
        "• Multi-service sales & revenue dashboards<br>"
        "• Exportable accounting summaries"
    )

    outcome_html = (
        "<b>4. OUTCOME (Immediate Operational Benefits)</b><hr style='margin: 4px 0; border: 0; border-top: 1px solid #94a3b8;'>"
        "• <b>Error-Free Multi-Product Intake:</b> Eliminates manual calculation errors, counter congestion, and pricing discrepancies across services.<br>"
        "• <b>Organized Workshop Floor:</b> Completely eliminates lost paper job tickets, misplaced client files, and production floor mix-ups.<br>"
        "• <b>24/7 Client Transparency:</b> Provides frictionless online order tracking, drastically reducing repeated follow-up calls and visits.<br>"
        "• <b>Zero Stockout Halts:</b> Prevents unexpected supply exhaustion during peak academic rush, election campaigns, and commercial events.<br>"
        "• <b>Data-Driven Procurement:</b> Replaces intuitive inventory guesswork with actual sales burn-rate velocity and automated reorder alerts."
    )

    impact_html = (
        "<b>5. IMPACT (Long-Term Organizational & Academic Contributions)</b><hr style='margin: 4px 0; border: 0; border-top: 1px solid #94a3b8;'>"
        "• <b>Enterprise Productivity & Profitability:</b> Maximizes operational throughput, reduces raw material waste, and boosts profit margins for printing MSMEs.<br>"
        "• <b>Heightened Market Trust & Reliability:</b> Strengthens client confidence through consistent turnaround times and superior service dependability.<br>"
        "• <b>Code-Free Business Adaptability:</b> Empowers printing shop owners to dynamically configure, price, and launch new print products without coding.<br>"
        "• <b>Information Systems Benchmark:</b> Establishes a validated empirical baseline for lightweight job-shop business automation and inventory replenishment."
    )

    # Styles
    box_style_input = (
        "rounded=1;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacingLeft=12;spacingRight=12;"
        "spacingTop=12;spacingBottom=12;fillColor=#f8fafc;strokeColor=#0284c7;strokeWidth=2;"
        "fontFamily=Helvetica;fontSize=11;fontColor=#0f172a;"
    )
    box_style_process = (
        "rounded=1;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacingLeft=12;spacingRight=12;"
        "spacingTop=12;spacingBottom=12;fillColor=#f8fafc;strokeColor=#6366f1;strokeWidth=2;"
        "fontFamily=Helvetica;fontSize=11;fontColor=#0f172a;"
    )
    box_style_output = (
        "rounded=1;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacingLeft=12;spacingRight=12;"
        "spacingTop=12;spacingBottom=12;fillColor=#f8fafc;strokeColor=#10b981;strokeWidth=2;"
        "fontFamily=Helvetica;fontSize=11;fontColor=#0f172a;"
    )
    box_style_outcome = (
        "rounded=1;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacingLeft=14;spacingRight=14;"
        "spacingTop=12;spacingBottom=12;fillColor=#fefce8;strokeColor=#eab308;strokeWidth=2;"
        "fontFamily=Helvetica;fontSize=11;fontColor=#0f172a;"
    )
    box_style_impact = (
        "rounded=1;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacingLeft=14;spacingRight=14;"
        "spacingTop=12;spacingBottom=12;fillColor=#f0fdf4;strokeColor=#14b8a6;strokeWidth=2;"
        "fontFamily=Helvetica;fontSize=11;fontColor=#0f172a;"
    )

    edge_style = (
        "edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;"
        "strokeWidth=2.5;strokeColor=#334155;endArrow=classic;endFill=1;fontSize=10;fontColor=#334155;fontStyle=1;"
    )

    feedback_style = (
        "edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;"
        "strokeWidth=2;strokeColor=#0284c7;dashed=1;endArrow=classic;endFill=1;fontSize=10;fontColor=#0284c7;fontStyle=1;"
    )

    title_style = (
        "text;html=1;align=center;verticalAlign=middle;resizable=0;points=[];autosize=1;"
        "strokeColor=none;fillColor=none;fontSize=15;fontStyle=1;fontColor=#0f172a;fontFamily=Helvetica;"
    )

    # XML Template
    xml_content = f"""<mxfile host="app.diagrams.net" modified="2026-09-07T10:00:00.000Z" agent="Antigravity" version="21.0.0" type="device">
  <diagram id="conceptual_framework" name="Figure 1. Conceptual Framework">
    <mxGraphModel dx="1422" dy="894" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1200" pageHeight="950" background="#ffffff" math="0" shadow="0">
      <root>
        <mxCell id="0" />
        <mxCell id="1" parent="0" />

        <!-- Title -->
        <mxCell id="title_node" value="Figure 1. Conceptual Framework for Integrated Dynamic Order, Job Scheduling, and Inventory Management System" style="{title_style}" vertex="1" parent="1">
          <mxGeometry x="120" y="20" width="960" height="30" as="geometry" />
        </mxCell>

        <!-- 1. INPUT -->
        <mxCell id="box_input" value="{saxutils.escape(input_html)}" style="{box_style_input}" vertex="1" parent="1">
          <mxGeometry x="50" y="80" width="340" height="420" as="geometry" />
        </mxCell>

        <!-- 2. PROCESS -->
        <mxCell id="box_process" value="{saxutils.escape(process_html)}" style="{box_style_process}" vertex="1" parent="1">
          <mxGeometry x="430" y="80" width="340" height="420" as="geometry" />
        </mxCell>

        <!-- 3. OUTPUT -->
        <mxCell id="box_output" value="{saxutils.escape(output_html)}" style="{box_style_output}" vertex="1" parent="1">
          <mxGeometry x="810" y="80" width="340" height="420" as="geometry" />
        </mxCell>

        <!-- 4. OUTCOME -->
        <mxCell id="box_outcome" value="{saxutils.escape(outcome_html)}" style="{box_style_outcome}" vertex="1" parent="1">
          <mxGeometry x="180" y="550" width="840" height="150" as="geometry" />
        </mxCell>

        <!-- 5. IMPACT -->
        <mxCell id="box_impact" value="{saxutils.escape(impact_html)}" style="{box_style_impact}" vertex="1" parent="1">
          <mxGeometry x="180" y="750" width="840" height="140" as="geometry" />
        </mxCell>

        <!-- Arrow: INPUT -> PROCESS -->
        <mxCell id="edge_input_process" value="Feeds Operational Data" style="{edge_style}" edge="1" parent="1" source="box_input" target="box_process">
          <mxGeometry relative="1" as="geometry" />
        </mxCell>

        <!-- Arrow: PROCESS -> OUTPUT -->
        <mxCell id="edge_process_output" value="Generates Functional Deliverables" style="{edge_style}" edge="1" parent="1" source="box_process" target="box_output">
          <mxGeometry relative="1" as="geometry" />
        </mxCell>

        <!-- Arrow: OUTPUT -> OUTCOME -->
        <mxCell id="edge_output_outcome" value="Delivers Immediate Operational Gains" style="{edge_style}" edge="1" parent="1" source="box_output" target="box_outcome">
          <mxGeometry relative="1" as="geometry">
            <Array as="points">
              <mxPoint x="980" y="525" />
              <mxPoint x="600" y="525" />
            </Array>
          </mxGeometry>
        </mxCell>

        <!-- Arrow: OUTCOME -> IMPACT -->
        <mxCell id="edge_outcome_impact" value="Achieves Long-Term Strategic Goals" style="{edge_style}" edge="1" parent="1" source="box_outcome" target="box_impact">
          <mxGeometry relative="1" as="geometry" />
        </mxCell>

        <!-- Feedback Loop: OUTCOME -> INPUT -->
        <mxCell id="edge_feedback" value="Continuous System Refinement &amp; Stock Synchronization" style="{feedback_style}" edge="1" parent="1" source="box_outcome" target="box_input">
          <mxGeometry relative="1" as="geometry">
            <Array as="points">
              <mxPoint x="180" y="625" />
              <mxPoint x="120" y="625" />
              <mxPoint x="120" y="500" />
            </Array>
          </mxGeometry>
        </mxCell>

      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
"""

    with open(output_file, "w", encoding="utf-8") as f:
        f.write(xml_content.strip())

    print(f"SUCCESS: Draw.io file created at: {output_file}")
    return output_file

if __name__ == "__main__":
    generate_drawio()
