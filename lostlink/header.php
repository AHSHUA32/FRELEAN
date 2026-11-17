
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>
<?php echo isset($page_title)
    ? e($page_title) . " | " : ""; ?>
LostLink
</title>

<link rel="stylesheet" href="style.css">
</head>

<body>

<header class="topbar">
<div class="wrap nav">

<a href="<?php echo is_logged_in()
    ? (is_admin() ? 'admin.php' : 'user_dashboard.php')
    : 'login.php'; ?>" class="logo">

    <span class="logo-mark">&#128279;</span>
    Lost<b>Link</b>
</a>

<?php if (is_logged_in()): ?>

<nav>
    <a href="<?php echo is_admin()
        ? 'admin.php' : 'user_dashboard.php'; ?>">
        Dashboard
    </a>

    <a href="index.php">Find Items</a>
    <a href="about.php">About</a>

    <?php if (!is_admin()): ?>
        <a href="report_lost.php">Report Lost Item</a>
        <a href="my_reports.php">My Reports</a>
        <a href="my_claims.php">My Claims</a>
    <?php else: ?>
        <a href="add_item.php">Record Item</a>
        <a href="admin_lost_reports.php">Lost Reports</a>
        <a href="admin_claims.php">Manage Claims</a>
        <a href="admin_users.php">Manage Users</a>
    <?php endif; ?>

    <a href="profile.php">My Profile</a>
    <a href="logout.php" class="btn-small">Logout</a>
</nav>

<?php endif; ?>

</div>
</header>

<main class="wrap">
