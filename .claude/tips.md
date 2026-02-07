# Claude Code Tips & Learnings

Things discovered while working on this project. Updated as we go.

## Getting the Most Out of Claude Code

### Use CLAUDE.md effectively
- Keep it concise — it's loaded into every conversation. Long docs belong in `.claude/rules/` files.
- Use `@file` references to point to detailed docs without bloating CLAUDE.md.

### Use `/init` wisely
- Run `/init` after major structural changes so CLAUDE.md stays accurate.

### Slash commands (skills)
- Create custom skills in `.claude/skills/` for repetitive workflows (e.g. `/deploy`, `/fix-issue 42`).
- Skills accept arguments via `$ARGUMENTS` in the SKILL.md template.

### Parallel work
- Ask Claude to run independent tasks "in parallel" — it can launch multiple subagents simultaneously.
- Use the Explore subagent for broad codebase searches; use direct Glob/Grep for targeted lookups.

### TDD workflow tip
- Tell Claude "write the test first, then make it pass" explicitly. It follows TDD better with clear phase instructions.

### Commits & PRs
- Use `/commit` to let Claude draft a commit message from staged changes.
- Claude can create PRs with `gh` — just ask.

### MCP servers
- Project-scoped MCP config goes in `.mcp.json` at the root (committed to git, shared with team).
- User-scoped goes in `~/.claude.json` (personal, not committed).

### Rules for context-specific guidance
- Put path-conditional rules in `.claude/rules/` with `paths:` frontmatter — they only load when relevant files are touched.

### Plan mode
- For non-trivial features, ask Claude to "plan first" or it will enter plan mode automatically. Review the plan before it writes code.

## Project-Specific Learnings

(Will be filled as we work on Cash)
