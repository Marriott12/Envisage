# Envisage Technology Zambia - Dynamic Website

This is the modernized version of the Envisage Technology Zambia website with modular structure and admin panel.

## Features Implemented

### 1. Modularization
- **Separated header, navigation, and footer** into reusable includes
- **Configuration file** for centralized settings
- **Database abstraction layer** for easy data management
- **Utility functions** for common operations

### 2. Admin Panel
- **Secure login system** with password hashing
- **Dashboard** with statistics and quick actions
- **Services management** - add, edit, delete services
- **Contact form submissions** management
- **Newsletter subscribers** management
- **Site settings** configuration
- **Responsive design** using Bootstrap 5

### 3. Dynamic Content
- **Database-driven content** for services, team, portfolio
- **Dynamic meta tags** for SEO
- **Contact form** with database storage
- **Newsletter subscription** system

## Setup Instructions

### Prerequisites
- WAMP/XAMPP server running
- PHP 7.4 or higher
- MySQL database

### Installation Steps

1. **Ensure WAMP is running** and access your localhost

2. **Database Setup**:
   ```bash
   # Navigate to the setup directory
   cd c:\wamp64\www\envisage
   
   # Run the installation script
   php setup/install.php
   ```
   
   This will:
   - Create the database `envisage_db`
   - Create all necessary tables
   - Insert default admin user and sample data

3. **Default Admin Credentials**:
   - **URL**: `http://localhost/envisage/admin/`
   - **Username**: `admin`
   - **Email**: `admin@envisagezm.com`
   - **Password**: `admin123`
   
   **⚠️ Important**: Change the default password after first login!

4. **File Permissions**:
   - Ensure the `assets/images/` directory is writable for image uploads
   - Create an `uploads/` directory if needed

### Directory Structure
```
envisage/
├── admin/                  # Admin panel
│   ├── assets/
│   ├── includes/
│   ├── login.php
│   ├── dashboard.php
│   ├── services.php
│   └── ...
├── config/                 # Configuration files
│   ├── config.php
│   └── database.php
├── includes/               # Modular includes
│   ├── header.php
│   ├── navigation.php
│   ├── footer.php
│   ├── functions.php
│   └── newsletter.php
├── setup/                  # Installation script
│   └── install.php
├── assets/                 # CSS, JS, Images
├── index.php              # Homepage (updated)
├── about.php
├── contact.php
├── services.php
└── process_contact.php    # Contact form handler
```

## Database Tables Created

1. **admin_users** - Admin login credentials
2. **pages** - Dynamic page content
3. **services** - Company services
4. **team_members** - Team information
5. **portfolio** - Project portfolio
6. **testimonials** - Client testimonials
7. **blog_posts** - Blog articles
8. **contact_submissions** - Contact form data
9. **newsletter_subscribers** - Email subscribers
10. **site_settings** - Configuration settings

## Key Features

### Admin Panel Features
- **Dashboard** with statistics
- **CRUD operations** for all content
- **File upload** for images
- **Settings management**
- **Responsive design**
- **Secure authentication**

### Frontend Improvements
- **Modular structure** - easy to maintain
- **Dynamic content** from database
- **SEO optimized** meta tags
- **Contact form** with validation
- **Newsletter signup**
- **Responsive design**

### Security Features
- **Password hashing** using PHP's password_hash()
- **SQL injection protection** using prepared statements
- **Input sanitization**
- **Session management**
- **CSRF protection** ready structure

## Usage

### Adding Content
1. Login to admin panel at `/admin/`
2. Use the navigation to manage different content types
3. Add/edit services, team members, portfolio items, etc.
4. Content appears automatically on the frontend

### Customizing Settings
1. Go to Admin Panel > Settings
2. Update site information, contact details, social media links
3. Changes reflect immediately on the website

### Managing Contact Forms
1. Admin Panel > Contact Messages
2. View and manage all form submissions
3. Mark as read/unread
4. Export data if needed

## Next Steps for Full Implementation

1. **Complete all admin pages** (portfolio, team, testimonials, etc.)
2. **Update remaining PHP files** to use modular structure
3. **Add blog functionality**
4. **Implement user roles** and permissions
5. **Add backup/restore** functionality
6. **Implement caching** for better performance
7. **Add API endpoints** for mobile app integration
8. **SEO enhancements** (sitemap generation, etc.)

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Ensure WAMP is running
   - Check database credentials in `config/config.php`
   - Verify MySQL service is started

2. **Admin Login Issues**:
   - Run the setup script again to reset admin credentials
   - Clear browser cache and cookies

3. **File Upload Issues**:
   - Check directory permissions
   - Verify PHP upload settings in `php.ini`

4. **Missing Styles**:
   - Ensure Bootstrap CDN is accessible
   - Check admin.css file path

## Support

For support or questions:
- Email: info@envisagezm.com
- Phone: +260 974 297 313 / +260 978 425 886

---

**Note**: This is a development version. Remember to:
- Change default passwords
- Update configuration for production
- Set proper file permissions
- Enable HTTPS in production
- Set up regular backups
