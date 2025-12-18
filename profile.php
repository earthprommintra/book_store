<?php
session_start();

// 1. ตรวจสอบ Login (Security Block)
if (!isset($_SESSION['UID'])) {
    header("location: login.php");
    exit();
}

// 2. ข้อมูลการเชื่อมต่อฐานข้อมูล
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
$message_profile = "";
$message_address = "";
$message_password = "";

// 3. **Logic การจัดการข้อมูลส่วนตัว (Update User)**
if (isset($_POST['update_profile'])) {
    $new_fullname = mysqli_real_escape_string($surachet, $_POST['new_fullname']);

    $sql_update_user = "UPDATE users SET Fullname = '$new_fullname' WHERE UID = '$current_uid'";
    
    if (mysqli_query($surachet, $sql_update_user)) {
        $message_profile = "✅ อัปเดตข้อมูลส่วนตัวสำเร็จแล้ว!";
        // อัปเดตข้อมูลใน session เพื่อให้ชื่อแสดงผลถูกต้องทันที
        // สมมติว่า Fullname ถูกเก็บใน session: $_SESSION['Fullname'] = $new_fullname; 
    } else {
        $message_profile = "❌ ผิดพลาดในการอัปเดตข้อมูล: " . mysqli_error($surachet);
    }
}

// 3.1. Logic การเปลี่ยนรหัสผ่าน (Update Password)
if (isset($_POST['change_password'])) {
    $old_password = mysqli_real_escape_string($surachet, $_POST['old_password']);
    $new_password = mysqli_real_escape_string($surachet, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($surachet, $_POST['confirm_password']);

    // 💡 ในระบบจริง ควรตรวจสอบว่ารหัสผ่านเก่าถูกต้องก่อน (SELECT Password จาก DB)
    // 💡 และควรใช้ password_hash() สำหรับรหัสผ่านใหม่
    
    if ($new_password !== $confirm_password) {
        $message_password = "❌ รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน";
    } else if (empty($new_password)) {
         $message_password = "❌ กรุณาป้อนรหัสผ่านใหม่";
    } else {
        $hashed_password = $new_password; // ⚠️ ควรใช้ password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update_pass = "UPDATE users SET Password = '$hashed_password' WHERE UID = '$current_uid'";
        
        if (mysqli_query($surachet, $sql_update_pass)) {
            $message_password = "✅ เปลี่ยนรหัสผ่านสำเร็จแล้ว!";
        } else {
            $message_password = "❌ ผิดพลาดในการเปลี่ยนรหัสผ่าน: " . mysqli_error($surachet);
        }
    }
}


// 4. **การดึงข้อมูลผู้ใช้ปัจจุบันและที่อยู่**

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$current_uid = mysqli_real_escape_string($surachet, $_SESSION['UID']);
$sql_user_info = "SELECT Fullname, Username, Role FROM users WHERE UID = '$current_uid'";
$result_user = mysqli_query($surachet, $sql_user_info);
$user_info = mysqli_fetch_assoc($result_user);

// ดึงที่อยู่ (สมมติว่าดึงที่อยู่เริ่มต้น is_default=TRUE มาแสดง)
$sql_address = "SELECT * FROM address WHERE UID = '$current_uid' AND is_default = TRUE LIMIT 1";
$result_address = mysqli_query($surachet, $sql_address);
$address_info = mysqli_fetch_assoc($result_address); // อาจเป็น null ถ้าไม่มีที่อยู่

// 5. ปิดการเชื่อมต่อ
// mysqli_close($surachet); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบัญชี | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
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
    <?php include('navbar.php'); // สมมติว่ามีไฟล์ navbar.php ที่มีเมนูนำทาง ?>
    <div class="container my-5">
        <h1 class="mb-4"><i class="bi bi-person-gear-fill me-2"></i> จัดการบัญชีส่วนตัว</h1>
        <div class="row g-4">
            
            <div class="col-md-3">
                <div class="list-group shadow-sm" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action active" id="list-profile-list" data-bs-toggle="list" href="#list-profile" role="tab"><i class="bi bi-person-vcard me-2"></i> ข้อมูลส่วนตัว</a>
                    <a class="list-group-item list-group-item-action" id="list-address-list" data-bs-toggle="list" href="#list-address" role="tab"><i class="bi bi-house-door-fill me-2"></i> ที่อยู่จัดส่ง</a>
                    <a class="list-group-item list-group-item-action" id="list-password-list" data-bs-toggle="list" href="#list-password" role="tab"><i class="bi bi-lock-fill me-2"></i> เปลี่ยนรหัสผ่าน</a>
                    <a href="member.php" class="list-group-item list-group-item-action text-center text-primary fw-semibold"><i class="bi bi-arrow-left-circle me-2"></i> กลับสู่หน้าสมาชิก</a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content" id="nav-tabContent">

                    <div class="tab-pane fade show active" id="list-profile" role="tabpanel">
                        <div class="card shadow-sm">
                            <div class="card-header h5"><i class="bi bi-pencil-square me-2"></i> แก้ไขข้อมูลส่วนตัว</div>
                            <div class="card-body">
                                <?php if ($message_profile): ?>
                                    <div class="alert alert-info alert-dismissible fade show" role="alert"><?php echo $message_profile; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                                <?php endif; ?>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                                        <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user_info['Username']); ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                                        <input type="text" name="new_fullname" id="fullname" class="form-control" value="<?php echo htmlspecialchars($user_info['Fullname']); ?>" required>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary"><i class="bi bi-save me-2"></i>บันทึกข้อมูลส่วนตัว</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="list-address" role="tabpanel">
                        <div class="card shadow-sm">
                            <div class="card-header h5"><i class="bi bi-geo-alt-fill me-2"></i> ที่อยู่จัดส่งเริ่มต้น</div>
                            <div class="card-body">
                                <?php if ($address_info): ?>
                                    <p><strong>ชื่อผู้รับ:</strong> <?php echo htmlspecialchars($address_info['Fullname']); ?></p>
                                    <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($address_info['Phone']); ?></p>
                                    <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($address_info['AddressLine']); ?></p>
                                    <p><strong>เขต/อำเภอ:</strong> <?php echo htmlspecialchars($address_info['District']); ?></p>
                                    <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($address_info['Province']); ?></p>
                                    <p><strong>รหัสไปรษณีย์:</strong> <?php echo htmlspecialchars($address_info['PostalCode']); ?></p>
                                    <a href="manage_address.php" class="btn btn-warning mt-3"><i class="bi bi-pencil me-2"></i> แก้ไข/จัดการที่อยู่ทั้งหมด</a>
                                <?php else: ?>
                                    <div class="alert alert-warning">ไม่พบที่อยู่เริ่มต้น กรุณาเพิ่มที่อยู่</div>
                                    <a href="manage_address.php" class="btn btn-success mt-3"><i class="bi bi-plus me-2"></i> เพิ่มที่อยู่</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="list-password" role="tabpanel">
                        <div class="card shadow-sm">
                            <div class="card-header h5"><i class="bi bi-key-fill me-2"></i> เปลี่ยนรหัสผ่าน</div>
                            <div class="card-body">
                                <?php if ($message_password): ?>
                                    <div class="alert alert-info alert-dismissible fade show" role="alert"><?php echo $message_password; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                                <?php endif; ?>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="old_password" class="form-label">รหัสผ่านปัจจุบัน (สำหรับยืนยัน)</label>
                                        <input type="password" name="old_password" id="old_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-danger"><i class="bi bi-lock me-2"></i>เปลี่ยนรหัสผ่าน</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>