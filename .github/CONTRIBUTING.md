# Contributing to DevPortal

Thank you for contributing to **DevPortal**! Please review the guidelines below before submitting pull requests.

---

## 1. Development Standards

1. **Strict PHP Standards:** Enforce PHP 8.4+ typed properties, constructor property promotion, and `declare(strict_types=1);`.
2. **Skinny Controllers:** Route handling only; business logic resides in `app/Actions/` or `app/Services/`.
3. **Zero-Local PNPM Policy:** Link 3rd-party dependencies globally (`pnpm link --global`).

---

## 2. Quality Assurance & Formatting

Before opening a pull request, ensure all local tests and linting passes:

```bash
# Run Pint code formatter
./vendor/bin/pint --dirty

# Run Pest test suite
./vendor/bin/pest

# Run Rector dry-run
./vendor/bin/rector process --dry-run
```

---

## 3. Commit & Versioning Conventions

- All pull requests and commits adhere to standard Conventional Commits or JLDN task taxonomy (`Fix PROJ-TODO-XX: ...`).
- Adhere to the **JLDN Generational Versioning Schema (GVS)** (`[YYMM].[SUBVERSION].[REVISION]-[TAG]`).
