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
-->



<?php
session_start();

$_SESSION['reset'] = 0;
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
        $stmt = $conn->prepare("SELECT * FROM dashboard_login WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        // Retrieve credentials
        $stmt->execute();
        $result = $stmt->get_result();

        // Verify username exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify correct password
            if (hash('sha256', $password) === $user['password']) {
                // Log user in and progress them to homepage
                $_SESSION['username'] = $user['username'];

                //If the user is reseting password
                if($user['resetting'] === 1) {
                    //Change session variable to appropriate value and change location to reset_password page
                    $_SESSION['reset'] = 1;
                    header("Location: reset_password.php");
                    exit();
                }
                else {
                    //Else, log in
                    header("Location: home.php");
                    exit();
                }
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
</head>
<!-- TODO: EVENTUAL STYLES NEEDED -->


<body>
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
