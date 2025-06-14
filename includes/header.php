<nav>
  <ul>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <li><a href="dashboard.php">Dashboard</a></li>
    <?php endif; ?>
    <li><a href="invoice.php">Invoice</a></li>
    <li><a href="inventory.php">Inventory</a></li>
    <li><a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
  </ul>
</nav>
<hr>
