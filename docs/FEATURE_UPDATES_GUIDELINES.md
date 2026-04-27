# Feature Updates Guidelines

## 1. Purpose
This document defines a standard process for implementing feature updates in FCNCHBD ISP ERP with predictable quality, low regression risk, and clear release communication.

## 2. Scope
Use these guidelines for:
- New features
- Changes to existing features
- UI and UX improvements
- API and integration updates
- Database schema or migration updates

## 3. Update Principles
- Keep updates small and focused when possible.
- Avoid unrelated refactors in feature PRs.
- Preserve backward compatibility unless a breaking change is approved.
- Prefer explicit, testable acceptance criteria before implementation.
- Document every user-visible change.

## 4. Pre-Update Checklist
- Confirm feature goal and expected behavior.
- Identify affected layers:
  - Routes (`routes/web.php`, `routes/api.php`, `routes/portal.php`)
  - Controllers (`app/Controllers`)
  - Services (`app/Services`)
  - Views (`views/...`)
  - Database schema/migrations (`database/*.sql`)
- Check whether the update impacts:
  - Auth or role permissions
  - Billing/finance calculations
  - MikroTik/Radius/OLT integrations
  - Portal and admin layout responsiveness
- Define rollback approach before merging.

## 5. Implementation Workflow
1. Plan
- Write a short change note: problem, scope, and acceptance criteria.
- Mark the update as one of:
  - Non-functional (layout/style only)
  - Functional (logic behavior changes)
  - Data-impacting (schema/data changes)

2. Build
- Route changes: keep endpoint naming consistent with current style.
- Controller changes: validate/sanitize input and return consistent response shape.
- Service changes: isolate external API logic in service classes.
- View changes: avoid inline business logic; keep UI readable and responsive.
- Database changes:
  - Update both MySQL and SQLite schema paths when required.
  - Use safe defaults for new columns.
  - Do not drop/rename columns without migration and fallback plan.

3. Validate
- Run syntax checks for edited PHP files (`php -l`).
- Test happy path and key edge cases.
- Verify role-based access.
- Verify desktop + tablet + mobile rendering for affected screens.
- For billing/financial changes, test amount precision and totals.

4. Document
- Add/update docs when behavior changes.
- Add release note entry before merge.

## 6. Testing Guidelines
Minimum manual testing for each feature update:
- Authentication:
  - Authorized user can access feature
  - Unauthorized user is blocked
- Data correctness:
  - Create, edit, and view flow works
  - Search/filter/sort still works if relevant
- UI:
  - No horizontal overflow on mobile
  - Sidebar/header/modal behavior remains stable
  - Table-heavy screens stay scrollable and usable
- Integrations (if touched):
  - MikroTik, SMS, payment, or OLT calls fail gracefully

## 7. Breaking Change Rules
Treat as breaking if any of the following changes:
- API response keys are removed or renamed
- Required request fields are changed
- Authentication/authorization rules are tightened
- Existing database fields are removed or semantics are changed

For breaking updates:
- Require explicit approval
- Provide migration notes
- Provide rollback steps

## 8. Performance and Security Checks
- Use prepared statements (PDO) for all database queries.
- Escape output in views (`htmlspecialchars` or existing helper).
- Avoid N+1 query patterns on list/report screens.
- Keep expensive external calls out of page render loops when possible.

## 9. Release Note Template
Use this format for each merged feature update:

```
Title:
Type: Non-functional | Functional | Data-impacting
Modules Affected:
User Impact:
Technical Summary:
Database Changes: Yes/No
API Changes: Yes/No
Security Impact:
Rollback Plan:
Verification Done:
```

## 10. Pull Request Checklist
- [ ] Scope is limited to intended feature update.
- [ ] No unrelated file changes.
- [ ] Syntax checks passed for edited files.
- [ ] Mobile responsiveness verified for touched screens.
- [ ] Docs/release notes updated.
- [ ] Rollback path documented (if functional or data-impacting).

