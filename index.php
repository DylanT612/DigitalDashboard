<!-- 
Login page(index)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Harr: 
Ty Steinbach:
1/25/25
Revisions: 
1/25/25: Dylan Theis created php db connection and html doc outline
02/04/25: Ty Steinbach added PHP to ensure reset_password functionality when needed
02/05/25: Keagan Haar created a styling CSS
-->



<?php
session_start();


// Database connection details
$host = '';
$user = '';
$pass = '';
$dbname = '';

$conn = new mysqli($host, $user, $pass, $dbname);

// If host, db, user, or pass is incorrect create error
if ($conn->connect_error) {
    die("Failed to connect: " . $conn->connect_error);
}



// Login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Submits username and password
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check filled in
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        // Retrieve credentials
        $stmt->execute();
        $result = $stmt->get_result();

        // Verify username exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify correct password
            if (password_verify($_POST['password'], $user['password'])) {
                // Log user in and progress them to homepage
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                header("Location: home.php");
                exit();

            } else {
                $error = "Invalid password";
            }

        } else {
            $error = "Invalid username";
        }
        $stmt->close();

    // If no input for user and pass
    } else {
        $error = "Please fill in all fields";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <head>
    <style>
        /* Apply styles to the entire page */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            color: white;
            overflow: hidden;
        }

        /* Background container */
        .background {
            position: absolute;
            width: 105%;
            height: 105%;
            background: url('./Images/Geo.jpg');
            filter: blur(5px);
            z-index: -1; /* Makes background lower stack order than content */
        }

        /* Add a slightly sharper content area */
        .login-container {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 15px;
        }

        /* Style headers */
        h1, h2, p {
            margin: 10px;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="login-container">
        <div class="profile-pic"></div>
        <h2>Login</h2>

        <!-- if login info incorrect show corresponding error -->
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- form information posted and verfied via DB -->
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        
        <p><a href="forgot_password.php">Forgot Password?</a></p>
        <p><a href="register.php">Create New User</a></p>
    </div>
</body>
</html>
