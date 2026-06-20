# Codex Workspace Setup

This workspace is prepared with persistent instructions in `AGENTS.md`.

## Current State

- No git repository was detected in this folder.
- No `.codex` project config folder was present.
- Existing `.claude/settings.local.json` was left unchanged.
- Browser plugin verification passed on 2026-06-17:
  - Codex In-app Browser opened a local HTTP test page, read DOM, and clicked a button.
  - Chrome extension backend opened the same local HTTP test page, read DOM, and clicked a button.
  - Direct `file://` navigation is blocked by Browser security policy; use local HTTP for file-based tests.

## Available Built-In Capabilities In This Session

- Browser plugin for local browser testing.
- Figma plugin for Figma files, diagrams, Code Connect, design generation, and design-system work.
- Codex Security plugin for security scans and reviews.
- OpenAI docs skill for current OpenAI/Codex documentation.
- Image generation skill for raster image creation or editing.

## Recommended Future Checks Per Project

- For Node projects: run `npm install` only when dependencies are missing, then `npm run build`, `npm test`, or the project-specific scripts.
- For PHP/WordPress projects: run `php -l` on touched PHP files and use WP-CLI where available.
- For frontend projects: start the dev server, open it in the Browser plugin, and verify desktop/mobile layouts.
- For security-sensitive changes: run the relevant Codex Security workflow.

## Notes

Global app-level plugin installation and account settings are controlled outside this workspace. If a future task requires a plugin that is not installed, Codex should search for the plugin first and request installation only when it exactly matches the user's explicit request.
