<?php
session_start();

// اتصال به دیتابیس
$conn = new mysqli("localhost", "root", "", "student");

if ($conn->connect_error) {
    $_SESSION['error_message'] = "خطا در اتصال به دیتابیس";
    header("Location: form.php");
    exit();
}

// دریافت اطلاعات از فرم
$f_name = $_POST['f_name'];
$l_name = $_POST['l_name'];
$fa_name = $_POST['fa_name'];
$n_code = $_POST['n_code'];
$username = $_POST['username'];
$pas = $_POST['pas'];
$save_password = isset($_POST['save_password']) ? $_POST['save_password'] : 'no';

// اعتبارسنجی کد ملی
if (!preg_match('/^\d{10}$/', $n_code)) {
    $_SESSION['error_message'] = "کد ملی باید ۱۰ رقم باشد";
    header("Location: form.php");
    exit();
}

// اعتبارسنجی نام کاربری
if (strlen($username) < 4) {
    $_SESSION['error_message'] = "نام کاربری باید حداقل ۴ کاراکتر باشد";
    header("Location: form.php");
    exit();
}

// بررسی تکراری نبودن نام کاربری
$check_stmt = $conn->prepare("SELECT id FROM stude WHERE username = ?");
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error_message'] = "این نام کاربری قبلاً ثبت شده است";
    header("Location: form.php");
    exit();
}
$check_stmt->close();

// درج اطلاعات در دیتابیس
$stmt = $conn->prepare("INSERT INTO stude (f_name, l_name, fa_name, n_code, username, pas) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $f_name, $l_name, $fa_name, $n_code, $username, $pas);

if ($stmt->execute()) {
    // ذخیره کوکی اگر کاربر انتخاب کرده
    if ($save_password === 'yes') {
        // رمزنگاری پسورد
        $encryptedPassword = base64_encode($pas);
        
        // ذخیره در کوکی برای ۳۰ روز
        setcookie('saved_username', $username, time() + (30 * 24 * 60 * 60), "/");
        setcookie('saved_password', $encryptedPassword, time() + (30 * 24 * 60 * 60), "/");
        setcookie('remember_password', 'yes', time() + (30 * 24 * 60 * 60), "/");
    }
    
    $_SESSION['success_message'] = "ثبت نام با موفقیت انجام شد! اکنون می‌توانید وارد شوید.";
    $_SESSION['new_username'] = $username;
    
    // هدایت به صفحه لاگین با پیام موفقیت
    header("Location: login.php?registered=1&username=" . urlencode($username));
} else {
    $_SESSION['error_message'] = "خطا در ثبت اطلاعات: " . $stmt->error;
    header("Location: form.php");
}

$stmt->close();
$conn->close();
?>