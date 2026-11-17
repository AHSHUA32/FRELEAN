
<?php
// my_reports.php

require_once "config.php";
require_login();

$page_title = "My Reports & Claims";
$user_id = (int)$_SESSION["user_id"];

$request_role = "user";
$request_view = $_GET["view"] ?? "reports";

if (!in_array($request_view, ["reports", "claims"], true)) {
    $request_view = "reports";
}

if ($request_view === "claims") {
    $sql = "
        SELECT c.id, c.status, c.created_at,
               i.item_name, i.category
        FROM claims c
        JOIN items i ON c.item_id = i.id
        WHERE c.user_id = ?
        ORDER BY c.id DESC
    ";
} else {
    $sql = "
        SELECT id, item_name, category,
               location_lost, date_lost,
               time_lost, status
        FROM lost_reports
        WHERE user_id = ?
        ORDER BY id DESC
    ";
}

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$records = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

include "header.php";
require __DIR__ . "/request_tabs.php";
?>

<div class="requests-content">

    <?php if ($request_view === "reports"): ?>

        <div class="requests-section-header">
            <div>
                <h2>My Lost Reports</h2>
                <p class="muted">
                    View and track your submitted reports.
                </p>
            </div>

            <a href="report_lost.php" class="btn">
                + Report Lost Item
            </a>
        </div>

        <?php if (isset($_GET["created"])): ?>
            <div class="success">
                Your lost report was submitted successfully.
            </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Date Lost</th>
                        <th>Time Lost</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$records): ?>
                        <tr>
                            <td colspan="7">
                                <div class="reports-empty-state">
                                    <h3>No Lost Reports Yet</h3>
                                    <p>You haven't submitted any lost reports.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td>LR-<?php echo str_pad(
                                $r["id"], 5, "0", STR_PAD_LEFT
                            ); ?></td>
                            <td><?php echo e($r["item_name"]); ?></td>
                            <td><?php echo e($r["category"]); ?></td>
                            <td><?php echo e($r["location_lost"]); ?></td>
                            <td><?php echo e($r["date_lost"]); ?></td>
                            <td><?php echo $r["time_lost"]
                                ? e(date("h:i A", strtotime($r["time_lost"])))
                                : "Not specified"; ?></td>
                            <td>
                                <span class="claim-status <?php
                                    echo e(strtolower($r["status"]));
                                ?>">
                                    <?php echo e($r["status"]); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="requests-section-header">
            <div>
                <h2>My Claim Requests</h2>
                <p class="muted">
                    Track your submitted claims and their status.
                </p>
            </div>
        </div>

        <?php if (isset($_GET["created"])): ?>
            <div class="success">
                Your claim was submitted successfully.
            </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$records): ?>
                        <tr>
                            <td colspan="5">
                                <div class="reports-empty-state">
                                    <h3>No Claim Requests Yet</h3>
                                    <p>You haven't submitted any claims.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td>CL-<?php echo str_pad(
                                $r["id"], 5, "0", STR_PAD_LEFT
                            ); ?></td>
                            <td><?php echo e($r["item_name"]); ?></td>
                            <td><?php echo e($r["category"]); ?></td>
                            <td><?php echo e(date(
                                "M d, Y - h:i A",
                                strtotime($r["created_at"])
                            )); ?></td>
                            <td>
                                <span class="claim-status <?php
                                    echo e(strtolower($r["status"]));
                                ?>">
                                    <?php echo e($r["status"]); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

<?php include "footer.php"; ?>
