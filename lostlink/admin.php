
<?php
// admin.php

require_once "config.php";
require_admin();

$page_title = "CSU Dashboard";

if (empty($_SESSION["delete_token"])) {
    $_SESSION["delete_token"] = bin2hex(random_bytes(32));
}

$keyword = trim($_GET["keyword"] ?? "");
$status = trim($_GET["status"] ?? "");

if ($status !== "" && !in_array($status, $statuses, true)) {
    $status = "";
}

$search = "%" . $keyword . "%";

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM items
     WHERE (? = '' OR status = ?)
       AND (
           ? = ''
           OR item_name LIKE ?
           OR finder_name LIKE ?
           OR claimant_name LIKE ?
       )
     ORDER BY id DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssssss",
    $status, $status,
    $keyword, $search, $search, $search
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Dashboard counts.
$counts = [
    "Available" => 0,
    "Claimed" => 0,
    "Returned" => 0
];

$res = mysqli_query(
    $conn,
    "SELECT status, COUNT(*) AS total
     FROM items GROUP BY status"
);

while ($row = mysqli_fetch_assoc($res)) {
    if (isset($counts[$row["status"]])) {
        $counts[$row["status"]] = (int)$row["total"];
    }
}

$user_count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"
);
$user_count = (int)mysqli_fetch_assoc(
    $user_count_result
)["total"];

include "header.php";
?>

<section class="dashboard-welcome">
    <h1>CSU Dashboard</h1>
    <p>Welcome to the LostLink Administration Panel.</p>
    <p>Manage found items, reports, claims, and registered users.</p>
</section>

<section class="stats admin-dashboard-stats">
    <div class="stat">
        <h2><?php echo $counts["Available"]; ?></h2>
        <p>Available Items</p>
    </div>

    <div class="stat">
        <h2><?php echo $counts["Claimed"]; ?></h2>
        <p>Claimed Items</p>
    </div>

    <div class="stat">
        <h2><?php echo $counts["Returned"]; ?></h2>
        <p>Returned Items</p>
    </div>

    <div class="stat">
        <h2><?php echo $user_count; ?></h2>
        <p>Registered Users</p>
    </div>
</section>

<div class="dashboard-section-heading">
    <h2>Quick Actions</h2>
    <p>Choose a management task to continue.</p>
</div>

<section class="dashboard-actions admin-dashboard-actions">
    <a href="add_item.php" class="dashboard-action-card">
        <span class="dashboard-action-icon">&#128230;</span>
        <h3>Record Found Item</h3>
        <p>Register an item turned over to the office.</p>
        <span class="dashboard-action-link">Record Item &rarr;</span>
    </a>

    <a href="admin_lost_reports.php" class="dashboard-action-card">
        <span class="dashboard-action-icon">&#128203;</span>
        <h3>Lost Reports</h3>
        <p>Review reports submitted by users.</p>
        <span class="dashboard-action-link">View Reports &rarr;</span>
    </a>

    <a href="admin_claims.php" class="dashboard-action-card">
        <span class="dashboard-action-icon">&#10003;</span>
        <h3>Manage Claims</h3>
        <p>Review ownership and release requests.</p>
        <span class="dashboard-action-link">View Claims &rarr;</span>
    </a>

    <a href="admin_users.php" class="dashboard-action-card">
        <span class="dashboard-action-icon">&#128101;</span>
        <h3>Manage Users</h3>
        <p>View user profiles and activity history.</p>
        <span class="dashboard-action-link">View Users &rarr;</span>
    </a>
</section>


<div class="admin-activity-shortcut">
    <div>
        <h3>System Activity Monitoring</h3>
        <p>
            Review logins, reports, claims,
            and administrative actions.
        </p>
    </div>

    <a href="admin_activity_logs.php"
       class="btn">
        View Activity Logs
    </a>
</div>


<section class="admin-records-section">
    <div class="dashboard-section-heading">
        <h2>Found Item Records</h2>
        <p>Search, review, and manage recorded items.</p>
    </div>

    <?php if (isset($_GET["msg"])): ?>
        <div class="success">
            <?php echo e($_GET["msg"]); ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="admin-filter-bar">
        <input
            type="text"
            name="keyword"
            maxlength="150"
            placeholder="Search item, finder, or claimant..."
            value="<?php echo e($keyword); ?>"
        >

        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?php echo e($s); ?>"
                    <?php echo $status === $s ? "selected" : ""; ?>>
                    <?php echo e($s); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">
            Search
        </button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Date Found</th>
                    <th>Finder</th>
                    <th>Status</th>
                    <th>Claimant</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="reports-empty-state">
                                <h3>No Item Records Found</h3>
                                <p>No found items match your filters.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($items as $row): ?>
                    <tr>
                        <td>
                            LL-<?php echo str_pad(
                                $row["id"], 5, "0", STR_PAD_LEFT
                            ); ?>
                        </td>

                        <td>
                            <a href="item.php?id=<?php
                                echo (int)$row["id"];
                            ?>">
                                <?php echo e($row["item_name"]); ?>
                            </a>
                        </td>

                        <td><?php echo e($row["category"]); ?></td>

                        <td><?php echo e(date(
                            "M d, Y",
                            strtotime($row["date_found"])
                        )); ?></td>

                        <td><?php echo e($row["finder_name"]); ?></td>

                        <td><?php echo badge($row["status"]); ?></td>

                        <td>
                            <?php echo e($row["claimant_name"] ?: "—"); ?>
                        </td>

                        <td>
                            <div class="admin-row-actions">
                                <a class="action-edit"
                                   href="edit_item.php?id=<?php
                                       echo (int)$row["id"];
                                   ?>">
                                    View / Edit
                                </a>

                                <form method="POST"
                                      action="delete_item.php"
                                      onsubmit="return confirm('Delete this item?');">

                                    <input type="hidden"
                                           name="id"
                                           value="<?php
                                               echo (int)$row["id"];
                                           ?>">

                                    <input type="hidden"
                                           name="delete_token"
                                           value="<?php echo e(
                                               $_SESSION["delete_token"]
                                           ); ?>">

                                    <button type="submit"
                                            class="action-delete">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "footer.php"; ?>
