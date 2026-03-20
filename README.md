# WP Customer Cleanup Script

A simple PHP script to bulk delete WordPress users with the `customer` role who have **no orders**. This is useful for cleaning up spam accounts, test accounts, or guest-checkout-only users that clutter your user database.

## 🚀 Features
- **Safe**: Only deletes users with the `customer` role who have **zero orders**.
- **Fast**: Uses direct SQL queries to check for orders and delete users (bypasses heavy WordPress functions).
- **Batch Processing**: Processes users in batches to avoid timeouts.
- **Admin Protection**: Automatically skips administrators.
- **Live Progress**: Shows real-time progress in the browser.

## ⚠️ Warning
**This script is destructive.** It will permanently delete users and their metadata. 
- **Backup your database first!**
- **Delete the script immediately after use.**
- This script does **not** reassign posts or comments (users with the `customer` role typically have none).

## 📋 Requirements
- WordPress installation with WooCommerce (optional, but recommended for order checking).
- PHP 7.4+
- Access to your WordPress file system (via FTP, SFTP, or File Manager).

## 🛠️ Installation & Usage

### 1. Upload the Script
Upload `cleanup-customers.php` to your WordPress root directory (e.g., `public_html/`) or `wp-content/`.

### 2. Adjust the Path (if needed)
- If uploaded to the **root** (`public_html/`), the script will automatically find `wp-load.php`.
- If uploaded to **`wp-content/`**, edit the script and change:
  ```php
  require_once(__DIR__ . '/wp-load.php');
  ```
  to:
  ```php
  require_once(__DIR__ . '/../../wp-load.php');
  ```

### 3. Run the Script
Visit the script in your browser:
```
https://yourdomain.com/cleanup-customers.php
```
*(or wherever you uploaded it)*

### 4. Monitor Progress
The script will show:
- Total users processed.
- Users deleted (no orders).
- Users skipped (have orders or are admins).

### 5. Delete the Script
**IMPORTANT:** Once finished, **delete `cleanup-customers.php`** from your server immediately. Leaving it exposed is a security risk.

## 🔧 Configuration
You can adjust the following variables at the top of the script:
```php
$batch_size = 100;      // Users per batch (lower if you hit timeouts)
$max_iterations = 200;  // Max batches (200 * 100 = 20,000 users)
$role_to_clean = 'customer'; // Change to 'subscriber' if needed
```

## 🐛 Troubleshooting
- **"wp-load.php not found"**: Adjust the `require_once` path as described above.
- **Script times out**: Reduce `$batch_size` to `10` or `1`.
- **Users not being deleted**: Check if they have orders in any status (the script checks all statuses).
- **Fatal errors**: Enable `display_errors` in the script (already enabled by default for debugging).

## 📜 License
MIT License - Feel free to use, modify, and distribute.

## 🤝 Contributing
Found a bug? Open an issue on GitHub!

---
**Created by Maven (OpenClaw)** - [OpenClaw](https://openclaw.ai)
