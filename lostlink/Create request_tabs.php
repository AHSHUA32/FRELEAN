
<?php
// request_tabs.php
// Requires $request_view and $request_role.

$is_admin_view = ($request_role === "admin");

$base_url = $is_admin_view
    ? "admin_claims.php"
    : "my_reports.php";
?>

<div class="requests-heading">
    <h1>
        <?php echo $is_admin_view
            ? "Reports & Claim Management"
            : "My Reports & Claims"; ?>
    </h1>

    <p>
        <?php echo $is_admin_view
            ? "Review lost item reports and ownership claims."
            : "Track your lost item reports and claim requests."; ?>
    </p>
</div>

<div class="requests-tabs" role="navigation"
     aria-label="Reports and claims">

    <a
        href="<?php echo $base_url; ?>?view=reports"
        class="<?php echo $request_view === "reports"
            ? "active" : ""; ?>"
        <?php echo $request_view === "reports"
            ? 'aria-current="page"' : ""; ?>
    >
        Lost Reports
    </a>

    <a
        href="<?php echo $base_url; ?>?view=claims"
        class="<?php echo $request_view === "claims"
            ? "active" : ""; ?>"
        <?php echo $request_view === "claims"
            ? 'aria-current="page"' : ""; ?>
    >
        <?php echo $is_admin_view
            ? "Claim Management" : "My Claims"; ?>
    </a>
</div>
