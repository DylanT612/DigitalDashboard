<!-- 
Create New User Page (register)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Haar: haark@csp.edu
Ty Steinbach:
1/25/25
Revisions: 
1/25/25: Dylan Theis created html doc outline
2/4/25: Keagan Haar created styling and input html
02/16/25: Ty Steinbach added PHP functionality to change the default value for a user profile pic
-->
<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = 'mysql'; 
$dbname = 'csc450temp';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize form input
    $first_name = $conn->real_escape_string($_POST['first']);
    $last_name = $conn->real_escape_string($_POST['last']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $birth_date = isset($_POST['bday']) ? $conn->real_escape_string($_POST['bday']) : NULL;
    $city = $conn->real_escape_string($_POST['city']);
    $state = $conn->real_escape_string($_POST['state']);
    
    // Handle file upload (profile picture)
    $profile_picture = "uploads/blankProfile.png";
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["picture"]["name"]);
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if file is an image
        if (in_array($fileType, ['jpg', 'png', 'jpeg'])) {
            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                $profile_picture = $target_file;
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "Only JPG, PNG, and JPEG files are allowed.";
        }
    }

    // Insert user data into database
    $sql = "INSERT INTO users (first_name, last_name, email, username, password, birth_date, city, state, profile_picture)
            VALUES ('$first_name', '$last_name', '$email', '$username', '$password', '$birth_date', '$city', '$state', '$profile_picture')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: /index.php"); // Redirect us back to our index (home page) upon successful creation
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-image: url(./Images/Geo.jpg);
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        form {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        h2 {
            text-align: center;
            font-weight: bold;
            font-size: 40px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }


        input, select, button {
            width: 97%;
            padding: 10px;
            margin-bottom: 15px;
        }

        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            font-size: 1em;
            margin-top: 10px;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>

<h2>Create Account</h2>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">

    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="first" placeholder="Enter your first name" required>

    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="last" placeholder="Enter your last name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required>

    <label for="username">Username</label>
    <input type="text" id="username" name="username" placeholder="Create a username" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Create a password" minlength="8" required>

    <label for="confirm-password">Confirm Password</label>
    <input type="password" id="confirm-password" name="confirm" placeholder="Confirm your password" minlength="8" required>

    <label for="bday">Birth Date (Optional)</label>
    <input type="date" id="bday" name="bday" placeholder="Enter your birth date">

    <label for="city">City</label>
    <input type="text" id="city" name="city" placeholder="Enter the city you reside in" required>

    <label for="state">State</label>
    <input type="text" id="state" name="state" placeholder="Enter the state you reside in" required>

    <label for="profile-picture">Profile Picture (PNG and JPEG Files)</label>
    <input type="file" id="profile-picture" name="picture" accept="image/png, image/jpg">

    <button type="submit">Create Account</button>
</form>


<script>
    const form = document.querySelector('form');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');

    // Function to display or hide error messages
    function handleError(input, message) {
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error')) {
            errorElement = document.createElement('span');
            errorElement.classList.add('error');
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        errorElement.textContent = message || '';
    }

    form.addEventListener('submit', function (e) {
        let valid = true;
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Assure that our password is intially 8 characters long
        if (password.length < 8) {
            handleError(passwordInput, 'Password must be at least 8 characters long.');
            valid = false;
        } else {
            handleError(passwordInput);
        }

        // Confirm that passwords do match
        if (confirmPassword !== password) {
            handleError(confirmPasswordInput, 'Passwords do not match.');
            valid = false;
        } else {
            handleError(confirmPasswordInput);
        }

        // If we error out, ensure the form cannot be submitted untils changes are made
        if (!valid) e.preventDefault();
    });
</script>

</body>
</html>
