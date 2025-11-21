# Workflow Playbook

Building a polished resource is more than writing code. This playbook turns the process into a repeatable checklist so nothing slips through the cracks.

## 1. Discover the Use Case

- **Stakeholders** — who will use this screen (support, content, ops)?
- **Goals** — what decision or action must the screen enable?
- **Data shape** — flat list, hierarchy, grouped sections, media-heavy content?
- **Constraints** — required approvals, publish windows, multi-language content?

Write these notes down before touching PHP; they will guide every decision.

## 2. Plan the Table

1. Draft the essential columns (primary identifier, owner, status, timestamps).
2. Decide which columns deserve inline editing (booleans, small numbers).
3. List the filters users will expect (status, date range, categories).
4. Choose the display mode (standard table, tree, sortable list, grouped list).
5. Define default sorting and page size so results feel curated out of the box.

Deliverable: a short table spec (columns, filters, actions) that you can hand to reviewers.

## 3. Plan the Form

1. Break the form into sections or tabs. Begin with the minimal data required to create a record.
2. Identify fields that repeat (Fieldset) or host media uploads.
3. Determine validation rules and any dynamic defaults (slug from title, author from current user).
4. Decide where contextual help is needed (tooltips, help text, placeholder hints).

Deliverable: a hierarchy of sections/fields plus validation notes.

## 4. Implement Iteratively

1. **Scaffold** using `php artisan ave:resource ModelName` if you want a head start.
2. **Fill metadata** (`$model`, `$label`, `$slug`, `$icon`, `$group`, `$navSort`).
3. **Implement the table** in small steps (columns → filters → display mode).
4. **Implement the form** section by section, testing each component in the browser.
5. **Add actions** after the table/form work, ensuring permissions and confirmations are in place.
6. **Hook into lifecycle** (`beforeCreate`, `afterUpdate`, etc.) only when business rules require it.

Commit early and often; reviewers prefer small, scoped changes.

## 5. Review Checklist

- [ ] All table columns have labels, alignment, and sensible widths.
- [ ] Search/sort/filter behaviour matches stakeholder expectations.
- [ ] Form contains help text for non-obvious fields.
- [ ] Validation covers both happy path and obvious misuses.
- [ ] Actions include confirmation text and emit meaningful success messages.
- [ ] Permission abilities are defined and seeded.
- [ ] Menu entry or dashboard card links to the resource.
- [ ] Tests (manual or automated) cover critical workflows.

Walk through the checklist with the stakeholder to sign off.

## 6. Launch & Iterate

1. Deploy migrations (if any) and publish updated assets/views when needed.
2. Seed new roles/permissions.
3. Monitor the first few sessions or error logs; note any UX friction.
4. Schedule a follow-up review after real users spend a week with the resource.

Use their feedback to fine-tune filters, add bulk actions, or restructure tabs.

## Communication Templates

- **Kickoff summary**  
  “Goal: enable editors to manage featured products. Table shows ID, cover, name, category, status, updated_at. Filters: status, category. Actions: toggle featured, duplicate. Form tabs: Basic, Content, Media.”

- **Implementation update**  
  “Table/filters/actions ready for review at `/admin/products`. Form includes Fieldset for features and Media for gallery. Pending: approval action.”

- **Rollout note**  
  “Products resource live. Permissions granted to Editorial role. Use `/admin/products` to manage catalog; `PublishProductAction` adds items to the featured carousel.”

Keeping stakeholders informed reduces surprises and speeds up approvals.
