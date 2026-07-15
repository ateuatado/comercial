# SPIV + Spec Kit

This project uses a Spec Kit inspired structure to keep requirements,
implementation decisions, and code layout consistent.

## Why this exists

The repository started as a CodeIgniter 4 starter, but the SPIV application now needs a project-level source of truth for features and architecture.
Spec Kit provides that discipline by separating:

- what the system must do
- how the system should be built
- which tasks are required to ship it

## Project flow

Use the same Spec Kit sequence for each relevant change:

1. `constitution` - define non-negotiable engineering principles.
2. `spec` - describe the feature in business terms.
3. `plan` - capture the technical approach and boundaries.
4. `tasks` - break the work into executable steps.
5. `implement` - apply the change in code.
6. `converge` - compare the result with the spec and close gaps.

For this repository, prefer the flow-forward model for new work: each feature gets its own directory under `specs/` and stays as historical context.

## Repository structure

The current codebase is intentionally small and should remain easy to scan:

- `app/Controllers/` - request handling and page orchestration.
- `app/Models/` - domain data access and business rules.
- `app/Views/` - page templates, layouts, and partials.
- `app/Config/` - routing, services, and framework configuration.
- `public/` - web entrypoint and public assets.
- `sql/` - schema files and database bootstrap scripts.
- `tests/` - automated test support and suites.
- `specs/` - feature-by-feature Spec Kit artifacts.

## Current application map

The main page currently follows a simple MVC path:

- route: `GET /`
- controller: `app/Controllers/Home.php`
- model: `app/Models/HomeModel.php`
- layout: `app/Views/layouts/main.php`
- route declaration: `app/Config/Routes.php`

This is the baseline to keep stable while the rest of the project evolves.

## How to keep the project cohesive

- Put decisions into the spec before turning them into code.
- Keep implementation notes in the plan, not in the README.
- When a feature changes behavior, update the matching `specs/<feature>/` artifact set before widening the code change.
- Avoid scattered one-off documentation; link back to this guide instead.

## Recommended next additions

- A project constitution for coding standards and architectural boundaries.
- A first feature spec under `specs/` for the next SPIV increment.
- A task checklist aligned with the first concrete feature delivery.