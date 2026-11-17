<?php
require_once "config.php";
require_login();

$page_title = "Find Lost Items";

$keyword = isset($_GET['keyword']) ? clean($conn, $_GET['keyword']) : "";
$category = isset($_GET['category']) ? clean($conn, $_GET['category']) : "";

$sql = "SELECT * FROM items WHERE status='Available'";
if ($keyword != "") {
    $sql .= " AND (item_name LIKE '%$keyword%' OR description LIKE '%$keyword%' OR location_found LIKE '%$keyword%')";
}
if ($category != "") {
    $sql .= " AND category='$category'";
}
$sql .= " ORDER BY date_found DESC, id DESC";
$result = mysqli_query($conn, $sql);

$r1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM items WHERE status='Available'"));
$r2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM items WHERE status='Returned'"));
$r3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM items"));

include "header.php";
?>

<section class="hero">
  <h1>Lost something on campus?<br>Check here first.</h1>
  <p>LostLink is one place to see items turned over to the CSU / Security Office, so you don't have to keep asking around or posting on social media.</p>
  <form method="get" action="index.php" class="search">
    <input type="text" name="keyword" placeholder="Search item, color, description, or location..." value="<?php echo e($keyword); ?>">
    <select name="category">
      <option value="">All categories</option>
      <?php foreach ($categories as $c) { ?>
        <option value="<?php echo e($c); ?>" <?php if ($category == $c) echo "selected"; ?>><?php echo e($c); ?></option>
      <?php } ?>
    </select>
    <button type="submit" class="btn">Search</button>
  </form>
</section>

<section class="stats">
  <div class="stat"><h2><?php echo $r1['c']; ?></h2><p>Items waiting for owners</p></div>
  <div class="stat"><h2><?php echo $r2['c']; ?></h2><p>Items returned</p></div>
  <div class="stat"><h2><?php echo $r3['c']; ?></h2><p>Total items recorded</p></div>
</section>

<h2 class="section-title">Found Items (<?php echo mysqli_num_rows($result); ?>)</h2>

<?php if (mysqli_num_rows($result) == 0) { ?>
  <div class="empty">
    <div style="font-size:42px;margin-bottom:12px;">
        &#128230;
    </div>

    <h3 style="margin-bottom:8px;color:#111827;">
        No Found Items Yet
    </h3>

    <p>
        No items match your search.
        Please check again later or contact
        the Lost &amp; Found Office.
    </p>
</div>

<?php } else { ?>
<div class="grid">
  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
  <a class="card" href="item.php?id=<?php echo $row['id']; ?>">
    <div class="card-img">
      
<?php if (!empty($row["photo"])): ?>

<img
    src="uploads/<?php echo e($row["photo"]); ?>"
    alt="<?php echo e($row["item_name"]); ?>"
    loading="lazy"
    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
>

<span class="item-photo-fallback" style="display:none;">
    &#128230;
</span>

<?php else: ?>

<span class="item-photo-fallback">
    &#128230;
</span>

<?php endif; ?>

    </div>
    <div class="card-body">
      <small><?php echo e($row['category']); ?></small>
      <h3><?php echo e($row['item_name']); ?></h3>
      <p>&#128205; <?php echo e($row['location_found']); ?></p>
      <p>&#128197; <?php echo date("M d, Y", strtotime($row['date_found'])); ?></p>
    </div>
  </a>
  <?php } ?>
</div>
<?php } ?>



<?php include "footer.php"; ?>
