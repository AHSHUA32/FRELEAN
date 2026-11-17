
<?php
// admin_user_detail.php

require_once "config.php";
require_admin();

$page_title = "User History";

$user_id = (int)($_GET["id"] ?? 0);

if ($user_id <= 0) {
    header("Location: admin_users.php");
    exit;
}

// Get user information.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, user_code, full_name,
            email, username, role, created_at
     FROM users WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: admin_users.php");
    exit;
}

// Get lost reports.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, item_name, status, created_at
     FROM lost_reports
     WHERE user_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$reports = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Get claim history.
$stmt = mysqli_prepare(
    $conn,
    "SELECT c.id, c.status, c.created_at,
            i.item_name
     FROM claims c
     JOIN items i ON c.item_id = i.id
     WHERE c.user_id = ?
     ORDER BY c.id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$claims = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Get activity history.
$stmt = mysqli_prepare(
    $conn,
    "SELECT action, description, created_at
     FROM activity_logs
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 100"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$activities = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

include "header.php";
?>

<a href="admin_users.php" class="back">
    &larr; Back to Users
</a>

<div class="page-head">
    <h1>User Tracking History</h1>
</div>

<div class="form-card">

    <h2><?php echo e($user["full_name"]); ?></h2>

    <table class="info">
        <tr>
            <td>User ID</td>
            <td><?php echo e($user["user_code"]); ?></td>
        </tr>

        <tr>
            <td>Username</td>
            <td><?php echo e($user["username"]); ?></td>
        </tr>

        <tr>
            <td>Email</td>
            <td><?php echo e($user["email"]); ?></td>
        </tr>

        <tr>
            <td>Account Type</td>
            <td><?php echo e($user["role"]); ?></td>
        </tr>

        <tr>
            <td>Registered</td>
            <td><?php echo e($user["created_at"]); ?></td>
        </tr>
    </table>
</div>

<h2 class="section-title">Lost Reports</h2>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>Report ID</th>
        <th>Item</th>
        <th>Status</th>
        <th>Date</th>
    </tr>

    <?php if (mysqli_num_rows($reports) === 0): ?>
        <tr>
            <td colspan="4" class="center">
                No reports found.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($reports)): ?>
        <tr>
            <td>LR-<?php echo str_pad(
                $row["id"], 5, "0", STR_PAD_LEFT
            ); ?></td>

            <td><?php echo e($row["item_name"]); ?></td>
            <td><?php echo e($row["status"]); ?></td>
            <td><?php echo e($row["created_at"]); ?></td>
        </tr>
    <?php endwhile; ?>

</table>
</div>

<h2 class="section-title">Claim History</h2>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>Claim ID</th>
        <th>Item</th>
        <th>Status</th>
        <th>Date</th>
    </tr>

    <?php if (mysqli_num_rows($claims) === 0): ?>
        <tr>
            <td colspan="4" class="center">
                No claims found.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($claims)): ?>
        <tr>
            <td>CL-<?php echo str_pad(
                $row["id"], 5, "0", STR_PAD_LEFT
            ); ?></td>

            <td><?php echo e($row["item_name"]); ?></td>
            <td><?php echo e($row["status"]); ?></td>
            <td><?php echo e($row["created_at"]); ?></td>
        </tr>
    <?php endwhile; ?>

</table>
</div>

<h2 class="section-title">Activity Logs</h2>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>Activity</th>
        <th>Description</th>
        <th>Date and Time</th>
    </tr>

    <?php if (mysqli_num_rows($activities) === 0): ?>
        <tr>
            <td colspan="3" class="center">
                No activity recorded yet.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($activities)): ?>
        <tr>
            <td><?php echo e($row["action"]); ?></td>
            <td><?php echo e($row["description"]); ?></td>
            <td><?php echo e($row["created_at"]); ?></td>
        </tr>
    <?php endwhile; ?>

</table>
</div>

<?php include "footer.php"; ?>
