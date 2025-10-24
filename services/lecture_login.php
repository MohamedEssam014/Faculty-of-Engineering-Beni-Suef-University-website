<?php
session_start();
$error_message = '';

define('LECTURE_USERNAME', 'teacher');
define('LECTURE_PASSWORD', 'LecturePass123');
define('TEACHER_USER_ID', 2); 

if (isset($_SESSION['is_lecture_manager']) && $_SESSION['is_lecture_manager'] === true) {
    header("Location: teacher_lectures.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === LECTURE_USERNAME && $password === LECTURE_PASSWORD) {
        $_SESSION['is_lecture_manager'] = true;
        $_SESSION['lecture_teacher_id'] = TEACHER_USER_ID;
        $_SESSION['lecture_teacher_name'] = "مدرس المحاضرات"; 

        header("Location: teacher_lectures.php");
        exit;
    } else {
        $error_message = "خطأ في اسم المستخدم أو كلمة المرور.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>تسجيل دخول - إدارة المحاضرات</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bsu_dark_blue: '#1e40af',
                        bsu_light_gray: '#f3f4f6',
                        bsu_medium_blue: '#3b82f6',
                        bsu_gold: '#FFC107',
                        danger_bg: '#f8d7da',
                        danger_border: '#f5c6cb',
                        danger_text: '#721c24',
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                         slideInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
                        'slide-in-down': 'slideInDown 0.5s ease-out forwards',
                    }
                },
            },
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'cairo', sans-serif;
        }
        header { 
            border-bottom: 3px solid #FFC107;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        header .container {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1200px; margin: 0 auto; padding: 0.75rem 1rem;
        }
        header .logo { display: flex; align-items: center; }
        header .logo img { width: 3rem; height: 3rem; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        header .logo h1 { font-size: 1.25rem; font-weight: 700; color: #1e40af; margin-right: 0.75rem; }
        
        main { padding-top: 3rem; padding-bottom: 3rem; }
        .page-content {
             max-width: 500px; margin: 40px auto;
             background-color: white; padding: 2rem;
             border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;
        }
        .form-group input[type="text"], .form-group input[type="password"] {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #D1D5DB;
            border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group input:focus {
            outline: none; border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .submit-btn {
            display: inline-flex; justify-content: center; align-items: center;
            width: 100%; padding: 0.75rem 1.5rem; background-color: #1e40af;
            color: white; font-weight: 700; border-radius: 0.375rem; border: none;
            cursor: pointer; transition: background-color 0.3s ease;
        }
        .submit-btn:hover { background-color: #3b82f6; }
        
        footer { 
            background-color: #1f2937; /* gray-800 */
            color: white; text-align: center;
            padding: 2rem 1rem; margin-top: 3rem;
        }
        footer .container { max-width: 1200px; margin: 0 auto; }
    </style>
</head>

<body class="bg-bsu_light_gray text-gray-800">

    <header>
        <div class="container">
            <div class="logo">
                <a href="../index.html" class="flex items-center space-x-3 rtl:space-x-reverse group">
                    <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة" class="transition-transform duration-300 group-hover:scale-110">
                    <h1 class="group-hover:text-bsu_medium_blue transition-colors duration-200">كلية الهندسة</h1>
                </a>
            </div>
            <nav>
                <a href="../index.html" class="text-gray-600 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">العودة للرئيسية</a>
            </nav> 
        </div>
    </header>

    <main>
        <div class="container">
            <section class="page-content animate-fade-in-up">
                <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue mb-4 text-center">تسجيل الدخول - إدارة المحاضرات</h2>

                <?php if (!empty($error_message)): ?>
                    <div class="p-4 mb-5 rounded-md text-center font-semibold text-danger_text bg-danger_bg border border-danger_border animate-slide-in-down">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="lecture_login.php" method="post" class="contact-form border-t border-gray-200 pt-6">
                    <div class="form-group">
                        <label for="username">اسم المستخدم</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">كلمة المرور</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn text-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i> تسجيل الدخول
                    </button>
                    <div class="text-center mt-4 p-3 bg-bsu_light_gray rounded-md border border-gray-200">
                        <small class="text-gray-600 text-sm">
                            بيانات الدخول (للتجربة):<br>
                            <strong class="text-bsu_dark_blue">teacher</strong> / <strong class="text-bsu_dark_blue">LecturePass123</strong>
                        </small>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <span id="currentYear"></span> كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>