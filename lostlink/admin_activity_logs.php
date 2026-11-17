
<?php
// admin_activity_logs.php

require_once "config.php";
require_admin();

$page_title = "Activity Logs";

// Filters
$keyword = trim($_GET["keyword"] ?? "");
$action = trim($_GET["action"] ?? "");
$date = trim($_GET["date"] ?? "");

// Available actions are loaded from saved logs.
$action_options = [];

$action_result = mysqli_query(
    $conn,
    "SELECT DISTINCT action
     FROM activity_logs
     ORDER BY action ASC"
);

while ($row = mysqli_fetch_assoc($action_result)) {
    $action_options[] = $row["action"];
}

if ($action !== "" &&
    !in_array($action, $action_options, true)) {
    $action = "";
}

// Check optional date filter.
if ($date !== "") {
    $date_object = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $date
    );

    if (!$date_object ||
        $date_object->format("Y-m-d") !== $date) {
        $date = "";
    }
}

// Basic search: user ID, user name, email,
// activity name, and description.
$search = "%" . $keyword . "%";

$sql = "SELECT
            a.id,
            a.action,
            a.description,
            a.created_at,
            u.user_code,
            u.full_name,
            u.email,
            u.role
        FROM activity_logs a
        INNER JOIN users u ON a.user_id = u.id
        WHERE (
            ? = ''
            OR u.user_code LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR a.action LIKE ?
            OR a.description LIKE ?
        )
        AND (? = '' OR a.action = ?)
        AND (? = '' OR DATE(a.created_at) = ?)
        ORDER BY a.id DESC
        LIMIT 100";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssss",
    $keyword,
    $search,
    $search,
    $search,
    $search,
    $search,
    $action,
    $action,
    $date,
    $date
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Total logs stored.
$total_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM activity_logs"
);

$total_logs = (int)mysqli_fetch_assoc(
    $total_result
)["total"];

// Distinct active users in activity logs.
$users_result = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT user_id) AS total
     FROM activity_logs"
);

$total_users = (int)mysqli_fetch_assoc(
    $users_result
)["total"];

// Today's log entries according to DB date.
$today_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM activity_logs
     WHERE DATE(created_at) = CURRENT_DATE()"
);

$today_logs = (int)mysqli_fetch_assoc(
    $today_result
)["total"];

include "header.php";
?>

<div class="admin-page-heading">
    <h1>Activity Logs</h1>
    <p>
        Monitor user activities, system events,
        and administrative actions.
    </p>
</div>

<section class="stats activity-stats">
    <div class="stat">
        <h2><?php echo $total_logs; ?></h2>
        <p>Total Activity Logs</p>
    </div>

    <div class="stat">
        <h2><?php echo $today_logs; ?></h2>
        <p>Activities Today</p>
    </div>

    <div class="stat">
        <h2><?php echo $total_users; ?></h2>
        <p>Users with Activity</p>
    </div>
</section>


<div class="activity-quick-filters">

    <span class="filter-label">Quick Filters:</span>

    <a href="admin_activity_logs.php"
       class="activity-filter-chip <?php
           echo $action === "" ? "active" : "";
       ?>">
        All
    </a>

    <?php foreach ($action_options as $option): ?>

        <?php
        $query = http_build_query([
            "keyword" => $keyword,
            "action" => $option,
            "date" => $date
        ]);
        ?>

        <a
            href="admin_activity_logs.php?<?php echo e($query); ?>"
            class="activity-filter-chip <?php
                echo $action === $option ? "active" : "";
            ?>"
        >
            <?php echo e(ucwords(strtolower(
                str_replace("_", " ", $option)
            ))); ?>
        </a>

    <?php endforeach; ?>

</div>


<form method="GET" class="activity-filter-bar">

    <input
        type="search"
        name="keyword"
        maxlength="150"
        placeholder="Search user, User ID, email, or activity..."
        value="<?php echo e($keyword); ?>"
    >

    <select name="action">
        <option value="">All Activities</option>

        <?php foreach ($action_options as $option): ?>
            <option
                value="<?php echo e($option); ?>"
                <?php echo $action === $option
                    ? "selected" : ""; ?>
            >
                <?php echo e(str_replace(
                    "_", " ", $option
                )); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input
        type="date"
        name="date"
        value="<?php echo e($date); ?>"
        aria-label="Filter by date"
    >

    <button type="submit" class="btn">
        Filter
    </button>

    <a href="admin_activity_logs.php"
       class="activity-reset">
        Reset
    </a>

</form>


<section class="activity-export-card">

    <div class="activity-export-heading">
        <h3>Download Activity Records</h3>
        <p>
            Export activity history by day, week, or month.
        </p>
    </div>

    <form method="GET"
          action="export_activity_logs.php"
          class="activity-export-form">

        <div>
            <label for="exportPeriod">Report Period</label>
            <select name="period"
                    id="exportPeriod"
                    required>

                <option value="day">
                    Daily
                </option>

                <option value="week">
                    Weekly
                </option>

                <option value="month">
                    Monthly
                </option>

            </select>
        </div>

        <div>
            <label for="exportDate">Select Date</label>
            <input type="date"
                   name="date"
                   id="exportDate"
                   value="<?php echo date("Y-m-d"); ?>"
                   required>
        </div>

        <button type="submit" class="btn">
            &#11015; Download CSV
        </button>

    </form>

</section>


<div class="activity-table-heading">
    <h2>Recent Activities</h2>
    <p>
        Showing up to 100 latest matching records.
    </p>
</div>

<div class="table-wrap">
    <table class="table activity-table">

        <thead>
            <tr>
                <th>Log ID</th>
                <th>User ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Activity</th>
                <th>Description</th>
                <th>Date & Time</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7">
                        <div class="reports-empty-state">
                            <div class="empty-icon">
                                &#128203;
                            </div>
                            <h3>No Activity Logs Found</h3>
                            <p>
                                No records match your filters.
                            </p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>

                <?php foreach ($logs as $log): ?>

                    <?php
                    $type = strtolower($log["action"]);

                    if (str_contains($type, "login")) {
                        $badge = "log-login";
                    } elseif (str_contains($type, "claim")) {
                        $badge = "log-claim";
                    } elseif (str_contains($type, "report")) {
                        $badge = "log-report";
                    } elseif (str_contains($type, "delete")) {
                        $badge = "log-delete";
                    } else {
                        $badge = "log-default";
                    }
                    ?>

                    <tr>
                        <td>
                            #<?php echo (int)$log["id"]; ?>
                        </td>

                        <td>
                            <strong>
                                <?php echo e(
                                    $log["user_code"]
                                ); ?>
                            </strong>
                        </td>

                        <td>
                            <div class="activity-user">
                                <strong>
                                    <?php echo e(
                                        $log["full_name"]
                                    ); ?>
                                </strong>
                                <small>
                                    <?php echo e(
                                        $log["email"]
                                    ); ?>
                                </small>
                            </div>
                        </td>

                        <td>
                            <?php echo e(ucfirst(
                                $log["role"]
                            )); ?>
                        </td>

                        <td>
                            <span class="activity-badge <?php
                                echo e($badge);
                            ?>">
                                <?php echo e(str_replace(
                                    "_", " ", $log["action"]
                                )); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo e(
                                $log["description"]
                            ); ?>
                        </td>

                        <td>
                            <?php
                            echo e(date(
                                "M d, Y - h:i A",
                                strtotime($log["created_at"])
                            ));
                            ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "footer.php"; ?>
