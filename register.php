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
-->

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
    </style>
</head>
<body>

<h2>Create Account</h2>

<form action="submit" method="POST">
    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="first" placeholder="Enter your first name" required>

    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="last" placeholder="Enter your last name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required>

    <label for="username">Username</label>
    <input type="text" id="username" name="username" placeholder="Create a password" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Create a password" required>

    <label for="confirm-password">Confirm Password</label>
    <input type="password" id="confirm-password" name="confirm" placeholder="Confirm your password" required>

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

</body>
</html>