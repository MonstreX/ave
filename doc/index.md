# Ave Admin Panel Documentation

This guide is written for people who create and maintain Ave resources. Each chapter teaches a concrete task: shaping tables, composing forms, wiring actions, guarding access, and keeping your workflow tidy. When you need to peek under the hood to extend the core, consult the developer appendix at the end.

## Resource Authoring

1. [Introduction](01-introduction.md) — quick start, workspace layout, and the day-to-day routine for building resources.
2. [Resources](02-resources.md) — metadata, discovery, tables, forms, hooks, and navigation rules.
3. [Workflow Playbook](05-architecture.md) — step-by-step walkthrough for planning, implementing, reviewing, and polishing a resource.

## UI & Data Building Blocks

4. [Fields](03-fields.md) — shared API, validation, and usage guidelines for each input type.
5. [Complex Fields](13-complex-fields.md) — Fieldset, Media, rich/code editors, and nested state paths.
6. [Columns](06-columns.md) — table presentation, inline editing, relation output, and custom templates.
7. [Filters & Criteria](07-filters.md) — exposing filter controls, wiring request parameters, and showing active badges.
8. [Layouts](10-layouts.md) — tabs, panels, grid helpers, sticky actions, and readable form composition.

## Behaviour & Security

9. [Actions](04-actions.md) — row/bulk/global/form actions, modal flows, and confirmation UX.
10. [Permissions](08-permissions.md) — designing abilities, seeding roles, guarding menus, and troubleshooting ACL.
11. [Lifecycle](11-lifecycle.md) — persistence hooks, deferred actions, relation syncing, and redirect strategy.
12. [Relationships](12-relationships.md) — BelongsTo/BelongsToMany selects, counts, badges, and filters.

## Tooling & Deep Dive

13. [Commands & Tooling](09-commands.md) — artisan helpers, vendor publish tags, asset builds, and testing.
14. [Developer Architecture](14-developer-architecture.md) — the internals for core contributors: service provider, routing, rendering, persistence, and extension points.
