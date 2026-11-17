
<?php
// user_dashboard.php

require_once "config.php";
require_login();

if (is_admin()) {
    header("Location: admin.php");
    exit;
}

$page_title = "My Dashboard";
$user_id = (int) $_SESSION["user_id"];

// Logged-in user's display name.
$stmt = mysqli_prepare(
    $conn,
    "SELECT full_name
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Found items available for claiming.
$available = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM items
         WHERE status = 'Available'"
    )
);

// Successfully returned items.
$returned = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM items
         WHERE status = 'Returned'"
    )
);

include "header.php";
?>

<div class="dashboard-welcome">
    <h1>
        Welcome, <?php echo e($user["full_name"]); ?>!
    </h1>

    <p>
        Welcome to your LostLink Dashboard.
    </p>

    <p>
        Manage your lost and found activities
        easily in one place.
    </p>
</div>

<section class="stats dashboard-stats">

    <div class="stat">
        <h2><?php echo (int)$available["total"]; ?></h2>
        <p>Available Found Items</p>
    </div>

    <div class="stat">
        <h2><?php echo (int)$returned["total"]; ?></h2>
        <p>Items Returned</p>
    </div>

</section>

<div class="dashboard-section-heading">
    <h2>Quick Actions</h2>
    <p>Choose what you would like to do.</p>
</div>

<section class="dashboard-actions">

    <a href="report_lost.php" class="dashboard-action-card">
        <span class="dashboard-action-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M16 5h6M19 2v6"/>
                <path d="M7 10h6M7 14h10M7 18h7"/>
            </svg>
        </span>

        <h3>Report Lost Item</h3>
        <p>Report your missing belongings.</p>

        <span class="dashboard-action-link">
            Report Item &rarr;
        </span>
    </a>

    <a href="my_reports.php" class="dashboard-action-card">
        <span class="dashboard-action-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="3" width="16" height="18" rx="2"/>
                <path d="M8 8h8M8 12h8M8 16h5"/>
            </svg>
        </span>

        <h3>My Lost Reports</h3>
        <p>Track your submitted lost reports.</p>

        <span class="dashboard-action-link">
            View Reports &rarr;
        </span>
    </a>

    <a href="my_claims.php" class="dashboard-action-card">
        <span class="dashboard-action-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 3h6M9 3a2 2 0 0 0-2 2H5v16h14V5h-2a2 2 0 0 0-2-2"/>
                <path d="m8 13 3 3 5-6"/>
            </svg>
        </span>

        <h3>My Claim Requests</h3>
        <p>Check your claim request status.</p>

        <span class="dashboard-action-link">
            View Claims &rarr;
        </span>
    </a>

    <a href="index.php" class="dashboard-action-card">
        <span class="dashboard-action-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="10.5" cy="10.5" r="6.5"/>
                <path d="m16 16 5 5"/>
            </svg>
        </span>

        <h3>Find Lost Items</h3>
        <p>Browse items turned over to security.</p>

        <span class="dashboard-action-link">
            Browse Items &rarr;
        </span>
    </a>

</section>

<?php include "footer.php"; ?>
