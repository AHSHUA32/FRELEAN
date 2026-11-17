
<?php
// my_reports.php

require_once "config.php";
require_login();

$page_title = "My Lost Reports";
$user_id = (int) $_SESSION["user_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, item_name, category,
            location_lost, date_lost, status
     FROM lost_reports
     WHERE user_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

include "header.php";
?>

<div class="page-head">
    <h1>My Lost Reports</h1>
    <a href="report_lost.php" class="btn">
        + Report Lost Item
    </a>
</div>

<?php if (isset($_GET["created"])): ?>
    <div class="success">
        Lost item reported successfully!
    </div>
<?php endif; ?>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>Report ID</th>
        <th>Item</th>
        <th>Category</th>
        <th>Last Location</th>
        <th>Date Lost</th>
        <th>Status</th>
    </tr>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <tr>
            <td colspan="6" class="center">
                You have no lost reports yet.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>
                LR-<?php echo str_pad(
                    $row["id"], 5, "0", STR_PAD_LEFT
                ); ?>
            </td>

            <td><?php echo e($row["item_name"]); ?></td>
            <td><?php echo e($row["category"]); ?></td>
            <td><?php echo e($row["location_lost"]); ?></td>
            <td><?php echo e($row["date_lost"]); ?></td>
            <td><?php echo e($row["status"]); ?></td>
        </tr>
    <?php endwhile; ?>

</table>
</div>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
