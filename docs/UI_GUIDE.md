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

Every paged list pages the same way: Previous / Next buttons, tappable page dots, and swipe (a two-finger swipe on a laptop trackpad,
a finger on a touch screen, or a mouse drag on card lists, not tables). The list follows the finger, resists at the first / last page and springs back; the new page slides in from the
side you went toward. A "Swipe" hint shows under the list on touch screens, plus a one-time nudge the first time it is on screen.
The borrower Equipment Catalog is the one exception: `data-pager-style="swipe"` removes Previous / Next (swipe or drag, tap a dot,
or use the arrow keys). Tables that scroll sideways on a phone keep their own scrolling instead of swiping.

## Motion (`shared/motion.css`, `motion.js`)

Page blocks rise in one after another, cards lift on hover (tables and table rows never move), modals pop in, the Pending tab
content fades up, big numbers count up. All of it stops when the system asks for reduced motion.

## Styled dropdown (`shared/dropdown.css`, `dropdown.js`)

Add `data-ce-dropdown` to a `<select>` to get a styled choice list (search box from 8 choices, tick on the chosen one, arrow keys,
opens upward near the bottom of the screen). The `<select>` stays in the page and keeps the value, so forms and `change` listeners are
untouched; after setting `select.value` from code call `CEDropdown.sync(form)`. Used on Export Records (Borrower, Equipment) and the
Serialized Items model filter. A filter with only a few short choices (Status on Export Records) uses pills like the other pages.

## Export Records

Exports the **transaction log** (one row per log entry: request, approval, handover, return, fine ...), not the reservations. The filters
(borrower, equipment, status, requested from / to) act on the request a log entry belongs to. The page lists every match and pages it
in the browser with the shared pager; Excel and Print always contain every match. Two-finger swipe changes the page on tables too, even
when the table scrolls sideways (scroll such a table with its scroll bar or shift + wheel).

## Collapsible sections

A section that can fold away uses `shared/collapse.css` + `assets/js/collapse.js` (markup is documented at the top of the CSS file).
The height-and-fade animation is pure CSS (a grid row going from `1fr` to `0fr`); the script only flips `.is-collapsed` and
`aria-expanded`, and remembers the choice for the browser tab so a page that reloads after an action keeps its sections as they were.
Used on Fines & Penalties (Overdue, Charged Fines) and Borrowing & Requests > Pending Request (Approved, Waiting).
Pending Request cards sit in a 3-column grid (2 rows = 6 per page), dropping to 2 and 1 columns when the group is narrow.

## Long text (`shared/truncate.css`, `truncate.js`)

Long names, titles, values and notes are cut with "..." instead of spilling out of their box: titles and card values keep two
lines, names and amounts one line, plain-text table cells one line (max 20rem), and closed dropdowns show one line with a
steady arrow. Buttons, pills and form fields are never cut. The full text appears as a tooltip when the mouse rests on a cut
piece, and everything prints in full. For new markup use `.text-truncate` (one line) or `.text-clamp-2` / `.text-clamp-3`.

## Contrast

Status text uses `--success-text` (#15803d) and `--danger-text` (#b91c1c), both at least 4.5:1 on white and on their tinted badge
backgrounds. Light greys (#94a3b8, #cbd5e1) are for decorative icons and borders only, never text.
