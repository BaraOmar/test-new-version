<?php
require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/config/auth.php";
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey Submitted</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">

<div class="bg-white shadow-lg rounded-xl p-10 text-center max-w-md">

<h1 class="text-2xl font-semibold text-green-600 mb-4">
✅ Survey Submitted
</h1>

<p class="text-gray-600 mb-6">
Thank you for completing the survey.
Your feedback has been recorded.
</p>

<a href="Employee_dashboard.php"
class="bg-[#eb652c] hover:bg-[#d85a24] text-white px-6 py-2 rounded-xl shadow-md transition-all">
Back to Dashboard
</a>

</div>

</body>
</html>