<?php
session_start();

// 1. ตรวจสอบ Login (Security Block)
if (!isset($_SESSION['UID'])) {
    header("location: login.php");
    exit();
}
if ($_SESSION['Role'] === 'admin') {
    header("location: admin.php"); // ไม่ให้ Admin ใช้งานหน้านี้
    exit();
}

// 2. ข้อมูลการเชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";

$conn = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($conn, "utf8");

if (!$conn) {
    die("❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

$current_uid = mysqli_real_escape_string($conn, $_SESSION['UID']);
$message = "";
$address_to_edit = null; // ตัวแปรสำหรับเก็บข้อมูลที่อยู่ที่จะแก้ไข

// 3. **Logic การจัดการข้อมูล (CRUD Operations)**

// 3.1. การเพิ่ม/แก้ไขที่อยู่
if (isset($_POST['save_address'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address_line = mysqli_real_escape_string($conn, $_POST['address_line']);
    $district = mysqli_real_escape_string($conn, $_POST['district']);
    $province = mysqli_real_escape_string($conn, $_POST['province']);
    $postal_code = mysqli_real_escape_string($conn, $_POST['postal_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $address_id = isset($_POST['address_id']) ? mysqli_real_escape_string($conn, $_POST['address_id']) : null;

    if ($is_default) {
        // ถ้าตั้งเป็น default ต้องยกเลิก default ของที่อยู่เดิมทั้งหมดก่อน
        $sql_clear_default = "UPDATE address SET is_default = FALSE WHERE UID = '$current_uid'";
        mysqli_query($conn, $sql_clear_default);
    }

    if ($address_id) {
        // อัปเดตที่อยู่ที่มีอยู่แล้ว (Update)
        $sql = "UPDATE address SET 
                    Fullname = '$fullname', 
                    Phone = '$phone', 
                    AddressLine = '$address_line', 
                    District = '$district', 
                    Province = '$province', 
                    PostalCode = '$postal_code', 
                    is_default = '$is_default'
                WHERE AddressID = '$address_id' AND UID = '$current_uid'";
        $action_message = "แก้ไขที่อยู่สำเร็จ!";
    } else {
        // เพิ่มที่อยู่ใหม่ (Create)
        $sql = "INSERT INTO address (UID, Fullname, Phone, AddressLine, District, Province, PostalCode, is_default)
                VALUES ('$current_uid', '$fullname', '$phone', '$address_line', '$district', '$province', '$postal_code', '$is_default')";
        $action_message = "เพิ่มที่อยู่ใหม่สำเร็จ!";
    }

    if (mysqli_query($conn, $sql)) {
        $message = "✅ " . $action_message;
    } else {
        $message = "❌ เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
}

// 3.2. การลบที่อยู่
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = mysqli_real_escape_string($conn, $_GET['id']);
    
    // ป้องกันการลบที่อยู่เริ่มต้น ถ้าเป็นที่อยู่เดียวที่มีอยู่
    $sql_check_count = "SELECT COUNT(*) FROM address WHERE UID = '$current_uid'";
    $res_count = mysqli_fetch_row(mysqli_query($conn, $sql_check_count))[0];
    
    $sql_check_default = "SELECT is_default FROM address WHERE AddressID = '$id_to_delete' AND UID = '$current_uid'";
    $res_default = mysqli_fetch_assoc(mysqli_query($conn, $sql_check_default));

    if ($res_default && $res_default['is_default'] == 1 && $res_count > 1) {
        $message = "⚠️ กรุณาตั้งที่อยู่เริ่มต้นใหม่ก่อนลบที่อยู่เริ่มต้นปัจจุบัน";
    } elseif ($res_count == 1) {
        // อนุญาตให้ลบได้ แต่ต้องตรวจสอบด้วยว่าระบบรองรับการไม่มีที่อยู่ไหม (แนะนำให้แจ้งให้เพิ่มใหม่)
        $sql_delete = "DELETE FROM address WHERE AddressID = '$id_to_delete' AND UID = '$current_uid'";
        if (mysqli_query($conn, $sql_delete)) {
            $message = "✅ ลบที่อยู่สำเร็จแล้ว (กรุณาเพิ่มที่อยู่ใหม่เพื่อสั่งซื้อสินค้า)";
        } else {
            $message = "❌ เกิดข้อผิดพลาดในการลบ: " . mysqli_error($conn);
        }
    } else {
        // ลบที่อยู่ที่ไม่ใช่ Default
        $sql_delete = "DELETE FROM address WHERE AddressID = '$id_to_delete' AND UID = '$current_uid'";
        if (mysqli_query($conn, $sql_delete)) {
            $message = "✅ ลบที่อยู่สำเร็จแล้ว";
        } else {
            $message = "❌ เกิดข้อผิดพลาดในการลบ: " . mysqli_error($conn);
        }
    }
    // ใช้ header redirect เพื่อล้าง parameter
    header("location: manage_address.php?msg=" . urlencode(substr($message, 0, 100)));
    exit();
}

// 3.3. การตั้งค่าเป็นที่อยู่เริ่มต้น
if (isset($_GET['action']) && $_GET['action'] == 'set_default' && isset($_GET['id'])) {
    $id_to_set = mysqli_real_escape_string($conn, $_GET['id']);

    // เริ่ม Transaction เพื่อป้องกันปัญหาการแข่งขัน (Race Condition)
    mysqli_begin_transaction($conn);
    try {
        // 1. ยกเลิก Default เดิม
        $sql_clear = "UPDATE address SET is_default = FALSE WHERE UID = '$current_uid'";
        if (!mysqli_query($conn, $sql_clear)) {
            throw new Exception("Error clearing default: " . mysqli_error($conn));
        }

        // 2. ตั้งค่า Default ใหม่
        $sql_set = "UPDATE address SET is_default = TRUE WHERE AddressID = '$id_to_set' AND UID = '$current_uid'";
        if (!mysqli_query($conn, $sql_set)) {
            throw new Exception("Error setting default: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        $message = "✅ ตั้งค่าที่อยู่เริ่มต้นสำเร็จแล้ว";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "❌ เกิดข้อผิดพลาดในการตั้งค่า: " . $e->getMessage();
    }
    header("location: manage_address.php?msg=" . urlencode(substr($message, 0, 100)));
    exit();
}

// 3.4. การดึงข้อมูลเพื่อแก้ไข (Pre-fill Form)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_to_edit = mysqli_real_escape_string($conn, $_GET['id']);
    $sql_edit = "SELECT * FROM address WHERE AddressID = '$id_to_edit' AND UID = '$current_uid'";
    $result_edit = mysqli_query($conn, $sql_edit);
    $address_to_edit = mysqli_fetch_assoc($result_edit);
    
    if (!$address_to_edit) {
        $message = "❌ ไม่พบที่อยู่ที่ต้องการแก้ไข หรือไม่มีสิทธิ์เข้าถึง";
    }
}

// 4. **การดึงรายการที่อยู่ทั้งหมดของผู้ใช้**
$sql_addresses = "SELECT * FROM address WHERE UID = '$current_uid' ORDER BY is_default DESC, AddressID ASC";
$result_addresses = mysqli_query($conn, $sql_addresses);

// 5. ดักจับข้อความแจ้งเตือนจากการ Redirect
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

// 6. ปิดการเชื่อมต่อ
mysqli_close($conn); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการที่อยู่จัดส่ง | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .address-card { border-left: 5px solid transparent; }
        .is-default { border-left-color: #0d6efd; background-color: #e9f0ff; }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="container my-5">
        <h1 class="mb-4 d-flex justify-content-between align-items-center">
            <i class="bi bi-geo-alt-fill me-2"></i> จัดการที่อยู่จัดส่ง
            <a href="profile.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> กลับไปจัดการบัญชี</a>
        </h1>

        <?php if ($message): ?>
            <div class="alert <?php echo (strpos($message, '✅') !== false) ? 'alert-success' : ((strpos($message, '❌') !== false) ? 'alert-danger' : 'alert-warning'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-<?php echo $address_to_edit ? 'warning' : 'primary'; ?> text-white">
                <h5 class="mb-0"><i class="bi bi-<?php echo $address_to_edit ? 'pencil-square' : 'plus-circle-fill'; ?> me-2"></i> <?php echo $address_to_edit ? 'แก้ไขที่อยู่' : 'เพิ่มที่อยู่ใหม่'; ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_address.php" method="POST">
                    <input type="hidden" name="address_id" value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['AddressID']) : ''; ?>">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="fullname" class="form-label">ชื่อผู้รับ</label>
                            <input type="text" name="fullname" id="fullname" class="form-control" required 
                                value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['Fullname']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" id="phone" class="form-control" required 
                                value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['Phone']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address_line" class="form-label">บ้านเลขที่/ถนน/ซอย</label>
                        <input type="text" name="address_line" id="address_line" class="form-control" required 
                            value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['AddressLine']) : ''; ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="district" class="form-label">อำเภอ/เขต</label>
                            <input type="text" name="district" id="district" class="form-control" required 
                                value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['District']) : ''; ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="province" class="form-label">จังหวัด</label>
                            <input type="text" name="province" id="province" class="form-control" required 
                                value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['Province']) : ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="postal_code" class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" name="postal_code" id="postal_code" class="form-control" required 
                                value="<?php echo $address_to_edit ? htmlspecialchars($address_to_edit['PostalCode']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" 
                            <?php echo ($address_to_edit && $address_to_edit['is_default']) ? 'checked' : ''; ?>
                            <?php echo (!$address_to_edit && mysqli_num_rows($result_addresses) == 0) ? 'checked disabled' : ''; ?>>
                        <label class="form-check-label" for="is_default">
                            ตั้งเป็นที่อยู่จัดส่งเริ่มต้น
                        </label>
                    </div>

                    <button type="submit" name="save_address" class="btn btn-<?php echo $address_to_edit ? 'warning' : 'success'; ?> me-2">
                        <i class="bi bi-save me-1"></i> <?php echo $address_to_edit ? 'บันทึกการแก้ไข' : 'เพิ่มที่อยู่'; ?>
                    </button>
                    <?php if ($address_to_edit): ?>
                        <a href="manage_address.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i> ยกเลิก</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <h3 class="mt-5 mb-3"><i class="bi bi-list-task me-2"></i> รายการที่อยู่ทั้งหมด</h3>
        <div class="row g-4">
            <?php if (mysqli_num_rows($result_addresses) > 0): ?>
                <?php while($address = mysqli_fetch_assoc($result_addresses)): ?>
                    <div class="col-lg-6">
                        <div class="card address-card h-100 shadow-sm <?php echo $address['is_default'] ? 'is-default' : ''; ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title d-flex justify-content-between align-items-start">
                                    <span><?php echo htmlspecialchars($address['Fullname']); ?></span>
                                    <?php if ($address['is_default']): ?>
                                        <span class="badge bg-primary ms-2"><i class="bi bi-star-fill me-1"></i> เริ่มต้น</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="card-text mb-1"><i class="bi bi-telephone-fill me-2"></i> <?php echo htmlspecialchars($address['Phone']); ?></p>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($address['AddressLine']) . ", " 
                                               . htmlspecialchars($address['District']) . ", " 
                                               . htmlspecialchars($address['Province']) . " " 
                                               . htmlspecialchars($address['PostalCode']); ?>
                                </p>
                                <div class="mt-auto pt-3 border-top">
                                    <a href="manage_address.php?action=edit&id=<?php echo $address['AddressID']; ?>" class="btn btn-sm btn-warning me-2"><i class="bi bi-pencil-fill"></i> แก้ไข</a>
                                    
                                    <?php if (!$address['is_default']): ?>
                                        <a href="manage_address.php?action=set_default&id=<?php echo $address['AddressID']; ?>" class="btn btn-sm btn-primary me-2"><i class="bi bi-check-circle-fill"></i> ตั้งเป็นเริ่มต้น</a>
                                    <?php endif; ?>

                                    <a href="manage_address.php?action=delete&id=<?php echo $address['AddressID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบที่อยู่จัดส่งนี้?');"><i class="bi bi-trash-fill"></i> ลบ</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle display-6"></i>
                        <p class="mt-2">คุณยังไม่มีที่อยู่จัดส่ง กรุณาเพิ่มที่อยู่ใหม่ด้านบน</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>