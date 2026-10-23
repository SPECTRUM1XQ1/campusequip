# Files to delete from your project folder

Copying the fixed version over your old project does NOT delete the old files, so they are still there as stale duplicates.
Nothing in the current code uses any of them. Delete exactly these:

    public/admin/dashboard.php      (now public/manage/dashboard.php)
    public/admin/requests.php       (now public/manage/requests.php)
    public/admin/checkouts.php      (now public/manage/checkouts.php)
    public/admin/fines.php          (now public/manage/fines.php)
    public/admin/catalog.php        (now public/manage/catalog.php)
    public/admin/serialized.php     (now public/manage/serialized.php)
    public/admin/logs.php           (now public/manage/logs.php)
    public/staff/checkouts.php      ("coming soon" stub)
    public/staff/logs.php           ("coming soon" stub)
    public/staff/requests.php       ("coming soon" stub)
    public/borrower/home.php        (old copy of dashboard.php)
    includes/coming-soon.php        (only the stubs used it)
    test.php                        (old test: writes to the REAL database and is reachable from a browser, delete first)
    database schema.txt             (out of date; see docs/DATABASE.md)

KEEP the folders public/admin (still holds users.php, settings.php, export.php) and public/staff (still holds handover.php and return.php).
