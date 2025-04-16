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
02/27/25: Ty Steinbach added more secure SQL statement with added fields to include country functionality, along with the required HTML and JS
-->
<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = 'mysql'; 
$dbname = 'dashboardDB';

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
    $country = $conn->real_escape_string($_POST['country']);
    
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

    // $sql for prepared statement
    $sql = "INSERT INTO users (first_name, last_name, email, username, password, birth_date, city, id_state, id_country, profile_picture) VALUES (?,?,?,?,?,?,?,?,?,?)";
    // Prepare
    if($stmt = $conn->prepare($sql)) {
        // Bind the parameters
        $stmt->bind_param("ssssssssss", $first_name, $last_name, $email, $username, $password, $birth_date, $city, $state, $country, $profile_picture); 
        if($stmt->errno) {
            print_r("stmt prepare( ) had error."); 
        }
        // Execute the query
        $stmt->execute();
        if($stmt->errno) {
            print_r("Could not execute prepared statement");
            $result = false;
        }
        else {
            $result = true;
        }

        // Free results
        $stmt->free_result( );

        // Close the statement
        $stmt->close( );
    }

    if ($result) {
        header("Location: login.php"); // Redirect us back to our index (home page) upon successful creation
        exit();
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Account</title>
        <style>
            html, body {
                margin: 0;
                padding: 0;
                overscroll-behavior: none;
            }

            html {
                background: url('./Images/digital.jpg') no-repeat center center fixed;
                background-size: cover;
                position: relative;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
                color: #EFEFEF;
            }

            .main-container {
                background-color: rgba(50, 50, 50, 0.8);
                padding: 20px;
                border-radius: 20px;
                width: 700px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.5);
                margin: 50px auto;
            }

            input, select {
                width: 97%;
                padding: 10px;
                margin-bottom: 15px;
            }

            button {
                background-color: #4CAF50; /* Green button */
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s;
                width: 100%; /* Full width button */
            }

            button:hover {
                background-color: #45a049;
            }

            /* Style for all buttons and links styled as buttons */
            .link-button {
                display: inline-block;
                background-color: rgba(50, 50, 50, 0.8); /* Same as the login-container for perfect blending */
                color: white;
                padding: 10px 15px;
                margin: 5px 0;
                border-radius: 5px;
                text-decoration: none; /* Removes underline for anchor tags */
                border: none; /* Ensures no visible borders */
                cursor: pointer; /* Cursor indicates clickability on hover */
                text-align: center; /* Centers text within buttons */
                width: 100%; /* Full width to match container */
                box-sizing: border-box; /* Includes padding in width calculation */
                transition: all 0.3s ease; /* Smooth transition for color and transform */
            }

            .link-button:hover {
                background-color: rgba(80, 80, 80, 0.9); /* Subtly lighter on hover to indicate interactivity */
                transform: translateY(-2px); /* Subtle lift effect on hover */
                box-shadow: 0 2px 5px rgba(0,0,0,0.3); /* Adds shadow on hover for depth */
            }

        </style>
    </head>
    <script type="module">
        import { eventOptAmerica } from './src/stateDisplayHandler.js';

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

        function submitEvent(event) {
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
            if (!valid) event.preventDefault();
        }

        //Add event listener to the country select element to see if USA was selected
        optCountry.addEventListener('change', (event) => eventOptAmerica(event.target.value));
    </script>

    <body>
        <div class="main-container">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">

                <h1>Create Account</h1>

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

                <!-- Country select -->
                <label for="country">Country</label>
                <select name="country" id="optCountry" value="">
                    <!-- Dropdown menu of all countries -->
                    <option disabled selected value>Choose Country</option>
                    <?php 
                        $sql = "SELECT * FROM countries ORDER BY country";
                        if($stmt = $conn->prepare($sql)) {
                            $stmt->execute();
                            if($stmt->errno) {
                                print_r("Could not execute prepared statement");
                            }
                            $result = $stmt->get_result();
                            $stmt->free_result();
                            $stmt->close();
                        }
                        while($row = $result->fetch_assoc()) {    
                            echo "<option value='" . $row['countryCode'] . "'>" . $row['country'] . "</option>\n";
                        }
                    ?>
                </select>

                <!-- State select -->
                <select name="state" id="optState" hidden>
                    <!-- Dropdown menu of all states -->
                    <option disabled selected value>Choose State</option>
                    <?php 
                        $sql = "SELECT * FROM states";
                        if($stmt = $conn->prepare($sql)) {
                            $stmt->execute();
                            if($stmt->errno) {
                                print_r("Could not execute prepared statement");
                            }
                            $result = $stmt->get_result();
                            $stmt->free_result();
                            $stmt->close();
                        }
                        while($row = $result->fetch_assoc()) {    
                            echo "<option value='" . $row['stateCode'] . "'>" . $row['state'] . "</option>\n";
                        }
                    ?>
                </select>

                <!--City text input-->
                <label for="txtCity">City</label>
                <input type="text" id="txtCity" name="city" placeholder="Enter the city you reside in">

                <label for="profile-picture">Profile Picture (PNG and JPEG Files)</label>
                <input type="file" id="profile-picture" name="picture" accept="image/png, image/jpg">

                <button type="submit">Create Account</button>
                <p><a href="login.php" class="link-button">Back to Login</a></p>
            </form>

        <script>
            form.addEventListener('submit', submitEvent);
        </script>

        </main>
    </body>
</html>