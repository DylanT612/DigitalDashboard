<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'usernot authenticated']);
        exit();
    }

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
    
    $sender_id = $_SESSION['user_id'] ?? null;
    // For each action
    if (isset($_GET['action']) || isset($_POST['action'])) {
        // Set content type
        header('Content-Type: application/json');

        // If the action is check messages
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_messages') {
            // Confirm user signed in
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['error' => 'User not logged in']);
                exit;
            }
            
            // Run query
            $userId = $_SESSION['user_id'];
            $query = "SELECT COUNT(*) as newMessages FROM messages WHERE receiver_id = ? AND seen = 0";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                // Display messages
                if ($result) {
                    echo json_encode(['newMessages' => $result['newMessages']]);
                } else {
                    echo json_encode(['newMessages' => 0]);
                }
                $stmt->close();
            } else {
                echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
            }
            exit;
        }

        // If action is search users
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'search_users' && isset($_GET['query'])) {
            // Display 10 max usernames
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE username LIKE CONCAT('%', ?, '%') LIMIT 10");
            $stmt->bind_param("s", $_GET['query']);
            $stmt->execute();
            $result = $stmt->get_result();

            // Display results
            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            } else {
                echo json_encode(['message' => 'No users found']);
            }
            exit();
        }

        // If send message
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
            // Get variables
            $message = $_POST['message'];
            $receiver_id = $_POST['receiver_id'];
            $sender_id = $_SESSION['user_id'];
            $seen = 1;
            
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, seen) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $sender_id, $receiver_id, $message, $seen);
            
            // Run query
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Failed to send message']);
            }
            exit();
        }

        // If action is get messages
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_messages' && isset($_GET['receiver_id'])) {
            $receiver_id = $_GET['receiver_id'];
            $sender_id = $_SESSION['user_id'];
            $seen = 0;

            $query = "SELECT m.message, u.username, m.sent_at 
                    FROM messages m 
                    JOIN users u ON m.sender_id = u.id 
                    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                    OR (m.sender_id = ? AND m.receiver_id = ?) 
                    ORDER BY m.sent_at ASC";

            $stmt = $conn->prepare($query);

            if (!$stmt) {
                echo json_encode(['error' => 'SQL Prepare Error', 'query' => $query, 'sql_error' => $conn->error]);
                exit();
            }

            // Run get messages between users query
            $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $messages = $result->fetch_all(MYSQLI_ASSOC);
            
            // Display messages
            if (empty($messages)) {
                echo json_encode(['error' => 'No messages found']);
            } else {
                echo json_encode($messages);
            }
            exit();
        }
        
        // If message seen request 
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'mark_messages_seen') {
            $receiver_id = $_SESSION['user_id'];
            $sender_id = $_POST['sender_id'];
            
            
            $query = "UPDATE messages
                    SET seen = 1
                    WHERE receiver_id = ? AND sender_id = ? AND seen = 0";

            // Update message seen property
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $receiver_id, $sender_id);
            $stmt->execute();

            echo json_encode(["success" => true]);
            exit();
        } 

        // If get unread users request
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_unread_users') {
            $user_id = $_SESSION['user_id'];
            
            $query = "SELECT m.sender_id, u.username 
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.receiver_id = ? 
                    AND m.seen = 0
                    GROUP BY m.sender_id";

            // Find seen = 0 for users messages
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // Return users with messages seen = 0
            $unreadUsers = [];
            while ($row = $result->fetch_assoc()) {
                $unreadUsers[] = [
                    "id" => $row["sender_id"],
                    "username" => $row["username"]
                ];
            }

            echo json_encode($unreadUsers);
            exit();
        }
    }
?>

<!DOCTYPE html>
<!--
Home Page (home)
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
02/14/25: Ty Steinbach added profile picture and dropdown menu for account settings, including styles
02/16/25: Ty Steinbach changed the profile pic to be dynamic
02/27/25: Ty Steinbach made the weather fetch dynamic with the user's stored info
02/21/25: Dylan Theis wrote basic functions for future queries (searchUser, openChat, send button, loadMessages)
02/22/25: Dylan Theis wrote event listeners for dragging chat window, closing chat X, 
02/23/25: Dylan Theis wrote styles and PHP and confirmed workability
02/28/25: Dylan Theis wrote scrollToBottom(), newMessageIndicator
03/01/25: Dylan Theis wrote showChatNotifcation, checkForNewMessages and included PHP
03/02/25: Dylan Theis wrote tested and confirmed workability
03/14/25: Dylan Theis added friending div 
03/15/25: Dylan Theis added friending badge for users who are friends
03/16/25: Dylan Theis added styles to friending div
03/17/25: Ty Steinbach ensured a single session_start() and correct SQL references
03/25/25: Ty Steinbach made some slight bug fixes
03/26/25: Ty Steinbach added full mini-calendar functionality

References:
GNEWS API for sourcing 10 headlines 
OPEN-METEO API for sourcing weather data
NOMINATIM API for sourcing the coordinates for the weather data
-->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /*Header Styles*/
        header {
            display: flex;
            justify-content: space-between;
            margin: 20px;
        }
        h1 {
            width: fit-content;
            height: fit-content;
        }
        #profileContainer {
            background-color: lightgray;
            width: 60px;
            height: 60px;
            text-align: center;
            border-radius: 100%;
            z-index: 2;
        }
        div#profileContainer:hover {
            cursor: pointer;
        }
        #profilePic {
            margin-top: 9px;
        }
        #profileNav {
            display: none;
            grid-template-columns: 100%;
            grid-template-rows: 60px 20px;
            justify-content: space-between;
            row-gap: 10px;
            position: absolute;
            right: 20px;
            top: 15px;
            min-width: 200px;
            min-height: 250px;
            color: white;
            background-color: rgb(93, 93, 104);
            border-radius: 10px;
            padding: 10px;
            z-index: 1;
        }
        #profNavUsername {
            display: block;
            height: fit-content;
            font-size: 15pt;
            font-weight: bold;
            margin-right: 80px;
        }
        .profNav {
            text-align: center;
            display: block;
            width: 90%;
            height: fit-content;
            font-size: 15pt;
            padding: 5px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 5px;
            background-color: rgb(33, 33, 53);
        }
        .profNav:hover {
            background-color:rgb(16, 16, 26);
            cursor: pointer;
        }
        /* News Ticker Container */
        .news-ticker {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: black;
            color: white;
            overflow: hidden;
            white-space: nowrap;
            padding: 10px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        /* Scrolling Wrapper */
        .ticker-wrap {
            display: flex;
            width: max-content;
            animation: ticker-scroll 250s linear infinite; 
        }

        /* Each News Item */
        .ticker-item {
            display: inline-block;
            margin-right: 60px; 
            white-space: nowrap;
        }

        .ticker-item a {
            color: white;
            text-decoration: none;
        }

        .ticker-item a:hover {
            text-decoration: underline;
        }

        /* Scrolling Animation */
        @keyframes ticker-scroll {
            from { transform: translateX(0); } 
            to { transform: translateX(-50%); } 
        }

        /* Weather Container */
        .weather-container {
            background-color: rgb(33, 33, 53);
            position: fixed;
            right: 20px;
            top: 90px;
            width: 350px;
            height: 80vh;
            overflow: hidden;
            padding: 15px;
            border-radius: 10px;
            color: white;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        /* Weather container color based on what time it is */
        .morning { background: blue; }
        .afternoon { background: light blue; }
        .evening { background: darkblue; }
        .night { background: darkslateblue; }

        /* Weather Details */
        .weather-header { font-size: 40px; font-weight: bold; }
        .weather-today { display: flex; flex-direction: column; align-items: center; }
        .weather-icon { width: 50px; margin: 10px 0; }
        .weather-temp { font-size: 28px; font-weight: bold; }
        .weather-feels-like {font-size: 1em; margin-top: 5px; }
        .weather-desc { font-size: 16px; margin-top: 5px; display: flex; }
        .clothing-rec { font-size: 12px; margin-top: 10px; font-style: italic; display: flex; }

        /* Forecast */
        #weeklyForecast {
            display: grid; 
            grid-template-columns: repeat(2, 1fr);
            margin-top: 20px;
            gap: 10px;
        }

        /* Each forecast day box */
        .forecast-day {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            background: lightgray;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Left side of forecast day box */
        .forecast-left {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Right side for the icon */
        .forecast-right {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        /* Styling for day name and temperatures */
        .forecast-day-name {
            font-weight: bold;
            font-size: 1.2em;
            color: navy;
        }

        .forecast-temp {
            font-size: 1em;
            color: dark grey;
        }

        /* Forecast icon styling */
        .forecast-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }


    </style>

    <!-- Chat Style -->
    <style>
        /* User query dropdown */
        #chatListContainer {
            width: 150px;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            position: absolute;
        }

        /* Individual chat window header */
        #chatHeader {
            background-color: #007aff;
            color: white;
            padding: 2px;
            font-size: 18px;
            display: flex;
            align-items: center;
            border-radius: 3px;
            cursor: grab;
            text-align: center;
            justify-content: center;
            position: relative;
            font-weight: bold;
        }

        #chatHeader:hover {
            cursor: grab;
        }

        #chatHeader.on-click {
            cursor: grabbing;
        }

        /* Close button */
        #closeChat {
            font-size: 20px;
            cursor: pointer;
            user-select: none;
            color: red;
            position: absolute;
            right: 8px;
        }

        /* text area */
        #messageArea {
            margin: 2px;
            max-height: 250px;
            overflow-y: auto;
            width: 90%;
            padding: 10px;
            border: 1px solid #ccc;
            position: relative;
            overflow-y: auto;
            z-index: 1;
        }

        #messageArea p {
            margin: 5px 0;
            padding: 8px;
            border-radius: 5px;
            max-width: 80%;
        }

        #messageInput {
            width: 90%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            margin: 5px;
            padding: 10px;
            background-color: #007aff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }

        /* Each item in the dropdown */
        .chat-list-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }

        .chat-list-item:hover {
            background-color: #f1f1f1;
        }

        #individualChatWindow {
            width: 300px;
            max-height: 80vh;
            background-color: white;
            position: absolute;
            top: 20px;
            right: 300px;
            display: none;
            border: 3px solid black;
            border-radius: 5px;
            display: none;
            z-index: 100;
        }

        #messageCenter #chatApp{
            height: 50px;
            width: 50px;
            right: 120px;
            top: 25px;
            position: absolute;
        }

        #newMessageIndicator {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            cursor: pointer;
            display: none;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .message {
            font-size: 16px;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 8px;
            word-wrap: break-word;
            display: inline-block;
            max-width: 70%;
            display: flex;
        }

        .user-message {
            background-color: #007aff;
            color: white;
            text-align: right;
            margin-left: auto;
            max-width: fit-content;
        }

        .other-message {
            background-color: lightgray;
            color: black;
            text-align: left;
            margin-right: auto;
            max-width: fit-content;
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            font-size: 12px;
            padding: 4px 6px;
            border-radius: 50%;
            display: none;
        }

        .new-message {
            background-color: red;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }

        #chatWindowContainer {
            display: none;
            right: 200px;
            top: 25px;
            position: absolute;
            z-index: 1001;
        }

        #chatWindowContainer.open {
            display: block;
        }

        #searchUser {
            display: block !important;
        }
        #userList {
            margin: 3px;
        }

    </style>

    <!-- Friend Style -->
    <style>
        .dropdown {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .dropdown.show {
            display: block;
        }
        #friendsIndicator {
            height: 20px;
            width: 20px;
            display: inline-flex;
            align-items: center;
            margin-bottom: -5px;
        }
        #friendRequestButton {
            margin: 2px;
            padding: 3px;
        }
        #friendFinder {
            float: left;
            margin: 5px;
            padding: 0px 10px 5px 15px;
            border-radius: 25px;
            background-color: violet;
            border-color: darkviolet;
            border-width: 5px;
            border-style: solid;
            width: 200px;
        }
        #acceptButton {
            background-color: springgreen;
            margin: 3px;
            padding: 3px;
        }
        #declineButton {
            background-color: red;
            margin: 3px;
            padding: 3px;
        }
        #friendFinder h3, p {
            margin: 0px 0px 0px 0px;
        }
        #friendFinder input {
            background-color: pink;
        }

    </style>


    <!--Calendar Style-->
    <style>
        #todaysEvents {
            width: 225px;
            min-height: 230px;
            background-color: rgb(42, 42, 48);
            border-radius: 10px;
            position: fixed;
            top: 300px;
        }
        #todaysEvents button {
            background-color: rgb(90, 90, 192);
            cursor: pointer;
        }
        #todaysEvents button:hover {
            background-color: rgb(64, 64, 136);
            cursor: pointer;
        }
        #calendarHeader {
            display: grid;
            grid-template-columns: auto auto;
            align-items: center;
            background-color: lightcoral;
            height: fit-content;
            padding: 5px;
        }
        #calendarLink {
            grid-column: 1 / span 2;
            text-align: center;
            background-color: rgb(218, 156, 156);
            padding: 5px;
        }
        #calendarLink:hover {
            background-color: rgb(190, 122, 122);
            cursor: pointer;
        }
        #day {
            height: fit-content;
        }
        #day span {
            font-size: 15pt;
            height: fit-content;
        }
        #date {
            text-align: right;
        }
        #events {
            padding: 3px;
            list-style-type: none;
        }
        #events li {
            background-color: rgb(132, 132, 206);
            padding: 5px;
        }

        #events li:hover {
            background-color: rgb(102, 102, 160);
            cursor: pointer;
        }

        #eventEdit {
            position: absolute;
            top: 30%;
            left: 30%;
            height: 400px;
            width: 300px;
            background-color: grey;
            padding: 10px;
        }

        #eventEditInner {
            display: grid;
            gap: 10px;
        }

        #eventEdit button {
            background-color: rgb(90, 90, 192);
            cursor: pointer;
        }

        #eventEdit button:hover {
            background-color: rgb(64, 64, 136);
            cursor: pointer;
        }

        #eventEdit button:disabled {
            background-color: rgb(132, 132, 172);
        }

        #eventEdit button:disabled:hover {
            background-color: rgb(132, 132, 172);
            cursor: default;
        }

        #editHeader {
            display: grid;
            grid-template-columns: auto auto;
            align-items: center;
        }

        #editHeader h5 {
            font-size: 17pt;
            margin-top: 0;
            margin-bottom: 0;
        }

        #editHeader button {
            width: 40px;
            height: 40px;
        }

        #eventForm {
            display: grid;
            grid-template-columns: auto;
            gap: 10px;
        }

        #friendsAdded {
            min-width: 80%;
            min-height: 40px;
            margin: 10px;
            border-radius: 7px;
            background-color: lightgrey;
        }
    </style>

    <?php
    // Database connection details
    $host = '';
    $user = '';
    $pass = ''; 
    $dbname = '';

    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        die(json_encode(["error" => "Database connection failed"]));
    }
    // Ensure user is logged in
    if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
        die("User not authenticated.");
    }
    // Prepare the SQL query
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    // Set up a prepared statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $_SESSION['username']);
        // Check for statement errors
        if ($stmt->errno) {
            die("Statement prepare() error: " . $stmt->error);
        }
        // Execute the statement
        if (!$stmt->execute()) {
            die("Execution error: " . $stmt->error);
        }
        // Fetch results
        $result = $stmt->get_result();
        if (!$result) {
            die("Query failed: " . $stmt->error);
        }
        $row = $result->fetch_assoc();
        if (!$row) {
            die("User not found.");
        }
        // Store user data
        $thisUser = [
            "profile_picture" => $row["profile_picture"],
            "city" => $row['city'],
            "country" => $row['id_country'],
            "state" => $row['id_state']
        ];
        // Close the statement after fetching results
        $stmt->close();
    } else {
        die("SQL Prepare Error: " . $conn->error);
    }
    ?>






    <script>
        var profileDisplayHandler = false;        
        async function handleWeather() {
            const locationData = await searchLocation('<?php echo $thisUser["city"]; ?>', '<?php echo $thisUser["state"]; ?>', '<?php echo $thisUser["country"]; ?>');

            //If a location is found
            if(locationData.length > 0) {
                //Set lat/lon const from locationData
                const lat = locationData[0]['lat'];
                const lon = locationData[0]['lon'];

                const data = await fetchWeather(lat, lon);

                //Used for troubleshooting API
                // console.log("API Response:", data); 

                if (!data || !data.daily || !data.daily.time) {
                    console.error("No daily forecast data found in the API response.");
                    return;
                }

                // Run nominatim api to get the city name from the coordinates
                const cityName = '<?php echo $thisUser["city"]; ?>';
                document.getElementById("cityName").textContent = cityName;

                // Get city weather information
                const cityTimezone = data.timezone;
                const now = new Date();
                const cityTime = new Intl.DateTimeFormat("en-US", {
                    timezone: cityTimezone,
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true
                }).format(now);
                const currentWeather = data.current;
                const feelsLikeTemp = currentWeather.apparent_temperature;
                const dailyForecast = data.daily;
                const weatherCode = currentWeather.weather_code;
                const currentTime = new Date().getHours();
                const windSpeed = data.current.windspeed_10m;

                // Change weather container color based on time of day
                const container = document.getElementById("weatherContainer");
                if (currentTime >= 20 || currentTime < 6) {
                    container.classList.add("night");
                } else if (currentTime >= 6 && currentTime < 12) {
                    container.classList.add("morning");
                } else if (currentTime >= 12 && currentTime < 17) {
                    container.classList.add("afternoon");
                } else {
                    container.classList.add("evening");
                }

                // Set Weather Data
                document.getElementById("cityName").textContent = cityName;
                document.getElementById("weatherTime").textContent = `Today ${cityTime}`;
                document.getElementById("weatherTemp").textContent = `Actual: ${currentWeather.temperature_2m}°F`;
                document.getElementById("weatherFeelsLike").textContent = `Feels Like: ${feelsLikeTemp}°F`;
                document.getElementById("weatherDesc").textContent = getWeatherDescription(weatherCode, windSpeed);
                document.getElementById("weatherIcon").src = getWeatherIcon(weatherCode, windSpeed);
                document.getElementById("clothingRec").textContent = getClothingRecommendation(currentWeather.temperature_2m, weatherCode);

                // Clear Weekly Forecast
                const forecastContainer = document.getElementById("weeklyForecast");
                forecastContainer.innerHTML = "";

                // For each day in forcast
                for (let i = 0; i < 7; i++) {
                    // Convert the Unix timestamp to a valid date
                    const forecastDate = new Date(dailyForecast.time[i] * 1000);

                    // Get the day of the week in short format (e.g., Mon, Tue)
                    const dayOfWeek = forecastDate.toLocaleDateString("en-US", { weekday: "short" });

                    // Create a new div for the forecast day
                    const forecastDay = document.createElement("div");
                    forecastDay.classList.add("forecast-day");

                    // Add the HTML content for the forecast
                    forecastDay.innerHTML = `
                        <div class="forecast-left">
                            <div class="forecast-day-name">${dayOfWeek}</div>
                            <div class="forecast-temp">${dailyForecast.temperature_2m_max[i]}°/${dailyForecast.temperature_2m_min[i]}°</div>
                        </div>
                        <div class="forecast-right">
                            <img class="forecast-icon" src="${getWeatherIcon(dailyForecast.weather_code[i])}" alt="Forecast Icon">
                        </div>
                    `;

                    // Add the new forecast day to the container
                    forecastContainer.appendChild(forecastDay);
                }
            } else { //Else display error
                console.error("Location not found.");
            }
            
        }


        function getWeatherDescription(code, windSpeed) {
            let descriptions = {
                0: "Clear Sky", 
                1: "Mostly Clear", 
                2: "Partly Cloudy", 
                3: "Cloudy",
                45: "Foggy", 
                48: "Dense Fog",
                51: "Light Drizzle", 
                53: "Drizzle", 
                55: "Heavy Drizzle",
                61: "Light Rain", 
                63: "Rain", 
                65: "Heavy Rain",
                66: "Light Freezing Rain", 
                67: "Heavy Freezing Rain",
                71: "Light Snow", 
                73: "Snow", 
                75: "Heavy Snow", 
                77: "Snow Flurries",
                80: "Scattered Showers", 
                81: "Moderate Showers", 
                82: "Heavy Showers",
                85: "Snow Showers", 
                86: "Heavy Snow Showers",
                95: "Thunderstorm", 
                96: "Thunderstorm with Light Hail", 
                99: "Thunderstorm with Heavy Hail"
            };

            let description = descriptions[code] || "Unknown";

            // Add Wind Descriptions
            if (windSpeed >= 10 && windSpeed < 20) {
                description += " & Breezy";
            } else if (windSpeed >= 20 && windSpeed < 30) {
                description += " & Windy";
            } else if (windSpeed >= 30) {
                description += " & Very Windy";
            }

            return description;
        }


        function getWeatherIcon(code, windSpeed) {
            const icons = {
                0: "https://www.svgrepo.com/show/53719/sun.svg",
                1: "https://www.svgrepo.com/show/398416/sun-behind-small-cloud.svg",
                2: "https://www.svgrepo.com/show/398413/sun-behind-cloud.svg",
                3: "https://www.svgrepo.com/show/276635/cloudy-cloud.svg",
                45: "https://www.svgrepo.com/show/528258/fog.svg", 
                48: "https://www.svgrepo.com/show/528258/fog.svg",
                51: "https://www.svgrepo.com/show/313157/rainy.svg", 
                53: "https://www.svgrepo.com/show/313157/rainy.svg", 
                55: "https://www.svgrepo.com/show/313157/rainy.svg",
                61: "https://www.svgrepo.com/show/313157/rainy.svg",
                63: "https://www.svgrepo.com/show/313157/rainy.svg", 
                65: "https://www.svgrepo.com/show/313157/rainy.svg",
                66: "https://www.svgrepo.com/show/313157/rainy.svg", 
                67: "https://www.svgrepo.com/show/313157/rainy.svg",
                71: "https://www.svgrepo.com/show/214992/snowing-snowy.svg",
                73: "https://www.svgrepo.com/show/214992/snowing-snowy.svg", 
                75: "https://www.svgrepo.com/show/214992/snowing-snowy.svg", 
                77: "https://www.svgrepo.com/show/214992/snowing-snowy.svg",
                80: "https://www.svgrepo.com/show/313157/rainy.svg", 
                81: "https://www.svgrepo.com/show/313157/rainy.svg", 
                82: "https://www.svgrepo.com/show/313157/rainy.svg",
                85: "https://www.svgrepo.com/show/214992/snowing-snowy.svg", 
                86: "https://www.svgrepo.com/show/214992/snowing-snowy.svg",
                95: "https://www.svgrepo.com/show/474586/thunderstorm.svg"
            };
            let icon = icons[code] || "https://www.svgrepo.com/show/310222/weather-partly-cloudy-day.svg";

            // If wind is higher than 20 km/h override weather
            if (windSpeed >= 20) {
                icon = "https://www.svgrepo.com/show/276672/windy-wind.svg";
            }

            return icon;
        }



        function getClothingRecommendation(temp, weatherCode) {
            let recommendation = "";

            // Check first for rain or snow
            if ([61, 63, 65, 80, 81, 82].includes(weatherCode)) { 
                recommendation += "Bring a raincoat or umbrella. ";
            } else if ([71, 73, 75, 77, 85, 86].includes(weatherCode)) { 
                recommendation += "Wear a heavy coat, gloves, and boots. ";
            }

            // If no precipitation adjust based on temperature
            if (temp >= 85) {
                recommendation += "Wear shorts, sunglasses, sunscreen, and a light t-shirt.";
            } else if (temp >= 70) {
                recommendation += "A t-shirt, jeans, or shorts should be fine.";
            } else if (temp >= 50) {
                recommendation += "Bring a light jacket or hoodie.";
            } else if (temp >= 32) {
                recommendation += "Wear a warm coat.";
            } else {
                recommendation += "Bundle up with a heavy coat, gloves, and a hat.";
            }

            return recommendation.trim();
        }

        async function searchLocation(city, stateCode, countryCode) {
            const API_KEY = '';
            try {
                //Fetch data from API
                const response = await fetch(`http://api.openweathermap.org/geo/1.0/direct?q=${city},${stateCode},${countryCode}&limit=1&appid=${API_KEY}`);
                
                //If response is not ok, throw error
                if (!response.ok) {
                    throw new Error(`HTTP Error\nStatus: ${response.status} - ${response.statusText}`);
                }

                //Put data in constant after parsing
                const data = await response.json();
                
                //Return data
                return(data);

            } catch (error) { //Catch thrown error
                //Log and display error
                console.error("Error fetching data:", error);
            }
        }

        //Fetches weather info
        async function fetchWeather(lat, lon) {
            try {
                //Fetch data from API
                const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,apparent_temperature,weather_code&daily=temperature_2m_max,temperature_2m_min,weather_code,precipitation_sum&timezone=auto&timeformat=unixtime&temperature_unit=fahrenheit`);
                
                //If response is not ok, throw error
                if (!response.ok) {
                    throw new Error(`HTTP Error\nStatus: ${response.status} - ${response.statusText}`);
                }

                //Put data in constant after parsing
                const data = await response.json();

                //Return data
                return(data);

            } catch (error) { //Catch thrown error
                //Log and display error
                console.error("Error fetching data:", error);
                return null;
            }
        }

        // Run GNEWS API
        async function fetchNews() {
            const apiKey = '';
            const url = `https://gnews.io/api/v4/top-headlines?category=general&lang=en&country=us&max=10&apikey=${apiKey}`;

            // Get GNEWS json data
            try {
                const response = await fetch(url);
                const data = await response.json();

                if (!data.articles) {
                    console.error("Error fetching news:", data);
                    return;
                }

                const ticker = document.getElementById('newsTicker');
                ticker.innerHTML = "";

                // Create ticker content in the form of title - source
                data.articles.forEach(article => {
                    const newsItem = document.createElement('div');
                    newsItem.classList.add('ticker-item');

                    newsItem.innerHTML = `<a href="${article.url}" target="_blank">${article.title} - ${article.source.name}</a>`;

                    ticker.appendChild(newsItem);
                });

                // Duplicate ticker content to make scrolling continuous
                const clonedContent = ticker.innerHTML;
                ticker.innerHTML += clonedContent;

            } catch (error) {
                console.error("Error fetching news:", error);
            }
        }

        //Handles displaying options when clicking on profile
        function profileEvents () {
            document.getElementById("profileContainer").addEventListener("click", () => {
                console.log(profileDisplayHandler);
                profileDisplayHandler = !profileDisplayHandler;
                displayProfileOptions(profileDisplayHandler);
            });

            document.getElementById('profNavSettings').addEventListener('click', () => {
                window.location.href = 'user_acct_settings.php';
            });
        }
        function displayProfileOptions(display) {
            const profileNav = document.getElementById("profileNav");
            if (display) {
                profileNav.style.display = 'grid';
            } else {
                profileNav.style.display = 'none';
            }
        }



        //Script for calendar

        //Gets all events for this user
        async function getEvents() {
            try {
                //Fetch data from API
                const response = await fetch("EventCalendarPHP-main/fetch_events.php");
                
                //If response is not ok, throw error
                if (!response.ok) {
                    throw new Error(`HTTP Error\nStatus: ${response.status} - ${response.statusText}`);
                }

                //Put data in constant after parsing
                var data = await response.json();

                //Filters out all except today's events
                const today = new Date().toLocaleDateString('en-CA');
                data = data.filter(item => {
                    const eventDate = item.start.split(' ')[0];
                    return eventDate == today;
                });
                //Return data
                return(data);

            } catch (error) { //Catch thrown error
                //Log and display error
                console.error("Error fetching data:", error);
            }
        }

        //Displays events on mini-calendar
        function displayEvents(events) {
            const eventList = document.getElementById('events');
            eventList.innerHTML = '';

            //If there are events
            if (events.length > 0) {
                //Sets up element and appends it to eventList
                events.forEach((event) => {
                    const eventElement = document.createElement('li');
                    const date = new Date(event.start);
                    const time = date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
                    var title = event.title;

                    //Ensures shortened version of long titles
                    if (title.length > 20) {
                        title = event.title.substring(0, 17);
                        title += '...';
                    }

                    eventElement.classList.add('event');
                    eventElement.id = event.id;
                    eventElement.innerHTML = `
                        <span id='eventTimeSpan'>${time}</span>
                        <span id='eventTitleSpan'>${title}</span>
                    `;

                    eventList.appendChild(eventElement);
                })
            }
            else { //Else show empty message
                const eventElement = document.createElement('li');
                eventElement.innerHTML = `No Events Today`;

                    eventList.appendChild(eventElement);
            }
        }

        //Calls function when conditions are met
        function closeEventEvent(e) {
            if(!document.getElementById('eventEdit').contains(e.target) && document.getElementById('eventEdit').hidden == false) {
                closeEvent();
            }
        }

        //Function for clicking an event to edit or for adding an event
        function eventClick(e, events) {
            const eventEdit = document.getElementById('eventEdit');
            //If e exists (called from an event listener) then filter out the correct event
            if (e) {
                // Find the closest parent <li> (or whatever your list element is)
                const listItem = e.target.closest('li'); // Adjust selector as needed
                
                var event = events.filter(item => {
                    return item.id == listItem.id; // Use the parent's ID
                });
                event = event[0];
            }
            else { //Else choose the first one
                var event = events[0];
            }
            
            //If the edit section is hidden
            if (eventEdit.hidden == true) {
                //Unhide it
                document.getElementById('eventEdit').hidden = false;

                //Add event listener for clicking outside element
                setTimeout(() => {
                    document.addEventListener('click', closeEventEvent);
                },400);

                // Format the date using JavaScript's Date API
                const formatDate = (date) => {
                    const d = new Date(date);
                    const pad = num => num.toString().padStart(2, '0');
                    
                    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ` +
                            `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                };

                // Populate the form inputs with the event data
                document.getElementById('createdBy').textContent = "Created by: " + event.created_by_username;
                document.getElementById('eventId').value = event.id;
                document.getElementById('eventTitle').value = event.title;
                document.getElementById('startTime').value = formatDate(event.start);
                document.getElementById('endTime').value = formatDate(event.end);

                //Adds friends to form if they exist
                event.friends.forEach((friend) => {
                    addFriend(friend.username, friend.id, false, event.user_created);
                });

                // Enable or disable elements based on user_created status
                const isDisabled = !event.user_created;
                ['optFriend', 'eventTitle', 'startTime', 'endTime', 'deleteEvent', 'saveEvent'].forEach(id => {
                    document.getElementById(id).disabled = isDisabled;
                });
            }
        }

        //Resets form elements and hides edit section
        function closeEvent() {
            document.getElementById('eventEdit').hidden = true;
            document.removeEventListener('click', closeEventEvent);

            document.getElementById('friendsAdded').innerHTML = '';
            document.getElementById('friendsResults').innerHTML = '';
            document.getElementById('eventForm').reset();
        }

        //Function for when the edit form is submitted
        async function submitEvent(e) {
            e.preventDefault();

            // Create FormData from your form
            const formData = new FormData();

            //Add data to formData
            formData.append('id', document.getElementById('eventId').value);
            formData.append('title', document.getElementById('eventTitle').value);
            formData.append('start', document.getElementById('startTime').value);
            formData.append('end', document.getElementById('endTime').value);

            //Decide on url from if id exists
            const url = formData.get('id') ? 'EventCalendarPHP-main/edit_event.php' : 'EventCalendarPHP-main/add_event.php';

            // Send as regular POST data
            await fetch(url, {
                method: "POST",
                body: formData
            });


            //This handles which hidden friends to delete and which to add. 
            let friends = [];
            let friendsRemove = [];
            document.querySelectorAll('#friendsAdded div').forEach(div => {
                if (div.hidden === true) { // Check if the div is hidden
                    friendsRemove.push(div.id);
                    div.remove();
                } else if (div.classList.contains('adding')) {
                    friends.push(div.id);
                    div.classList.remove('adding');
                }
            });

            // Send added friends data using FormData
            const addFriendsData = new FormData();
            friends.forEach((friendId, index) => {
                addFriendsData.append(`friends[${index}]`, friendId);
            });

            await fetch('EventCalendarPHP-main/add_event_friends.php', {
                method: "POST",
                body: addFriendsData  // No Content-Type header needed
            });

            // Send removed friends data using FormData
            const removeFriendsData = new FormData();
            friendsRemove.forEach((friendId, index) => {
                removeFriendsData.append(`friends[${index}]`, friendId);
            });

            await fetch('EventCalendarPHP-main/delete_event_friends.php', {
                method: "POST",
                body: removeFriendsData  // No Content-Type header needed
            });

            //Reload events
            await initiateEvents();

            //Close event edit
            closeEvent();
        }

        //Function for removing event
        async function deleteEvent(e) {
            const id = document.getElementById('eventId').value; // Event ID
                
            //If id exists
            if (id) {
                try {
                    // First delete the event
                    const eventFormData = new FormData();
                    eventFormData.append('id', id);
                    
                    const eventResponse = await fetch('EventCalendarPHP-main/delete_event.php', {
                        method: "POST",
                        body: eventFormData
                    });
                    
                    if (!eventResponse.ok) throw new Error('Failed to delete event');
                    
                    // Then delete associated friends
                    const friendsFormData = new FormData();
                    friendsFormData.append('event_id', id);
                    
                    const friendsResponse = await fetch('EventCalendarPHP-main/delete_all_friends.php', {
                        method: "POST",
                        body: friendsFormData
                    });
                    
                    if (!friendsResponse.ok) throw new Error('Failed to delete friends');
                    
                    
                } catch (error) {
                    console.error('Deletion error:', error);
                    alert('Error deleting event: ' + error.message);
                }
            }

            await initiateEvents();

            closeEvent();
        }

        //Function for adding an event
        async function addEvent(e) {
            //Default date values @ midnight today and midnight tomorrow
            const midnightToday = new Date();
            midnightToday.setHours(0, 0, 0, 0); // Sets time to 00:00:00.000

            const midnightTomorrow = new Date();
            midnightTomorrow.setDate(midnightTomorrow.getDate() + 1); // Add 1 day
            midnightTomorrow.setHours(0, 0, 0, 0); // Sets time to 00:00:00.000

            //Create array of event object with appropriate info so we can reuse eventClick() 
            const event = [{
                created_by: <?php echo $_SESSION['user_id'] ?>,
                created_by_username: '<?php echo $_SESSION['username'] ?>',
                end: midnightTomorrow,
                friends: [],
                id: '',
                start: midnightToday,
                title: '',
                user_created: true
            }]

            //Open editing for event
            eventClick(null, event);
        }

        //Gets events, calls function to display them, and adds event listener to it
        async function initiateEvents() {
            var data = await getEvents();
            displayEvents(data);
            
            const eventElements = document.getElementsByClassName('event');

            for(var i = 0; i < eventElements.length; i++) {
                eventElements[i].addEventListener("click", (e) => {
                    eventClick(e, data);
                });
            }
        }

        //Displays today's date on mini-calendar
        function displayDate() {
            const DOW = document.getElementById('DOW');
            const date = document.getElementById('date');

            const today = new Date();

            // Get day name (e.g., "Monday")
            const dayName = today.toLocaleDateString('en-US', { weekday: 'long' }); 

            DOW.innerHTML = `${dayName}, `;
            date.innerHTML = today.getDate();
        }

        //Handles event listeners and functions for mini-calendar
        async function handleEvents() {
            displayDate();

            await initiateEvents();

            document.getElementById('closeEventEdit').addEventListener('click', closeEvent);

            document.getElementById('eventForm').addEventListener('submit', await submitEvent);

            document.getElementById('deleteEvent').addEventListener('click', await deleteEvent);

            document.getElementById('addEvent').addEventListener('click', await addEvent);

            document.getElementById('calendarLink').addEventListener('click', (e) => {
                window.location.href = 'EventCalendarPHP-main';
            });
        }
        
    </script>


</head>
    

<body>
    <header>
        <h1>Home Page</h1>
        <div id="profileContainer">
            <img src="<?php echo $thisUser['profile_picture']; ?>" width="42px" height="42px" alt="profile picture" id="profilePic">
        </div>
        
    </header>
    <nav id="profileNav">
        <div id="profNavUsername"><?php echo $_SESSION['username']; ?></div>
        <div class="profNav" id="profNavSettings">Account Settings</div>
    </nav>

        <!-- Messaging Icon -->
        <div id="messageCenter">
        <img src="https://www.svgrepo.com/show/304507/messages.svg" id="chatApp" alt="Chatting Icon">
    </div>

    <!-- Main Chat Window -->
    <div id="chatWindowContainer">
        <div id="searchContainer">
            <input type="text" id="searchUser" placeholder="Search User..." oninput="searchUsers()">
            <ul id="userList">
                <!-- Username new message notifcations appear here-->
            </ul>

        </div>

        <div id="chatListContainer">
            <!-- Dropdown of usernames matching query -->
        </div>
    </div>

    <!-- Individual Chat Window -->
    <div id="individualChatWindow">
        <div id="chatHeader">
            <p id="chatUsername"></p>
            <p id="closeChat">✖</p>
        </div>
        <div id="messageArea"></div>
            <div id="newMessageIndicator" onclick="scrollToBottom()">New Messages</div>

        <input type="text" id="messageInput" placeholder="Type a message...">
        <button id="sendMessage">Send</button>
    </div>
    
    <!-- Add Friends div-->
    <div id="friendFinder">
        <h3>Search for friends</h3>
        <input type="text" id="searchInput" placeholder="Add Friends...">

        <div id="searchResults" class="dropdown"></div>
        <h3>Friend Requests</h3>
        <div id="friendRequests"></div>
        <h3>Your Friends</h3>
        <div id="friendsList"></div>
    </div>

    <!-- Mini-Calendar Div -->
    <div id="todaysEvents">
        <div id="calendarHeader">
            <div id="calendarLink">Open Calendar</div>
            <p id="day">
                <span id="DOW"></span>
                <span id="date"></span>
            </p>
            <button type="button" id="addEvent">Add</button>
        </div>
        
        <ul id="events">

        </ul>
    </div>

    <!-- Weather Display -->
    <aside id="weatherContainer" class="weather-container">
        <div class="weather-header" id="cityName">-</div>
        <div class="weather-today">
            <span id="weatherTime">-</span>
            <img id="weatherIcon" class="weather-icon" src="" alt="Weather Icon">
        </div>
        <div class="weather-temp" id="weatherTemp">-</div>
        <div class="weather-feels-like" id="weatherFeelsLike">-</div>
        <div class="weather-desc" id="weatherDesc">-</div>
        <div class="clothing-rec" id="clothingRec">-</div>
        <div class="forecast" id="weeklyForecast"></div>
        <div class="forecast" id="forecast-container"></div>
    </aside>

    <!-- News Ticker -->
    <div class="news-ticker">
        <div class="ticker-wrap" id="newsTicker"></div>
    </div>

    <!-- Event Edit Div -->
    <div id="eventEdit" hidden>
        <div id="eventEditInner">
            
            <div id="editHeader">
                <h5>Add/Edit Event</h5>
                
                <button type="button" id="closeEventEdit">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="createdBy"></div>
            <div>
                <form id="eventForm">
                    <input type="hidden" id="eventId">
                    <div>
                        <label for="eventTitle">Event Title</label>
                        <input type="text" id="eventTitle" required>
                    </div>
                    <div>
                        <label for="startTime">Start Time</label>
                        <input type="text" id="startTime" required>
                    </div>
                    <div>
                        <label for="endTime">End Time</label>
                        <input type="text" id="endTime" required>
                    </div>
                    <div>
                        <select id="optFriend" name="optFriend">
                            <option disabled selected value="">Add Friends</option>
                            <?php
                                //Provides all friends as a select option
                                $user_id = $_SESSION['user_id'];

                                $stmt = $conn->prepare("
                                    SELECT users.username, users.id FROM friends
                                    JOIN users ON (friends.user1_id = users.id OR friends.user2_id = users.id) 
                                    WHERE (friends.user1_id = ? OR friends.user2_id = ?) AND users.id != ?
                                ");
                                $stmt->bind_param("iii", $user_id, $user_id, $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                // Display each user
                                print_r($result);
                                while ($row = $result->fetch_assoc()) {
                                    
                                    echo '<option value="'.$row['id'].'">'.$row['username'].'</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div id="friends">
                        <div id="friendsResults"></div>
                        <strong>Added Friends:</strong>
                        <div id="friendsAdded"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveEvent">Save Event</button>
                    <button type="button" class="btn btn-danger" id="deleteEvent">Delete Event</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fetch weather data on load
        handleWeather();

        // Get news when loaded
        fetchNews(); 

        // Refresh News every hour
        setInterval(fetchNews, 3600000);

        profileEvents();

        handleEvents();
    </script>

    <!-- Messaging window Script -->
    <script>
        // Global variables that will be reused
        let activeChatUser = null;
        let lastMessageTimestamp = null;
        
        // Constants for session variables
        const loggedInUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
        const loggedInUsername = <?php echo json_encode($_SESSION['username'] ?? null); ?>;
        
        
        
        
        // Chat search bar opens and closes
        function toggleChatSearch() {
            let chatSearch = document.getElementById('chatWindowContainer');
            chatSearch.classList.toggle("open");
        }

        // Event listener to toggle open or close chat search bar based on chat icon click
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("messageCenter").addEventListener("click", toggleChatSearch);
        });

        



        // To make the chat window draggable
        const chatWindow = document.getElementById("individualChatWindow");
        const chatHeader = document.getElementById("chatHeader");

        let isDragging = false;
        let offsetX, offsetY;

        // When the user clicks and drags the header (mousedown)
        chatHeader.addEventListener("mousedown", function(e) {
            // Prevent things like text selection while dragging
            e.preventDefault();

            // Make sure user mouse within the chatWindow
            offsetX = e.clientX - chatWindow.getBoundingClientRect().left;
            offsetY = e.clientY - chatWindow.getBoundingClientRect().top;

            isDragging = true;

            // Start dragging
            function onMouseMove(e) {
                if (isDragging) {
                    // Move the chat window with cursor
                    chatWindow.style.left = `${e.clientX - offsetX}px`;
                    chatWindow.style.top = `${e.clientY - offsetY}px`;
                }
            }

            // Stop dragging
            function onMouseUp() {
                isDragging = false;
                document.removeEventListener("mousemove", onMouseMove);
                document.removeEventListener("mouseup", onMouseUp);
            }

            // Event listeners for dragging/not dragging
            document.addEventListener("mousemove", onMouseMove);
            document.addEventListener("mouseup", onMouseUp);
        });

    
        
        
        
        // Check if you received new messages
        function checkForNewMessages(userId) {
        fetch(`home.php?action=get_messages&receiver_id=${userId}`)
            .then(response => response.json())
            .then(messages => {
                // If json data for new messages is not in array form
                if (!Array.isArray(messages)) {
                    return;
                }
                // Get the latest message
                let latestMessage = messages[messages.length - 1];
                // If no messages
                if (!latestMessage) {
                    return;
                }
                // If received new message
                if (lastMessageTimestamp && latestMessage.sent_at > lastMessageTimestamp && latestMessage.username !== loggedInUsername) {
                    showChatNotification();
                }
                // Update last message timestamp
                lastMessageTimestamp = latestMessage.sent_at;
            });
        }

        
        
        
        
        // To show a new chat notification
        function showChatNotification() {
            let chatApp = document.getElementById("chatApp");
            let badge = document.getElementById("chatNotificationBadge");
            // Show a notifcation in the chatApp
            if (!badge) {
                badge = document.createElement("span");
                badge.id = "chatNotificationBadge";
                badge.classList.add("notification-badge");
                chatApp.appendChild(badge);
            }
            // Show notification
            badge.style.display = "block";
        }

        

        
        
        // Loading unread messages
        function loadUnreadMessageUsers() {
            fetch("home.php?action=get_unread_users")
                .then(response => response.json())
                .then(users => {
                    // Create a list of users that you have unread messages from
                    let userList = document.getElementById("userList");
                    userList.innerHTML = "";
                    fetch("get_friends.php")
                        .then(response => response.json())
                        .then(friends => {
                            // For each user
                            users.forEach(user => {
                                // Create a new list element in the unordered list
                                let userItem = document.createElement("li");
                                userItem.classList.add("userItem");
                                userItem.dataset.userId = user.id;

                                // Get the username and show a New Message banner
                                if (friends.includes(user.id)) {
                                    userItem.innerHTML = `${user.username} <img src="uploads/friend.svg" alt="friends" id="friendsIndicator"><span class="new-message">New Message</span>`;
                                } else {
                                    userItem.innerHTML = `${user.username} <span class="new-message">New Message</span>`;
                                }

                                showNewMessageIndicator();
                                // When clicking the list element be brought to the individual chat window with the user
                                userItem.addEventListener("click", () => openChat(user.id, user.username));
                                userList.appendChild(userItem);
                            });
                        })
                        .catch(error => {
                            console.error("Error getting friends list:", error)
                        });
                    })
                .catch(error => {
                    console.error("Error loading unread users:", error)
                });
        }




        
        
        // Display usernames
        function searchUsers() {
            // Adding letters to the query
            let query = document.getElementById('searchUser').value;
            if (query.length < 1) {
                document.getElementById('chatListContainer').style.display = 'none';
                return;
            }

            // PHP response getting usernames in the query
            fetch(`home.php?action=search_users&query=${query}`)
                .then(response => response.json())
                .then(users => {
                    // Show the results of the query
                    let results = document.getElementById('chatListContainer');
                    if (!results) {
                        console.error("chatListContainer not found");
                        return;
                    }
                    // Start with clean results 
                    results.innerHTML = ''; 
                    results.style.display = 'block'; 

                    // If there are no results of the query
                    if (users.length === 0) {
                        results.innerHTML = '<div class="chat-list-item">No users found</div>';
                    } else {
                        // Otherwise for each user
                        users.forEach(user => {
                            // For each username in the query
                            let div = document.createElement('div');
                            div.classList.add('chat-list-item');
                            // If clicking username in the query openchat with them
                            div.onclick = () => openChat(user.id, user.username);
                            // Create username and last message
                            div.innerHTML = `
                                <div class="chat-user">${user.username}</div>
                                <div class="chat-message">Last message...</div>
                            `;
                            results.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error("Error fetching users:", error);
                });
        }

        
        
        
        
        // Listener for search bar
        document.addEventListener('click', function(event) {
            let searchBox = document.getElementById('searchUser');
            let results = document.getElementById('chatListContainer');
            // If clicking within the search box keep it open
            if (searchBox.contains(event.target)) {
                return;
            } 
            // If clicking on a username (userItem)
            if (event.target.classList.contains('userItem')) {
                // Close dropdown when selecting a username
                results.style.display = 'none';
                return; 
            }
            // Clicked outside both hide dropdown
            results.style.display = 'none';
        });



        
        
        
        // Opening chat with selected user
        function openChat(userId, username) {
            let chatContainer = document.getElementById('messageArea');
            chatContainer.innerHTML = '<div class="error-message">No messages yet. Say Hello!</div>';
            // Display who your chatting with username
            let chatUsernameElement = document.getElementById('chatUsername');
            if (chatUsernameElement) {
                chatUsernameElement.innerText = username;
            } else {
                console.error('Error: chatUsername element not found.');
            }
            // Get your userId to mark as read
            activeChatUser = userId;
            document.getElementById('individualChatWindow').style.display = 'block';
            // Load the messages from the user
            loadMessages(userId);
            // Mark the message as read and refresh your unread message users list
            fetch("home.php?action=mark_messages_seen", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=mark_messages_seen&sender_id=${userId}`
            }).then(() => loadUnreadMessageUsers());
        }

        

        
        
        // Event listener to send message when clicking the send button
        document.getElementById('sendMessage').addEventListener('click', function () {
            // Get the message you are sending
            let message = document.getElementById('messageInput').value;
            if (message.trim() === "" || !activeChatUser) {
                return;
            }
            // PHP request to send the message to the receiver from the sender
            fetch('home.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_message&message=${encodeURIComponent(message)}&receiver_id=${encodeURIComponent(activeChatUser)}&sender_id=${encodeURIComponent(loggedInUserId)}`
            })
            .then(response => response.json())
            .then(data => {
                // Message sent successfully clear input else error
                if (data.success) {
                    loadMessages(activeChatUser);
                    document.getElementById('messageInput').value = '';
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error sending message: ' + error);
            });
        });




        
        
        // Load messages from user
        function loadMessages(userId) {
            // PHP request for messages from the chatter
            fetch(`home.php?action=get_messages&receiver_id=${userId}`)
                .then(response => response.json())
                .then(messages => {
                    // If json response messages not in array form
                    if (!Array.isArray(messages)) {
                        console.error("Expected 'messages' to be an array, but got:", messages);
                        return;
                    }
                    // Load messages in messageArea
                    let chatContainer = document.getElementById('messageArea');
                    if (!chatContainer) {
                        return;
                    }
                    // Find where user is in chat logs and if they are at the bottom 
                    let isAtBottom = chatContainer.scrollHeight - chatContainer.scrollTop <= chatContainer.clientHeight + 50;

                    chatContainer.innerHTML = '';

                    // For each message
                    messages.forEach(message => {
                        let messageDiv = document.createElement('div');
                        messageDiv.classList.add('message');

                        // If the message is from the sender apply sender style
                        if (message.username === loggedInUsername) {
                            messageDiv.classList.add('user-message');
                        } else {
                            // Otherwise apply receiver style
                            messageDiv.classList.add('other-message');
                        }
                        // Display the message
                        messageDiv.innerText = message.message;
                        chatContainer.appendChild(messageDiv);
                    });

                    // Set the lastMessageTimestamp to the last message
                    // If first message
                    if (lastMessageTimestamp === null && messages.length > 0) {
                        lastMessageTimestamp = messages[messages.length - 1].sent_at;
                        // If not first message
                    } else if (lastMessageTimestamp !== null) {
                        // Check for more recent messages
                        let newMessages = messages.filter(message => message.sent_at > lastMessageTimestamp);
                        // If received new message and user is in the middle of chat logs show new message
                        if (newMessages.length > 0 && !isAtBottom) {
                            showNewMessageIndicator();
                        }

                        // Update the lastMessageTimestamp
                        lastMessageTimestamp = messages[messages.length - 1].sent_at;
                    }

                    // If message comes in while user is at the bottom of chat logs scroll to bottom
                    if (isAtBottom) {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }
                })
                .catch(error => {
                    // If messageArea has no messages 
                    console.error("Error loading messages:", error);
                });
        }

        
        
        

        // Showing new message indicator
        function showNewMessageIndicator() {
            let indicator = document.getElementById('newMessageIndicator');
            if (indicator) {
                indicator.style.display = 'block';
            }
        }

        // Hide new message indicator
        function hideNewMessageIndicator() {
            let indicator = document.getElementById('newMessageIndicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
        }




        // When clicking 'X' in chat header close chat
        document.getElementById('closeChat').addEventListener('click', function () {
            document.getElementById('individualChatWindow').style.display = 'none';
        });

        
        // If there are no messages in the messageArea
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('messageArea');
            // Display prompt
            if (chatMessages) {
                chatMessages.innerHTML = '<div class="error-message">No messages yet. Say Hello!</div>';
            }
        });
        
        

    
        

        // Scroll to bottom of chat logs and hide notfication
        function scrollToBottom() {
            let chatContainer = document.getElementById('messageArea');
            chatContainer.scrollTop = chatContainer.scrollHeight;
            // Scrolling to bottom marks messages as seen
            fetch("home.php?action=mark_messages_seen", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=mark_messages_seen&sender_id=${activeChatUser}`
            }).then(() => loadUnreadMessageUsers());
            hideNewMessageIndicator();
            
        }
        // Scroll the user to the bottom
        document.getElementById('messageArea').addEventListener('scroll', function () {
            let chatContainer = this;
            if (chatContainer.scrollHeight - chatContainer.scrollTop <= chatContainer.clientHeight + 50) {
                hideNewMessageIndicator();
            }
        });


        
        
        // Call checkForNewMessages every 10 seconds
        setInterval(() => {
            if (activeChatUser) {
                checkForNewMessages(activeChatUser);
            }
        }, 2000);

        // Auto-refresh unread users list every 10 seconds
        setInterval(loadUnreadMessageUsers, 2000);

        // Load messages every 10 seconds
        setInterval(() => {
            if (activeChatUser) {
                loadMessages(activeChatUser);
            }
        }, 2000);
        
    </script>
    
    <!-- Friending JS -->
    <script>
        // Reuse search_users chatting feature to get username dropdown
        document.getElementById('searchInput').addEventListener('input', function() {
        // Add input to the query, using query search db for compatible names
        let query = this.value;
        fetch(`home.php?action=search_users&query=${query}`)
            .then(response => response.json())
            .then(data => {
                // Display json results as usernames, start as empty
                let resultsDiv = document.getElementById('searchResults');
                resultsDiv.innerHTML = "";
                // For each user
                data.forEach(user => {
                    // Add each user to a div where you have the option to send them a friend request
                    let div = document.createElement('div');
                    div.innerHTML = user.username + 
                        ` <button id="friendRequestButton" onclick="sendRequest(${user.id})">Add Friend</button>`;
                    resultsDiv.appendChild(div);
                });
            });
        });
        
        // To send a friend request when button is clicked
        function sendRequest(receiverId) {
            // Creates a pending request to receiver
        fetch('send_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'receiver_id=' + receiverId
        })
            // Display results of the friend request
        .then(response => response.text())
        .then(alert);
        }

        // Change the friend request from pending to accepted or declined
        function handleRequest(requestId, action) {
            // Change request status from pending to accepted or declined
            fetch('handle_request.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `request_id=${requestId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                // Display updated information with your choice
                if (data.success) {
                    loadFriendRequests();
                    loadFriendsList();
                } else {
                    alert("Error handling request");
                }
            });
        }

        
        // Get open friend requests 
        function loadFriendRequests() {
            // Get open friend requests to you
            fetch('view_requests.php')
                .then(response => response.json())
                .then(data => {
                    let requestsDiv = document.getElementById('friendRequests');
                    requestsDiv.innerHTML = "";
                    // If no incoming requests
                    if (data.length === 0) {
                        requestsDiv.innerHTML = "<p>No friend requests</p>";
                        return;
                    }
                    // If incoming friend requests
                    data.forEach(request => {
                        // In new div display they sent a request and give option to accept or decline invitation
                        let div = document.createElement('div');
                        div.innerHTML = `${request.username} sent you a friend request. 
                            <button id="acceptButton" onclick="handleRequest(${request.id}, 'accept')">Accept</button>
                            <button id="declineButton" onclick="handleRequest(${request.id}, 'decline')">Decline</button>`;
                        requestsDiv.appendChild(div);
                    });
                });
        }

        // Load accepted friend requests
        function loadFriendsList() {
            // Get status "accepted" friend requests
            fetch('friends_list.php')
                .then(response => response.json())
                .then(data => {
                    let friendsDiv = document.getElementById('friendsList');
                    friendsDiv.innerHTML = "";
                    // If no friends
                    if (data.length === 0) {
                        friendsDiv.innerHTML = "<p>No friends yet</p>";
                        return;
                    }
                    // For each friend 
                    data.forEach(friend => {
                        // Create new div with their username
                        let div = document.createElement('div');
                        div.textContent = friend.username;
                        friendsDiv.appendChild(div);
                    });
                });
        }

        // Event listener to run on selected usernames from the dropdown
        document.addEventListener("DOMContentLoaded", function() {
            let searchInput = document.getElementById("searchInput");
            // When displaying names based on user input
            document.addEventListener("click", function(event) {
                let searchResults = document.getElementById("searchResults");
                // To hide the dropdown
                if (searchResults) {
                    if (!searchResults.contains(event.target) && event.target !== searchInput) {
                        searchResults.style.display = "none";
                    }
                }
            });
            // Show the search results when typing
            searchInput.addEventListener("input", function() {
                let searchResults = document.getElementById("searchResults");
                // Show each result in the dropdown
                if (searchResults) {
                    searchResults.style.display = "block";
                }
            });
        });


        // Run requests every second
        setInterval(loadFriendRequests, 1000);
        setInterval(loadFriendsList, 1000);
    </script>




</body>
<script src="./EventCalendarPHP-main/assets/js/friendScript.js"></script>
</html>
