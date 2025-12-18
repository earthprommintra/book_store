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

$message = ""; 
$product_data = null; 

// กำหนดโฟลเดอร์สำหรับเก็บไฟล์อัปโหลด
$target_dir = "uploads/"; 
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // สร้างโฟลเดอร์ถ้าไม่มี
}

// ดึงรายการหมวดหมู่ทั้งหมดสำหรับ Dropdown
$sql_categories = "SELECT CID, CategoryName FROM categories ORDER BY CategoryName ASC";
$result_categories = mysqli_query($surachet, $sql_categories);

$categories_list = [];
while($cat = mysqli_fetch_assoc($result_categories)) {
    $categories_list[] = $cat;
}

// รับ PID และดึงข้อมูลสินค้าปัจจุบัน
if (isset($_GET['pid'])) {
    $pid_to_edit = mysqli_real_escape_string($surachet, $_GET['pid']);
    
    // ดึงข้อมูลสินค้าทั้งหมดรวมถึง CID
    $sql_fetch = "SELECT PID, ProductName, Price, Stock, Image, CID FROM products WHERE PID = '$pid_to_edit'";
    $result_fetch = mysqli_query($surachet, $sql_fetch);
    
    if (mysqli_num_rows($result_fetch) == 1) {
        $product_data = mysqli_fetch_assoc($result_fetch);
        // เก็บชื่อรูปภาพเก่าไว้ใช้ในการลบไฟล์หากมีการอัปโหลดใหม่
        $old_image = $product_data['Image'];
    } else {
        header("location: manage_products.php");
        exit();
    }
} else {
    header("location: manage_products.php");
    exit();
}

// Logic การแก้ไข/อัปเดตข้อมูล
if (isset($_POST['update_product'])) {
    $pid = $product_data['PID']; 
    $new_cid = mysqli_real_escape_string($surachet, $_POST['new_cid']);
    $new_pname = mysqli_real_escape_string($surachet, $_POST['new_pname']);
    $new_price = mysqli_real_escape_string($surachet, $_POST['new_price']);
    $new_stock = mysqli_real_escape_string($surachet, $_POST['new_stock']);
    
    $image_to_save = $old_image; // เริ่มต้นใช้รูปภาพเดิม

    // --- ส่วนที่ปรับปรุง: จัดการไฟล์อัปโหลดใหม่ ---
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
        $file_name = basename($_FILES["new_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // กำหนดประเภทไฟล์ที่อนุญาต
        $allowed_types = array("jpg", "jpeg", "png", "gif");

        // 1. ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
        $check = getimagesize($_FILES["new_image"]["tmp_name"]);
        if ($check !== false) {
            // 2. สร้างชื่อไฟล์ใหม่และย้ายไฟล์
            $new_file_name = uniqid() . "." . $imageFileType;
            $final_target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($_FILES["new_image"]["tmp_name"], $final_target_file)) {
                $image_to_save = $new_file_name; // ตั้งค่าชื่อไฟล์ใหม่สำหรับบันทึก

                // 3. ลบรูปภาพเก่า (ถ้ามี)
                if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                    unlink($target_dir . $old_image);
                }

            } else {
                $message = "❌ ผิดพลาดในการย้ายไฟล์รูปภาพใหม่";
            }
        } else {
            $message = "❌ ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
        }
    } else if (isset($_POST['delete_current_image']) && $_POST['delete_current_image'] == '1') {
        // --- ส่วนที่เพิ่ม: จัดการการลบรูปภาพปัจจุบัน ---
        if (!empty($old_image) && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
            $image_to_save = ""; // ล้างชื่อไฟล์ใน DB
            $message .= " | รูปภาพเดิมถูกลบแล้ว";
        } else {
            $message .= " | ไม่มีรูปภาพเดิมให้ลบ";
        }
    }
    // ----------------------------------------------------

    // SQL UPDATE
    if (empty($message) || strpos($message, '❌') === false) { 
        $sql_update = "UPDATE products SET 
                            CID = '$new_cid',
                            ProductName = '$new_pname', 
                            Price = '$new_price', 
                            Stock = '$new_stock',
                            Image = '$image_to_save'
                        WHERE PID = '$pid'";
        
        if (mysqli_query($surachet, $sql_update)) {
            // อัปเดตข้อมูลใน $product_data ทันทีเพื่อให้แสดงผลลัพธ์ใหม่ในฟอร์ม
            $product_data['CID'] = $new_cid;
            $product_data['ProductName'] = $new_pname;
            $product_data['Price'] = $new_price;
            $product_data['Stock'] = $new_stock;
            $product_data['Image'] = $image_to_save;

            // ต้องอัปเดต $old_image ด้วยเพื่อใช้ในการวนซ้ำครั้งต่อไป
            $old_image = $image_to_save; 

            $message = "✅ อัปเดตข้อมูลสินค้า **{$new_pname}** สำเร็จแล้ว!" . $message;
        } else {
            $message = "❌ ผิดพลาดในการอัปเดตข้อมูล: " . mysqli_error($surachet);
        }
    }
}

// ปิดการเชื่อมต่อเมื่อจบการใช้งาน
// ไม่ปิดตรงนี้ เพราะต้องใช้ข้อมูลใน HTML
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า: <?php echo htmlspecialchars($product_data['ProductName']); ?> | Admin Panel</title>
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
        .product-preview {
            max-width: 150px;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 8px;
            margin-top: 10px;
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
        <h1 class="mb-4"><i class="bi bi-pencil-square me-2"></i> แก้ไขสินค้า: **<?php echo htmlspecialchars($product_data['ProductName']); ?>**</h1>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5><i class="bi bi-info-circle-fill me-2"></i> ข้อมูลสินค้า PID: <?php echo $product_data['PID']; ?></h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productname" class="form-label">ชื่อสินค้า</label>
                                <input type="text" name="new_pname" id="productname" class="form-control" value="<?php echo htmlspecialchars($product_data['ProductName']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="cid" class="form-label">หมวดหมู่</label>
                                <select name="new_cid" id="cid" class="form-select" required>
                                    <?php foreach ($categories_list as $cat): ?>
                                        <option value="<?php echo $cat['CID']; ?>" 
                                            <?php echo ($product_data['CID'] == $cat['CID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">ราคา (บาท)</label>
                                        <input type="number" name="new_price" id="price" class="form-control" value="<?php echo htmlspecialchars($product_data['Price']); ?>" required min="0" step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">สต็อก</label>
                                        <input type="number" name="new_stock" id="stock" class="form-control" value="<?php echo htmlspecialchars($product_data['Stock']); ?>" required min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_image" class="form-label">อัปโหลดรูปภาพใหม่ (ไม่บังคับ)</label>
                                <input type="file" name="new_image" id="new_image" class="form-control" accept="image/*">
                                <small class="text-muted">ไฟล์ที่อนุญาต: JPG, JPEG, PNG</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ตัวอย่างรูปภาพปัจจุบัน:</label><br>
                                <?php if (!empty($product_data['Image'])): ?>
                                    <img src="<?php echo $target_dir . htmlspecialchars($product_data['Image']); ?>" alt="รูปภาพสินค้า" class="product-preview">
                                    
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="delete_current_image" value="1" id="deleteImage">
                                        <label class="form-check-label text-danger" for="deleteImage">
                                            <i class="bi bi-trash"></i> **ลบรูปภาพปัจจุบัน** (ถ้าเลือกและกดบันทึก)
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted"><i class="bi bi-x-circle me-1"></i>ไม่มีรูปภาพ</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" name="update_product" class="btn btn-warning me-2"><i class="bi bi-save-fill me-2"></i> บันทึกการแก้ไข</button>
                    <a href="manage_products.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับไปยังจัดการสินค้า</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php mysqli_close($surachet); // ปิดการเชื่อมต่อ ?>
</body>
</html>