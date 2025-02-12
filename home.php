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
References:
GNEWS API for sourcing 10 headlines 
OPEN-METEO API for sourcing weather data
NOMINATIM API for sourcing the coordinates for the weather data
-->


<?php 
session_start();

// Confirm login of user from index
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
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
    </style>

    <style>
        /* Weather Container */
        .weather-container {
            position: fixed;
            right: 20px;
            top: 60px;
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
        .evening { background: dark blue; }
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

</head>

<body>
    <h1>Home Page</h1>

    <!-- Weather Display -->
    <div id="weatherContainer" class="weather-container">
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
    </div>

    <script>

        // TODO: MAKE IT SO API IS DYNAMIC ON USER CITY DROPDOWN CHOICE IN USER ACCT change api based on lat and lon 
        async function fetchWeather() {
            const latitude = 44.9778;  // Example: Minneapolis
            const longitude = -93.265;
            const weatherUrl = `https://api.open-meteo.com/v1/forecast?latitude=44.9778&longitude=-93.265&current=temperature_2m,apparent_temperature,weather_code&daily=temperature_2m_max,temperature_2m_min,weather_code,precipitation_sum&timezone=auto&timeformat=unixtime&temperature_unit=fahrenheit`; 

            // Get json data from API
            try {
                const response = await fetch(weatherUrl);
                const data = await response.json();

                //Used for troubleshooting API
                // console.log("API Response:", data); 

                if (!data || !data.daily || !data.daily.time) {
                    console.error("No daily forecast data found in the API response.");
                    return;
                }

                // Run nominatim api to get the city name from the coordinates
                const cityName = await getCityName(latitude, longitude);
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

            } catch (error) {
                console.error("Error fetching weather:", error);
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

        // Fetch weather data on load
        fetchWeather();


        // Api gets city name based on coordinates selected
        async function getCityName(latitude, longitude) {
            const url = `https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`;

            // Get json data
            try {
                const response = await fetch(url);
                const data = await response.json();

                // Return name
                if (data.address) {
                    return data.address.city || data.address.town || data.address.village || "Unknown Location";
                } else {
                    return "Unknown Location";
                }

            } catch (error) {
                console.error("Error getting city name: ", error);
                return "Unknown Location";
            }
            
        }

    </script>



    <!-- News Ticker -->
    <div class="news-ticker">
        <div class="ticker-wrap" id="newsTicker"></div>
    </div>

    <script>
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

        // Get news when loaded
        fetchNews(); 
   
        // Refresh News every hour
        setInterval(fetchNews, 3600000);

    </script>

</body>

</html>
