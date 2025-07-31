# Advanced File Manager

A modern, secure, and feature-rich file management system built with PHP, featuring intuitive navigation, responsive design, and comprehensive security measures.

## 🚀 Features

### Core Features
- **File Upload & Management**: Drag & drop upload, multiple file support
- **Folder Organization**: Create, delete, and organize folders
- **File Operations**: Download, delete, and search files
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

### Advanced Features
- **Security**: CSRF protection, file validation, secure headers
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support
- **Performance**: Lazy loading, optimized assets, caching
- **Analytics**: Google Analytics integration for usage tracking
- **Maintenance**: Automated cleanup, log rotation, health monitoring

### UX Enhancements
- **Intuitive Navigation**: Breadcrumbs, search functionality, view switching
- **Progress Indicators**: Real-time upload progress with visual feedback
- **Keyboard Shortcuts**: Quick access to common functions
- **Modern UI**: Beautiful gradients, animations, and responsive design

## 📋 Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- File system write permissions
- Modern web browser with JavaScript enabled

## 🛠️ Installation

1. **Download the files**
   ```bash
   git clone [repository-url]
   cd filemanager/public
   ```

2. **Set up permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   chmod 644 *.php
   ```

3. **Configure your web server**
   - Point your web server to the `public/` directory
   - Ensure PHP is properly configured
   - Enable URL rewriting if using Apache

4. **Customize configuration**
   - Edit `index.php` to modify upload limits and allowed file types
   - Update Google Analytics ID in the configuration
   - Change the maintenance key in `maintenance.php`

5. **Test the installation**
   - Navigate to your web server URL
   - Try uploading a file to test functionality
   - Check the logs directory for any errors

## 🔧 Configuration

### File Upload Settings
```php
// In index.php - Config class
const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'mp3', 'mp4', 'avi'];
```

### Security Settings
```php
// CSRF protection is enabled by default
// Security headers are automatically set
// File validation includes type and size checks
```

### Analytics Configuration
```php
// Replace with your Google Analytics ID
const ANALYTICS_ID = 'G-XXXXXXXXXX';
```

## 🔒 Security Features

### Built-in Security Measures
- **CSRF Protection**: All forms include CSRF tokens
- **File Validation**: Strict file type and size validation
- **Path Traversal Prevention**: Secure file path handling
- **XSS Protection**: Input sanitization and output encoding
- **Security Headers**: X-Frame-Options, X-XSS-Protection, etc.

### File Upload Security
- File type validation using MIME types
- File size limits to prevent abuse
- Filename sanitization to prevent malicious uploads
- Secure file permissions (0755 for directories)

### Access Control
- Session-based authentication
- Secure cookie handling
- IP-based rate limiting (configurable)

## 📱 Responsive Design

The file manager is fully responsive and works on:
- **Desktop**: Full feature set with grid/list views
- **Tablet**: Optimized touch interface
- **Mobile**: Simplified layout with touch-friendly buttons

### Breakpoints
- **Desktop**: 1200px and above
- **Tablet**: 768px - 1199px
- **Mobile**: Below 768px

## ♿ Accessibility Features

### Screen Reader Support
- ARIA labels on all interactive elements
- Semantic HTML structure
- Screen reader announcements for dynamic content
- Skip links for keyboard navigation

### Keyboard Navigation
- **Tab**: Navigate through elements
- **Enter**: Activate buttons and links
- **Escape**: Close modals
- **Ctrl/Cmd + U**: Upload file
- **Ctrl/Cmd + N**: Create folder
- **Ctrl/Cmd + F**: Focus search

### Visual Accessibility
- High contrast mode support
- Reduced motion support for users with vestibular disorders
- Focus indicators for keyboard navigation
- Color-blind friendly design

## 🚀 Performance Optimization

### Loading Optimizations
- CSS and JavaScript minification
- Image lazy loading
- Critical CSS inlining
- Resource preloading

### Caching Strategy
- Browser caching for static assets
- ETags for conditional requests
- Gzip compression support

### Database Optimization
- Efficient file listing algorithms
- Pagination for large file sets
- Optimized search functionality

## 📊 Analytics Integration

### Google Analytics
- Page view tracking
- File download tracking
- Upload event tracking
- Performance monitoring

### Custom Events
- File uploads
- File downloads
- Folder creation
- Search usage
- View switching

## 🛠️ Maintenance

### Automated Maintenance
The system includes a comprehensive maintenance script (`maintenance.php`) that:

1. **File Cleanup**: Removes old files based on configurable age
2. **Log Rotation**: Manages log file sizes and ages
3. **System Health**: Monitors disk usage, memory, and PHP extensions
4. **Security Checks**: Identifies potential security issues
5. **Report Generation**: Creates detailed maintenance reports

### Running Maintenance

**Command Line:**
```bash
php maintenance.php
```

**Web Interface:**
```bash
curl -H "X-Maintenance-Key: your-secret-maintenance-key" https://your-domain.com/maintenance.php
```

### Scheduled Maintenance
Set up a cron job for automated maintenance:

```bash
# Run maintenance daily at 2 AM
0 2 * * * /usr/bin/php /path/to/filemanager/public/maintenance.php

# Run maintenance weekly on Sundays at 3 AM
0 3 * * 0 /usr/bin/php /path/to/filemanager/public/maintenance.php
```

## 📁 File Structure

```
public/
├── index.php              # Main application file
├── maintenance.php        # Maintenance script
├── assets/
│   ├── css/
│   │   └── style.css     # Enhanced styles
│   ├── js/
│   │   └── script.js     # Enhanced JavaScript
│   └── bootstrap5/       # Bootstrap framework
├── uploads/              # File storage directory
├── logs/                 # Log files directory
└── includes/
    └── maincontent.php   # Main content template
```

## 🔧 Customization

### Styling
- Modify `assets/css/style.css` for custom styling
- CSS custom properties for easy theming
- Dark mode support included

### Functionality
- Extend the `FileManager` class for additional features
- Add new file operations in the `RequestHandler` class
- Customize the main content template

### Security
- Modify allowed file types in the `Config` class
- Adjust file size limits
- Customize security headers

## 🐛 Troubleshooting

### Common Issues

**Upload Fails:**
- Check file permissions on uploads directory
- Verify PHP upload settings in php.ini
- Check file size limits

**Performance Issues:**
- Monitor disk space usage
- Check PHP memory limits
- Review log files for errors

**Security Warnings:**
- Update maintenance key
- Review file permissions
- Check for suspicious files

### Log Files
- **Application Logs**: `logs/filemanager.log`
- **Maintenance Logs**: `logs/maintenance.log`
- **Error Logs**: Check web server error logs

## 📈 Performance Monitoring

### Key Metrics
- Page load time
- File upload speed
- Memory usage
- Disk space utilization

### Monitoring Tools
- Built-in performance tracking
- Google Analytics integration
- Maintenance script health checks

## 🔄 Updates

### Version History
- **v2.0**: Enhanced security and accessibility
- **v1.5**: Performance optimizations
- **v1.0**: Initial release

### Update Process
1. Backup your current installation
2. Download the latest version
3. Replace files (preserve uploads and logs)
4. Run maintenance script
5. Test functionality

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- Check the troubleshooting section
- Review the log files
- Create an issue on GitHub

## 🔮 Roadmap

### Planned Features
- [ ] Multi-user support
- [ ] File sharing links
- [ ] Advanced search filters
- [ ] File preview functionality
- [ ] API endpoints
- [ ] Mobile app integration

### Performance Goals
- [ ] Sub-second page loads
- [ ] Support for 10,000+ files
- [ ] Real-time collaboration
- [ ] Offline functionality

---

**Built with ❤️ for modern web development** 