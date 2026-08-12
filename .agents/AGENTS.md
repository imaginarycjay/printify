# Project Rules & Guidelines

## Agent Activity Journal

To save tokens and preserve context across sessions:
1. **Read the Journal First**: At the start of a new session or task, read `.agents/journal.md` to quickly catch up on recent modifications, status of tasks, and context without re-scanning the entire codebase.
2. **Update the Journal on Completion**: Before ending your turn or finishing a user request, you MUST append a new markdown entry to `.agents/journal.md` summarizing the task.

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
