#!/bin/bash

# دریافت توکن ربات تلگرام از فایل baseInfo.php
telegramBotToken=$(cat /var/www/html/wizwizxui-timebot/baseInfo.php | grep '$botToken' | cut -d"'" -f2)
telegramBotToken2=$(cat /var/www/html/wizwizxui-timebot/baseInfo.php | grep '$botToken' | cut -d'"' -f2)

# آیدی چت مورد نظر شما که به صورت دستی وارد شده است.
# خط قبلی که این آیدی را از فایل می خواند، حذف شده است.
chatID="-1002600229376"

# دریافت اطلاعات دیتابیس از فایل baseInfo.php
databaseUser=$(cat /var/www/html/wizwizxui-timebot/baseInfo.php | grep '$dbUserName' | cut -d"'" -f2)
databasePassword=$(cat /var/www/html/wizwizxui-timebot/baseInfo.php | grep '$dbPassword' | cut -d"'" -f2)
databaseName=$(cat /var/www/html/wizwizxui-timebot/baseInfo.php | grep '$dbName' | cut -d"'" -f2)

# ایجاد یک دایرکتوری موقت برای بکاپ
backupDir='/tmp/db_backup'
mkdir -p $backupDir

# ایجاد نام فایل بکاپ با تاریخ و ساعت
backupFilename="wizwiz_$(date +'%Y-%m-%d_%H-%M-%S').sql"

# گرفتن بکاپ از دیتابیس
mysqldump -u$databaseUser -p$databasePassword $databaseName > $backupDir/$backupFilename

# آماده‌سازی آدرس API تلگرام برای ارسال فایل
telegramAPI="https://api.telegram.org/bot$telegramBotToken/sendDocument"

# ارسال فایل بکاپ به آیدی چت تلگرام
curl -F "chat_id=$chatID" -F "document=@$backupDir/$backupFilename" "$telegramAPI"

# حذف فایل و دایرکتوری موقت پس از ارسال
rm "$backupDir/$backupFilename"
rmdir "$backupDir"

