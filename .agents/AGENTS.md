# Project Rules & Guidelines

## Agent Activity Journal

To save tokens and preserve context across sessions:
1. **Read the Journal First**: At the start of a new session or task, read `.agents/journal.md` to quickly catch up on recent modifications, status of tasks, and context without re-scanning the entire codebase.
2. **Update the Journal on Completion (Major Tasks Only)**: Before ending your turn or finishing a user request, append a new markdown entry to `.agents/journal.md` summarizing the task. **Do NOT record in the journal for minor fixes or adjustments** unless explicitly instructed by the user. Only record major milestones, new modules, structural additions, or significant features.

### Journal Entry Template

Use the following format for each entry, appending it to the end of `.agents/journal.md`:

```markdown
---

## [YYYY-MM-DD HH:MM:SS] <Short Task Name>
- **Request:** <Brief summary of the user's prompt or goal>
- **Status:** [Success | Error | In Progress]
- **Steps Taken:**
  - <Action 1 (e.g. created component/file)>
  - <Action 2 (e.g. run test command)>
- **Verification & Outcome:** <How it was verified, test output summaries, or error messages if any>
- **Key State Changes:** <Any structural changes (e.g. dependencies added, database tables created/migrated, config keys added)>
```

## Active Skill Verification & Installation

1. **Verify Installed Skills First**: At the start of every task or session, you MUST list/inspect the directories in `.agents/skills/` to identify which installed skills are relevant to the user's request. If any skill matches the domain of the task (e.g., Livewire, Flux UI, Pest, Fortify, etc.), you MUST read its `SKILL.md` using the `view_file` tool to activate and apply its instructions.
2. **On-Demand Skill Discovery & Installation**: If the user's prompt involves a framework, tool, or library where you lack specific expertise or best practices, you MUST:
   - Search the web for a matching AI agent skill or standard workflow instructions.
   - Install the new skill by creating the folder `.agents/skills/<skill-name>/` and writing a `SKILL.md` file (including YAML frontmatter with `name` and `description`).

## Academic Thesis & Capstone Writing Rules

Whenever the prompt involves academic writing, capstone paper, thesis manuscript, outline, or revisions:
1. **Mandatory Skill Activation**: Always read and apply `.agents/skills/research-paper-writing/SKILL.md` (from https://github.com/Master-cai/Research-Paper-Writing-Skills.git), `.agents/skills/humanize-academic-writing/SKILL.md`, and `.agents/skills/scholarly/SKILL.md`.
2. **USM Institutional Standards**: Follow the accepted USM BSIS outline structure (e.g., Nonakan and Comission references):
   - No in-text citations in Chapter 1 (citations are reserved for Chapter 2).
   - Statement of the Problem in cohesive paragraph form (no numbered itemized questions).
   - 5-Stage IPO Model (Input-Process-Output-Outcome-Impact) for Conceptual Framework.
   - Professional academic English for paper text, without AI clichés or empty transitions.
3. **Conversational Language**: Always respond to the user in **Taglish** in the chat, but write the paper manuscript text in formal academic English.

