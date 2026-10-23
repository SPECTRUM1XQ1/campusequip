# Design fixes: what changed and how to upgrade

## Upgrade steps (existing install)
1. Back up your database.
2. Run `database/migrations/2026-10-17_design_fixes.sql` once (fresh installs: just import `database/campusequipdb.sql`).
3. Copy the new files over the project (note: `public/admin/*` pages moved to `public/manage/`, `test.php` and `database schema.txt` were removed).
4. Optional but recommended: schedule `scripts/expire_pickups.php` (cron / Windows Task Scheduler, every 15 min).
5. Run the tests against a throwaway DB: `CAMPUSEQUIP_DB=campusequipdb_test php tests/run_tests.php`

## The 14 fixes
| # | Problem | Fix |
|---|---|---|
| 1 | No CSRF protection | Session token checked in `require_post()` and the auth handler; `app-core.js` adds it to every `fetch()` POST automatically. Session cookie is now HttpOnly + SameSite=Lax (+Secure on HTTPS). |
| 2 | Requests ignored availability; two sources of truth | `Reservation::create()` locks the model and refuses when overlapping pending/approved/checked-out requests already equal the in-service units; same borrower cannot double-book. Availability = date ranges; unused `reserved` unit status removed. Unit picker offers units free for the requested dates. |
| 3 | Due "00:00" made same-day returns late | Loans start 00:00 on day 1 and are due `LOAN_DUE_TIME` (17:00, in `config.php`) on the last day. Migration adjusts open loans. |
| 4 | One unpaid fine per loan blocked late + damage | Several fines per loan; each has `fine_type`, `reason`, `days_late`, `rate_applied`, `created_at`. One late fine per loan; double-click guard. Charge form has a type picker. |
| 5 | Auto-expiry logged the borrower as actor | `actor_id` is nullable; shown as "System". `scripts/expire_pickups.php` runs expiry on a schedule (page loads remain a fallback). |
| 6 | Permissions implemented twice; open catalog endpoints | One `Permissions` class used by pages and API. `catalog/details` and `catalog/categories` now require login (delete the `current_user();` line to make them public again). |
| 7 | SQL/logic inside pages; duplicate borrower page | New `Dashboard` class, `Fine::totals()`. Removed unused `borrower/home.php`. |
| 8 | Redundant state | Dropped `reservations.pickup_at` (duplicate of `checkouts.checked_out_at`) and the unused `reserved` status. Reservation status stays (it is the booking lifecycle). |
| 9 | Folder layout vs roles | `public/manage/` = shared staff+admin pages, `public/admin/` = admin-only, `public/staff/` = single-job pages. Three "coming soon" stubs removed. |
| 10 | Stale schema doc | `docs/DATABASE.md` generated from the live schema; old txt removed. |
| 11 | Hardcoded `/campusequip` | Only `APP_BASE` in `config.php` (JS reads it from a meta tag). Apache's `ErrorDocument` line in `public/.htaccess` must be edited by hand if you rename the folder. |
| 12 | N+1 query on pending requests | 2 queries total. |
| 13 | `test.php` hit the real DB from the web root | Replaced by `tests/run_tests.php` (CLI only, refuses non-`_test` databases). `tests/`, `scripts/`, `docs/`, `bruno/`, `.vscode/` are denied by `.htaccess`. |
| 14 | Escaping audit | No exploitable XSS found. Hardened inline-JS arguments (`addslashes` -> `js_arg()`), found and fixed broken notification links (`admin/handover.php` never existed), documented the raw-HTML helper in `borrower/fines.php`. |

## Things to know
- Your **Bruno** API requests will now get 403 on POSTs: add header `X-CSRF-Token` (value from the `csrf-token` meta tag of any page, same session cookie).
- Requests that are *pending* now reserve capacity, so a model can show "all booked" while requests await review.
- `Equipment.php` still calls `mb_strlen` directly (fine on XAMPP; needs the mbstring extension).
- **Fines are managed by administrators only.** Charge, Mark Paid and Waive are admin-only both on the page and in the API (staff get 403). Staff with the "inspect returns" permission can still see every fine (read-only) so they can remind borrowers. A late fine is still created automatically when staff process a late return.
