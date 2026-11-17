<?php
include "config.php";
$page_title = "CSU Login";
$error = "";

if (isset($_POST['login'])) {
    if ($_POST['username'] == $admin_user && $_POST['password'] == $admin_pass) {
        $_SESSION['admin'] = $admin_user;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Wrong username or password.";
    }
}
include "header.php";
?>

<div class="form-card small">
  <h2>CSU / Security Login</h2>
  <p class="muted">Only authorized personnel can record and manage found items.</p>
  <?php if ($error != "") { echo "<div class='alert'>" . e($error) . "</div>"; } ?>
  <form method="post">
    <label>Username</label>
    <input type="text" name="username" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit" name="login" class="btn full">Login</button>
  </form>
</div>

<?php include "footer.php"; ?>
