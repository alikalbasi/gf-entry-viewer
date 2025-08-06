# Gravity Form Entry Viewer

A secure, styled, and professional viewer for Gravity Forms entries, accessible via a clean URL (`/gf-entries`). This plugin provides an admin-only interface to browse form submissions with a modern, responsive design.

![Screenshot of GF Entry Viewer](https://example.com/screenshot.png)
*(نکته: بعداً می‌توانید یک اسکرین‌شات از افزونه بگیرید، آن را جایی آپلود کنید و لینک آن را اینجا قرار دهید)*

## ✨ Features

- **Modern & Responsive UI:** Clean, card-based design that works beautifully on desktop and mobile.
- **Form Selector:** Easily switch between different forms in your Gravity Forms setup.
- **Advanced Filtering:** Filter entries by their exact payment status (e.g., "Paid", "Processing", etc.).
- **Pagination:** A full pagination system to easily navigate through hundreds or thousands of entries.
- **Smart File Previews:**
    - Displays a thumbnail preview for image uploads.
    - Provides a clean "Download File" button for other file types.
- **Secure Access:** The viewer page is only accessible to users with administrator privileges.
- **Easy Setup:** Simply activate the plugin and go to `/gf-entries`.

## 🚀 Installation

1.  Download the latest version from this repository.
2.  Upload the `gf-entry-viewer-v5` folder to the `/wp-content/plugins/` directory on your WordPress site.
3.  Activate the plugin through the 'Plugins' menu in WordPress.
4.  Go to **Settings > Permalinks** in your WordPress dashboard and click "Save Changes" to flush the rewrite rules.
5.  Visit `https://your-site.com/gf-entries` to view the entries.

## 🔧 Requirements

- WordPress 5.0 or higher
- Gravity Forms 2.5 or higher
- PHP 7.4 or higher