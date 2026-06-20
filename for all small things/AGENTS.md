# Workspace Instructions

These instructions apply to this workspace.

## Language

- Reply to the user in Russian by default.
- Keep final answers concise and practical.

## General Workflow

- Inspect the repository before editing.
- Prefer `rg` / `rg --files` for search.
- Use `apply_patch` for manual file edits.
- Do not revert user changes unless explicitly asked.
- Before changing files, explain briefly what will be edited.
- After changes, run the narrowest useful verification command available.

## Tool And Plugin Preferences

- Use Browser / in-app browser for local web app testing, screenshots, clicks, and visual verification.
- Use Figma tools only through the required Figma skills:
  - `figma:figma-use` before `use_figma`
  - `figma:figma-create-new-file` before creating a new Figma file
  - `figma:figma-generate-diagram` before generating FigJam diagrams
- Use Codex Security skills for security reviews and scans:
  - `codex-security:security-diff-scan` for PRs, commits, branches, and working-tree diffs
  - `codex-security:security-scan` for repository-wide or scoped scans
  - `codex-security:deep-security-scan` for exhaustive multi-pass scans
- Use `openai-docs` for OpenAI API, Codex, model, and ChatGPT documentation questions.
- Use `imagegen` only when a raster image is actually needed.

## Frontend Work

- Build the actual app/tool view first, not a marketing page, unless a landing page is explicitly requested.
- Reuse the existing framework, styling system, and component patterns.
- Use icons for icon-like commands and stable responsive dimensions for controls.
- Verify local UI changes in the Browser plugin when a local URL is available.
- Run build/test/lint commands when present.

## Dependency And Runtime Handling

- If dependencies are missing, inspect package files first.
- Ask for approval only when a command needs network access, writes outside the workspace, launches GUI apps, or performs a destructive operation.
- Do not add dependencies unless they materially simplify or de-risk the requested work.

## Project Hygiene

- Keep changes scoped to the user's request.
- Avoid unrelated formatting churn.
- Add comments only when they explain non-obvious logic.
- Prefer project-native parsers and APIs over ad hoc string manipulation.

