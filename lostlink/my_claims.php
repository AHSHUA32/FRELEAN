
<?php
// my_claims.php

require_once "config.php";
require_login();

$page_title = "My Claims";
$user_id = (int)$_SESSION["user_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT c.id, c.status, c.created_at,
            i.item_name, i.category
     FROM claims c
     INNER JOIN items i ON c.item_id = i.id
     WHERE c.user_id = ?
     ORDER BY c.id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include "header.php";
?>

<div class="page-head">
    <h1>My Claim Requests</h1>
    <a href="index.php" class="btn">Find Items</a>
</div>

<?php if (isset($_GET["created"])): ?>
    <div class="success">
        Claim submitted successfully.
    </div>
<?php endif; ?>

<div class="table-wrap">
<table class="table">
    <tr>
        <th>Claim ID</th>
        <th>Item</th>
        <th>Category</th>
        <th>Date Submitted</th>
        <th>Status</th>
    </tr>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <tr>
            <td colspan="5" class="center">
                You have no claim requests yet.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>
                CL-<?php echo str_pad(
                    $row["id"], 5, "0", STR_PAD_LEFT
                ); ?>
            </td>
            <td><?php echo e($row["item_name"]); ?></td>
            <td><?php echo e($row["category"]); ?></td>
            <td><?php echo e($row["created_at"]); ?></td>
            <td><?php echo e($row["status"]); ?></td>
        </tr>
    <?php endwhile; ?>
</table>
</div>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
