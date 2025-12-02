<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اتصال به دیتابیس
$host = 'localhost';
$dbname = 'student';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// گرفتن شناسه دانش‌آموز از URL
if (!isset($_GET['id'])) {
    die("کاربر مشخص نشده است.");
}
$user_id = (int)$_GET['id'];

// لیست دروس پیش‌فرض
$lessons = ['فارسی','ریاضی','قرآن','دینی','تاریخ','هنر','ورزش'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['score'], $_POST['name_dars']) && is_numeric($_POST['score'])) {
        $score = (int)$_POST['score'];
        $name_dars = trim($_POST['name_dars']);

        if ($score < 0 || $score > 20) {
            $message = "نمره باید بین 0 تا 20 باشد.";
        } elseif (empty($name_dars)) {
            $message = "لطفاً نام درس را وارد کنید.";
        } else {
            // بررسی وجود نمره قبلی برای همان دانش‌آموز و همان درس
            $stmtCheck = $pdo->prepare("SELECT id FROM studen WHERE user_id=? AND name_dars=?");
            $stmtCheck->execute([$user_id, $name_dars]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                // بروزرسانی نمره
                $stmtUpdate = $pdo->prepare("UPDATE studen SET score=? WHERE user_id=? AND name_dars=?");
                $stmtUpdate->execute([$score, $user_id, $name_dars]);
            } else {
                // ثبت نمره جدید
                $stmtInsert = $pdo->prepare("INSERT INTO studen (user_id, name_dars, score) VALUES (?,?,?)");
                $stmtInsert->execute([$user_id, $name_dars, $score]);
            }

            $message = "نمره برای درس {$name_dars} با موفقیت ثبت شد ✅";
        }
    } else {
        $message = "لطفاً درس و نمره معتبر انتخاب کنید.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت نمره</title>
<style>
body { font-family: 'Vazirmatn', sans-serif; background:#121212; color:#eee; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;}
.container { background:#222; padding:30px; border-radius:14px; width:350px; box-shadow:0 0 20px #4a7cff88; text-align:center;}
h2 { color:#4a7cff; margin-bottom:15px;}
input, select, button { width:100%; padding:10px; margin:10px 0; border-radius:10px; border:none; font-size:16px; }
input, select { background:#111; color:#eee; box-shadow: inset 2px 2px 6px #1a1a1a, inset -2px -2px 6px #2a2a2a;}
button { background:#4a7cff; color:#fff; cursor:pointer; transition:.3s; box-shadow:0 0 15px #4a7cff;}
button:hover { background:#2a50c7; }
.message { margin-top:10px; padding:10px; border-radius:8px; }
.success { background:#004400; color:#0f0; box-shadow:0 0 15px #00ff66;}
.error { background:#440000; color:#f00; box-shadow:0 0 15px #ff3333;}
</style>
</head>
<body>

<div class="container">
<h2>ثبت نمره</h2>

<form method="post">
<!-- لیست انتخاب درس و امکان تایپ -->
<input list="lessons" name="name_dars" placeholder="انتخاب یا تایپ درس" required>
<datalist id="lessons">
<?php foreach($lessons as $lesson): ?>
    <option value="<?php echo $lesson; ?>">
<?php endforeach; ?>
</datalist>

<!-- انتخاب نمره -->
<select name="score" required>
<option value="">انتخاب نمره</option>
<?php for($i=0;$i<=20;$i++): ?>
    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php endfor; ?>
</select>

<button type="submit">ثبت نمره</button>
</form>

<?php if($message): ?>
<div class="message <?php echo strpos($message,'موفقیت')!==false ? 'success':'error'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>
</div>

</body>
</html>
