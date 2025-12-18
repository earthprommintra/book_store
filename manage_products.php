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

$surachet = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($surachet, "utf8");

if (!$surachet) {
    die("❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

$message = ""; // ตัวแปรสำหรับเก็บข้อความแจ้งเตือน

// กำหนดโฟลเดอร์สำหรับเก็บไฟล์อัปโหลด
$target_dir = "uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // สร้างโฟลเดอร์ถ้าไม่มี
}

// ส่วนที่เพิ่มใหม่: ดึงรายการหมวดหมู่
$sql_categories = "SELECT CID, CategoryName FROM categories ORDER BY CategoryName ASC";
$result_categories = mysqli_query($surachet, $sql_categories);

$categories_list = [];
while($cat = mysqli_fetch_assoc($result_categories)) {
    $categories_list[] = $cat;
}

// Logic การจัดการข้อมูล (CRUD Operations)
// การเพิ่มสินค้าใหม่
if (isset($_POST['add_product'])) {
    $pname = mysqli_real_escape_string($surachet, $_POST['new_pname']);
    $price = mysqli_real_escape_string($surachet, $_POST['new_price']);
    $stock = mysqli_real_escape_string($surachet, $_POST['new_stock']);
    $cid = mysqli_real_escape_string($surachet, $_POST['new_cid']);
    $image_url = ""; // ตัวแปรสำหรับเก็บชื่อไฟล์รูปภาพ

    // --- ส่วนที่ปรับปรุง: จัดการไฟล์อัปโหลด ---
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
        $file_name = basename($_FILES["new_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ตรวจสอบว่าเป็นไฟล์รูปภาพจริงหรือไม่ (ป้องกันการอัปโหลดไฟล์อันตราย)
        $check = getimagesize($_FILES["new_image"]["tmp_name"]);
        if ($check !== false) {
            // สร้างชื่อไฟล์ที่ไม่ซ้ำกันเพื่อป้องกันการทับซ้อน
            $new_file_name = uniqid() . "." . $imageFileType;
            $final_target_file = $target_dir . $new_file_name;

            // ย้ายไฟล์จาก temp ไปยังโฟลเดอร์เป้าหมาย
            if (move_uploaded_file($_FILES["new_image"]["tmp_name"], $final_target_file)) {
                $image_url = $new_file_name; // บันทึกเฉพาะชื่อไฟล์เพื่อใช้ในพาธ
            } else {
                $message = "❌ ผิดพลาดในการย้ายไฟล์รูปภาพ";
            }
        } else {
            $message = "❌ ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
        }
    }
    // ----------------------------------------------------

    // ปรับปรุง SQL INSERT ให้มี CID และ Image
    if (empty($message) || strpos($message, '❌') === false) { // ถ้าไม่มีข้อความผิดพลาดเกี่ยวกับการอัปโหลด
        $sql_insert = "INSERT INTO products (CID, ProductName, Price, Stock, Image) 
                       VALUES ('$cid', '$pname', '$price', '$stock', '$image_url')";
        
        if (mysqli_query($surachet, $sql_insert)) {
            $message = "✅ เพิ่มสินค้า **{$pname}** สำเร็จแล้ว!";
        } else {
            $message = "❌ ผิดพลาดในการเพิ่มสินค้า: " . mysqli_error($surachet);
        }
    }
}

// การลบสินค้า (โค้ดเดิม)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['pid'])) {
    $pid_to_delete = mysqli_real_escape_string($surachet, $_GET['pid']);

    // --- ส่วนที่เพิ่ม: ลบไฟล์รูปภาพออกจากเซิร์ฟเวอร์ด้วย ---
    $sql_get_image = "SELECT Image FROM products WHERE PID = '$pid_to_delete'";
    $result_image = mysqli_query($surachet, $sql_get_image);
    $image_row = mysqli_fetch_assoc($result_image);
    $old_image = $image_row['Image'];
    
    $sql_delete = "DELETE FROM products WHERE PID = '$pid_to_delete'";
    
    if (mysqli_query($surachet, $sql_delete)) {
        // ลบไฟล์จริง
        if (!empty($old_image) && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
        }

        $message = "🗑️ ลบสินค้า PID: {$pid_to_delete} สำเร็จแล้ว!";
    } else {
        $message = "❌ ผิดพลาดในการลบสินค้า: " . mysqli_error($surachet);
    }
}

// การดึงข้อมูลสินค้าทั้งหมด (Read) พร้อมชื่อหมวดหมู่ (โค้ดเดิม)
$sql_select_all = "SELECT 
                        p.PID, p.ProductName, p.Price, p.Stock, p.Image,
                        c.CategoryName
                    FROM products p
                    INNER JOIN categories c ON p.CID = c.CID
                    ORDER BY p.PID DESC";
$result_products = mysqli_query($surachet, $sql_select_all);

// ปิดการเชื่อมต่อเมื่อจบการใช้งาน
// *** จะปิดตรงนี้ไม่ได้ เพราะยังต้องใช้ $result_products ในการแสดงผล
// mysqli_close($surachet); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า | Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #007bff;
        }
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: #343a40;
            color: white;
            position: fixed;
            height: 100%;
            padding-top: 20px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 20px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
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
        .product-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header p-3 mb-4" style="font-weight: 700; font-size: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
            <i class="bi bi-gear-fill me-2"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="admin.php"><i class="bi bi-speedometer2 me-2"></i> หน้าหลัก (Dashboard)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_users.php"><i class="bi bi-people-fill me-2"></i> จัดการผู้ใช้</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage_products.php"><i class="bi bi-box-seam-fill me-2"></i> จัดการสินค้า</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_orders.php"><i class="bi bi-cart-check-fill me-2"></i> จัดการคำสั่งซื้อ</a>
            </li>
        </ul>
        
        <a href="logout.php" class="btn btn-danger logout-btn-sidebar">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>

    <div class="main-content">
        <h1 class="mb-4"><i class="bi bi-box-seam-fill me-2"></i> จัดการสินค้า</h1>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-tag-fill me-2"></i> เพิ่มสินค้าใหม่</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="new_cid" class="form-label">หมวดหมู่</label>
                            <select name="new_cid" id="new_cid" class="form-select" required>
                                <option value="" disabled selected>-- เลือกหมวดหมู่ --</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo $cat['CID']; ?>">
                                        <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="new_pname" class="form-label">ชื่อสินค้า</label>
                            <input type="text" name="new_pname" id="new_pname" class="form-control" placeholder="ชื่อสินค้า" required>
                        </div>
                        <div class="col-md-1">
                            <label for="new_price" class="form-label">ราคา</label>
                            <input type="number" name="new_price" id="new_price" class="form-control" placeholder="ราคา" required min="0" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <label for="new_stock" class="form-label">สต็อก</label>
                            <input type="number" name="new_stock" id="new_stock" class="form-control" placeholder="สต็อก" required min="0">
                        </div>
                        <div class="col-md-3">
                            <label for="new_image" class="form-label">รูปภาพสินค้า</label>
                            <input type="file" name="new_image" id="new_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_product" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i> เพิ่มสินค้า</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>รายการสินค้าทั้งหมด (<?php echo mysqli_num_rows($result_products); ?> รายการ)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>PID</th>
                                <th>หมวดหมู่</th> <th>รูปภาพ</th>
                                <th>ชื่อสินค้า</th>
                                <th>ราคา</th>
                                <th>สต็อก</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result_products) > 0) {
                                while($row = mysqli_fetch_assoc($result_products)) {
                                    $stock_color = ($row['Stock'] < 10) ? 'text-danger fw-bold' : '';
                                    echo "<tr>";
                                    echo "<td>" . $row['PID'] . "</td>";
                                    // แสดงชื่อหมวดหมู่ที่ JOIN มา
                                    echo "<td><span class='badge bg-info text-dark'>" . htmlspecialchars($row['CategoryName']) . "</span></td>";
                                    // ปรับปรุง: ใช้พาธไฟล์ที่อัปโหลด (uploads/ชื่อไฟล์)
                                    echo "<td>";
                                    if (!empty($row['Image'])) {
                                        echo "<img src='" . $target_dir . htmlspecialchars($row['Image']) . "' alt='รูปภาพสินค้า' class='product-img-thumb'>";
                                    } else {
                                        echo "<i class='bi bi-image-fill text-muted'></i>";
                                    }
                                    echo "</td>";
                                    // ------------------------------------
                                    echo "<td>" . htmlspecialchars($row['ProductName']) . "</td>";
                                    echo "<td>" . number_format($row['Price'], 2) . "</td>";
                                    echo "<td class='{$stock_color}'>" . number_format($row['Stock']) . "</td>";
                                    echo "<td>
                                        <a href='edit_product.php?pid=" . $row['PID'] . "' class='btn btn-sm btn-warning me-2'><i class='bi bi-pencil'></i> แก้ไข</a>
                                        <a href='?action=delete&pid=" . $row['PID'] . "' class='btn btn-sm btn-danger' onclick=\"return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้า: " . htmlspecialchars($row['ProductName']) . "?');\"><i class='bi bi-trash'></i> ลบ</a>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>ไม่พบรายการสินค้าในระบบ</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php mysqli_close($surachet); // ปิดการเชื่อมต่อเมื่อจบการทำงานส่วน HTML ?>
</body>
</html>