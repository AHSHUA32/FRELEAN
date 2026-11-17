
<?php
// admin_users.php

require_once "config.php";
require_admin();

$page_title = "Manage Users";

$keyword = trim($_GET["keyword"] ?? "");

$sql = "SELECT
            u.id,
            u.user_code,
            u.full_name,
            u.email,
            u.username,
            u.role,
            u.created_at,
            (SELECT COUNT(*)
             FROM lost_reports r
             WHERE r.user_id = u.id) AS reports_count,
            (SELECT COUNT(*)
             FROM claims c
             WHERE c.user_id = u.id) AS claims_count
        FROM users u
        WHERE (
            u.user_code LIKE ?
            OR u.full_name LIKE ?
            OR u.username LIKE ?
            OR u.email LIKE ?
        )
        ORDER BY u.id DESC
        LIMIT 100";

$search = "%" . $keyword . "%";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $search, $search, $search, $search
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include "header.php";
?>

<div class="page-head">
    <h1>Registered Users</h1>
</div>

<form method="GET" class="search inline">

    <input
        type="text"
        name="keyword"
        maxlength="150"
        placeholder="Search User ID, name, email, username..."
        value="<?php echo e($keyword); ?>"
    >

    <button type="submit" class="btn">
        Search
    </button>

</form>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>User ID</th>
        <th>Full Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Reports</th>
        <th>Claims</th>
        <th>Action</th>
    </tr>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <tr>
            <td colspan="8" class="center">
                No users found.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($user = mysqli_fetch_assoc($result)): ?>

        <tr>
            <td><?php echo e($user["user_code"]); ?></td>

            <td><?php echo e($user["full_name"]); ?></td>

            <td><?php echo e($user["username"]); ?></td>

            <td><?php echo e($user["email"]); ?></td>

            <td><?php echo e($user["role"]); ?></td>

            <td><?php echo (int)$user["reports_count"]; ?></td>

            <td><?php echo (int)$user["claims_count"]; ?></td>

            <td>
                <a
                    href="admin_user_detail.php?id=<?php
                        echo (int)$user["id"];
                    ?>"
                    class="link-btn"
                >
                    View History
                </a>
            </td>
        </tr>

    <?php endwhile; ?>

</table>
</div>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
