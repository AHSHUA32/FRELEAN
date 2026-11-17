
<?php
// admin_lost_reports.php

require_once "config.php";
require_admin();

$page_title = "Manage Lost Reports";

$sql = "SELECT
            r.id,
            r.item_name,
            r.category,
            r.description,
            r.location_lost,
            r.date_lost,
            r.status,
            u.user_code,
            u.full_name,
            u.email
        FROM lost_reports r
        INNER JOIN users u ON r.user_id = u.id
        ORDER BY r.id DESC";

$result = mysqli_query($conn, $sql);

include "header.php";
?>

<div class="page-head">
    <h1>Lost Item Reports</h1>
</div>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>Report ID</th>
        <th>User ID</th>
        <th>Reported By</th>
        <th>Item</th>
        <th>Location</th>
        <th>Date Lost</th>
        <th>Status</th>
    </tr>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <tr>
            <td colspan="7" class="center">
                No lost reports recorded.
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
            <td><?php echo e($row["user_code"]); ?></td>
            <td><?php echo e($row["full_name"]); ?></td>
            <td><?php echo e($row["item_name"]); ?></td>
            <td><?php echo e($row["location_lost"]); ?></td>
            <td><?php echo e($row["date_lost"]); ?></td>
            <td><?php echo e($row["status"]); ?></td>
        </tr>
    <?php endwhile; ?>

</table>
</div>

<?php include "footer.php"; ?>
