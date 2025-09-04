# ZohoMailer for Perfex CRM

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)
![Perfex CRM](https://img.shields.io/badge/Perfex-CRM-orange)
![Status](https://img.shields.io/badge/Status-Active-brightgreen)

ZohoMailer is a **Perfex CRM module** that allows you to send emails using the **Zoho Mail API** with full attachment support, logging, and flexible fallback options.  
It is designed to replace or extend Perfex’s default email functionality with seamless Zoho integration.

---

## ✨ Features

- 📩 Send all emails directly via Zoho Mail API.  
- 📎 Full **attachment support**.  
- 🔄 **Fallback option** to default email if Zoho fails.  
- 🛠 Easy-to-use **settings page** inside Perfex CRM.  
- 📊 Logging & error tracking (`zohomailer/logs/`).  
- 🎨 Clean UI with steps seperated in sections.  
- 📱 Test email sending before enabling.  

---

## 📥 Installation

1. Download the latest release (`zohomailer.zip`) from [Releases](../../releases).  
2. Upload it into your Perfex CRM `modules/` directory:  
3. Go to **Setup → Modules** in Perfex admin.  
4. Activate **ZohoMailer**.  
5. Configure Zoho API credentials under:  
**Setup → ZohoMailer Settings**.  

---

## ⚙️ Configuration

1. Copy **Hopepage URL and Authorized Redirect URL** from Step 0.
2. Go to [Zoho API Console](https://api-console.zoho.com/) and create an app and enter copied **Hopepage URL and Authorized Redirect URL**.
3. Obtain **Client ID, Client Secret** from [Zoho API Console](https://api-console.zoho.com/).  
4. Enter them in the **ZohoMailer Settings page** in Step 1.
5. Select **domain** where your zoho account is hosted, enter Zoho authorized **from email, from name**, save credentials.
6. **Authorize Zoho** in Step 2.
7. **Test Configurations by sending test email** before enablig system emails to use ZohoMailer in Step 3.
8. **Enable ZohoMailer** and optionally, enable **Fallback to System Mailer** → uses default Perfex mail if Zoho fails.

---

## 📂 Module Structure
```
zohomailer/ # Actual Perfex module
├── zohomailer.php # Main module file
├── install.php # Install script
├── controllers/ # Controllers (admin pages)
├── models/ # Database model
├── libraries/ # Mailer logic & interceptors
├── helpers/ # Utility helpers
├── views/ # Settings page view
├── assets/ # CSS, JS, images (logo)
└── logs/ # Error log storage
```
---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

---

## 🚀 Roadmap

- [ ] Multi-account support  
- [ ] Filter to route email sending.  
- [ ] Advanced email logging UI    

---

## 🤝 Contributing

Contributions are welcome!  
- Fork this repo  
- Create a feature branch (`git checkout -b feature/my-feature`)  
- Commit changes (`git commit -m 'Add my feature'`)  
- Push and create a Pull Request  

---

## ⚖️ License

This project is licensed under the **GNU General Public License v3.0 (GPLv3)** – see the [LICENSE](LICENSE) file for details.  

© 2025 InWebzer Solutions.  
ZohoMailer is not affiliated with or endorsed by Zoho Corporation.







