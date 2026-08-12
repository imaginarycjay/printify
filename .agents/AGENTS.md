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
