<?php
include "config.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM items WHERE id=$id");
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: index.php");
    exit;
}

$page_title = $row['item_name'];
$here = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/item.php?id=" . $row['id'];
include "header.php";
?>

<a href="index.php" class="back">&larr; Back to found items</a>

<div class="detail">
  <div class="detail-img">
    <?php if ($row['photo'] != "") { ?>
      <img src="uploads/<?php echo e($row['photo']); ?>" alt="">
    <?php } else { ?>
      <span>&#128230;</span>
    <?php } ?>
  </div>
  <div class="detail-info">
    <small><?php echo e($row['category']); ?></small>
    <h1><?php echo e($row['item_name']); ?> <?php echo badge($row['status']); ?></h1>
    <table class="info">
      <tr><td>Description</td><td><?php echo nl2br(e($row['description'])); ?></td></tr>
      <tr><td>Location found</td><td><?php echo e($row['location_found']); ?></td></tr>
      <tr><td>Date found</td><td><?php echo date("F d, Y", strtotime($row['date_found'])); ?></td></tr>
      <tr><td>Turned over by</td><td><?php echo e($row['finder_name']); ?> &#127775;</td></tr>
      <tr><td>Item code</td><td>LL-<?php echo str_pad($row['id'], 5, "0", STR_PAD_LEFT); ?></td></tr>
    </table>

    <?php if ($row['status'] == "Available") { ?>
      <div class="notice">
        <b>Is this yours?</b><br>
        Go to the CSU / Security Office and bring your ID. Be ready to describe the item or show proof of ownership. CSU will verify before releasing it.
      </div>
    <?php } else { ?>
      <div class="notice gray">This item is already <b><?php echo e(strtolower($row['status'])); ?></b>.</div>
    <?php } ?>

    <div class="qr-box">
      <div id="qrcode"></div>
      <p>Scan to open this item page.<br>CSU may also print this code on the item's storage tag.</p>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById("qrcode"), { text: "<?php echo $here; ?>", width: 120, height: 120 });
</script>

<?php include "footer.php"; ?>
