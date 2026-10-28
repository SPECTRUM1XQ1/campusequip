<?php
// Public landing page: the first thing a visitor sees. It explains what LendLab is, how borrowing works and
// what the rules are, then sends people to Log in / Sign up. Signed-in users skip it and go to their dashboard.
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Settings.php';

app_session_start();

if (isset($_SESSION['user_id'])) {
    $dest = in_array($_SESSION['role'], ['staff', 'admin'], true)
        ? APP_BASE . '/public/manage/dashboard.php'
        : APP_BASE . '/public/borrower/dashboard.php';
    header('Location: ' . $dest);
    exit;
}

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$loginUrl  = APP_URL . '/login/login.php';
$signupUrl = APP_URL . '/login/signup.php';
$deskHours   = trim(Settings::get('desk_hours'));
$deskContact = trim(Settings::get('desk_contact'));
$maintenance = Settings::isMaintenance();
$dueTime = date('g:i A', strtotime(LOAN_DUE_TIME));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h(APP_NAME) ?> — Equipment Reservation &amp; Asset Management</title>
    <meta name="description" content="<?= $h(APP_NAME) ?> is the equipment reservation and asset management portal for PHINMA UPang IT students. Reserve laptops, cameras and kits online.">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/public/assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_BASE ?>/public/assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/vendor/phosphor/style.css?v=2.1.2">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/base.css?v=<?= @filemtime(__DIR__ . '/assets/css/base.css') ?: 1 ?>">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/pages/landing.css?v=<?= @filemtime(__DIR__ . '/assets/css/pages/landing.css') ?: 1 ?>">
</head>

<body class="landing">
    <a class="skip-link" href="#main">Skip to content</a>

    <!-- ===== Top bar ===== -->
    <header class="lp-nav" id="lpNav">
        <div class="lp-wrap lp-nav-inner">
            <a class="lp-brand" href="#top" aria-label="<?= $h(APP_NAME) ?> home">
                <span class="app-logo lp-logo"><span class="app-logo-mark"></span></span>
                <span><?= $h(APP_NAME) ?></span>
            </a>

            <nav class="lp-links" id="lpLinks" aria-label="Main">
                <a href="#about">About</a>
                <a href="#how">How it works</a>
                <a href="#rules">Borrowing rules</a>
                <a href="#help">Equipment desk</a>
                <a class="lp-only-mobile" href="<?= $h($loginUrl) ?>">Log in</a>
                <a class="lp-only-mobile lp-mobile-cta" href="<?= $h($signupUrl) ?>">Sign up</a>
            </nav>

            <div class="lp-actions">
                <a class="lp-btn lp-btn-ghost" href="<?= $h($loginUrl) ?>">Log in</a>
                <a class="lp-btn lp-btn-solid" href="<?= $h($signupUrl) ?>">Sign up</a>
            </div>

            <button type="button" class="lp-burger" id="lpBurger" aria-label="Open menu" aria-expanded="false" aria-controls="lpLinks">
                <i class="ph ph-list" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <main id="main">
        <!-- ===== Hero ===== -->
        <section class="lp-hero" id="top">
            <div class="lp-wrap lp-hero-grid">
                <div class="lp-hero-copy">
                    <?php if ($maintenance): ?>
                        <p class="lp-notice"><i class="ph ph-wrench" aria-hidden="true"></i>
                            <?= $h(Settings::maintenanceMessage()) ?></p>
                    <?php endif; ?>
                    <p class="lp-eyebrow">PHINMA UPang &middot; IT equipment portal</p>
                    <h1>Equipping your <span>IT journey.</span></h1>
                    <p class="lp-lead">
                        <?= $h(APP_NAME) ?> is the official place to borrow school equipment. Browse what is available,
                        reserve laptops, cameras and kits online, and track every request from approval to return,
                        with no paper forms and no guessing if an item is free.
                    </p>
                    <div class="lp-cta">
                        <a class="lp-btn lp-btn-light lp-btn-lg" href="<?= $h($signupUrl) ?>"><i class="ph ph-user-plus" aria-hidden="true"></i>Create an account</a>
                        <a class="lp-btn lp-btn-outline lp-btn-lg" href="<?= $h($loginUrl) ?>"><i class="ph ph-sign-in" aria-hidden="true"></i>Log in</a>
                    </div>
                    <p class="lp-small">Use your PHINMA email and Student / Employee ID to sign up.</p>
                </div>

                <!-- Illustration only: a sample of what the catalog looks like -->
                <div class="lp-hero-art" aria-hidden="true">
                    <div class="lp-sample-tag">Sample view</div>
                    <div class="lp-sample lp-sample-1">
                        <span class="lp-sample-ico"><i class="ph ph-laptop"></i></span>
                        <div><strong>Laptop</strong><small>Max 3 days</small></div>
                        <span class="lp-pill lp-pill-ok">Available</span>
                    </div>
                    <div class="lp-sample lp-sample-2">
                        <span class="lp-sample-ico"><i class="ph ph-camera"></i></span>
                        <div><strong>DSLR Camera</strong><small>Max 3 days</small></div>
                        <span class="lp-pill lp-pill-ok">Available</span>
                    </div>
                    <div class="lp-sample lp-sample-3">
                        <span class="lp-sample-ico"><i class="ph ph-calendar-check"></i></span>
                        <div><strong>Your request</strong><small>Approved &middot; ready for pick-up</small></div>
                        <span class="lp-pill lp-pill-info">Approved</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== About ===== -->
        <section class="lp-section" id="about">
            <div class="lp-wrap">
                <header class="lp-head">
                    <p class="lp-kicker">What is <?= $h(APP_NAME) ?>?</p>
                    <h2>One place to borrow, track and return school equipment</h2>
                    <p>
                        <?= $h(APP_NAME) ?> is an equipment reservation and asset management system. Students and
                        faculty request equipment online, the equipment staff review and hand it over, and every
                        item is checked when it comes back, so everyone can see what is available, what is borrowed
                        and what is due.
                    </p>
                </header>

                <div class="lp-cards">
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-magnifying-glass" aria-hidden="true"></i></span>
                        <h3>See what is available</h3>
                        <p>Browse the equipment catalog and check availability before you walk to the desk. Save items you want for later.</p>
                    </article>
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-calendar-check" aria-hidden="true"></i></span>
                        <h3>Reserve in advance</h3>
                        <p>Pick your dates and tell us what the equipment is for. Your request goes straight to the equipment staff for approval.</p>
                    </article>
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-bell-ringing" aria-hidden="true"></i></span>
                        <h3>Stay informed</h3>
                        <p>Get notified when your request is approved or declined, and see your due dates and countdowns on your dashboard.</p>
                    </article>
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-shield-check" aria-hidden="true"></i></span>
                        <h3>Fair and accountable</h3>
                        <p>Every handover and return is recorded and inspected. Fines are clear, itemized and visible to you at all times.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ===== How it works ===== -->
        <section class="lp-section lp-section-tint" id="how">
            <div class="lp-wrap">
                <header class="lp-head">
                    <p class="lp-kicker">How it works</p>
                    <h2>Four steps from request to return</h2>
                </header>

                <ol class="lp-steps">
                    <li>
                        <span class="lp-step-num">1</span>
                        <h3>Reserve</h3>
                        <p>Log in, choose the equipment and the dates you need, and send your request.</p>
                    </li>
                    <li>
                        <span class="lp-step-num">2</span>
                        <h3>Wait for approval</h3>
                        <p>The equipment staff review your request. You are notified as soon as there is an answer.</p>
                    </li>
                    <li>
                        <span class="lp-step-num">3</span>
                        <h3>Pick it up</h3>
                        <p>Go to the equipment desk with your Student ID within your pick-up window.</p>
                    </li>
                    <li>
                        <span class="lp-step-num">4</span>
                        <h3>Return it on time</h3>
                        <p>Bring it back by <?= $h($dueTime) ?> on your due date. Staff inspect it and close the loan.</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- ===== Rules ===== -->
        <section class="lp-section" id="rules">
            <div class="lp-wrap lp-rules-grid">
                <header class="lp-head lp-head-left">
                    <p class="lp-kicker">Borrowing rules</p>
                    <h2>Know the rules before you borrow</h2>
                    <p>A few simple rules keep equipment available for everyone. The catalog shows the exact limits for each item.</p>
                </header>

                <ul class="lp-rules">
                    <li>
                        <i class="ph ph-clock" aria-hidden="true"></i>
                        <div><strong>Return by <?= $h($dueTime) ?></strong><span>Items are due at <?= $h($dueTime) ?> on the last day of your loan.</span></div>
                    </li>
                    <li>
                        <i class="ph ph-hand-arrow-up" aria-hidden="true"></i>
                        <div><strong>Pick up within <?= (int) PICKUP_WINDOW_DAYS ?> days</strong><span>Collect an approved item within <?= (int) PICKUP_WINDOW_DAYS ?> days of its approval (or of your start date, if that is later), or the reservation expires.</span></div>
                    </li>
                    <li>
                        <i class="ph ph-identification-card" aria-hidden="true"></i>
                        <div><strong>Bring your Student / Employee ID</strong><span>You will be asked for it at the equipment desk.</span></div>
                    </li>
                    <li>
                        <i class="ph ph-warning-circle" aria-hidden="true"></i>
                        <div><strong>Late and damaged items are fined</strong><span>Each item has its own maximum loan days and daily late fine, shown in the catalog before you reserve.</span></div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- ===== Who uses it ===== -->
        <section class="lp-section lp-section-tint" id="roles">
            <div class="lp-wrap">
                <header class="lp-head">
                    <p class="lp-kicker">Who uses it</p>
                    <h2>Built for borrowers, staff and administrators</h2>
                </header>
                <div class="lp-cards lp-cards-3">
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-student" aria-hidden="true"></i></span>
                        <h3>Borrowers</h3>
                        <p>Students and faculty browse the catalog, reserve equipment, follow their requests and see any fines.</p>
                    </article>
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-clipboard-text" aria-hidden="true"></i></span>
                        <h3>Equipment staff</h3>
                        <p>Review requests, hand equipment over at the desk, inspect returns and charge fines when needed.</p>
                    </article>
                    <article class="lp-card">
                        <span class="lp-card-ico"><i class="ph ph-users-three" aria-hidden="true"></i></span>
                        <h3>Administrators</h3>
                        <p>Manage the catalog, items, users and settings, and export borrowing reports for the school.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ===== Desk + final call ===== -->
        <section class="lp-section" id="help">
            <div class="lp-wrap">
            <div class="lp-final">
                <div>
                    <p class="lp-kicker">Equipment desk</p>
                    <h2>Ready to borrow?</h2>
                    <p>Create your account in a minute, or log in if you already have one.</p>
                    <div class="lp-cta">
                        <a class="lp-btn lp-btn-solid lp-btn-lg" href="<?= $h($signupUrl) ?>"><i class="ph ph-user-plus" aria-hidden="true"></i>Create an account</a>
                        <a class="lp-btn lp-btn-line lp-btn-lg" href="<?= $h($loginUrl) ?>"><i class="ph ph-sign-in" aria-hidden="true"></i>Log in</a>
                    </div>
                </div>

                <?php if ($deskHours !== '' || $deskContact !== ''): ?>
                    <aside class="lp-desk" aria-label="Equipment desk">
                        <h3>Visit the equipment desk</h3>
                        <?php if ($deskHours !== ''): ?><p><i class="ph ph-clock" aria-hidden="true"></i><?= $h($deskHours) ?></p><?php endif; ?>
                        <?php if ($deskContact !== ''): ?><p><i class="ph ph-headset" aria-hidden="true"></i><?= $h($deskContact) ?></p><?php endif; ?>
                    </aside>
                <?php endif; ?>
            </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-wrap lp-footer-inner">
            <span class="lp-brand lp-brand-foot">
                <span class="app-logo lp-logo"><span class="app-logo-mark"></span></span>
                <span><?= $h(APP_NAME) ?></span>
            </span>
            <p>Equipment Reservation &amp; Asset Management &middot; PHINMA University of Pangasinan</p>
            <p class="lp-copy">&copy; <?= date('Y') ?> <?= $h(APP_NAME) ?></p>
        </div>
    </footer>

    <script src="<?= APP_BASE ?>/public/assets/js/pages/landing.js?v=<?= @filemtime(__DIR__ . '/assets/js/pages/landing.js') ?: 1 ?>"></script>
</body>

</html>
