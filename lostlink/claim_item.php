
<?php
// claim_item.php

require_once "config.php";
require_login();

if (is_admin()) {
    header("Location: admin.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$item_id = (int)($_GET["id"] ?? $_POST["item_id"] ?? 0);

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, item_name, category, status
     FROM items WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$item) {
    header("Location: index.php");
    exit;
}

$page_title = "Claim Item";
$error = "";

if (empty($_SESSION["claim_token"])) {
    $_SESSION["claim_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["claim_token"] ?? "";
    $reason = trim($_POST["reason"] ?? "");

    if (!hash_equals($_SESSION["claim_token"], $token)) {
        $error = "Invalid request.";
    } elseif (strlen($reason) < 10) {
        $error = "Please provide more ownership details.";
    } else {

        mysqli_begin_transaction($conn);

        try {
            $lock = mysqli_prepare(
                $conn,
                "SELECT status FROM items
                 WHERE id = ? FOR UPDATE"
            );
            mysqli_stmt_bind_param($lock, "i", $item_id);
            mysqli_stmt_execute($lock);
            $current = mysqli_fetch_assoc(
                mysqli_stmt_get_result($lock)
            );
            mysqli_stmt_close($lock);

            if (!$current || $current["status"] !== "Available") {
                throw new Exception("Item is no longer available.");
            }

            $check = mysqli_prepare(
                $conn,
                "SELECT id FROM claims
                 WHERE item_id = ? AND user_id = ?
                 AND status = 'Pending' LIMIT 1"
            );
            mysqli_stmt_bind_param(
                $check, "ii", $item_id, $user_id
            );
            mysqli_stmt_execute($check);
            $existing = mysqli_fetch_assoc(
                mysqli_stmt_get_result($check)
            );
            mysqli_stmt_close($check);

            if ($existing) {
                throw new Exception(
                    "You already have a pending claim for this item."
                );
            }

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO claims (item_id, user_id, reason)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt, "iis",
                $item_id, $user_id, $reason
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Unable to save claim.");
            }
            $claim_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            if (!log_activity(
                $user_id,
                "CLAIM_SUBMITTED",
                "Submitted claim CL-" .
                str_pad($claim_id, 5, "0", STR_PAD_LEFT)
            )) {
                throw new Exception("Unable to record claim activity.");
            }
            
            mysqli_commit($conn);

            $_SESSION["claim_token"] = bin2hex(random_bytes(32));

            header("Location: my_claims.php?created=1");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

include "header.php";
?>

<div class="form-card small">
    <h2>Claim Found Item</h2>

    <p><b>Item:</b> <?php echo e($item["item_name"]); ?></p>
    <p><b>Category:</b> <?php echo e($item["category"]); ?></p>

    <?php if ($error): ?>
        <div class="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($item["status"] === "Available"): ?>
        <form method="POST">

            <input type="hidden" name="item_id"
                   value="<?php echo $item_id; ?>">

            <input type="hidden" name="claim_token"
                   value="<?php echo e($_SESSION["claim_token"]); ?>">

            <label>Proof of Ownership / Explanation</label>

            <textarea
                name="reason"
                rows="5"
                maxlength="3000"
                placeholder="Explain why this item belongs to you."
                required
            ><?php echo e($_POST["reason"] ?? ""); ?></textarea>

            <p class="muted">
                The administrator will verify your claim
                before releasing the item.
                Do not submit passwords, PINs, or other secrets.
            </p>

            <button type="submit" class="btn full">
                Submit Claim
            </button>
        </form>
    <?php else: ?>
        <div class="alert">
            This item is no longer available for claiming.
        </div>
    <?php endif; ?>

    <a href="index.php" class="btn-link">
        Back to Found Items
    </a>
</div>

<?php include "footer.php"; ?>
