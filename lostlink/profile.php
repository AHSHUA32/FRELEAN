
<?php
// profile.php
require_once "config.php";
require_login();

$page_title = "My Profile";
$user_id = (int)$_SESSION["user_id"];
$error = "";
$success = "";

function get_profile_user($conn, $id) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, user_code, full_name, email, username,
                password, role, created_at, profile_photo
         FROM users WHERE id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $user;
}

$user = get_profile_user($conn, $user_id);

if (!$user) {
    header("Location: logout.php");
    exit;
}

if (empty($_SESSION["profile_csrf"])) {
    $_SESSION["profile_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";
    $action = $_POST["action"] ?? "";

    if (!hash_equals($_SESSION["profile_csrf"], $token)) {
        $error = "Invalid request. Refresh and try again.";
    }

    // SAVE PROFILE INFORMATION
    elseif ($action === "save_profile") {

        $name = trim($_POST["full_name"] ?? "");
        $email = strtolower(trim($_POST["email"] ?? ""));

        if ($name === "" || mb_strlen($name) > 100) {
            $error = "Enter a valid full name.";
        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 150
        ) {
            $error = "Enter a valid email address.";
        } else {
            try {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET full_name = ?, email = ?, username = ?
                     WHERE id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmt, "sssi",
                    $name, $email, $email, $user_id
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $_SESSION["full_name"] = $name;
                $_SESSION["username"] = $email;

                $success = "Profile details updated.";

                log_activity(
                    $user_id,
                    "PROFILE_UPDATED",
                    "Updated account profile."
                );

            } catch (mysqli_sql_exception $e) {
                $error = ($e->getCode() == 1062)
                    ? "Email address is already used."
                    : "Unable to update profile.";
            }
        }
    }

    // CHANGE PASSWORD
    elseif ($action === "change_password") {

        $current = $_POST["current_password"] ?? "";
        $new = $_POST["new_password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";

        if (!password_verify($current, $user["password"])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new) < 8) {
            $error = "New password needs at least 8 characters.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (password_verify($new, $user["password"])) {
            $error = "Choose a different password.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users SET password = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param(
                $stmt, "si", $hash, $user_id
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            session_regenerate_id(true);

            log_activity(
                $user_id,
                "PASSWORD_CHANGED",
                "User changed account password."
            );

            $success = "Password changed successfully.";
        }
    }

    // UPLOAD PROFILE PHOTO
    elseif ($action === "upload_photo") {

        $file = $_FILES["profile_photo"] ?? null;

        if (!$file || $file["error"] !== UPLOAD_ERR_OK) {
            $error = "Please choose a valid image.";
        } elseif ($file["size"] > 3 * 1024 * 1024) {
            $error = "Photo must be 3MB or smaller.";
        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file["tmp_name"]);

            $allowed = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            if (!isset($allowed[$mime])) {
                $error = "Only JPG, PNG, or WEBP images are allowed.";
            } elseif (@getimagesize($file["tmp_name"]) === false) {
                $error = "Invalid image file.";
            } else {

                $upload_dir = __DIR__ . "/uploads/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = "avatar_" .
                    bin2hex(random_bytes(16)) .
                    "." . $allowed[$mime];

                $destination = $upload_dir . $filename;

                if (move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )) {

                    try {
                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE users
                             SET profile_photo = ?
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $stmt, "si", $filename, $user_id
                        );

                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $success = "Profile picture updated.";

                        log_activity(
                            $user_id,
                            "PHOTO_UPDATED",
                            "Updated profile picture."
                        );

                    } catch (Throwable $e) {
                        @unlink($destination);
                        $error = "Unable to save profile photo.";
                    }

                } else {
                    $error = "Photo upload failed.";
                }
            }
        }
    }
    else {
        $error = "Invalid action.";
    }

    $user = get_profile_user($conn, $user_id);
}

$photo = !empty($user["profile_photo"])
    ? "uploads/" . rawurlencode($user["profile_photo"])
    : "";

include "header.php";
?>

<div class="form-card profile-wide">

    <div class="profile-heading">
        <h2>My Profile</h2>
        <p class="muted">
            Manage your account, profile photo, and password.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <section class="profile-top">

        <div class="avatar-control">

            <div class="profile-avatar"
                 tabindex="0"
                 aria-label="Profile picture options">

                <?php if ($photo): ?>
                    <img src="<?php echo e($photo); ?>"
                         alt="Your profile picture">
                <?php else: ?>
                    <span><?php
                        echo e(strtoupper(
                            mb_substr($user["full_name"], 0, 1)
                        ));
                    ?></span>
                <?php endif; ?>

                <div class="avatar-overlay">
                    <?php if ($photo): ?>
                        <button type="button"
                                onclick="viewProfilePhoto()">
                            View Photo
                        </button>
                    <?php endif; ?>

                    <button type="button"
                            onclick="document.getElementById('avatarFile').click()">
                        Change Photo
                    </button>
                </div>
            </div>

        </div>

        <div class="profile-basic">
            <h3><?php echo e($user["full_name"]); ?></h3>
            <p><?php echo e($user["email"]); ?></p>
            <span class="profile-role">
                <?php echo e(ucfirst($user["role"])); ?> Account
            </span>
        </div>

    </section>

    <form method="POST"
          enctype="multipart/form-data"
          id="avatarForm">

        <input type="hidden" name="csrf_token"
               value="<?php echo e($_SESSION["profile_csrf"]); ?>">

        <input type="hidden" name="action"
               value="upload_photo">

        <input type="file"
               id="avatarFile"
               name="profile_photo"
               accept="image/jpeg,image/png,image/webp"
               hidden
               onchange="if(this.files.length) document.getElementById('avatarForm').submit();">
    </form>

    <div class="profile-section">
        <h3>Personal Information</h3>
        <p class="muted">Edit your account information below.</p>

        <form method="POST" class="profile-form">

            <input type="hidden" name="csrf_token"
                   value="<?php echo e($_SESSION["profile_csrf"]); ?>">

            <input type="hidden" name="action"
                   value="save_profile">

            <div class="row">
                <div>
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name"
                           name="full_name"
                           maxlength="100"
                           value="<?php echo e($user["full_name"]); ?>"
                           required>
                </div>

                <div>
                    <label for="email">Email Address</label>
                    <input type="email" id="email"
                           name="email"
                           maxlength="150"
                           value="<?php echo e($user["email"]); ?>"
                           required>
                </div>
            </div>

            <button type="submit" class="btn">
                Save Profile
            </button>
        </form>
    </div>

    <div class="profile-section">
        <h3>Change Password</h3>
        <p class="muted">
            Enter your current password to set a new one.
        </p>

        <form method="POST" class="profile-form">

            <input type="hidden" name="csrf_token"
                   value="<?php echo e($_SESSION["profile_csrf"]); ?>">

            <input type="hidden" name="action"
                   value="change_password">

            <label for="current_password">Current Password</label>
            <input type="password"
                   id="current_password"
                   name="current_password"
                   autocomplete="current-password"
                   required>

            <div class="row">
                <div>
                    <label for="new_password">New Password</label>
                    <input type="password"
                           id="new_password"
                           name="new_password"
                           minlength="8"
                           autocomplete="new-password"
                           required>
                </div>

                <div>
                    <label for="confirm_password">
                        Confirm New Password
                    </label>
                    <input type="password"
                           id="confirm_password"
                           name="confirm_password"
                           minlength="8"
                           autocomplete="new-password"
                           required>
                </div>
            </div>

            <button type="submit" class="btn">
                Update Password
            </button>

        </form>
    </div>

</div>

<?php if ($photo): ?>
<dialog id="photoDialog" class="photo-dialog">
    <button type="button"
            class="dialog-close"
            onclick="document.getElementById('photoDialog').close()">
        Close
    </button>

    <img src="<?php echo e($photo); ?>"
         alt="Profile picture full view">
</dialog>
<?php endif; ?>

<script>
function viewProfilePhoto() {
    const dialog = document.getElementById("photoDialog");
    if (dialog) dialog.showModal();
}
</script>

<?php include "footer.php"; ?>
