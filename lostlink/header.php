<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo isset($page_title) ? e($page_title) . " | " : ""; ?>LostLink</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap nav">
    <a href="index.php" class="logo"><span class="logo-mark">&#128279;</span> Lost<b>Link</b></a>
    <nav>
      <a href="index.php">Find Items</a>
      <a href="about.php">About</a>
      <?php if (is_admin()) { ?>
        <a href="admin.php">Dashboard</a>
        <a href="add_item.php">Record Item</a>
        <a href="logout.php" class="btn-small">Logout</a>
      <?php } else { ?>
        <a href="login.php" class="btn-small">CSU Login</a>
      <?php } ?>
    </nav>
  </div>
</header>
<main class="wrap">
