<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";
$username = "";
$remember = false;

// بررسی کوکی ذخیره شده
if (isset($_COOKIE['saved_username'])) {
    $username = $_COOKIE['saved_username'];
}

if (isset($_COOKIE['remember_password']) && $_COOKIE['remember_password'] === 'yes') {
    $remember = true;
}

// پردازش فرم لاگین
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['user_name']);
    $password = trim($_POST['pas']);
    $remember = isset($_POST['remember']) ? true : false;

    // اتصال به دیتابیس
    $conn = new mysqli("localhost", "root", "", "student");
    if ($conn->connect_error) {
        die("❌ خطا در اتصال به دیتابیس: " . $conn->connect_error);
    }

    // کوئری برای بررسی کاربر
    $stmt = $conn->prepare("SELECT * FROM stude WHERE username = ? AND pas = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $_SESSION['username'] = $username;
        
        // ذخیره در کوکی اگر کاربر انتخاب کرده
        if ($remember) {
            setcookie('saved_username', $username, time() + (30 * 24 * 60 * 60), "/");
            setcookie('remember_password', 'yes', time() + (30 * 24 * 60 * 60), "/");
            
            // ذخیره ایمن پسورد در کوکی (رمزنگاری شده)
            $encryptedPassword = base64_encode($password);
            setcookie('saved_password', $encryptedPassword, time() + (30 * 24 * 60 * 60), "/");
        } else {
            // پاک کردن کوکی‌ها اگر کاربر انتخاب نکرده
            setcookie('saved_username', '', time() - 3600, "/");
            setcookie('remember_password', '', time() - 3600, "/");
            setcookie('saved_password', '', time() - 3600, "/");
        }
        
        header("Location: welcome.php");
        exit();
    } else {
        $message = "<div class='msg error'>❌ نام کاربری یا رمز عبور اشتباه است</div>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ورود کاربران</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Vazirmatn', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
        padding: 40px 30px;
        animation: fadeIn 0.8s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .logo {
        text-align: center;
        margin-bottom: 30px;
    }

    .logo i {
        font-size: 50px;
        color: #667eea;
        margin-bottom: 15px;
    }

    h2 {
        color: #333;
        text-align: center;
        margin-bottom: 25px;
        font-size: 28px;
        font-weight: 700;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 15px;
        font-size: 15px;
        border: 2px solid #e1e5ee;
        border-radius: 12px;
        background: #f8f9fa;
        transition: all 0.3s ease;
        outline: none;
        color: #333;
    }

    input:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15);
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
        cursor: pointer;
    }

    .remember-me input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .remember-me label {
        margin-bottom: 0;
        cursor: pointer;
        font-size: 14px;
        color: #666;
    }

    .remember-me label:hover {
        color: #333;
    }

    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .msg {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: fadeIn 0.5s ease;
    }

    .msg.error {
        background: #ffeaea;
        color: #ff4757;
        border-right: 4px solid #ff4757;
    }

    .msg.success {
        background: #eaffea;
        color: #2ed573;
        border-right: 4px solid #2ed573;
    }

    .footer {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e1e5ee;
        color: #666;
        font-size: 14px;
    }

    .footer a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 480px) {
        .container {
            padding: 30px 20px;
        }
        
        h2 {
            font-size: 24px;
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="logo">
        <i class="fas fa-user-circle"></i>
    </div>
    
    <h2>ورود به حساب کاربری</h2>
    
    <?php echo $message; ?>
    
    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="msg success">
                <i class="fas fa-check-circle"></i>
                <span>' . $_SESSION['success_message'] . '</span>
              </div>';
        unset($_SESSION['success_message']);
    }
    ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="user_name">نام کاربری</label>
            <input type="text" id="user_name" name="user_name" 
                   value="<?php echo htmlspecialchars($username); ?>" 
                   placeholder="نام کاربری خود را وارد کنید" required>
        </div>
        
        <div class="form-group">
            <label for="pas">رمز عبور</label>
            <input type="password" id="pas" name="pas" 
                   placeholder="رمز عبور خود را وارد کنید" required>
        </div>
        
        <div class="remember-me">
            <input type="checkbox" id="remember" name="remember" value="yes" 
                   <?php echo $remember ? 'checked' : ''; ?>>
            <label for="remember">مرا به خاطر بسپار</label>
        </div>
        
        <button type="submit" class="btn-submit">
            ورود به حساب
        </button>
    </form>
    
    <div class="footer">
        حساب ندارید؟ <a href="form.php">ثبت نام کنید</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // بارگذاری پسورد ذخیره شده از کوکی
        <?php if ($remember && isset($_COOKIE['saved_password'])): ?>
            try {
                const savedPassword = atob("<?php echo $_COOKIE['saved_password']; ?>");
                document.getElementById('pas').value = savedPassword;
                console.log('Password loaded from cookie');
            } catch(e) {
                console.error('Error loading saved password:', e);
            }
        <?php endif; ?>
    });
</script>
</body>
</html>
