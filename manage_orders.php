<?php
session_start();

// ตรวจสอบ Login และ Role
if (!isset($_SESSION['UID']) || $_SESSION['Role'] !== 'admin') {
    header("location: login.php");
    exit();
}

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";
$conn = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($conn, "utf8");
if (!$conn) die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . mysqli_connect_error());

$message = '';
$message_type = '';

// Logic การอัปเดตสถานะคำสั่งซื้อ
if (isset($_POST['update_status'])) {
    $oid = intval($_POST['OID']);
    $new_status = mysqli_real_escape_string($conn, $_POST['Status']);
    
    // ตรวจสอบสถานะที่ถูกต้อง
    $valid_statuses = ['pending', 'paid', 'shipped', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $sql = "UPDATE orders SET status='$new_status' WHERE OID=$oid";
        if (mysqli_query($conn, $sql)) {
            $message = "✅ อัปเดตสถานะคำสั่งซื้อ #{$oid} เป็น '" . ucfirst($new_status) . "' สำเร็จ!";
            $message_type = 'success';
        } else {
            $message = '❌ อัปเดตสถานะไม่สำเร็จ: ' . mysqli_error($conn);
            $message_type = 'danger';
        }
    } else {
        $message = '⚠️ สถานะไม่ถูกต้อง!';
        $message_type = 'warning';
    }
    header("refresh:2; url=manage_orders.php");
}

// ดึงข้อมูลคำสั่งซื้อทั้งหมด พร้อมชื่อผู้ใช้
$result = mysqli_query($conn, "
    SELECT o.*, u.Fullname, u.Username
    FROM orders o
    JOIN users u ON o.UID = u.UID
    ORDER BY o.order_date DESC
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>จัดการคำสั่งซื้อ - Admin Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
        --sidebar-width: 250px;
        --primary-color: #007bff; /* สีน้ำเงินหลัก */
        --light-bg: #e9ecef; /* พื้นหลังสีเทาอ่อน */
        --card-hover-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        --table-header-bg: #007bff;
        --table-header-color: white;
    }

    body {
        font-family: 'Prompt', sans-serif;
        background-color: var(--light-bg);
        min-height: 100vh;
    }

    .sidebar {
        width: var(--sidebar-width);
        background-color: #343a40;
        color: white;
        position: fixed;
        height: 100%;
        padding-top: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }
    .sidebar-header {
        padding: 10px 20px;
        margin-bottom: 20px;
        font-weight: 700;
        font-size: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.7);
        padding: 12px 20px;
        transition: background-color 0.3s, color 0.3s;
    }
    .sidebar .nav-link.active {
        color: white;
        background-color: var(--primary-color);
        border-radius: 5px;
        margin: 0 10px;
    }
    .sidebar .nav-link:hover {
        color: white;
        background-color: #495057;
        border-radius: 5px;
        margin: 0 10px;
    }
    .logout-btn-sidebar {
        position: absolute;
        bottom: 20px;
        width: 85%;
        margin-left: 7.5%;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        padding: 30px;
    }
    
    .card {
        border-radius: 12px;
        border: none;
    }
    .card-hover:hover { 
        transform: translateY(-3px); 
        box-shadow: var(--card-hover-shadow); 
        transition: 0.3s; 
    }
    
    .table thead th {
        background-color: var(--table-header-bg);
        color: var(--table-header-color);
        border-color: #0056b3; 
        font-weight: 600;
    }
    .table-striped>tbody>tr:nth-of-type(odd)>* {
        background-color: rgba(0, 0, 0, 0.03);
    }
    .table td {
        vertical-align: middle;
    }
    
    .status-pending { background-color: #ffc107; color: #333; }
    .status-paid { background-color: #28a745; color: white; }
    .status-shipped { background-color: #007bff; color: white; }
    .status-cancelled { background-color: #dc3545; color: white; }
    
    .alert-custom {
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: 500;
        margin-bottom: 20px;
    }
    .action-cell {
        min-width: 250px; /* ให้ช่องนี้กว้างพอสำหรับปุ่ม */
    }
  </style>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-gear-fill me-2"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="admin.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_users.php"><i class="bi bi-people-fill me-2"></i> จัดการผู้ใช้</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_products.php"><i class="bi bi-box-seam-fill me-2"></i> จัดการสินค้า</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage_orders.php"><i class="bi bi-cart-check-fill me-2"></i> จัดการคำสั่งซื้อ</a>
            </li>
        </ul>
        
        <a href="logout.php" class="btn btn-danger logout-btn-sidebar">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>

    <div class="main-content">
        <h1 class="mb-4"><i class="bi bi-receipt-cutoff me-2"></i> จัดการคำสั่งซื้อ</h1>
        <p class="text-muted">ตรวจสอบและอัปเดตสถานะคำสั่งซื้อของลูกค้า</p>

        <?php if ($message) { ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-custom">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <div class="card p-3 shadow-sm card-hover">
            <h4 class="mb-3">รายการคำสั่งซื้อล่าสุด</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped mt-3 align-middle text-center">
                    <thead>
                        <tr>
                            <th style="width: 5%;">OID</th>
                            <th style="width: 15%;">ผู้สั่งซื้อ</th>
                            <th style="width: 10%;">วันที่สั่ง</th>
                            <th style="width: 10%;">ยอดรวม</th>
                            <th style="width: 15%;">สถานะปัจจุบัน</th>
                            <th style="width: 35%;">อัปเดตสถานะ</th>
                            <th style="width: 10%;">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { 
                            // กำหนด Badge และ Class ของสถานะ
                            $status_class = 'status-' . $row['status'];
                            $status_display = ucfirst($row['status']);
                        ?>
                            <tr>
                                <td><?php echo $row['OID']; ?></td>
                                <td class="text-start">
                                    <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($row['Fullname']); ?><br>
                                    <small class="text-muted">(<?php echo htmlspecialchars($row['Username']); ?>)</small>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($row['order_date'])); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2); ?> ฿</td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?> p-2"><?php echo $status_display; ?></span>
                                </td>
                                <td class="action-cell">
                                    <form method="POST" class="d-flex justify-content-center align-items-center">
                                        <input type="hidden" name="OID" value="<?php echo $row['OID']; ?>">
                                        <select name="Status" class="form-select form-select-sm me-2" style="max-width: 120px;">
                                            <option value="pending" <?php if($row['status']=='pending') echo 'selected'; ?>>Pending</option>
                                            <option value="paid" <?php if($row['status']=='paid') echo 'selected'; ?>>Paid</option>
                                            <option value="shipped" <?php if($row['status']=='shipped') echo 'selected'; ?>>Shipped</option>
                                            <option value="cancelled" <?php if($row['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                            <i class="bi bi-arrow-repeat"></i> อัปเดต
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="order_details.php?oid=<?php echo $row['OID']; ?>" class="btn btn-info btn-sm text-white">
                                        <i class="bi bi-search"></i> ดู
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>