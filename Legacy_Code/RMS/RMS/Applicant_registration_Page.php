<?php

session_start();
require_once "config/database.php";

// ================= DATABASE CONNECTION =================
$conn = new mysqli("localhost", "root", "", "recruitment_management");
if ($conn->connect_error) {
    die("Database connection failed");
}

$error = "";
$success = "";

// ================= HANDLE FORM SUBMISSION =================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role_id = 2; // Applicant

            $stmt = $conn->prepare(
                "INSERT INTO users (role_id, full_name, email, password_hash)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("isss", $role_id, $full_name, $email, $password_hash);
            $stmt->execute();

            $success = "Account created successfully. You can now log in.";
        }
    }
}
?>
<!DOCTYPE html>

<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Applicant Registration | RMS Portal</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
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

<body class="bg-background-light min-h-screen flex flex-col">

<header class="bg-white border-b px-6 py-4 flex items-center gap-3">
    <div class="text-primary font-bold text-xl">RMS Portal</div>
</header>

<main class="flex-1 flex items-center justify-center p-6">
    <div class="w-full max-w-[500px]">

        <h1 class="text-3xl font-extrabold text-center mb-6">Create Your Account</h1>

        <div class="bg-white rounded-xl shadow-xl border p-8">

            <!-- Messages -->
            <?php if ($error): ?>
                <p class="mb-4 text-red-600 font-medium"><?= $error ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="mb-4 text-green-600 font-medium"><?= $success ?></p>
            <?php endif; ?>

            <!-- FORM -->
            <form method="POST" class="space-y-5">

                <!-- Full Name -->
                <div>
                    <label class="text-sm font-bold">Full Name</label>
                    <input
                        type="text"
                        name="full_name"
                        required
                        class="w-full mt-1 px-4 py-3 border rounded-lg"
                        placeholder="Enter your full name"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="text-sm font-bold">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        required
                        class="w-full mt-1 px-4 py-3 border rounded-lg"
                        placeholder="example@university.edu"
                    />
                </div>

                <!-- Password -->
                <div>
                    <label class="text-sm font-bold">Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full mt-1 px-4 py-3 border rounded-lg"
                        placeholder="Min. 8 characters"
                    />
                </div>

                <!-- Terms -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" required>
                    <span class="text-xs">I agree to the Terms & Privacy Policy</span>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/90">
                    Create Account
                </button>
            </form>

            <!-- Login Link -->
            <p class="text-center text-sm mt-6">
                Already have an account?
                <a href="Applicant_login_Page.php" class="text-primary font-bold">Log in</a>
            </p>
        </div>
    </div>
</main>

</body>
</html>
