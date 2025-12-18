<?php
session_start();

// ตรวจสอบ Login
if (!isset($_SESSION['UID'])) {
    header("location: login.php");
    exit();
}

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";

$surachet = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($surachet, "utf8");

if (!$surachet) {
    die("❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

$current_uid = mysqli_real_escape_string($surachet, $_SESSION['UID']);
$message = "";
$subtotal = 0; // ตัวแปรสำหรับคำนวณราคารวม

// --- เพิ่ม: กำหนดโฟลเดอร์สำหรับเก็บไฟล์อัปโหลด ---
$target_dir = "uploads/";

// Logic การจัดการตะกร้าสินค้า (CRUD on Cart)

// การลบสินค้าออกจากตะกร้า (Delete)
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['pid'])) {
    $pid_to_remove = mysqli_real_escape_string($surachet, $_GET['pid']);

    $sql_delete = "DELETE FROM cart WHERE UID = '$current_uid' AND PID = '$pid_to_remove'";
    
    if (mysqli_query($surachet, $sql_delete)) {
        $message = "🗑️ ลบสินค้าออกจากตะกร้าแล้ว!";
        // เปลี่ยนเส้นทางเพื่อล้าง GET parameter
        header("location: cart.php");
        exit();
    } else {
        $message = "❌ ผิดพลาดในการลบสินค้า: " . mysqli_error($surachet);
    }
}

// การอัปเดตจำนวนสินค้า (Update Quantity)
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $pid => $new_qty) {
        $pid_update = mysqli_real_escape_string($surachet, $pid);
        $qty_update = intval($new_qty);

        // ตรวจสอบให้แน่ใจว่าจำนวนไม่ติดลบ
        if ($qty_update > 0) {
            $sql_update = "UPDATE cart SET quantity = '$qty_update' 
                           WHERE UID = '$current_uid' AND PID = '$pid_update'";
            mysqli_query($surachet, $sql_update);
        }
    }
    $message = "✅ อัปเดตจำนวนสินค้าในตะกร้าสำเร็จแล้ว!";
}

// การดึงข้อมูลสินค้าในตะกร้า (Read)
// ใช้ JOIN เพื่อดึงรายละเอียดสินค้าและคำนวณราคาย่อย
$sql_cart = "SELECT 
                c.PID, c.quantity, 
                p.ProductName, p.Price, p.Image
            FROM cart c
            INNER JOIN products p ON c.PID = p.PID
            WHERE c.UID = '$current_uid'
            ORDER BY c.CartID DESC";
$result_cart = mysqli_query($surachet, $sql_cart);

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$sql_user_info = "SELECT Fullname, Username, Role FROM users WHERE UID = '$current_uid'";
$result_user = mysqli_query($surachet, $sql_user_info);
$user_info = mysqli_fetch_assoc($result_user);

// ปิดการเชื่อมต่อ 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .product-img-thumb { width: 80px; height: 80px; object-fit: cover; }
        .navbar-custom {  background-color: #212529; /* Dark Navbar */ }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="member.php"><i class="bi bi-shop me-2"></i> หน้าสมาชิก</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="bi bi-grid-fill me-1"></i> ดูสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="bi bi-cart-fill me-1"></i> ตะกร้าสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders_history.php"><i class="bi bi-clock-history me-1"></i> ประวัติคำสั่งซื้อ</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($user_info['Fullname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear-fill me-2"></i> จัดการบัญชี</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container my-5">
        <h1 class="mb-4"><i class="bi bi-cart4 me-2"></i> ตะกร้าสินค้าของคุณ</h1>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result_cart) > 0): ?>
            <form action="" method="POST">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">รายการสินค้าในตะกร้า</h5>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>สินค้า</th>
                                                <th>ราคาต่อหน่วย</th>
                                                <th>จำนวน</th>
                                                <th>ราคารวม</th>
                                                <th>ลบ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($item = mysqli_fetch_assoc($result_cart)): 
                                                $item_total = $item['Price'] * $item['quantity'];
                                                $subtotal += $item_total;
                                                
                                                // --- ปรับปรุง: สร้าง URL รูปภาพที่ถูกต้อง ---
                                                $image_src = !empty($item['Image']) ? $target_dir . htmlspecialchars($item['Image']) : 'https://via.placeholder.com/80x80?text=No+Image';
                                            ?>
                                                <tr>
                                                    <td class="d-flex align-items-center">
                                                        <?php if (!empty($item['Image'])): ?>
                                                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($item['ProductName']); ?>" class="product-img-thumb me-3">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['ProductName']); ?>
                                                    </td>
                                                    <td><?php echo number_format($item['Price'], 2); ?> บาท</td>
                                                    <td>
                                                        <input type="number" name="quantity[<?php echo $item['PID']; ?>]" 
                                                               value="<?php echo $item['quantity']; ?>" min="1" 
                                                               class="form-control form-control-sm text-center" style="width: 70px;">
                                                    </td>
                                                    <td class="fw-bold"><?php echo number_format($item_total, 2); ?> บาท</td>
                                                    <td>
                                                        <a href="?action=remove&pid=<?php echo $item['PID']; ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('ต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?');"><i class="bi bi-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" name="update_cart" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-clockwise me-1"></i> อัปเดตตะกร้า</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-body">
                                <h5 class="card-title mb-4"><i class="bi bi-currency-dollar me-2"></i> สรุปคำสั่งซื้อ</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>ราคารวม (Subtotal)</span>
                                        <span class="fw-bold"><?php echo number_format($subtotal, 2); ?> บาท</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between text-success">
                                        <span>ค่าจัดส่ง</span>
                                        <span class="fw-bold">ฟรี!</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between bg-light">
                                        <span class="h5">ยอดสุทธิ (Total)</span>
                                        <span class="h5 text-danger fw-bold"><?php echo number_format($subtotal, 2); ?> บาท</span>
                                    </li>
                                </ul>
                                <a href="checkout.php" class="btn btn-primary btn-lg w-100 mt-4"><i class="bi bi-bag-check-fill me-2"></i> ดำเนินการชำระเงิน</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center p-5">
                <i class="bi bi-bag-x-fill display-4"></i>
                <p class="mt-3 fs-5">ตะกร้าสินค้าของคุณว่างเปล่า</p>
                <a href="products.php" class="btn btn-success mt-2"><i class="bi bi-grid-fill me-1"></i> เลือกซื้อสินค้าต่อ</a>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php mysqli_close($surachet); // ปิดการเชื่อมต่อ ?>
</body>
</html>