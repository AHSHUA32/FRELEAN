
<?php
// profile.php
require_once "config.php";
require_login();

$page_title = "My Profile";
$user_id = (int) $_SESSION["user_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT user_code, full_name, email,
            username, role, created_at
     FROM users WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: user_dashboard.php");
    exit;
}

include "header.php";
?>

<div class="form-card">
    <h2>My Profile</h2>
    <p class="muted">Your LostLink account information.</p>

    <table class="info">
        <tr>
            <td>User ID</td>
            <td><?php echo e($user["user_code"]); ?></td>
        </tr>

        <tr>
            <td>Full Name</td>
            <td><?php echo e($user["full_name"]); ?></td>
        </tr>

        <tr>
            <td>Email</td>
            <td><?php echo e($user["email"]); ?></td>
        </tr>

        <tr>
            <td>Username</td>
            <td><?php echo e($user["username"]); ?></td>
        </tr>

        <tr>
            <td>Account Type</td>
            <td><?php echo e(ucfirst($user["role"])); ?></td>
        </tr>

        <tr>
            <td>Date Registered</td>
            <td><?php echo e($user["created_at"]); ?></td>
        </tr>
    </table>

    <a href="<?php echo is_admin() ? 'admin.php' : 'user_dashboard.php'; ?>"
       class="btn">
        Back to Dashboard
    </a>
</div>

<?php include "footer.php"; ?>
