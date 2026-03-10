# Smart Grievance Management System

A comprehensive hackathon-level web application for managing civic complaints with modern UI, real-time tracking, and automatic escalation system.

## 🚀 Features

### User Features
- **User Registration & Login** - Secure authentication system
- **Complaint Submission** - Multi-category complaint filing with image upload
- **Real-time Tracking** - Track complaints with unique IDs
- **User Dashboard** - Personal dashboard with statistics and history
- **Notifications** - Real-time status updates via notifications

### Admin Features
- **Department-wise Access** - Separate logins for different departments
- **Complaint Management** - View, assign, and update complaint status
- **Admin Dashboard** - Comprehensive dashboard with statistics
- **Status Updates** - Update complaint status with admin notes
- **Report Generation** - Generate department reports

### Advanced Features
- **Automatic Escalation** - Complaints auto-escalate after 48 hours
- **Higher Authority Dashboard** - Special dashboard for escalated complaints
- **AI Chatbot** - Multi-language support (English, Hindi, Marathi)
- **Image Slider** - Auto-rotating hero section slider
- **Responsive Design** - Works perfectly on all devices

## 🛠 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Server**: XAMPP
- **UI Framework**: Custom CSS with modern design
- **Icons**: Font Awesome 6.0

## 📋 System Requirements

- XAMPP (or equivalent LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

## 🚀 Installation

### 1. Database Setup
1. Start XAMPP and start Apache and MySQL services
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the `database.sql` file to create the database and tables

### 2. File Setup
1. Copy all project files to `htdocs/hack/` directory
2. Ensure the `uploads/` directory exists and is writable

### 3. Configuration
1. Open `config.php` and update database credentials if needed
2. Default credentials are set for XAMPP installation

### 4. Access the Application
- **Home Page**: http://localhost/hack/
- **User Registration**: http://localhost/hack/register.html
- **User Login**: http://localhost/hack/login.html
- **Admin Login**: http://localhost/hack/admin_login.html

## 👤 Demo Credentials

### Department Admin Logins
- **Garbage Department**: `garbage_admin` / `admin123`
- **Water Department**: `water_admin` / `admin123`
- **Road Department**: `road_admin` / `admin123`
- **Electricity Department**: `electricity_admin` / `admin123`
- **Higher Authority**: `higher_admin` / `admin123`

### User Registration
- Register any user account through the registration form
- Use valid email format and 10-digit mobile number

## 📊 Database Structure

### Main Tables
- **users** - User registration data
- **admin_users** - Department administrator accounts
- **complaints** - All complaint records
- **complaint_timeline** - Complaint status history
- **escalated_complaints** - Escalated complaint details
- **notifications** - User and admin notifications
- **department_stats** - Department-wise statistics
- **system_settings** - System configuration

## 🔧 Key Features Explained

### 1. Complaint Workflow
```
User Registration → Login → Submit Complaint → Generate ID → 
Department Review → Status Update → Resolution
```

### 2. Escalation System
- Complaints pending for >48 hours auto-escalate
- Higher authority can review and reassign
- Multi-level escalation (Level 1, 2, 3)

### 3. Real-time Notifications
- Users get notified on status changes
- Admins get notified on new complaints
- Escalation alerts to higher authority

### 4. AI Chatbot
- Multi-language support (English, Hindi, Marathi)
- Helps users with common queries
- Provides guidance on complaint process

## 🎨 UI/UX Features

### Modern Design
- Gradient backgrounds and glassmorphism effects
- Smooth animations and transitions
- Responsive grid layouts
- Modern card-based design

### Interactive Elements
- Auto-rotating image slider (3 seconds)
- Hover effects on all interactive elements
- Animated statistics counters
- Modal windows for detailed views

### Accessibility
- Semantic HTML5 structure
- ARIA labels for screen readers
- Keyboard navigation support
- High contrast color scheme

## 📱 Responsive Design

The application is fully responsive and works on:
- Desktop computers (1200px+)
- Tablets (768px - 1199px)
- Mobile devices (320px - 767px)

## 🔒 Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- CSRF protection on forms

## 📈 System Statistics

The dashboard displays real-time statistics:
- Total complaints per department
- Pending, in-progress, resolved counts
- Escalated complaints tracking
- Average resolution time
- User satisfaction metrics

## 🚀 Future Enhancements

- Email notifications integration
- SMS alerts for urgent complaints
- Mobile app development
- Advanced analytics dashboard
- Integration with government systems
- Multi-city support

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL service is running in XAMPP
   - Check credentials in `config.php`

2. **File Upload Not Working**
   - Ensure `uploads/` directory exists and is writable
   - Check PHP file upload permissions

3. **Images Not Displaying**
   - Verify image paths in `uploads/` directory
   - Check file permissions

4. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check session save path permissions

## 📞 Support

For any issues or queries:
- **Helpline**: 1800-123-4567 (Demo)
- **Email**: support@grievance.gov.in (Demo)
- **Live Chat**: Built-in AI chatbot

## 📄 License

This project is developed for educational and hackathon purposes. Feel free to use and modify according to your needs.

## 👥 Developed By

Smart Grievance Management System Team
Hackathon Project 2024

---

**Note**: This is a demonstration project. For production use, additional security measures and optimizations are recommended.
