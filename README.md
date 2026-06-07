# SWITCH VP - Telegram Bot for 3x-ui (Sanaei v3+)

<p align="center">
  <img src="assets/switchvp-logo.jpg" alt="SWITCH VP Logo" width="200"/>
</p>

<p align="center">
🔒 به SWITCH VP خوش آمدید!    
</p>

اینجا کانال رسمی فروش و اطلاع‌رسانی سرویس‌های وی‌پی‌ان ماست.     

با SWITCH VP تجربه‌ای متفاوت از اینترنت بدون محدودیت داشته باشید:    

🛡️ سرعت بالا | امنیت کامل     

💰 قیمت اقتصادی با کیفیت تضمینی     

🌍 دسترسی آزاد به محتوای جهانی    

    

📦 خرید آنی: @SwitchVpBot     

📚 آموزش و اطلاعیه‌ها: @SwitchVpGuide     

🎧 پشتیبانی ۲۴/۷: @SwitchVpSupport    

    

📡 همراه ما باشید و از بهترین شبکه‌ها استفاده کنید.    

## Features (Ultimate Version - June 2026)
- **Full support for latest 3x-ui v3.2.8+** (Sanaei official repo): multi-hop nodes, PostgreSQL support, new /panel/api/ endpoints, all protocols (VLESS, VMESS, Trojan, Reality, Hysteria, WireGuard, etc.)
- **SOCKS5 Proxy support** for all Telegram API calls (ideal for Iran servers – configurable user/pass/IP)
- **Latest Telegram features**: Premium emojis fully recognized and used, glass-style colored inline keyboards with modern emoji design for a premium look
- Personalized completely for **SWITCH VP** brand
- Advanced config search (search.php) with UUID/link support for vmess/vless/trojan
- Custom values.php with SWITCH VP texts and emojis
- Custom DB backup script with Telegram delivery
- Cool QR codes with logo overlay support
- Time/volume subscriptions, wallet, agency, discounts, Tron/card payments, and 50+ features
- No port 80 or 8080 dependency (custom port support, e.g. 8443)

## Installation on Ubuntu Server (Easy One-Command - Custom Port)
Run as root:

```bash
bash <(curl -s https://raw.githubusercontent.com/parhamkhanmohammadi/switchbot/main/install.sh)
```

The script will:
- Install LAMP + phpMyAdmin + SSL
- Ask for **domain, bot token, admin chat ID, SOCKS5 proxy, and custom web port** (default 8443 to avoid 80/8080 conflicts)
- Set up everything automatically
- Configure webhook and cron jobs

**Proxy example:**
`socks5://Khodam:Parham1384@127.0.0.1:8080`

After install, access at `https://yourdomain:YOURPORT/switchbot/`

**Port note:** We completely avoid port 80 and 8080. The install asks for a free port (e.g. 8443). If needed, manually edit Apache ports.conf to `Listen YOURPORT`.

## Personalization & Cool Features
- All texts, buttons, and messages branded for SWITCH VP with premium emojis
- Glass buttons: Modern emoji layouts for a "glassmorphism" premium feel (Telegram native button colors enhanced)
- Logo integrated in assets and ready for QR codes
- Everything optimized for Iranian servers with proxy

## Supported Panels
- Sanaei 3x-ui (latest v3.2.8+ from https://github.com/MHSanaei/3x-ui)
- Marzban
- Full API compatibility with new features like nodes and multi-hop

## Update Bot
Pull latest from GitHub or use update mechanisms in the panel.

## Donation
Tron (TRX): `TY8j7of18gbMtneB8bbL7SZk5gcntQEemG`

## Contact & Channels
- 📦 Bot: @SwitchVpBot
- 📚 Guide: @SwitchVpGuide
- 🎧 Support: @SwitchVpSupport

---

**This is the ultimate, best-in-class SWITCH VP bot – fully updated, personalized, proxy-ready, and optimized for the latest 3x-ui and Telegram!** 🛡️💎

Enjoy selling premium VPN services!