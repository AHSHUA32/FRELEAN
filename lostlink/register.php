
<?php
// register.php
require_once "config.php";

if (is_logged_in()) {
    header("Location: " . (
        is_admin() ? "admin.php" : "user_dashboard.php"
    ));
    exit;
}

$page_title = "Register";
$error = "";
$success = "";

if (empty($_SESSION["register_token"])) {
    $_SESSION["register_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";
    $token = $_POST["register_token"] ?? "";

    if (!hash_equals($_SESSION["register_token"], $token)) {
        $error = "Invalid request.";
    } elseif ($name === "" || strlen($name) > 100) {
        $error = "Please enter a valid full name.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)
              || strlen($email) > 150) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must have at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Email is also stored as the internal username.
        // Public registration can only create regular users.
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users
             (full_name, email, username, password, role)
             VALUES (?, ?, ?, ?, 'user')"
        );

        mysqli_stmt_bind_param(
            $stmt, "ssss",
            $name, $email, $email, $hash
        );

        try {
            mysqli_begin_transaction($conn);

            mysqli_stmt_execute($stmt);
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            $code = "USR-" . str_pad(
                $id, 5, "0", STR_PAD_LEFT
            );

            $update = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET user_code = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update, "si", $code, $id
            );

            mysqli_stmt_execute($update);
            mysqli_stmt_close($update);

            mysqli_commit($conn);

            if (function_exists("log_activity")) {
                log_activity(
                    $id,
                    "REGISTER",
                    "New user account created."
                );
            }

            $_SESSION["register_token"] =
                bin2hex(random_bytes(32));

            $success = "Account created! Your User ID is " .
                       $code . ". You can now log in.";

        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conn);

            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                try { mysqli_stmt_close($stmt); }
                catch (Throwable $ignored) {}
            }

            if ($e->getCode() == 1062) {
                $error = "Email already registered.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

include "header.php";
?>

<div class="form-card small">

    
<div class="login-icon">
    <span>&#128100;</span>
</div>

<h2>Create your account</h2>

    <?php if ($error): ?>
        <div class="alert">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <?php echo e($success); ?>
        </div>

        <a href="login.php" class="btn full"
           style="display:block;text-align:center;">
            Proceed to Login
        </a>

    <?php else: ?>

        <form method="POST">

            <input type="hidden" name="register_token"
                value="<?php echo e($_SESSION["register_token"]); ?>">

            <label>Full Name</label>
            <input type="text" name="full_name"
                   maxlength="100" required>

            <label>Email Address</label>
            <input type="email" name="email"
                   maxlength="150" autocomplete="email" required>

            <label>Password</label>
            <input type="password" name="password"
                   minlength="8" autocomplete="new-password"
                   required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password"
                   minlength="8" autocomplete="new-password"
                   required>

            <button type="submit" class="btn full">
                Create Account
            </button>

        </form>

        <p class="muted">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

    <?php endif; ?>

</div>

<?php include "footer.php"; ?>
