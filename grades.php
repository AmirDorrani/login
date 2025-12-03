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

// بررسی وجود ستون date_update
$check_column = $pdo->prepare("SHOW COLUMNS FROM studen LIKE 'date_update'");
$check_column->execute();
if (!$check_column->fetch()) {
    $pdo->exec("ALTER TABLE studen ADD COLUMN date_update DATETIME NULL");
}

// گرفتن id دانش‌آموز
if (!isset($_GET['id'])) die("کاربر مشخص نشده است.");
$user_id = (int)$_GET['id'];

// مشخصات دانش‌آموز
$stmt = $pdo->prepare("SELECT id, f_name, l_name FROM stude WHERE id=?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$userData) die("دانش‌آموز یافت نشد.");

// متغیرها
$lessons = ['فارسی','ریاضی','قرآن','دینی','تاریخ','هنر','ورزش'];
$message = '';
$edit_mode = false;
$edit_data = null;

// گرفتن نمرات
$stmt_scores = $pdo->prepare("SELECT id, name_dars, score, date_time, date_update FROM studen WHERE user_id=? ORDER BY id DESC");
$stmt_scores->execute([$user_id]);
$scores = $stmt_scores->fetchAll(PDO::FETCH_ASSOC);

// حالت ویرایش
if (isset($_GET['edit']) && !empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt_edit = $pdo->prepare("SELECT id, name_dars, score FROM studen WHERE id=? AND user_id=?");
    $stmt_edit->execute([(int)$_GET['edit'], $user_id]);
    $edit_data = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    if ($edit_data) $edit_mode = true;
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // ویرایش
        if (isset($_POST['score']) && is_numeric($_POST['score'])) {
            $score = (int)$_POST['score'];
            $edit_id = (int)$_POST['edit_id'];
            
            if ($score < 0 || $score > 20) {
                $message = "نمره باید بین 0 تا 20 باشد.";
            } else {
                $current_datetime = date('Y-m-d H:i:s');
                $stmtUpdate = $pdo->prepare("UPDATE studen SET score=?, date_update=? WHERE id=? AND user_id=?");
                $stmtUpdate->execute([$score, $current_datetime, $edit_id, $user_id]);
                
                if ($stmtUpdate->rowCount() > 0) {
                    header("Location: ?id=" . $user_id . "&success=edited");
                    exit();
                } else {
                    $message = "خطا در ویرایش نمره!";
                }
            }
        }
    } else {
        // ثبت جدید
        if (isset($_POST['score'], $_POST['name_dars']) && is_numeric($_POST['score'])) {
            $score = (int)$_POST['score'];
            $name_dars = trim($_POST['name_dars']);
            
            if ($score < 0 || $score > 20) {
                $message = "نمره باید بین 0 تا 20 باشد.";
            } elseif (empty($name_dars)) {
                $message = "لطفاً نام درس را وارد کنید.";
            } else {
                $current_datetime = date('Y-m-d H:i:s');
                
                $stmtCheck = $pdo->prepare("SELECT id FROM studen WHERE user_id=? AND name_dars=?");
                $stmtCheck->execute([$user_id, $name_dars]);
                $exists = $stmtCheck->fetch();

                if ($exists) {
                    $stmtUpdate = $pdo->prepare("UPDATE studen SET score=?, date_update=? WHERE id=?");
                    $stmtUpdate->execute([$score, $current_datetime, $exists['id']]);
                    $message = "نمره برای درس {$name_dars} با موفقیت به‌روزرسانی شد ✅";
                } else {
                    $stmtInsert = $pdo->prepare("INSERT INTO studen (user_id, name_dars, score, date_time) VALUES (?,?,?,?)");
                    $stmtInsert->execute([$user_id, $name_dars, $score, $current_datetime]);
                    $message = "نمره برای درس {$name_dars} با موفقیت ثبت شد ✅";
                }
            }
        }
    }
}

// پیام موفقیت
if (isset($_GET['success']) && $_GET['success'] == 'edited') {
    $message = "نمره با موفقیت ویرایش شد ✅";
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت و ویرایش نمرات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: #111; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        
        .container { background: #1a1a1a; padding: 30px; border-radius: 20px; width: 100%; max-width: 800px; box-shadow: 0 0 25px #ff1a1a99; }
        h2 { color: #ff4d4d; text-align: center; margin-bottom: 25px; font-size: 28px; }
        h3 { color: #ff9999; margin: 25px 0 15px; font-size: 22px; }
        
        .user-info { background: #220000; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; font-size: 18px; }
        
        form { margin-bottom: 25px; }
        input, button, .edit-btn { width: 100%; padding: 12px; margin: 8px 0; border-radius: 10px; border: none; font-size: 16px; }
        input { background: #222; color: #ff4d4d; text-align: center; }
        input:focus { outline: none; box-shadow: 0 0 10px #ff1a1a; }
        button { background: linear-gradient(145deg, #ff1a1a, #b30000); color: #fff; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: linear-gradient(145deg, #b30000, #ff1a1a); transform: translateY(-2px); }
        
        .edit-btn { background: linear-gradient(145deg, #ff9900, #cc7a00); color: #fff; padding: 8px 15px; width: auto; font-size: 14px; display: inline-block; margin: 0 5px; }
        .edit-btn:hover { background: linear-gradient(145deg, #cc7a00, #ff9900); }
        
        .message { padding: 12px; border-radius: 10px; margin: 15px 0; font-weight: bold; }
        .success { background: #330000; color: #ff4d4d; }
        .error { background: #4d0000; color: #ff9999; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #330000; color: #ff4d4d; padding: 12px; border-bottom: 2px solid #ff1a1a; }
        td { padding: 10px; border-bottom: 1px solid #444; background: #222; }
        tr:hover td { background: #2a0000; }
        
        .datetime-info { font-size: 12px; color: #ff9999; margin-top: 3px; }
        
        .lesson-display { background: #333; color: #ff9999; padding: 12px; border-radius: 10px; margin-bottom: 12px; text-align: center; }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            table { font-size: 14px; }
            th, td { padding: 8px 5px; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>📝 ثبت و ویرایش نمرات</h2>
    
    <div class="user-info">
        <strong>دانش‌آموز:</strong> 
        <?php echo htmlspecialchars($userData['f_name'] . ' ' . $userData['l_name']); ?>
    </div>
    
    <h3><?php echo $edit_mode ? '✏️ ویرایش نمره' : '➕ ثبت نمره جدید'; ?></h3>
    
    <form method="post">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
            <div class="lesson-display">
                درس: <strong><?php echo htmlspecialchars($edit_data['name_dars']); ?></strong>
            </div>
            <input type="number" name="score" min="0" max="20" placeholder="نمره جدید" value="<?php echo $edit_data['score']; ?>" required>
            <button type="submit" style="background:linear-gradient(145deg, #ff9900, #cc7a00);">
                💾 ذخیره تغییرات
            </button>
            <a href="?id=<?php echo $user_id; ?>" style="display:block; text-align:center; margin-top:10px; color:#ff9999; text-decoration:none;">
                ❌ لغو ویرایش
            </a>
        <?php else: ?>
            <input list="lessons" name="name_dars" placeholder="انتخاب یا تایپ درس" required>
            <datalist id="lessons">
                <?php foreach($lessons as $lesson): ?>
                    <option value="<?php echo $lesson; ?>">
                <?php endforeach; ?>
            </datalist>
            <input type="number" name="score" min="0" max="20" placeholder="نمره (0 تا 20)" required>
            <button type="submit">ثبت نمره</button>
        <?php endif; ?>
    </form>
    
    <?php if($message): ?>
        <div class="message <?php echo strpos($message,'موفقیت')!==false || strpos($message,'ویرایش')!==false ? 'success':'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($scores)): ?>
        <h3>📊 نمرات ثبت شده</h3>
        <table>
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>نام درس</th>
                    <th>نمره</th>
                    <th>تاریخ ثبت</th>
                    <th>آخرین ویرایش</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($scores as $index => $score): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($score['name_dars']); ?></td>
                    <td><?php echo $score['score']; ?></td>
                    <td>
                        <?php echo date('Y/m/d H:i', strtotime($score['date_time'])); ?>
                        <div class="datetime-info">اولین ثبت</div>
                    </td>
                    <td>
                        <?php if(!empty($score['date_update'])): ?>
                            <?php echo date('Y/m/d H:i', strtotime($score['date_update'])); ?>
                            <div class="datetime-info">آخرین ویرایش</div>
                        <?php else: ?>
                            <span style="color:#666;">--</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?id=<?php echo $user_id; ?>&edit=<?php echo $score['id']; ?>" class="edit-btn">
                            ✏️ ویرایش
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="color:#ff9999; text-align:center; padding:20px; background:#222; border-radius:10px; margin-top:20px;">
            هنوز نمره‌ای ثبت نشده است.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
