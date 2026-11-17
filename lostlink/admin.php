<?php
include "config.php";
require_admin();
$page_title = "Dashboard";

$status = isset($_GET['status']) ? clean($conn, $_GET['status']) : "";
$keyword = isset($_GET['keyword']) ? clean($conn, $_GET['keyword']) : "";

$sql = "SELECT * FROM items WHERE 1=1";
if ($status != "") {
    $sql .= " AND status='$status'";
}
if ($keyword != "") {
    $sql .= " AND (item_name LIKE '%$keyword%' OR finder_name LIKE '%$keyword%' OR claimant_name LIKE '%$keyword%')";
}
$sql .= " ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

$a = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM items WHERE status='Available'"));
$b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM items WHERE status='Claimed'"));
$c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM items WHERE status='Returned'"));

include "header.php";
?>

<div class="page-head">
  <h1>CSU Dashboard</h1>
  <a href="add_item.php" class="btn">+ Record Found Item</a>
</div>

<?php if (isset($_GET['msg'])) { echo "<div class='success'>" . e($_GET['msg']) . "</div>"; } ?>

<section class="stats">
  <div class="stat"><h2><?php echo $a['c']; ?></h2><p>Available (unclaimed)</p></div>
  <div class="stat"><h2><?php echo $b['c']; ?></h2><p>Claimed (pending release)</p></div>
  <div class="stat"><h2><?php echo $c['c']; ?></h2><p>Returned to owners</p></div>
</section>

<form method="get" class="search inline">
  <input type="text" name="keyword" placeholder="Search item, finder, or claimant..." value="<?php echo e($keyword); ?>">
  <select name="status">
    <option value="">All status</option>
    <?php foreach ($statuses as $s) { ?>
      <option value="<?php echo $s; ?>" <?php if ($status == $s) echo "selected"; ?>><?php echo $s; ?></option>
    <?php } ?>
  </select>
  <button type="submit" class="btn">Filter</button>
</form>

<div class="table-wrap">
<table class="table">
  <tr>
    <th>Code</th><th>Item</th><th>Category</th><th>Found</th><th>Finder (credited)</th><th>Status</th><th>Claimant</th><th>Actions</th>
  </tr>
  <?php if (mysqli_num_rows($result) == 0) { ?>
    <tr><td colspan="8" class="center">No records found.</td></tr>
  <?php } ?>
  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td>LL-<?php echo str_pad($row['id'], 5, "0", STR_PAD_LEFT); ?></td>
    <td><a href="item.php?id=<?php echo $row['id']; ?>"><?php echo e($row['item_name']); ?></a></td>
    <td><?php echo e($row['category']); ?></td>
    <td><?php echo date("M d, Y", strtotime($row['date_found'])); ?></td>
    <td><?php echo e($row['finder_name']); ?></td>
    <td><?php echo badge($row['status']); ?></td>
    <td><?php echo e($row['claimant_name']); ?></td>
    <td>
      <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="link-btn">Verify / Edit</a>
      <a href="delete_item.php?id=<?php echo $row['id']; ?>" class="link-btn red" onclick="return confirm('Delete this record?');">Delete</a>
    </td>
  </tr>
  <?php } ?>
</table>
</div>

<?php include "footer.php"; ?>
