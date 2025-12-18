<?php
session_start();

// ตรวจสอบ Login
if (!isset($_SESSION['UID'])) {
    header("location: login.php");
    exit();
}

// การเชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";
$conn = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($conn, "utf8");

if (!$conn) {
    die("❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

$uid = $_SESSION['UID'];
$message = "";

// ดึงข้อมูลผู้ใช้
$sql_user = "SELECT Fullname FROM users WHERE UID = '$uid'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);

// ดึงที่อยู่จัดส่ง
$sql_address = "SELECT * FROM address WHERE UID = '$uid'";
$result_address = mysqli_query($conn, $sql_address);

// ดึงสินค้าในตะกร้า
$sql_cart = "SELECT c.PID, c.quantity, p.ProductName, p.Price, p.Image
    FROM cart c
    INNER JOIN products p ON c.PID = p.PID
    WHERE c.UID = '$uid'
";
$result_cart = mysqli_query($conn, $sql_cart);
$subtotal = 0;

// หากผู้ใช้กดยืนยันคำสั่งซื้อ
if (isset($_POST['confirm_order'])) {
    $address_id = mysqli_real_escape_string($conn, $_POST['address_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    // คำนวณยอดรวม
    $sql_sum = "SELECT SUM(c.quantity * p.Price) AS total 
                FROM cart c INNER JOIN products p ON c.PID = p.PID 
                WHERE c.UID = '$uid'";
    $result_sum = mysqli_query($conn, $sql_sum);
    $row_sum = mysqli_fetch_assoc($result_sum);
    $total = $row_sum['total'];

    if ($total > 0) {
        // เพิ่มคำสั่งซื้อใน orders
        $sql_order = "INSERT INTO orders (UID, order_date, total_amount, status) 
                      VALUES ('$uid', NOW(), '$total', 'pending')";
        mysqli_query($conn, $sql_order);
        $order_id = mysqli_insert_id($conn);

        // เพิ่มสินค้าใน order_items
        $result_cart_items = mysqli_query($conn, $sql_cart);
        while ($item = mysqli_fetch_assoc($result_cart_items)) {
            $pid = $item['PID'];
            $qty = $item['quantity'];
            $total = $row_sum['total'];
            mysqli_query($conn, "INSERT INTO order_items (OID, PID, quantity, price) 
                                 VALUES ('$order_id', '$pid', '$qty', '$total')");
        }

        // เพิ่มข้อมูลการชำระเงิน
        $sql_payment = "INSERT INTO payment (OID, payment_method, amount, payment_status) 
                        VALUES ('$order_id', '$payment_method', '$total', 'pending')";
        mysqli_query($conn, $sql_payment);

        // ล้างตะกร้า
        mysqli_query($conn, "DELETE FROM cart WHERE UID = '$uid'");

        $message = "✅ คำสั่งซื้อของคุณถูกบันทึกเรียบร้อยแล้ว!";
        header("refresh:2; url=orders_history.php");
    } else {
        $message = "❌ ไม่มีสินค้าในตะกร้า ไม่สามารถทำการสั่งซื้อได้";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .navbar-custom { background-color: #212529; }
        .product-img-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

<!-- Navbar -->
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
                <li class="nav-item"><a class="nav-link" href="orders_history.php"><i class="bi bi-clock-history me-1"></i> ประวัติคำสั่งซื้อ</a></li>
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

<!-- Checkout Form -->
<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-bag-check-fill me-2"></i> สรุปการสั่งซื้อ</h1>

    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($result_cart) > 0): ?>
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <!-- รายการสินค้า -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-cart-check me-2"></i> รายการสินค้า</h5>
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>สินค้า</th>
                                        <th>ราคา</th>
                                        <th>จำนวน</th>
                                        <th>รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($result_cart)): 
                                        $total_item = $item['Price'] * $item['quantity'];
                                        $subtotal += $total_item;
                                    ?>
                                    <tr>
                                        <td class="d-flex align-items-center">
                                            <img src="<?php echo $item['Image']; ?>" class="product-img-thumb me-3">
                                            <?php echo htmlspecialchars($item['ProductName']); ?>
                                        </td>
                                        <td><?php echo number_format($item['Price'], 2); ?> บาท</td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="fw-bold text-end"><?php echo number_format($total_item, 2); ?> บาท</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ที่อยู่จัดส่ง -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-geo-alt-fill me-2"></i> ที่อยู่จัดส่ง</h5>
                            <?php if (mysqli_num_rows($result_address) > 0): ?>
                                <?php while ($addr = mysqli_fetch_assoc($result_address)): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="address_id" value="<?php echo $addr['AddressID']; ?>" required>
                                        <label class="form-check-label">
                                            <?php echo htmlspecialchars($addr['AddressLine']); ?>,
                                            <?php echo htmlspecialchars($addr['District']); ?>,
                                            <?php echo htmlspecialchars($addr['Province']); ?>,
                                            <?php echo htmlspecialchars($addr['PostalCode']); ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-danger">ยังไม่มีที่อยู่ในระบบ <a href="profile.php" class="text-decoration-none">เพิ่มที่อยู่ที่นี่</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- วิธีชำระเงิน -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-credit-card-2-back me-2"></i> วิธีชำระเงิน</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="cash_on_delivery" required>
                                <label class="form-check-label">เก็บเงินปลายทาง (COD)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="bank_transfer" required>
                                <label class="form-check-label">โอนผ่านบัญชีธนาคาร</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="credit_card" required>
                                <label class="form-check-label">บัตรเครดิต</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- สรุปยอดรวม -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-cash-coin me-2"></i> สรุปยอดชำระ</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>ราคารวมสินค้า</span>
                                    <span><?php echo number_format($subtotal, 2); ?> บาท</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between text-success">
                                    <span>ค่าจัดส่ง</span>
                                    <span>ฟรี</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-light">
                                    <span class="fw-bold">ยอดสุทธิ</span>
                                    <span class="fw-bold text-danger"><?php echo number_format($subtotal, 2); ?> บาท</span>
                                </li>
                            </ul>
                            <button type="submit" name="confirm_order" class="btn btn-primary w-100 mt-4 btn-lg">
                                <i class="bi bi-bag-check-fill me-2"></i> ยืนยันคำสั่งซื้อ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning text-center p-5">
            <i class="bi bi-bag-x-fill display-4"></i>
            <p class="mt-3 fs-5">ไม่มีสินค้าในตะกร้า</p>
            <a href="products.php" class="btn btn-success mt-2"><i class="bi bi-grid-fill me-1"></i> ไปเลือกซื้อสินค้า</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
