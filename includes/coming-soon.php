<?php
// Expects $pageTitle and optionally $comingSoonMessage to be set by the
// including page before this file loads.
?>
<div class="page-header">
    <h2><?= htmlspecialchars($pageTitle ?? 'Coming Soon') ?></h2>
    <p class="text-muted">This page is still being built.</p>
</div>

<div class="coming-soon-card">
    <i class="ph ph-hammer"></i>
    <h3>Coming Soon</h3>
    <p class="text-muted">
        <?= htmlspecialchars($comingSoonMessage ?? 'This feature is under active development.') ?>
    </p>
</div>
