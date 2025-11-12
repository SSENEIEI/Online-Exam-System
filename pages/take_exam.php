<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กำลังทำข้อสอบ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body id="take-exam-page">
    <div id="timer-container" class="hidden">
        <svg class="timer-svg" viewBox="0 0 100 100">
            <g class="timer-circle">
                <circle class="timer-bg" cx="50" cy="50" r="45"></circle>
                <path id="timer-progress-bar" class="timer-progress-bar"
                    d="M 50, 50 m -45, 0 a 45,45 0 1,0 90,0 a 45,45 0 1,0 -90,0">
                </path>
            </g>
        </svg>
        <span id="timer-countdown"></span>
    </div>

    <div class="container">
        <header id="exam-header">
            <!-- Exam title will be loaded here -->
        </header>
        <main>
            <form id="exam-form">
                <!-- Questions will be loaded here -->
            </form>
            <button type="submit" form="exam-form" id="submit-exam-btn">ส่งคำตอบ</button>
        </main>
    </div>
    <script src="../assets/js/student.js"></script>
</body>
</html>
