# LendLab — CSS and JavaScript layout

The old single `style.css` (3,300+ lines) and the inline `<script>` blocks have been split per module.

## CSS (`public/assets/css/`)

| File | What is in it | Loaded by |
|---|---|---|
| `base.css` | Design tokens (`:root`), reset, layout (sidebar, top bar), buttons, cards, forms, tables, modals, snackbar, notifications, pager, responsive rules. Everything that more than one module needs. | every page (`includes/html-head.php`, login/signup/404 heads) |
| `shared/borrower.css` | Tabs, loan cards, pills, notices, empty states used by several borrower pages | borrower pages |
| `shared/checkout.css` | Handover / Return screens shared by `staff/handover`, `staff/return`, `manage/checkouts` | those three |
| `pages/<module>.css` | Rules used by **one** page only (e.g. `manage-dashboard.css`, `borrower-dashboard.css`, `manage-catalog.css`) | that page |
| ~~`_unused-rules.css`~~ | **Deleted.** Selectors no PHP/JS file references any more (old Figma/home/fines-panel experiments). Safe to delete. | nobody |

Load order is always `base.css` → `shared/*` → `pages/*`, so a page file can refine a base rule.

### Adding styles for a page
1. Put the rules in `pages/<module>.css` (create it if needed).
2. At the top of the page, before the head include, list it:
   ```php
   $pageTitle  = 'My Page';
   $pageStyles = ['pages/my-page'];   // add 'shared/borrower' etc. if the page uses those too
   ```
`includes/html-head.php` prints one `<link>` per entry. If a rule is needed by two or more modules, move it to `base.css` (or a `shared/` file).

## JavaScript (`public/assets/js/`)

| File | Purpose |
|---|---|
| `app-core.js` | `APP_BASE`, CSRF header on every `fetch`, `setBtnText()`, `postForm(url, formData)` (head of every page) |
| `snackbar.js`, `sidebar.js`, `dashboard-actions.js`, `topbar.js` | Toasts, mobile sidebar, modals (`openModal`/`closeModal`), page search + notification bell (loaded by `includes/footer.php`) |
| `filters.js`, `countdown.js` | Paged / filtered lists, pick-up countdowns (Handover) and due-date countdowns that turn into "Overdue by ..." (Active Returns) |
| `shared/handover.js`, `shared/return.js` | Handover and Return modals, used by both the staff pages and `manage/checkouts` (they used to be copy-pasted) |
| `pages/<module>.js` | The page's own behaviour (e.g. `manage-requests.js`, `borrower-wishlist.js`) |

PHP never writes JavaScript any more. Values the scripts need are passed as data:
- `data-*` attributes (`#reserve-form` carries the pick-up window and due time; `#bellBtn` carries the user id and role), or
- a JSON data block: `<script type="application/json" id="accessories-data">` (read with `JSON.parse(...)`).

Page scripts go at the very end of the page, after `includes/footer.php`:
```php
<script src="<?= APP_BASE ?>/public/assets/js/pages/my-page.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/my-page.js') ?: 1 ?>"></script>
```

## Modals (pop-up windows)

The markup of every pop-up lives in `includes/modal/`, not inside the pages. A page includes the ones it needs:
```php
<?php include __DIR__ . '/../../includes/modal/manage/requests-approve.php'; ?>
```

| Folder | Contents |
|---|---|
| `includes/modal/shared/` | used by several pages: `logout`, `notification`, `handover`, `return` |
| `includes/modal/manage/` | `catalog-add/edit/accessories/category`, `fines-charge/pay/waive`, `requests-approve/decline`, `serialized-add-unit/edit-unit` |
| `includes/modal/borrower/` | `catalog-reserve`, `wishlist-reserve`, `wishlist-remove`, `requests-cancel` |
| `includes/modal/admin/` | `users-edit` |

- Modal styles: `css/shared/modal.css` (loaded on every page by `html-head.php`).
- Modal behaviour: `openModal` / `closeModal` in `js/dashboard-actions.js`; each modal's own logic stays in the page or `js/shared/*.js` file.
- Buttons no longer use `onclick`. Use `data-open-modal="approveModal"` / `data-close-modal="approveModal"`.
  Buttons that pass values use `data-*` attributes (e.g. `data-approve`, `data-borrower`, `data-model`) and a listener in the page's `.js` file.
- Dashboards: `css/shared/dashboard.css` (grid shared by both dashboards) + `css/pages/manage-dashboard.css` / `borrower-dashboard.css`.
- Former inline `style=""` attributes became classes named `<page>-sN` at the bottom of that page's CSS file. Styles that JavaScript toggles (`display:none`) or that PHP computes (progress bar width, availability colours) intentionally stay inline.

## Inter font
`base.css` declares `@font-face` for Inter. To self-host it, download the variable font (Inter-roman.var.woff2, from rsms.me/inter) into
`public/assets/vendor/inter/`. Without the file the page falls back to the system UI font (Segoe UI, Roboto, etc.), so nothing breaks.

## Shared CSS files
`base.css` holds tokens, typography, buttons, cards, tables, forms and page components. The app shell is split out and loaded right after it in `includes/html-head.php`:
`shared/sidebar.css` (left navigation), `shared/topbar.css` (top bar + notification bell/list), `shared/snackbar.css` (toasts; also linked on the login and sign-up pages).
There is no footer stylesheet: `includes/navigation/footer.php` has no visible footer, it only includes the logout modal (styled in `shared/modal.css`) and the shared scripts.
