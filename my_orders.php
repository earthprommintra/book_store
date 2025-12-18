<?php
session_start();

// 🔒 SECURITY BLOCK: ตรวจสอบว่าผู้ใช้ได้ Login แล้ว
if (!isset($_SESSION['UID'])) {
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

// ⭐ ส่วนที่แก้ไข: กำหนดโฟลเดอร์สำหรับเก็บไฟล์อัปโหลด
$target_dir = "uploads/";

// รับ OID จาก URL
$oid = isset($_GET['oid']) ? intval($_GET['oid']) : 0;
$current_uid = mysqli_real_escape_string($conn, $_SESSION['UID']);

// 🔒 SECURITY CHECK: ดึงข้อมูลหลักของคำสั่งซื้อและผู้ใช้ (ต้องเป็นของ UID นี้เท่านั้น)
$order_query = mysqli_query($conn, "SELECT o.*, u.Fullname 
    FROM orders o
    JOIN users u ON o.UID = u.UID
    WHERE o.OID = $oid AND o.UID = '$current_uid'
");
$order = mysqli_fetch_assoc($order_query);

// ตรวจสอบว่ามีคำสั่งซื้อนี้อยู่หรือไม่ หรือไม่ตรงกับ UID ปัจจุบัน
if (!$order) {
    echo "<script>alert('❌ ไม่พบคำสั่งซื้อ หรือคุณไม่มีสิทธิ์เข้าถึง'); window.location.href='orders_history.php';</script>";
    exit();
}

// ⭐ ส่วนที่แก้ไข: ดึงรายการสินค้าในคำสั่งซื้อ (รวม ProductName และ Image)
$items_query = mysqli_query($conn, "SELECT oi.quantity, oi.price, p.ProductName, p.Image
    FROM order_items oi
    JOIN products p ON oi.PID = p.PID
    WHERE oi.OID = $oid
");

// ดึงข้อมูลที่อยู่จัดส่งที่บันทึกไว้ในตาราง orders
// (ใช้ข้อมูลที่บันทึกไว้ ณ วันที่สั่งซื้อ)
$address_query = mysqli_query($conn, "SELECT Fullname, Phone, AddressLine, District, Province, PostalCode
    FROM orders 
    WHERE OID = $oid
");

// ดึงข้อมูลการจัดส่ง
$address_query = mysqli_query($conn, "SELECT * FROM address 
    WHERE UID = {$order['UID']} AND is_default = TRUE 
    LIMIT 1
");

$address = mysqli_fetch_assoc($address_query); 

// ดึงข้อมูลการชำระเงิน
$payment_query = mysqli_query($conn, "SELECT * FROM payment 
    WHERE OID = $oid 
    LIMIT 1
");
$payment = mysqli_fetch_assoc($payment_query);

// ฟังก์ชันช่วยแสดง Badge สถานะ (เดิม)
function get_status_badge($status, $type = 'order') {
    $status_text = ucfirst($status);
    $class = '';
    if ($type == 'order') {
        switch ($status) {
            case 'pending': $class = 'warning text-dark'; $icon = 'bi-hourglass-split'; break;
            case 'paid': $class = 'info text-dark'; $icon = 'bi-cash-stack'; break;
            case 'shipped': $class = 'success'; $icon = 'bi-truck'; break;
            case 'cancelled': $class = 'danger'; $icon = 'bi-x-circle'; break;
            default: $class = 'secondary'; $icon = 'bi-question-circle';
        }
    } else { // payment status
        switch ($status) {
            case 'successful': $class = 'success'; $icon = 'bi-check-circle-fill'; break;
            case 'failed': $class = 'danger'; $icon = 'bi-x-octagon-fill'; break;
            case 'pending': $class = 'warning text-dark'; $icon = 'bi-arrow-repeat'; break;
            default: $class = 'secondary'; $icon = 'bi-question-circle';
        }
    }
    return "<span class='badge bg-$class p-2'><i class='bi $icon me-1'></i> $status_text</span>";
}

// ดึงข้อมูลผู้ใช้สำหรับ Navbar
$user_info_query = mysqli_query($conn, "SELECT Fullname FROM users WHERE UID = '$current_uid'");
$user = mysqli_fetch_assoc($user_info_query);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ #<?php echo $oid; ?> | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .navbar-custom { background-color: #212529; }
        .card-custom { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .table thead th { background-color: #f0f0f0; }
        .product-img-small { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .summary-box { border-left: 5px solid #0d6efd; padding: 15px; background-color: #e9f2ff; border-radius: 8px; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="member.php"><i class="bi bi-shop me-2"></i> หน้าสมาชิก</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-grid-fill me-1"></i> ดูสินค้า</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php"><i class="bi bi-cart-fill me-1"></i> ตะกร้าสินค้า</a></li>
                    <li class="nav-item"><a class="nav-link active" href="orders_history.php"><i class="bi bi-clock-history me-1"></i> ประวัติคำสั่งซื้อ</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($user['Fullname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear-fill me-2"></i> จัดการบัญชี</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary"><i class="bi bi-file-earmark-text-fill me-2"></i> รายละเอียดคำสั่งซื้อ #<?php echo $oid; ?></h1>
            <?php echo get_status_badge($order['status'], 'order'); ?>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card card-custom p-4 h-100">
                    <h4 class="mb-3 text-secondary"><i class="bi bi-info-circle-fill me-2"></i> ข้อมูลคำสั่งซื้อ</h4>
                    <p><strong>รหัสคำสั่งซื้อ:</strong> <?php echo $order['OID']; ?></p>
                    <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i:s', strtotime($order['order_date'])); ?></p>
                    
                    <hr>
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

            <div class="col-lg-7">
                <div class="card card-custom p-4 mb-4">
                    <h4 class="mb-3 text-secondary"><i class="bi bi-credit-card-fill me-2"></i> ข้อมูลการชำระเงิน</h4>
                    <?php if ($payment) { ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>สถานะ:</strong> <?php echo get_status_badge($payment['payment_status'], 'payment'); ?></p>
                                <p><strong>วิธีชำระ:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>ยอดชำระ:</strong> <span class="text-success fs-5"><?php echo number_format($payment['amount'], 2); ?> ฿</span></p>
                                <p><strong>วันที่ชำระ:</strong> <?php echo date('d/m/Y H:i:s', strtotime($payment['payment_date'])); ?></p>
                            </div>
                        </div>
                    <?php } else { ?>
                        <p class="text-warning">ยังไม่มีข้อมูลการชำระเงินสำหรับคำสั่งซื้อนี้</p>
                    <?php } ?>
                </div>

                <div class="summary-box">
                    <p class="fs-5 mb-0 text-dark"><strong>ยอดรวมสุทธิ:</strong></p>
                    <p class="display-6 fw-bold text-danger mb-0"><?php echo number_format($order['total_amount'], 2); ?> ฿</p>
                </div>
                
                <?php if ($order['status'] == 'pending' && !$payment): ?>
                <a href="payment.php?oid=<?php echo $oid; ?>" class="btn btn-warning w-100 mt-3 text-dark fw-bold">
                    <i class="bi bi-credit-card-2-front-fill me-2"></i> ชำระเงินคำสั่งซื้อนี้
                </a>
                <?php endif; ?>
            </div>
        </div>

        <h3 class="mt-5 mb-3"><i class="bi bi-list-ul me-2"></i> รายการสินค้าในคำสั่งซื้อ</h3>
        <div class="card card-custom p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th></th>
                            <th>สินค้า</th>
                            <th class="text-end">ราคา/หน่วย</th>
                            <th class="text-end">จำนวน</th>
                            <th class="text-end">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        mysqli_data_seek($items_query, 0); // รีเซ็ตตัวชี้ผลลัพธ์
                        while ($item = mysqli_fetch_assoc($items_query)) { 
                            $item_total = $item['quantity'] * $item['price'];
                            $subtotal += $item_total;
                            // ⭐ ส่วนที่แก้ไข: สร้าง URL รูปภาพที่ถูกต้อง
                            $image_src = !empty($item['Image']) ? $target_dir . htmlspecialchars($item['Image']) : 'https://via.placeholder.com/50x50?text=No+Image';
                        ?>
                            <tr>
                                <td><img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($item['ProductName']); ?>" class="product-img-small"></td>
                                <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                <td class="text-end"><?php echo number_format($item['price'], 2); ?> ฿</td>
                                <td class="text-end"><?php echo $item['quantity']; ?></td>
                                <td class="text-end fw-bold text-danger"><?php echo number_format($item_total, 2); ?> ฿</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">ยอดรวมสินค้า:</td>
                            <td class="text-end fw-bold"><?php echo number_format($subtotal, 2); ?> ฿</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">ค่าจัดส่ง:</td>
                            <td class="text-end fw-bold">0.00 ฿</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end fw-bold fs-5 text-primary">ยอดรวมสุทธิ:</td>
                            <td class="text-end fw-bold fs-5 text-primary"><?php echo number_format($order['total_amount'], 2); ?> ฿</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <a href="orders_history.php" class="btn btn-secondary mt-4"><i class="bi bi-arrow-left-circle me-1"></i> กลับสู่ประวัติคำสั่งซื้อ</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>