# Gravity Form Entry Viewer

[![Plugin Version](https://img.shields.io/badge/Version-1.1.0-blue.svg)](https://github.com/alikalbasi/gf-entry-viewer)
[![Required WordPress Version](https://img.shields.io/badge/WordPress-5.5%2B-orange.svg)](https://wordpress.org/download/)
[![Required Gravity Forms Version](https://img.shields.io/badge/Gravity%20Forms-2.5%2B-red.svg)](https://www.gravityforms.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

A secure, beautiful, and professional viewer for Gravity Forms entries, accessible via a clean URL (`/gf-entries`). This plugin provides an admin-only interface to browse form submissions with a modern, responsive design and auto-updates directly from GitHub.

---

## About The Project

Gravity Forms is a powerful tool, but viewing entries sometimes requires navigating deep into the WordPress admin area. This plugin was created to solve that problem by providing a beautiful, dedicated, and secure page at `/gf-entries` where administrators can quickly view, filter, and browse entries from any form.

It's designed to be lightweight, secure, and highly user-friendly, with a focus on clean code and a professional user interface.

## ✨ Features

- **Elegant & Responsive UI:** A modern, card-based design that looks stunning on both desktop and mobile devices.
- **Auto-Updates from GitHub:** Receive update notifications directly in your WordPress dashboard whenever a new version is released here.
- **Dynamic Form Selector & Filtering:** Easily switch between forms and filter entries by payment status.
- **Smart WhatsApp Integration:** Automatically adds a "Click-to-Chat" WhatsApp link next to phone number fields, with intelligent number formatting.
- **Advanced File & Image Previews:**
  - Correctly handles both single and multiple file uploads.
  - Displays a rich, clickable preview for image uploads.
  - Provides a clean "Download File" button for other file types.
- **Secure Access & Custom Login:** The viewer is admin-only and features a beautifully integrated custom login page for non-logged-in users.
- **Clean & Commented Code:** Built with best practices and fully documented for developers who want to learn from or contribute to the project.

## 🚀 Installation

1.  **Download:** Click on the `Code` button on this repository page and select `Download ZIP`.
2.  **Extract:** Unzip the downloaded file. You will have a folder named `gf-entry-viewer-main`. Rename it to `gf-entry-viewer`.
3.  **Add Update Library:**
  - Download the latest release of the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker/releases/latest) library.
  - Create a folder named `lib` inside your `gf-entry-viewer` plugin folder.
  - Extract the downloaded library and place the `plugin-update-checker` folder inside the `lib` directory.
4.  **Upload:** Upload the entire `gf-entry-viewer` folder to the `/wp-content/plugins/` directory on your WordPress site.
5.  **Activate:** Activate the plugin through the 'Plugins' menu in your WordPress dashboard.
6.  **Set Permalinks:** Go to **Settings > Permalinks** and click "Save Changes" once to ensure the new URL rule is active.
7.  **All Done!** You can now visit `https://your-site.com/gf-entries` to see the viewer in action.

## 🔧 Development & Contribution

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".

1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'feat: Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

## 📄 License

This project is licensed under the MIT License.