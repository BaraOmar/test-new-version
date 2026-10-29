<?php
session_start();
require_once "config/database.php";

// ================= DATABASE CONNECTION =================
$conn = new mysqli("localhost", "root", "", "recruitment_management");
if ($conn->connect_error) {
    die("Database connection failed");
}

$error = "";

// ================= HANDLE LOGIN =================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, password_hash
             FROM users
             WHERE username = ? AND role_id = 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password_hash"])) {
                // LOGIN SUCCESS
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role_id"] = 1;

                header("Location: Recruiter_Dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recruiter Login - RMS Portal</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#137fec"
                    },
                    fontFamily: {
                        display: ["Manrope", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Manrope', sans-serif; }
    </style>
</head>

<body class="bg-background-light min-h-screen flex items-center justify-center">

<div class="w-full max-w-md bg-white p-10 rounded-xl shadow-xl border">

    <h2 class="text-3xl font-extrabold mb-3">Recruiter Sign In</h2>
    <p class="text-slate-500 mb-6">Enter your recruiter credentials</p>

    <!-- Error Message -->
    <?php if ($error): ?>
        <p class="mb-4 text-red-600 text-sm font-medium"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">

        <!-- Username -->
        <div>
            <label class="block text-sm font-bold mb-2">Username</label>
            <input
                type="text"
                name="username"
                required
                class="w-full px-4 py-3 border rounded-lg bg-slate-50"
                placeholder="Enter your username"
            />
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-bold mb-2">Password</label>
            <input
                type="password"
                name="password"
                required
                class="w-full px-4 py-3 border rounded-lg bg-slate-50"
                placeholder="••••••••"
            />
        </div>

        <button
            type="submit"
            class="w-full bg-primary text-white font-extrabold py-3 rounded-lg hover:bg-primary/90">
            Recruiter Sign In
        </button>
    </form>

</div>

</body>
</html>
