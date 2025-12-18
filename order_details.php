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

$oid = intval($_GET['oid']);

// ดึงข้อมูลหลักของคำสั่งซื้อและผู้ใช้
$order_query = mysqli_query($conn, "
    SELECT o.*, u.Fullname AS CustomerName, u.Username 
    FROM orders o
    JOIN users u ON o.UID = u.UID
    WHERE o.OID = $oid
");
$order = mysqli_fetch_assoc($order_query);

// ตรวจสอบว่ามีคำสั่งซื้อนี้อยู่หรือไม่
if (!$order) {
    header("location: manage_orders.php");
    exit();
}

// ดึงรายการสินค้าในคำสั่งซื้อ
$items_query = mysqli_query($conn, "
    SELECT oi.*, p.ProductName
    FROM order_items oi
    JOIN products p ON oi.PID = p.PID
    WHERE oi.OID = $oid
");

// ดึงข้อมูลการจัดส่ง
$address_query = mysqli_query($conn, "
    SELECT * FROM address 
    WHERE UID = {$order['UID']} AND is_default = TRUE 
    LIMIT 1
");
$address = mysqli_fetch_assoc($address_query);

// ดึงข้อมูลการชำระเงิน
$payment_query = mysqli_query($conn, "
    SELECT * FROM payment 
    WHERE OID = $oid 
    LIMIT 1
");
$payment = mysqli_fetch_assoc($payment_query);

// ฟังก์ชันช่วยแสดง Badge สถานะ
function get_status_badge($status, $type = 'order') {
    $status_text = ucfirst($status);
    $class = '';
    if ($type == 'order') {
        switch ($status) {
            case 'pending': $class = 'warning'; break;
            case 'paid': $class = 'success'; break;
            case 'shipped': $class = 'primary'; break;
            case 'cancelled': $class = 'danger'; break;
            default: $class = 'secondary';
        }
    } else { // payment status
        switch ($status) {
            case 'successful': $class = 'success'; break;
            case 'failed': $class = 'danger'; break;
            case 'pending': $class = 'warning'; break;
            default: $class = 'secondary';
        }
    }
    return "<span class='badge bg-$class p-2'>$status_text</span>";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>รายละเอียดคำสั่งซื้อ #<?php echo $oid; ?> - Admin Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
        --sidebar-width: 250px;
        --primary-color: #007bff; /* สีน้ำเงินหลัก */
        --light-bg: #e9ecef;
        --card-hover-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); /* ลดเงาเล็กน้อย */
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
    }
    .sidebar .nav-link.active {
        color: white;
        background-color: var(--primary-color);
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
    .card-hover { /* ไม่ต้องใช้ hover effect ในหน้านี้มากนัก */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .table thead th {
        background-color: #f8f9fa; /* สีอ่อนสำหรับรายละเอียดสินค้า */
        color: #343a40;
        font-weight: 600;
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
        <h2 class="mb-4 text-primary"><i class="bi bi-file-earmark-text-fill me-2"></i> รายละเอียดคำสั่งซื้อ #<?php echo $oid; ?></h2>
        
        <div class="card p-4 mb-4 shadow-sm card-hover">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="mb-3 text-secondary"><i class="bi bi-info-circle-fill me-2"></i> ข้อมูลหลัก</h4>
                    <p><strong>ผู้สั่งซื้อ:</strong> <?php echo htmlspecialchars($order['CustomerName']); ?> (<?php echo htmlspecialchars($order['Username']); ?>)</p>
                    <p><strong>วันที่สั่ง:</strong> <?php echo date('d/m/Y H:i:s', strtotime($order['order_date'])); ?></p>
                    <p><strong>ยอดรวมสุทธิ:</strong> <span class="fs-4 text-danger"><?php echo number_format($order['total_amount'], 2); ?> ฿</span></p>
                    <p class="mt-3">
                        <strong>สถานะ:</strong> <?php echo get_status_badge($order['status'], 'order'); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-3 text-secondary"><i class="bi bi-geo-alt-fill me-2"></i> ที่อยู่จัดส่ง</h4>
                    <?php if ($address) { ?>
                        <address class="bg-light p-3 rounded">
                            <strong><?php echo htmlspecialchars($address['Fullname']); ?></strong> (<?php echo htmlspecialchars($address['Phone']); ?>)<br>
                            <?php echo htmlspecialchars($address['AddressLine']); ?><br>
                            <?php echo htmlspecialchars($address['District']); ?>, <?php echo htmlspecialchars($address['Province']); ?>, <?php echo htmlspecialchars($address['PostalCode']); ?>
                        </address>
                    <?php } else { ?>
                        <p class="text-danger">ไม่พบข้อมูลที่อยู่จัดส่งสำหรับคำสั่งซื้อนี้</p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-4 shadow-sm card-hover">
            <h4 class="mb-3"><i class="bi bi-cart-fill me-2"></i> รายการสินค้า (<?php echo mysqli_num_rows($items_query); ?> ชิ้น)</h4>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th style="width: 15%;">ราคาต่อหน่วย</th>
                            <th style="width: 10%;">จำนวน</th>
                            <th style="width: 15%;">ราคารวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        while ($item = mysqli_fetch_assoc($items_query)) { 
                            $item_total = $item['quantity'] * $item['price'];
                            $subtotal += $item_total;
                        ?>
                            <tr>
                                <td class="text-start"><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                <td><?php echo number_format($item['price'], 2); ?> ฿</td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>**<?php echo number_format($item_total, 2); ?> ฿**</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">ยอดรวมสินค้า:</td>
                            <td class="fw-bold"><?php echo number_format($subtotal, 2); ?> ฿</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">ค่าจัดส่ง:</td>
                            <td class="fw-bold">0.00 ฿ (สมมติ)</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold fs-5 text-danger">รวมทั้งหมด:</td>
                            <td class="fw-bold fs-5 text-danger"><?php echo number_format($order['total_amount'], 2); ?> ฿</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="card p-4 mb-4 shadow-sm card-hover">
            <h4 class="mb-3 text-secondary"><i class="bi bi-credit-card-fill me-2"></i> ข้อมูลการชำระเงิน</h4>
            <?php if ($payment) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>วิธีชำระเงิน:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                        <p><strong>วันที่ชำระ:</strong> <?php echo date('d/m/Y H:i:s', strtotime($payment['payment_date'])); ?></p>
                        <p><strong>หมายเลขธุรกรรม:</strong> <?php echo htmlspecialchars($payment['transaction_no']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>สถานะการชำระเงิน:</strong> <?php echo get_status_badge($payment['payment_status'], 'payment'); ?></p>
                        <p><strong>ยอดชำระ:</strong> <span class="text-success"><?php echo number_format($payment['amount'], 2); ?> ฿</span></p>
                    </div>
                </div>
            <?php } else { ?>
                <p class="text-warning">ยังไม่มีข้อมูลการชำระเงินสำหรับคำสั่งซื้อนี้</p>
            <?php } ?>
        </div>

        <a href="manage_orders.php" class="btn btn-secondary mt-4"><i class="bi bi-arrow-left-circle me-1"></i> กลับไปจัดการคำสั่งซื้อ</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>