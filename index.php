<!-- 
Login page(index)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Haar: haark@csp.edu
Ty Steinbach: 
1/25/25
Revisions: 
1/25/25: Dylan Theis created php db connection and html doc outline
02/04/25: Ty Steinbach added PHP to ensure reset_password functionality when needed
02/05/25: Keagan Haar created a styling CSS
02/16/25: Ty Steinbach changed hash() to password_hash() for security and changed comparison to password_verify, changed table to users

-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="indexStyles.css">
</head>

<body>
    <section class="tile white">
        <h1>Welcome to My Digital Dashboard</h1>
        <p><a href="login.php" class="link-button">Login</a></p>
    </section>
    <section class="tile black">
        <h1>About Us</h1>
        <p>This section has a black background with white text.</p>
    </section>
    <section class="tile white">
        <h1>Our Services</h1>
        <p>Back to a white background for this section.</p>
    </section>
    <section class="tile black">
        <h1>Contact Us</h1>
        <p>Final section with a black background.</p>
    </section>
</body>
</body>
</html>