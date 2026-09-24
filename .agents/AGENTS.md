# Project Rules & Guidelines

## Mandatory Instructions (READ ON EVERY PROMPT)

1. **Strict English Response Mandate**: You MUST ALWAYS respond to the user in **English** across all conversational replies and outputs, regardless of what language or dialect (e.g., Tagalog, Taglish, Cebuano, etc.) the user uses in their prompt.
2. **Mandatory Rule Review**: You MUST read and follow the instructions in `AGENTS.md` and `.agents/AGENTS.md` on **EVERY prompt** to maintain strict alignment with user instructions, architectural decisions, and project rules.

---

## Active Skill Verification & Installation

1. **Verify Installed Skills First**: At the start of every task or session, you MUST list/inspect the directories in `.agents/skills/` to identify which installed skills are relevant to the user's request. If any skill matches the domain of the task (e.g., Livewire, Flux UI, Pest, Fortify, etc.), you MUST read its `SKILL.md` using the `view_file` tool to activate and apply its instructions.
2. **On-Demand Skill Discovery & Installation**: If the user's prompt involves a framework, tool, or library where you lack specific expertise or best practices, you MUST:
   - Search the web for a matching AI agent skill or standard workflow instructions.
   - Install the new skill by creating the folder `.agents/skills/<skill-name>/` and writing a `SKILL.md` file (including YAML frontmatter with `name` and `description`).

---

## Academic Thesis & Capstone Writing Rules

Whenever the prompt involves academic writing, capstone paper, thesis manuscript, outline, or revisions:
1. **Mandatory Skill Activation**: Always read and apply `.agents/skills/research-paper-writing/SKILL.md`, `.agents/skills/humanize-academic-writing/SKILL.md`, and `.agents/skills/scholarly/SKILL.md`.
2. **USM Institutional Standards & Capstone Conventions**: Follow the accepted USM BSIS outline structure (e.g., Nonakan and Comission references):
   - Use **"project"**, **"capstone project"**, and **"project developers"** instead of "research", "study", or "researchers" when working on BSIS capstones.
   - Statement of the Problem in cohesive paragraph form (no numbered itemized questions).
   - 5-Stage IPO Model (Input-Process-Output-Outcome-Impact) for Conceptual Framework.
   - Professional academic English for paper text, without AI clichés or empty transitions.
   - Incorporate in-text citations when specifically requested by the adviser or required by academic rigor.
3. **Conversational Language**: Always respond to the user in **English** in the chat, regardless of what language the user uses in the prompt. Write all academic paper manuscript text in formal academic English.
