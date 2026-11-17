
<?php
// admin_claims.php

require_once "config.php";
require_admin();

$page_title = "Manage Claims";
$error = "";

if (empty($_SESSION["admin_claim_token"])) {
    $_SESSION["admin_claim_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["claim_token"] ?? "";
    $claim_id = (int)($_POST["claim_id"] ?? 0);
    $action = $_POST["action"] ?? "";
    $notes = trim($_POST["admin_notes"] ?? "");
    $admin_id = (int)$_SESSION["user_id"];

    if (!hash_equals($_SESSION["admin_claim_token"], $token)) {
        $error = "Invalid request.";
    } elseif (!in_array($action, ["approve", "reject"], true)) {
        $error = "Invalid action.";
    } elseif ($claim_id < 1) {
        $error = "Invalid claim.";
    } elseif ($notes === "") {
        $error = "Please enter verification or rejection notes.";
    } elseif (strlen($notes) > 3000) {
        $error = "Notes are too long.";
    } else {
        mysqli_begin_transaction($conn);

        try {
            // Find the item linked to this claim.
            $stmt = mysqli_prepare(
                $conn,
                "SELECT item_id FROM claims WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $claim_id);
            mysqli_stmt_execute($stmt);
            $ref = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );
            mysqli_stmt_close($stmt);

            if (!$ref) {
                throw new Exception("Claim not found.");
            }

            $item_id = (int)$ref["item_id"];

            // Lock item so it cannot be released twice.
            $stmt = mysqli_prepare(
                $conn,
                "SELECT status FROM items
                 WHERE id = ? FOR UPDATE"
            );
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);
            $item = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );
            mysqli_stmt_close($stmt);

            // Lock the claim being reviewed.
            $stmt = mysqli_prepare(
                $conn,
                "SELECT user_id, status FROM claims
                 WHERE id = ? AND item_id = ?
                 FOR UPDATE"
            );
            mysqli_stmt_bind_param(
                $stmt, "ii", $claim_id, $item_id
            );
            mysqli_stmt_execute($stmt);
            $claim = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );
            mysqli_stmt_close($stmt);

            if (!$claim || $claim["status"] !== "Pending") {
                throw new Exception("Claim is already reviewed.");
            }

            if (!$item || $item["status"] !== "Available") {
                throw new Exception("Item is no longer available.");
            }

            $new_status = $action === "approve"
                ? "Approved" : "Rejected";

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE claims
                 SET status = ?, admin_id = ?,
                     admin_notes = ?, reviewed_at = NOW()
                 WHERE id = ? AND status = 'Pending'"
            );

            mysqli_stmt_bind_param(
                $stmt, "sisi",
                $new_status, $admin_id, $notes, $claim_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($action === "approve") {

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT full_name, user_code
                     FROM users WHERE id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmt, "i", $claim["user_id"]
                );
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );
                mysqli_stmt_close($stmt);

                if (!$user) {
                    throw new Exception("User not found.");
                }

                // Update item only after physical release.
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE items
                     SET status = 'Returned',
                         claimant_name = ?,
                         claimant_id = ?,
                         claim_proof = ?,
                         date_released = NOW()
                     WHERE id = ? AND status = 'Available'"
                );

                mysqli_stmt_bind_param(
                    $stmt, "sssi",
                    $user["full_name"],
                    $user["user_code"],
                    $notes,
                    $item_id
                );

                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    throw new Exception("Unable to release item.");
                }

                mysqli_stmt_close($stmt);

                // Close competing pending requests.
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE claims
                     SET status = 'Rejected',
                         admin_id = ?,
                         admin_notes =
                           'Item released to another verified claimant.',
                         reviewed_at = NOW()
                     WHERE item_id = ?
                       AND id <> ?
                       AND status = 'Pending'"
                );

                mysqli_stmt_bind_param(
                    $stmt, "iii",
                    $admin_id, $item_id, $claim_id
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            if (!log_activity(
                $admin_id,
                "CLAIM_REVIEW",
                "Reviewed claim CL-" .
                str_pad($claim_id, 5, "0", STR_PAD_LEFT) .
                " with decision: " . $new_status
            )) {
                throw new Exception("Unable to record review activity.");
            }

            mysqli_commit($conn);

            $_SESSION["admin_claim_token"] =
                bin2hex(random_bytes(32));

            header("Location: admin_claims.php?updated=1");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Display all claims.
$sql = "SELECT c.id, c.reason, c.status,
               c.created_at,
               u.user_code, u.full_name,
               i.item_name, i.status AS item_status
        FROM claims c
        INNER JOIN users u ON c.user_id = u.id
        INNER JOIN items i ON c.item_id = i.id
        ORDER BY
            CASE WHEN c.status = 'Pending' THEN 0 ELSE 1 END,
            c.id DESC";

$result = mysqli_query($conn, $sql);

include "header.php";
?>

<div class="page-head">
    <h1>Claim Management</h1>
</div>

<?php if ($error): ?>
    <div class="alert"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (isset($_GET["updated"])): ?>
    <div class="success">
        Claim successfully updated.
    </div>
<?php endif; ?>

<div class="table-wrap">
<table class="table">

    <tr>
        <th>Claim ID</th>
        <th>User ID</th>
        <th>Claimant</th>
        <th>Item</th>
        <th>Ownership Explanation</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <tr>
            <td colspan="7" class="center">
                No claim requests yet.
            </td>
        </tr>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>CL-<?php echo str_pad(
                $row["id"], 5, "0", STR_PAD_LEFT
            ); ?></td>

            <td><?php echo e($row["user_code"]); ?></td>
            <td><?php echo e($row["full_name"]); ?></td>
            <td><?php echo e($row["item_name"]); ?></td>

            <td><?php echo nl2br(e($row["reason"])); ?></td>

            <td><?php echo e($row["status"]); ?></td>

            <td>
            <?php if (
                $row["status"] === "Pending" &&
                $row["item_status"] === "Available"
            ): ?>

                <form method="POST"
                      onsubmit="return confirm('Confirm this decision?');">

                    <input type="hidden"
                           name="claim_token"
                           value="<?php echo e(
                               $_SESSION["admin_claim_token"]
                           ); ?>">

                    <input type="hidden"
                           name="claim_id"
                           value="<?php echo (int)$row["id"]; ?>">

                    <label>Admin Notes</label>
                    <textarea name="admin_notes"
                              rows="3"
                              maxlength="3000"
                              required></textarea>

                    <button type="submit"
                            name="action"
                            value="approve"
                            class="btn">
                        Verified & Released
                    </button>

                    <button type="submit"
                            name="action"
                            value="reject"
                            class="btn">
                        Reject
                    </button>

                </form>

            <?php else: ?>
                Reviewed / Unavailable
            <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>

</table>
</div>

<?php include "footer.php"; ?>
