# DevPortal Knowledge Base & Documentation Wiki

> **Project:** DevPortal  
> **Generation Epoch:** `2605.4.3-bs`  
> **Status:** Active  
> **License:** MIT  
> **Author:** JLDesignNetwork  

Welcome to the internal **DevPortal Knowledge Base and Documentation Wiki**. This documentation hub provides comprehensive technical references, architectural blueprints, subsystem flows, and operational guides for DevPortal.

---

## 📚 Documentation Index

| Section | Description | Target Document |
| :--- | :--- | :--- |
| **System Architecture** | Deep-dive into Laravel 13 backend, Scanner pipeline, Action/Service pattern, and security boundaries. | [Architecture Guide](architecture.md) |
| **Core Features** | Detailed breakdown of the dashboard, scanner metadata extraction, and type inference logic. | [Features](features.md) |
| **User & Operations Guide** | Configuration nuances, category routing, Git activity widgets, and local domain integration. | [Usage & Workflows](usage.md) |
| **Generational Roadmap** | Master strategic milestones, generational backlogs, and multi-era roadmaps. | [Roadmap](../.dev/ROADMAP.md) |
| **Release History** | Formal chronological changelog adhering to the JLDN Generational Versioning Schema. | [Changelog](../CHANGELOG.md) |

---

## 🎯 Executive Overview

**DevPortal** is a high-performance local developer portal and directory scanner designed to monitor, categorize, and inspect development codebases across local and external storage volumes.

```
                                SYSTEM OVERVIEW DIAGRAM
  ┌───────────────────────┐       ┌───────────────────────┐       ┌───────────────────────┐
  │ Local Watch Paths     │       │ Directory Scanner     │       │ Portal Web UI         │
  │ /Volumes/.../Sites    │ ───>  │ ProjectScanner.php    │ ───>  │ Semantic HSL Tokens   │
  │ /Users/.../Sites      │       │ Caching + Git Parser  │       │ Custom JLMeter Bars   │
  └───────────────────────┘       └───────────────────────┘       └───────────────────────┘
```

### Key Capabilities
- **Multi-Category Scanning:** Automatically parses `Active/`, `Archived/`, and `Sandboxed/` folders across multiple allowlisted disk paths.
- **Deep Metadata Extraction:** Automatically extracts framework versions, production versions, dirty Git file counts, latest 5 Git commits, and README feature checklists.
- **Mathematical Security Boundaries:** Path traversal prevention guaranteeing project move and delete actions never escape allowlisted directories.
- **Modern Zero-Local Tooling:** Pure Vanilla CSS custom properties with native dark mode support, zero-dependency custom web components (`JLMeter`), and PNPM global linking.
