<?php
include "config.php";
require_admin();
$page_title = "Verify / Edit Item";

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}
$id = (int)$_GET['id'];
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM items WHERE id=$id"));
if (!$row) {
    header("Location: admin.php");
    exit;
}

if (isset($_POST['update'])) {
  $check = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM claims WHERE item_id = ?"
);

mysqli_stmt_bind_param($check, "i", $id);
mysqli_stmt_execute($check);

$claim_count = mysqli_fetch_assoc(
    mysqli_stmt_get_result($check)
)["total"];

mysqli_stmt_close($check);

if ($claim_count > 0) {
    http_response_code(409);
    exit(
        "This item has claim history. " .
        "Use Claim Management for verification. " .
        "Editing this item is temporarily restricted."
    );
}


    $item_name = clean($conn, $_POST['item_name']);
    $category = clean($conn, $_POST['category']);
    $description = clean($conn, $_POST['description']);
    $location_found = clean($conn, $_POST['location_found']);
    $date_found = clean($conn, $_POST['date_found']);
    $finder_name = clean($conn, $_POST['finder_name']);
    $finder_contact = clean($conn, $_POST['finder_contact']);
    $status = clean($conn, $_POST['status']);
    $claimant_name = clean($conn, $_POST['claimant_name']);
    $claimant_id = clean($conn, $_POST['claimant_id']);
    $claim_proof = clean($conn, $_POST['claim_proof']);

    $photo = upload_photo();
    if ($photo == "") {
        $photo = $row['photo'];
    }

    // set release date when item becomes Returned
    $date_released = "date_released";
    if ($status == "Returned" && $row['date_released'] == null) {
        $date_released = "NOW()";
    }
    if ($status != "Returned") {
        $date_released = "NULL";
    }

    $sql = "UPDATE items SET item_name='$item_name', category='$category', description='$description',
            location_found='$location_found', date_found='$date_found', photo='$photo',
            finder_name='$finder_name', finder_contact='$finder_contact', status='$status',
            claimant_name='$claimant_name', claimant_id='$claimant_id', claim_proof='$claim_proof',
            date_released=$date_released WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        header("Location: admin.php?msg=Item updated successfully.");
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
include "header.php";
?>

<div class="form-card">
  <h2>Verify / Edit &mdash; LL-<?php echo str_pad($row['id'], 5, "0", STR_PAD_LEFT); ?></h2>
  <?php if (isset($error)) { echo "<div class='alert'>" . e($error) . "</div>"; } ?>
  <form method="post" enctype="multipart/form-data">

    <h3 class="sub">Item details</h3>
    <label>Item name</label>
    <input type="text" name="item_name" value="<?php echo e($row['item_name']); ?>" required>

    <div class="row">
      <div>
        <label>Category</label>
        <select name="category">
          <?php foreach ($categories as $c) { ?>
            <option <?php if ($row['category'] == $c) echo "selected"; ?>><?php echo e($c); ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label>Date found</label>
        <input type="date" name="date_found" value="<?php echo e($row['date_found']); ?>" required>
      </div>
    </div>

    <label>Description</label>
    <textarea name="description" rows="3"><?php echo e($row['description']); ?></textarea>

    <label>Location found</label>
    <input type="text" name="location_found" value="<?php echo e($row['location_found']); ?>">

    <div class="row">
      <div>
        <label>Finder's name</label>
        <input type="text" name="finder_name" value="<?php echo e($row['finder_name']); ?>">
      </div>
      <div>
        <label>Finder's contact</label>
        <input type="text" name="finder_contact" value="<?php echo e($row['finder_contact']); ?>">
      </div>
    </div>

    <label>Replace photo (optional)</label>
    <input type="file" name="photo" accept="image/*">

    <h3 class="sub">Claim verification</h3>
    <div class="row">
      <div>
        <label>Claimant's name</label>
        <input type="text" name="claimant_name" value="<?php echo e($row['claimant_name']); ?>">
      </div>
      <div>
        <label>Claimant's ID number</label>
        <input type="text" name="claimant_id" value="<?php echo e($row['claimant_id']); ?>">
      </div>
    </div>

    <label>Proof of ownership / verification notes</label>
    <textarea name="claim_proof" rows="3"><?php echo e($row['claim_proof']); ?></textarea>

    <label>Status</label>
    <select name="status">
      <?php foreach ($statuses as $s) { ?>
        <option <?php if ($row['status'] == $s) echo "selected"; ?>><?php echo $s; ?></option>
      <?php } ?>
    </select>
    <p class="muted">Available = waiting for owner &bull; Claimed = claim under verification &bull; Returned = released to the verified owner.</p>
    <?php if ($row['date_released'] != null) { ?>
      <p class="muted">Released on: <?php echo date("F d, Y h:i A", strtotime($row['date_released'])); ?></p>
    <?php } ?>

    <button type="submit" name="update" class="btn full">Save Changes</button>
    <a href="admin.php" class="btn-link">Cancel</a>
  </form>
</div>

<?php include "footer.php"; ?>
