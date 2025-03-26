<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }

    // Database connection details
    $host = '';
    $user = '';
    $pass = ''; 
    $dbname = '';

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Failed to connect: " . $conn->connect_error);
    }

    $errorMessage = "";
    $messageType = "error";
    $redirect = false;
    $redirectPage = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(array_key_exists('formUserInfo',$_POST)) {
            // Sanitize form input
            $first_name = $conn->real_escape_string($_POST['first']);
            $last_name = $conn->real_escape_string($_POST['last']);
            $email = $conn->real_escape_string($_POST['email']);
            $username = $conn->real_escape_string($_POST['username']);
            $birth_date = isset($_POST['bday']) ? $conn->real_escape_string($_POST['bday']) : NULL;
            $city = $conn->real_escape_string($_POST['city']);
            $state = $conn->real_escape_string($_POST['state']);
            $country = $conn->real_escape_string($_POST['country']);
            $profile_picture = "uploads/blankProfile.png";

            // Check if the new username is unique
            $checkSql = "SELECT username FROM users WHERE username = ? AND username<> ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ss", $username, $_SESSION['username']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $errorMessage = "Username already taken.";
                $messageType = "error";
            } else {
                // $sql for prepared statement to update user
                $sql = "UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?, username = ?,
                    birth_date = ?, city = ?, id_state = ?, id_country = ?,
                    profile_picture = ? WHERE username = ?";
                if($stmt = $conn->prepare($sql)) {
                    // Bind the parameters
                    $stmt->bind_param("ssssssssss", $first_name, $last_name, $email, $username, $birth_date, $city, $state, $country, $profile_picture, $_SESSION['username']); 
                    // Execute the query
                    if ($stmt->execute()) {
                        // Check if username was updated
                        $redirect = false;
                        if ($_SESSION['username'] != $username) {
                            $_SESSION['username'] = $username; // Update session username
                            session_destroy(); // Destroy current session
                            $redirect = true;
                            $errorMessage = "Username updated successfully. Redirecting to login...";
                            $messageType = "success";
                            $redirectPage = "login.php";
                        } else {
                            $errorMessage = "User succesfully updated";
                            $messageType = "success";
                        }
                    } else {
                        $errorMessage = "Could not execute prepared statement";
                        $messageType = "error";
                    }
                    $stmt->close();
                }
            }
            $checkStmt->close();
        }
        
        if(array_key_exists('formPassword',$_POST)) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $stmt->close();
                if (password_verify($_POST['oldPassword'], $user['password'])) {
                    $newPassword = password_hash($_POST['confirmPassword'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $stmt->bind_param("ss", $newPassword, $_SESSION['username']);
                    if ($stmt->execute()) {
                        $redirect = true;
                        $errorMessage = "Password successfully updated. Redirecting to login...";
                        $messageType = "success";
                        $redirectPage = "login.php";
                    } else {
                        $errorMessage = "Could not update password";
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $errorMessage = "Invalid old password";
                    $messageType = "error";
                }
            } else {
                $errorMessage = "Invalid username";
                $messageType = "error";
                $stmt->close();
            }
        }

        if (isset($_POST['uploadPicture'])) {
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($_FILES["picture"]["name"]);
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if file is an actual image or fake image
                if (in_array($fileType, ['jpg', 'png', 'jpeg'])) {
                    if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                        $profile_picture = $target_file;
                        // Update database record
                        $sql = "UPDATE users SET profile_picture = ? WHERE username = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ss", $profile_picture, $_SESSION['username']);
                        if ($stmt->execute()) {
                            $redirect = false;
                            $errorMessage = "Profile picture updated successfully.";
                            $messageType = "success";
                        } else {
                            $errorMessage = "Error updating profile picture: " . $stmt->error;
                            $messageType = "error";
                        }
                        $stmt->close();
                    } else {
                        $errorMessage = "Sorry, there was an error uploading your file.";
                        $messageType = "error";
                    }
                } else {
                    $errorMessage = "Only JPG, JPEG, PNG files are allowed.";
                    $messageType = "error";
                }
            } else {
                $errorMessage = "No file uploaded or file upload error.";
                $messageType = "error";
            }
        }        
    }

    if (!empty($errorMessage)) {
        // The error message will be used in the HTML to display the error
    }

    $sql = "SELECT users.*, states.stateCode, countries.countryCode FROM users 
            JOIN states ON users.id_state = states.stateCode 
            JOIN countries ON users.id_country = countries.countryCode 
            WHERE username = ? LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->free_result();
        $stmt->close();
    }

    $thisUser = [
        "first_name" => $row["first_name"],
        "last_name" => $row["last_name"],
        "email" => $row["email"],
        "username" => $row["username"],
        "birth_date" => $row["birth_date"],
        "city" => $row["city"],
        "state" => $row["stateCode"],
        "country" => $row["countryCode"],
        "profile_picture" => $row["profile_picture"]
    ];


?>
<!DOCTYPE html>
<!-- 
User Account Settings (user_acct_settings)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Harr: 
Ty Steinbach:
1/25/25
Revisions: 
1/25/25: Dylan Theis created php session verifier and html doc outline
2/08/25: Dylan Theis created news ticker (css, html, JS) began working on creating weather api as well
2/09/25: Dylan Theis linked API and wrote (css, html, JS) for weather api for items such as city, temperature, SVGs, forecast boxes
2/11/25: Dylan Theis made advancements on weather, features such as feels like, city time, added cityName api to find whatever city based on coords. Improved getClothingRec function
2/12/25: Dylan Theis added wind and made small edits(colors, etc)
02/14/25: Ty Steinbach started a wireframe and started implimenting forms
02/15/25: Ty Steinbach added full PHP functionality 
02/16/25: Ty Steinbach styled
02/27/25: Ty Steinbach ensured first select options are disabled
-->
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Settings</title>
        <link rel="stylesheet" href="userAcctStyles.css">
        
        <script type="module">
            import {eventOptAmerica} from './src/stateDisplayHandler.js';
            
            //DOM constants
            const form = document.getElementById("formPassword");
            const txtReset = document.getElementById('txtPassword');
            const txtConfirm = document.getElementById('txtConfirmPassword');
            const message = document.getElementById('message');
            const optCountry = document.getElementById('optCountry');
            const settings = document.getElementsByClassName('settingsOption');

            const debouncedCheck = debounce(checkPass, 400);

            function debounce(func, delay) {
                let timeoutId;
                return function(...args) {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        func.apply(this, args);
                    }, delay);
                };
            }

            function checkPass() {
                const resetValue = txtReset.value;
                const confirmVal = txtConfirm.value;

                if(resetValue != confirmVal){
                    message.innerText = "Password doesn't match";
                }
                else if(confirmVal.length < 8) {
                    message.innerText = "Password is too short";
                }
                else {
                    message.innerText = "";
                }
            }


            if (optCountry.value != "") {
                eventOptAmerica(optCountry.value);
            }
            


            //Ensures correct input when entering in first password field
            txtReset.addEventListener("input", debouncedCheck);

            //Ensures correct input when entering in second password field
            txtConfirm.addEventListener("input", debouncedCheck);

            //Ensures correct input when submitting form
            form.addEventListener("submit", (event) => {
                event.preventDefault(); //Prevents button default behavior
                const resetValue = txtReset.value;
                const confirmVal = txtConfirm.value;
                
                if((resetValue == "") || (confirmVal == "")) {
                    message.innerText = "Please fill all fields";
                }
                else if(resetValue != confirmVal){
                    message.innerText = "Passwords do not match";
                }
                else if(confirmVal.length < 8) {
                    message.innerText = "Password is too short";
                }
                else {
                    form.submit();
                }
            });

            //Add event listener to the country select element to see if USA was selected
            optCountry.addEventListener('change', (event) => eventOptAmerica(event.target.value));

            for(var i = 0; i < settings.length; i++) {
                settings[i].addEventListener('click', (event) => {
                    if (event.target.innerText === "User Settings") {
                        window.location.href = "user_acct_settings.php";
                    } else if (event.target.innerText === "Dashboard Settings") {
                        console.log("Dashboard Settings");
                    }
                });
            }   
        </script>

        <script>
        document.getElementById('dashboardLink').addEventListener('click', function() {
            window.location.href = 'home.php';  // Adjust URL as needed
        });
        </script>
    </head>
    
    <body>
        <div class="main-container">
            <span id="dashboardLink">🡰 Dashboard</span>
            <div class="message-container <?php echo !empty($errorMessage) ? ($messageType == 'success' ? 'success' : 'error') : ''; ?>" style="<?php echo !empty($errorMessage) ? 'display:block;' : 'display:none;'; ?>">
                <?php echo $errorMessage; ?>
            </div>

            <div class="credentials-container">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    
                    <h2>User Information</h2>

                    <label for="first-name">First Name</label>
                    <input type="text" id="first-name" name="first" placeholder="Enter your first name" required>

                    <label for="last-name">Last Name</label>
                    <input type="text" id="last-name" name="last" placeholder="Enter your last name" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>

                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Create a username" required>

                    <label for="bday">Birth Date (Optional)</label>
                    <input type="date" id="bday" name="bday" placeholder="Your birth date">
                        
                        
                    <!-- Country select -->
                    <label for="country">Country</label>
                    <select name="country" id="optCountry">
                        <option disabled selected value>Choose Country</option>
                        <?php 
                            $sql = "SELECT * FROM countries ORDER BY country";
                            if($stmt = $conn->prepare($sql)) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $stmt->free_result();
                                $stmt->close();
                            }
                            while($row = $result->fetch_assoc()) {
                                $selected = ($thisUser['country'] == $row['countryCode']) ? ' selected' : '';
                                echo "<option value='" . $row['countryCode'] . "'" . $selected . ">" . $row['country'] . "</option>\n";
                            }
                        ?>
                    </select>

                    <label for="state">State</label>
                    <select name="state" id="optState">
                        <option disabled selected value>Choose State</option>
                        <?php 
                            $sql = "SELECT * FROM states ORDER BY state";
                            if($stmt = $conn->prepare($sql)) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while($row = $result->fetch_assoc()) {
                                    $selected = ($thisUser['state'] == $row['stateCode']) ? ' selected' : '';
                                    echo "<option value='" . $row['stateCode'] . "'" . $selected . ">" . $row['state'] . "</option>\n";
                                }
                                $stmt->free_result();
                                $stmt->close();
                            }
                        ?>
                    </select>

                    <!--City text input-->
                    <label for="city">City</label>
                    <input type="text" id="city" name="city">

                    <button type="submit" id="submit">Submit</button>
                    <input type="hidden" name="formUserInfo" value="true">
                </form>
            </div>

            <div class="password-container">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    <h2>Password Change</h2>
                    
                    <div class="formPass">
                        <label for="txtOldPassword">Confirm Old Password</label>
                        <input type="password" id="txtOldPassword" name="oldPassword" placeholder="Old password" minlength="8" required>
                    </div>
                    <div class="formPass">
                        <label for="txtPassword">New Password</label>
                        <input type="password" id="txtPassword" name="newPassword" placeholder="Create a password" minlength="8" required>
                    </div>
                    <div class="formPass">
                        <label for="txtConfirmPassword">Confirm New Password</label>
                        <input type="password" id="txtConfirmPassword" name="confirmPassword" placeholder="Confirm your password" minlength="8" required>
                    </div>
                    
                    <button type="submit" id="submit">Submit</button>
                    <input type="hidden" name="formPassword" value="true">

                    <div id="message" class="error"></div>
                </form>

                <div class="profile-picture-container">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                        <h2>Profile Picture (PNG and JPEG)</h2>
                        <input type="file" id="filProfilePic" name="picture" accept="image/png, image/jpeg">

                        <div id="profileContainer">
                            <img src="<?php echo htmlspecialchars($thisUser['profile_picture']); ?>?<?php echo time(); ?>" alt="Profile Picture">
                        </div>

                        <button type="submit" id="submit">Upload Picture</button>
                        <input type="hidden" name="uploadPicture" value="true">
                    </form>
                </div>
            </div>
        </main>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handling Redirects
            <?php if ($redirect && $redirectPage): ?>
                setTimeout(function() {
                    window.location.href = "<?php echo $redirectPage; ?>"; // Use the PHP variable to control the redirect
                }, 5000);
            <?php endif; ?>

            // Handling Message Disappearance
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer && messageContainer.style.display !== 'none') {
                setTimeout(function() {
                    messageContainer.style.display = 'none'; // Hide the message
                }, 5000); // Adjust as necessary
            }
        });

        document.getElementById('dashboardLink').addEventListener('click', function() {
            window.location.href = 'home.php';  // Redirects to the home.php page
        });
        </script>
    </body>

    <script>
        function setInputValue(name, value) {
            const element = document.getElementById(name);
            if(element) {
                element.value = value;
            }
        }

        // Setting initial values from PHP variables
        setInputValue('first-name', '<?php echo $thisUser["first_name"]; ?>');
        setInputValue('last-name', '<?php echo $thisUser["last_name"]; ?>');
        setInputValue('email', '<?php echo $thisUser["email"]; ?>');
        setInputValue('username', '<?php echo $thisUser["username"]; ?>');
        setInputValue('bday', '<?php echo $thisUser["birth_date"]; ?>');
        setInputValue('optCountry', '<?php echo $thisUser["country"]; ?>');
        setInputValue('city', '<?php echo $thisUser["city"]; ?>');
        setInputValue('optState', '<?php echo $thisUser["state"]; ?>'); // Ensure you have a similar mechanism for state if needed
    </script>
</html>