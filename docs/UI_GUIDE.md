# LendLab — Button guide

One look per **role**. Same shape (6px corners) and same height everywhere; only two sizes:
default (forms, dialogs) and `btn-sm` (inside cards and tables). Icons are optional and always go before the label.

| Role | Class | Look | Use it for | Examples |
|---|---|---|---|---|
| Primary | `btn btn-primary` | Filled blue | The ONE main action of a card or form | Save Changes, + Add New Item, View Units |
| Secondary | `btn btn-outline` | White, grey border | Routine actions | Edit, Cancel, Accessories, Restore |
| Approve | `btn-approve` | Filled green | Only "yes, accept" | Approve, Mark paid |
| Destructive (outline) | `btn-decline`, `btn btn-outline-danger`, `btn-cancel-outline`, `btn-trash` | White, red border + red text | Risky actions that can be undone | Decline, Waive, Archive, Cancel request, Remove |
| Destructive (solid) | `btn btn-danger` | Filled red | The final confirm inside a delete / archive dialog | Confirm archive |

Rules: at most one primary button per card · Edit always looks the same (`btn btn-outline btn-rect btn-sm` + pencil icon) ·
red is only for destructive actions · filter chips (`filter-pill`) are intentionally pill-shaped because they are filters, not actions.

## Icons and the hover reveal

- Every button has an icon (Phosphor, self-hosted in `public/assets/vendor/phosphor/`, so it works offline).
- **Icon-first buttons** (`btn-reveal`) are used for repeated row/card actions: Edit, Accessories, View Units, Archive, Restore,
  Approve, Decline, Mark Paid, Waive. Only the icon shows; on hover or keyboard focus the label slides out (220 ms).
- **Always-labelled buttons** are one-per-page or dialog buttons (+ Add New Item, Save Changes, Cancel, Hand Over, Reserve...), because
  the decisive action must never be ambiguous.
- Touch screens have no hover, so they always show the full label. Screen readers always read the label.
- To change a button's text from JavaScript use `setBtnText(btn, 'Saving...')`, not `btn.textContent = ...` (which would delete the icon).
- **Tables never move on hover.** The Actions column of a table has a reserved width (`.col-action`, or `.col-action--wide` for two buttons),
  sized for the widest expanded button. The label slides out inside space that is already there, neighbours shift over exactly like in the
  Equipment Catalog cards, and the table itself does not move (measured in Chromium: 0 px; before the fix 23-69 px).
  If you add a longer label, widen the column with `--wide` or a larger `width`.

## Colour = meaning

| Colour | Class | Meaning | Where |
|---|---|---|---|
| Navy, filled | `btn-primary` | main action | View Units, Save, Add |
| Teal tint | `btn-soft-teal` | related items | Accessories |
| Grey outline | `btn-outline` | routine | Edit, Cancel |
| Green tint | `btn-soft-green` | bring back | Restore |
| Green, filled | `btn-approve` | accept / collect | Approve, Mark Paid |
| Red outline | `btn-outline-danger`, `btn-decline` | risky | Archive, Decline, Waive |

All text/background pairs are at least 4.5:1 contrast. Do not add a colour without giving it a meaning in this table.

## Cards and layout

- **White cards that hold information "stand"**: white background plus the soft shadow `0 1px 3px rgba(0,0,0,.06)` (the same as Fines & Penalties). Empty-state cards carry no information, so they stay flat and dashed.
- `.bg-white` is the utility class for a white card. (It was used in the markup but had no CSS rule, so those cards were transparent.)
- A list of cards never stretches across the screen. It sits in a grid that fits as many cards per row as the width allows:

| Class | Used for |
|---|---|
| `.ops-cards` | staff Handover and Process Return, and both columns of the admin Checkout & Return page |
| `.loan-grid` | borrower Home (approved pick-ups), Borrowing & Requests (all three tabs), Fines & Penalties |
| `.catalog-list` | staff/admin Equipment Catalog |

- No gradients anywhere: use a flat colour plus a coloured left border.

## One Edit button (instructor feedback)

Every Edit button is produced by one PHP helper, `edit_button()` in `includes/helpers/view-helpers.php`, so it cannot drift:
`btn btn-outline btn-sm btn-rect btn-reveal` + pencil icon + "Edit", with `title="Edit"` and `aria-label="Edit"` so the icon-only
state still has a name (tooltip on hover, announced by screen readers). Used on Manage Users, Equipment Catalog and Serialized Units.
Never hand-write an Edit button; call `<?= edit_button(['id' => $id], 'optional-js-hook-class') ?>`.

## Paging (instructor feedback)

Long lists use `data-page-size` + `data-pager` (see `filters.js`): tables 10 rows, card grids 6 (catalog 9, wishlist columns 4).
Paged lists: all admin/manage tables, Catalog, Borrowing & Requests (all three tabs), Fines, Wishlist (both columns).

## Contrast

Status text uses `--success-text` (#15803d) and `--danger-text` (#b91c1c), both at least 4.5:1 on white and on their tinted badge
backgrounds. Light greys (#94a3b8, #cbd5e1) are for decorative icons and borders only, never text.
