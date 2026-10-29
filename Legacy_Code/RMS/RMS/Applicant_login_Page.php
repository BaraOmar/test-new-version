<?php
session_start();

// ================= DATABASE CONNECTION =================
$conn = new mysqli("localhost", "root", "", "recruitment_management");
if ($conn->connect_error) {
    die("Database connection failed");
}

$error = "";

// ================= HANDLE LOGIN =================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, password_hash, role_id 
             FROM users 
             WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // role_id = 2 → Applicant
            if ($user["role_id"] != 2) {
                $error = "This login is for applicants only.";
            } elseif (!password_verify($password, $user["password_hash"])) {
                $error = "Invalid email or password.";
            } else {
                // LOGIN SUCCESS
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role_id"] = $user["role_id"];

                header("Location: Applicant_dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Applicant Login | Academic RMS</title>

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

<body class="bg-background-light min-h-screen flex items-center justify-center">

<div class="w-full max-w-[420px] bg-white p-8 rounded-xl shadow-xl border">

    <h2 class="text-3xl font-extrabold text-center mb-6">Applicant Login</h2>

    <!-- Error Message -->
    <?php if ($error): ?>
        <p class="mb-4 text-red-600 text-sm font-medium text-center">
            <?= $error ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="space-y-5">

        <!-- Email -->
        <div>
            <label class="text-sm font-semibold">Email</label>
            <input
                type="email"
                name="email"
                required
                class="w-full mt-1 px-4 py-3 border rounded-lg"
                placeholder="Enter your email"
            />
        </div>

        <!-- Password -->
        <div>
            <label class="text-sm font-semibold">Password</label>
            <input
                type="password"
                name="password"
                required
                class="w-full mt-1 px-4 py-3 border rounded-lg"
                placeholder="Enter your password"
            />
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/90">
            Sign In
        </button>
    </form>

    <!-- Register -->
    <p class="text-center text-sm mt-6">
        New here?
        <a href="Applicant_registration_Page.php" class="text-primary font-bold">
            Create an account
        </a>
    </p>

</div>

</body>
</html>
