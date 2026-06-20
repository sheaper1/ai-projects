# Project Release Rules

## WordPress plugin ZIP

- Build release archives only with:
  `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .tools/build-release.ps1`
- Never use PowerShell `Compress-Archive` for WordPress plugin releases. On Windows it can store backslashes in ZIP entry names, which is not portable to Linux/WordPress.
- Every ZIP entry must use `/` as the path separator.
- The archive must contain exactly one top-level directory named `der-flugschreiber-subscriptions/`.
- The main plugin file must be located at `der-flugschreiber-subscriptions/der-flugschreiber-subscriptions.php`.
- Exclude tests, local tooling, caches, previous archives, and release staging directories.
- A release is complete only after the build script validates the archive and PHP lint passes on the extracted copy.
