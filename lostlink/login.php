
<?php
// login.php
require_once "config.php";

$page_title = "Login";
$error = "";

if (is_logged_in()) {
    header("Location: " . (
        is_admin() ? "admin.php" : "user_dashboard.php"
    ));
    exit;
}

if (empty($_SESSION["login_token"])) {
    $_SESSION["login_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $token = $_POST["login_token"] ?? "";

    if (!hash_equals($_SESSION["login_token"], $token)) {
        $error = "Invalid request.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, user_code, full_name,
                    username, password, role
             FROM users WHERE email = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $user = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if ($user && password_verify(
            $password, $user["password"]
        )) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_code"] = $user["user_code"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            unset($_SESSION["login_token"]);

            if (function_exists("log_activity")) {
                log_activity(
                    $user["id"],
                    "LOGIN",
                    "User logged in successfully."
                );
            }

            header("Location: " . (
                $user["role"] === "admin"
                ? "admin.php"
                : "user_dashboard.php"
            ));
            exit;
        }

        $error = "Incorrect email or password.";
    }
}

include "header.php";
?>

<div class="form-card small">

    <h2>Welcome to LostLink</h2>
    <p class="muted">Sign in to your account.</p>

    <?php if ($error): ?>
        <div class="alert">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="login_token"
            value="<?php echo e($_SESSION["login_token"]); ?>">

        <label>Email Address</label>
        <input type="email" name="email"
            placeholder="Enter email address"
            autocomplete="email" required>

        <label>Password</label>
        <input type="password" name="password"
            placeholder="Enter password"
            autocomplete="current-password" required>

        <button type="submit" class="btn full">
            Login
        </button>
    </form>

    <hr>

    <p class="muted">Don't have an account?</p>

    <a href="register.php" class="btn full"
       style="display:block;text-align:center;">
        Register New Account
    </a>

</div>

<?php include "footer.php"; ?>
