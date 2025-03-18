# 📅 PHP Event Calendar Web Application
A robust and interactive Event Calendar web application built using PHP, HTML, CSS, JavaScript, Bootstrap, Ajax, and MySQL. This application allows users to add, edit, delete, and view events by day, week, or month with a user-friendly interface.

## 🌟 Features
- Add new events 🆕
- Edit existing events ✏️
- Delete events ❌
- View events by day, week, or month 📅
- Responsive design using Bootstrap 📱💻
- Modal for event details and time specification 🕒

## 🛠️ Technologies Used
- **Frontend**: HTML, CSS, Bootstrap, JavaScript, jQuery, FullCalendar
- **Backend**: PHP, MySQL
- **AJAX**: For seamless updates

## 📝 Setup Instructions
Follow these steps to set up the project locally:

### 1. Clone the Repository
```bash
git clone https://github.com/obadaKraishan/EventCalendarPHP.git
cd EventCalendarPHP
```

### 2. Set Up the Database
1. Start your local server using XAMPP, WAMP, or MAMP.
2. Open phpMyAdmin and create a new database named `event_calendar`.
3. Import the SQL file to set up the `events` table:
   ```sql
   CREATE DATABASE event_calendar;
   USE event_calendar;

   CREATE TABLE events (
       id INT AUTO_INCREMENT PRIMARY KEY,
       title VARCHAR(255) NOT NULL,
       description TEXT,
       start_datetime DATETIME NOT NULL,
       end_datetime DATETIME NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

### 3. Run the Application
Open a web browser and navigate to `http://localhost/EventCalendarPHP`.

## 🎨 Customization
### 1. Update Styles
Modify the styles in `assets/css/style.css` to match your design preferences.

### 2. Update JavaScript
Enhance the JavaScript functionality in `assets/js/script.js` to customize the interactivity.

### 3. Update Content
Modify the HTML content in `index.php` to personalize the text and events.

## 📄 License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Contributors
- [Obada Kraishan](https://github.com/obadaKraishan)
