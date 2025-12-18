<?php
session_start();

// ตรวจสอบ Login (Security Block - ถ้าต้องการให้ดูสินค้าได้เฉพาะสมาชิก)
// ถ้าต้องการให้ทุกคนดูได้ (Guest) สามารถลบ Block นี้ได้
if (!isset($_SESSION['UID'])) {
    // header("location: login.php");
    // exit();
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

$message = ""; 
$current_uid = isset($_SESSION['UID']) ? mysqli_real_escape_string($surachet, $_SESSION['UID']) : null;

// --- เพิ่ม: กำหนดโฟลเดอร์สำหรับเก็บไฟล์อัปโหลด ---
$target_dir = "uploads/";

// Logic สำหรับการค้นหาและกรอง (Search & Filter)
$where_clauses = []; // อาร์เรย์เก็บเงื่อนไข WHERE
$search_query = ""; // ตัวแปรสำหรับเก็บคำค้นหา
$filter_cid = "";   // ตัวแปรสำหรับเก็บ CID ที่ถูกกรอง

// การค้นหา
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($surachet, $_GET['search']);
    // ค้นหาในชื่อสินค้า
    $where_clauses[] = "p.ProductName LIKE '%$search_query%'";
}

// การกรองตามหมวดหมู่
if (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
    $filter_cid = mysqli_real_escape_string($surachet, $_GET['cid']);
    $where_clauses[] = "p.CID = '$filter_cid'";
}

// การดึงข้อมูลสินค้า
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$sql_products = "SELECT 
                    p.PID, p.ProductName, p.Price, p.Stock, p.Image,
                    c.CategoryName
                FROM products p
                INNER JOIN categories c ON p.CID = c.CID
                $where_sql
                ORDER BY p.PID DESC";

$result_products = mysqli_query($surachet, $sql_products);

// การดึงรายการหมวดหมู่ทั้งหมด
// ต้องทำการ Query ใหม่ เพราะ Query ข้างบนอาจถูกใช้ไปแล้ว
$sql_categories = "SELECT CID, CategoryName FROM categories ORDER BY CategoryName ASC";
$result_categories = mysqli_query($surachet, $sql_categories);

// Logic การเพิ่มสินค้าลงตะกร้า
if (isset($_GET['action']) && $_GET['action'] == 'addtocart' && isset($_GET['pid']) && $current_uid) {
    $pid_to_add = mysqli_real_escape_string($surachet, $_GET['pid']);

    // ตรวจสอบว่ามีสินค้านี้ในตะกร้าอยู่แล้วหรือไม่
    $sql_check_cart = "SELECT quantity FROM cart WHERE UID = '$current_uid' AND PID = '$pid_to_add'";
    $result_check = mysqli_query($surachet, $sql_check_cart);

    if (mysqli_num_rows($result_check) > 0) {
        // ถ้ามีอยู่แล้ว ให้อัปเดตจำนวน +1
        $sql_update_cart = "UPDATE cart SET quantity = quantity + 1 WHERE UID = '$current_uid' AND PID = '$pid_to_add'";
        if (mysqli_query($surachet, $sql_update_cart)) {
            $message = "✅ เพิ่มจำนวนสินค้าในตะกร้าแล้ว!";
        }
    } else {
        // ถ้ายังไม่มี ให้เพิ่มรายการใหม่
        $sql_insert_cart = "INSERT INTO cart (UID, PID, quantity) VALUES ('$current_uid', '$pid_to_add', 1)";
        if (mysqli_query($surachet, $sql_insert_cart)) {
            $message = "✅ เพิ่มสินค้าลงในตะกร้าสำเร็จแล้ว!";
        }
    }
    // เปลี่ยนเส้นทางเพื่อล้าง GET parameter และป้องกันการเพิ่มซ้ำ
    header("location: products.php" . (count($_GET) > 2 ? "?" . http_build_query(array_diff_key($_GET, array_flip(['action', 'pid']))) : ""));
    exit();
} else if (isset($_GET['action']) && $_GET['action'] == 'addtocart' && !$current_uid) {
    // กรณีที่ผู้ใช้ไม่ได้ Login แต่พยายามเพิ่มสินค้า
    $message = "⚠️ กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงในตะกร้า";
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน (ต้องทำหลังจาก Logic การเพิ่มตะกร้า)
$user_info = ['Fullname' => 'Guest', 'Role' => ''];
if ($current_uid) {
    $sql_user_info = "SELECT Fullname, Username, Role FROM users WHERE UID = '$current_uid'";
    $result_user = mysqli_query($surachet, $sql_user_info);
    if (mysqli_num_rows($result_user) > 0) {
        $user_info = mysqli_fetch_assoc($result_user);
    }
}


// ปิดการเชื่อมต่อ
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้าทั้งหมด | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .product-card {
            height: 100%;
            transition: transform 0.2s;
            overflow: hidden;
        }
        .navbar-custom {
            background-color: #212529; /* Dark Navbar */
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .product-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
            background-color: #eee;
        }
        .stock-low {
            color: red;
            font-weight: bold;
        }
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
                    <?php if ($current_uid): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-sm btn-outline-light" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container my-5">
        <h1 class="mb-4"><i class="bi bi-grid-fill me-2"></i> รายการสินค้าทั้งหมด</h1>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4 p-3">
            <form action="" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="searchInput" class="form-label">ค้นหาสินค้า</label>
                        <input type="text" name="search" id="searchInput" class="form-control" placeholder="ใส่ชื่อสินค้าที่ต้องการค้นหา..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="categoryFilter" class="form-label">กรองตามหมวดหมู่</label>
                        <select name="cid" id="categoryFilter" class="form-select">
                            <option value="">-- แสดงทุกหมวดหมู่ --</option>
                            <?php 
                            // ต้อง reset pointer ของ result_categories ก่อนใช้ใหม่
                            mysqli_data_seek($result_categories, 0);
                            while($cat = mysqli_fetch_assoc($result_categories)): 
                            ?>
                                <option value="<?php echo $cat['CID']; ?>" 
                                    <?php echo ($filter_cid == $cat['CID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex">
                        <button type="submit" class="btn btn-primary w-50 me-2"><i class="bi bi-funnel-fill me-1"></i> กรอง/ค้นหา</button>
                        <a href="products.php" class="btn btn-outline-secondary w-50"><i class="bi bi-x-circle-fill me-1"></i> ล้างค่า</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php 
            if (mysqli_num_rows($result_products) > 0) {
                while($product = mysqli_fetch_assoc($result_products)) {
                    $stock_class = $product['Stock'] < 5 ? 'stock-low' : '';
                    $stock_text = $product['Stock'] > 0 ? 'มีสินค้า' : 'สินค้าหมด';
                    
                    // --- ปรับปรุง: สร้าง URL รูปภาพที่ถูกต้อง ---
                    $image_src = !empty($product['Image']) ? $target_dir . htmlspecialchars($product['Image']) : 'https://via.placeholder.com/200x200?text=No+Image';
            ?>
                    <div class="col">
                        <div class="card product-card shadow-sm">
                            <img src="<?php echo $image_src; ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start"><?php echo htmlspecialchars($product['CategoryName']); ?></span>
                                <h5 class="card-title"><?php echo htmlspecialchars($product['ProductName']); ?></h5>
                                <p class="card-text text-danger fs-4 fw-bold mb-1"><?php echo number_format($product['Price'], 2); ?> บาท</p>
                                <p class="card-text mb-2">
                                    สถานะ: 
                                    <span class="<?php echo $stock_class; ?>">
                                        <?php echo $stock_text; ?>
                                        <?php echo ($product['Stock'] > 0 ? " ({$product['Stock']} ชิ้น)" : ''); ?>
                                    </span>
                                </p>

                                <div class="mt-auto">
                                <?php if ($product['Stock'] > 0 && $current_uid): ?>
                                    <a href="?action=addtocart&pid=<?php echo $product['PID']; ?>&search=<?php echo htmlspecialchars($search_query); ?>&cid=<?php echo htmlspecialchars($filter_cid); ?>" 
                                       class="btn btn-success w-100"><i class="bi bi-bag-plus-fill me-1"></i> เพิ่มลงตะกร้า</a>
                                <?php elseif ($product['Stock'] > 0 && !$current_uid): ?>
                                    <a href="login.php" class="btn btn-outline-success w-100" onclick="alert('กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงในตะกร้า');"><i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบเพื่อซื้อ</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled><i class="bi bi-x-octagon-fill me-1"></i> สินค้าหมด</button>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php 
                }
            } else {
                echo "<div class='col-12'><div class='alert alert-warning text-center'><i class='bi bi-search me-2'></i> ไม่พบสินค้าที่ตรงกับเงื่อนไขการค้นหา/การกรอง</div></div>";
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php mysqli_close($surachet); // ปิดการเชื่อมต่อ ?>
</body>
</html>