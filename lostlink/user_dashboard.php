
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

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, user_code, full_name, email,
            username, created_at
     FROM users WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: logout.php");
    exit;
}

$available = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM items WHERE status = 'Available'"
    )
);

$returned = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM items WHERE status = 'Returned'"
    )
);

include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Welcome, <?php echo e($user["full_name"]); ?>!</h1>
        <p class="muted">
            Welcome to your LostLink Dashboard.
        </p>
        <p>
            Your User ID:
            <strong><?php echo e($user["user_code"]); ?></strong>
        </p>
    </div>
</div>

<section class="stats">
    <div class="stat">
        <h2><?php echo (int)$available["total"]; ?></h2>
        <p>Available Found Items</p>
    </div>

    <div class="stat">
        <h2><?php echo (int)$returned["total"]; ?></h2>
        <p>Items Returned</p>
    </div>
</section>

<h2 class="section-title">Quick Actions</h2>


<div class="card">
    <div class="card-body">
        <h3>Report Lost Item</h3>
        <p>Submit a report for your missing belongings.</p>

        <a href="report_lost.php" class="btn">
            Report Item
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3>My Lost Reports</h3>
        <p>View and track your submitted reports.</p>

        <a href="my_reports.php" class="btn">
            View Reports
        </a>
    </div>
</div>


<div class="grid">

    
<div class="card">
    <div class="card-body">
        <h3>My Claim Requests</h3>
        <p>
            Track your submitted claim requests
            and their current status.
        </p>

        <a href="my_claims.php" class="btn">
            View My Claims
        </a>
    </div>
</div>


    <div class="card">
        <div class="card-body">
            <h3>Find Lost Items</h3>
            <p>Search for items that have been found.</p>
            <a href="index.php" class="btn">
                Browse Found Items
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>My Profile</h3>
            <p>View your account information.</p>
            <a href="profile.php" class="btn">
                View Profile
            </a>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
