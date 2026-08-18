# AI Agent & Copilot Development Guidelines

> [!IMPORTANT]
> **Global Governance:** Universal JLDN rules apply to this workspace. Local rules are codified in:
> - **Local Rules:** `.agents/AGENTS.md`
> - **Generational Hub:** `.dev/`

## Key Architecture Invariants
1. **PHP 8.4+ Standards:** Enforce strict typing (`declare(strict_types=1);`), constructor property promotion, and match expressions.
2. **Skinny Controllers:** Keep HTTP controllers focused strictly on routing. All scanner and IO logic resides in `app/Services/` or `app/Actions/`.
3. **Pest Testing:** All features and services must be covered by Pest PHP tests under `tests/`.
4. **Zero-Local Policy:** Never download local node packages into standalone project roots; link globally using `pnpm link --global`.
