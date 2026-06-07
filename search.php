<?php
// error_reporting(0); // Uncomment for production
date_default_timezone_set('Asia/Tehran');

// --- Required Files Check ---
if (!file_exists("baseInfo.php") || !file_exists("config.php")) {
    form("خطای سیستمی: فایل‌های مورد نیاز یافت نشدند. لطفاً با پشتیبانی تماس بگیرید.");
    exit();
}
require "baseInfo.php";
require "config.php";
include "jdf.php";

// --- Main Logic ---
if (isset($_REQUEST['id'])) {
    $config_link_raw = trim($_REQUEST['id']);
    
    if (preg_match('/^vmess:\/\/(.*)/', $config_link_raw, $match)) {
        $jsonDecode = json_decode(base64_decode($match[1]), true);
        $config_link = $jsonDecode['id'] ?? '';
    } elseif (preg_match('/^vless:\/\/([a-f0-9-]{36})/', $config_link_raw, $match)) {
        $config_link = $match[1];
    } elseif (preg_match('/^trojan:\/\/([a-f0-9-]{36})/', $config_link_raw, $match)) {
        $config_link = $match[1];
    } else {
        $config_link = $config_link_raw;
    }

    if (!preg_match('/[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{3}\-[a-f0-9]{12}/', $config_link) && !(preg_match('/^[a-zA-Z0-9]{5,15}/', $config_link)) && !(preg_match('/^vmess/', $config_link_raw))) {
        form("لینک یا شناسه وارد شده معتبر نمی‌باشد. لطفاً مجدداً بررسی کنید.");
        exit();
    }
    
    $config_link = htmlspecialchars(stripslashes($config_link));

    $stmt = $connection->prepare("SELECT * FROM `server_config`");
    $stmt->execute();
    $serversList = $stmt->get_result();
    $stmt->close();
    $found = false;
    
    while ($row = $serversList->fetch_assoc()) {
        $serverId = $row['id'];
        $serverType = $row['type'];

        if ($serverType == "marzban") {
             $response = getMarzbanUser($config_link_raw, $serverId);
            if(isset($response->username)){
                $config = $response;
                $found = true;
                $remark = $config->username;
                $total = $config->data_limit;
                $totalUsed = $config->used_traffic;
                $state = $config->status == "active" ? "فعال 🟢" : "غیرفعال 🔴";
                $expiryTime = $config->expire != 0 ? jdate("Y-m-d H:i:s", $config->expire) : "نامحدود";
                $leftMb = $total != 0 ? $total - $totalUsed : 0;
                if ($leftMb < 0) $leftMb = 0;
                $expiryDay = $config->expire != 0 ? floor(($config->expire - time()) / (60 * 60 * 24)) : "نامحدود";
                if (is_numeric($expiryDay) && $expiryDay < 0) $expiryDay = 0;
                break;
            }
        } else {
            $response = getJson($serverId);
            if ($response && $response->success) {
                $list = $response->obj;
                $foundClient = null;

                foreach ($list as $client) {
                    $settings = json_decode($client->settings, true);
                    if (isset($settings['clients'])) {
                        foreach ($settings['clients'] as $user) {
                            if (isset($user['id']) && $user['id'] == $config_link) {
                                $foundClient = $client;
                                $clientStats = $client->clientStats ?? [];
                                $email = $user['email'];
                                $userStats = null;
                                foreach($clientStats as $stat){
                                    if($stat->email == $email){
                                        $userStats = $stat;
                                        break;
                                    }
                                }
                                $remark = $user['email'] ?? 'N/A';
                                $total = $userStats->total ?? $client->total;
                                $upload = $userStats->up ?? 0;
                                $download = $userStats->down ?? 0;
                                $totalUsed = $upload + $download;
                                $expiryTimeValue = $userStats->expiryTime ?? $client->expiryTime;
                                $state = ($userStats->enable ?? $client->enable) ? "فعال 🟢" : "غیر فعال 🔴";
                                break 2;
                            }
                        }
                    } else if (isset($client->remark) && str_contains($client->settings, $config_link)) {
                         $foundClient = $client;
                         $remark = $client->remark;
                         $total = $client->total;
                         $upload = $client->up;
                         $download = $client->down;
                         $totalUsed = $upload + $download;
                         $expiryTimeValue = $client->expiryTime;
                         $state = $client->enable ? "فعال 🟢" : "غیر فعال 🔴";
                         break;
                    }
                }

                if ($foundClient) {
                    $found = true;
                    $leftMb = $total != 0 ? $total - $totalUsed : 0;
                    if ($leftMb < 0) $leftMb = 0;
                    $expiryTime = $expiryTimeValue != 0 ? jdate("Y-m-d H:i:s", substr($expiryTimeValue, 0, -3)) : "نامحدود";
                    $expiryDay = $expiryTimeValue != 0 ? floor((substr($expiryTimeValue, 0, -3) - time()) / (60 * 60 * 24)) : "نامحدود";
                    if (is_numeric($expiryDay) && $expiryDay < 0) $expiryDay = 0;
                    break;
                }
            }
        }
    }
    
    if (!$found) {
        form("سرویسی با این مشخصات یافت نشد یا لینک شما منقضی شده است.");
    } else {
        showForm("configInfo");
    }
} else {
    showForm("unknown");
}
?>

<?php
function showForm($type)
{
    global $remark, $state, $total, $totalUsed, $leftMb, $expiryTime, $expiryDay;
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            <?php if ($type == "unknown") echo "استعلام سرویس | SWITCH VP";
            else echo "وضعیت سرویس | SWITCH VP"; ?>
        </title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
        
        <style>
            :root {
                --primary-hue: 210;
                --primary-color: hsl(var(--primary-hue), 100%, 60%);
                --primary-glow: hsl(var(--primary-hue), 100%, 50%, 0.5);
                --bg-dark: #0d1117;
                --bg-light: #161b22;
                --border-color: #30363d;
                --text-color: #c9d1d9;
                --text-secondary: #8b949e;
                --font-family: 'Vazirmatn', sans-serif;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes chart-animate {
                from { stroke-dashoffset: 251.2; }
                to { stroke-dashoffset: var(--chart-offset); }
            }

            body {
                font-family: var(--font-family);
                background-color: var(--bg-dark);
                color: var(--text-color);
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: flex-start; /* Changed to flex-start */
                min-height: 100vh;
                padding: 2rem 1rem;
                box-sizing: border-box;
                overflow-y: auto; /* Allow vertical scrolling */
            }

            .main-container {
                width: 100%;
                max-width: 500px;
                background-color: var(--bg-light);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 2.5rem;
                text-align: center;
                animation: fadeIn 0.8s ease-out;
            }
            
            .header { margin-bottom: 2rem; }
            .logo {
                width: 80px; height: 80px; margin: 0 auto 1.5rem;
                background: linear-gradient(135deg, var(--primary-color), hsl(var(--primary-hue), 80%, 70%));
                color: white; display: flex; justify-content: center; align-items: center;
                font-size: 2.5rem; font-weight: 800; border-radius: 50%;
                box-shadow: 0 0 25px var(--primary-glow);
            }
            .header h1 { margin: 0; font-size: 2rem; font-weight: 700; color: #fff; }
            .header p { margin-top: 0.5rem; color: var(--text-secondary); font-size: 1rem; }

            /* Search Form */
            .search-form input {
                width: 100%; padding: 16px; border: 1px solid var(--border-color); border-radius: 12px;
                font-family: var(--font-family); font-size: 1rem; text-align: center;
                transition: all 0.3s; box-sizing: border-box; background: var(--bg-dark); color: var(--text-color);
            }
            .search-form input::placeholder { color: var(--text-secondary); }
            .search-form input:focus {
                outline: none; border-color: var(--primary-color);
                box-shadow: 0 0 0 4px hsla(var(--primary-hue), 100%, 60%, 0.3);
            }
            .search-form button {
                width: 100%; padding: 16px; margin-top: 1rem; border: none; border-radius: 12px;
                background: var(--primary-color); color: white; font-size: 1.1rem; font-weight: 700;
                cursor: pointer; transition: all 0.3s;
            }
            .search-form button:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 15px var(--primary-glow);
            }

            /* Config Info */
            .info-header { margin-bottom: 2rem; }
            .info-header .name { font-size: 1.75rem; font-weight: 700; color: #fff; word-break: break-all; margin-bottom: 0.5rem; }
            .info-header .status { font-size: 1.1rem; font-weight: 500; }
            
            .chart-container {
                position: relative;
                width: 200px;
                height: 200px;
                margin: 2rem auto;
            }
            .chart-svg {
                transform: rotate(-90deg);
                width: 100%;
                height: 100%;
            }
            .chart-bg, .chart-fg {
                fill: none;
                stroke-width: 12;
            }
            .chart-bg { stroke: var(--border-color); }
            .chart-fg {
                stroke-linecap: round;
                stroke: var(--primary-color);
                stroke-dasharray: 251.2;
                animation: chart-animate 1.5s cubic-bezier(0.65, 0, 0.35, 1) forwards;
            }
            .chart-text {
                position: absolute;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
            }
            .chart-text-value {
                font-size: 2rem;
                font-weight: 800;
                color: #fff;
            }
            .chart-text-label {
                font-size: 0.9rem;
                color: var(--text-secondary);
            }

            .info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
            .info-item {
                background: var(--bg-dark);
                padding: 1.5rem 1rem;
                border: 1px solid var(--border-color);
                border-radius: 16px;
            }
            .info-item .label {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                font-size: 0.9rem;
                color: var(--text-secondary);
                margin-bottom: 0.75rem;
            }
            .info-item .value { font-size: 1.4rem; font-weight: 700; color: #fff; }

            .guides { margin-top: 2.5rem; }
            .section-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; color: #fff; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; display: inline-block; }
            
            .guides-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
            .guide-link {
                display: flex; align-items: center; justify-content: center; gap: 0.75rem;
                padding: 1rem; text-decoration: none; color: var(--text-secondary);
                background: var(--bg-dark); border: 1px solid var(--border-color);
                border-radius: 12px; transition: all 0.3s;
                font-weight: 500;
            }
            .guide-link:hover {
                transform: translateY(-3px);
                border-color: var(--primary-color);
                background-color: var(--bg-light);
                color: #fff;
            }
            .guide-link svg { flex-shrink: 0; }

            .footer { margin-top: 2.5rem; font-size: 0.9rem; color: #666; }
            .footer a { color: var(--text-secondary); text-decoration: none; font-weight: 500; }
        </style>
    </head>
    <body>
    <?php if ($type == "configInfo"):
        $total_bytes = $total > 0 ? $total : 0;
        $used_bytes = $totalUsed > 0 ? $totalUsed : 0;
        
        $total_gb = $total_bytes / (1024 * 1024 * 1024);
        $used_gb = $used_bytes / (1024 * 1024 * 1024);
        $left_gb = ($total_bytes - $used_bytes) / (1024 * 1024 * 1024);

        $percent_used = $total_bytes > 0 ? ($used_bytes / $total_bytes) : 0;
        $chart_offset = 251.2 * (1 - $percent_used);
        
        if ($percent_used >= 0.9) $chart_color = 'var(--danger-color)';
        elseif ($percent_used >= 0.7) $chart_color = 'var(--warning-color)';
        else $chart_color = 'var(--primary-color)';
    ?>
        <div class="main-container">
            <header class="header">
                <div class="logo">SV</div>
                <div class="info-header">
                    <h1 class="name"><?= htmlspecialchars($remark) ?></h1>
                    <div class="status">وضعیت: <?= $state ?></div>
                </div>
            </header>

            <div class="chart-container">
                <svg class="chart-svg" viewBox="0 0 100 100">
                    <circle class="chart-bg" cx="50" cy="50" r="40"></circle>
                    <circle class="chart-fg" cx="50" cy="50" r="40" style="--chart-offset: <?= $chart_offset ?>; stroke: <?= $chart_color ?>;"></circle>
                </svg>
                <div class="chart-text">
                    <div class="chart-text-value"><?= round($percent_used * 100) ?>%</div>
                    <div class="chart-text-label">مصرف شده</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
                        <span>مصرف شده</span>
                    </div>
                    <div class="value"><?= round($used_gb, 2) ?> GB</div>
                </div>
                <div class="info-item">
                    <div class="label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 4.86 2.451 10.342c-.64.64.305 1.658 1.183 1.658h9.592a1.002 1.002 0 0 0 .707-1.707l-4.796-5.48a1 1 0 0 0-1.506 0z"/></svg>
                        <span>باقی‌مانده</span>
                    </div>
                    <div class="value"><?= ($total_bytes > 0) ? round($left_gb, 2) . ' GB' : '∞' ?></div>
                </div>
                <div class="info-item">
                    <div class="label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2z"/></svg>
                        <span>روزهای باقی‌مانده</span>
                    </div>
                    <div class="value"><?= is_numeric($expiryDay) ? $expiryDay . ' روز' : '∞' ?></div>
                </div>
                <div class="info-item">
                    <div class="label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/><path d="M7.5 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/></svg>
                        <span>تاریخ انقضا</span>
                    </div>
                    <div class="value" style="font-size: 1.1rem;"><?= $expiryTime ?></div>
                </div>
            </div>

            <div class="guides">
                <div class="section-title">راهنمای اتصال</div>
                <div class="guides-grid">
                    <a href="https://t.me/SwitchVpGuide/10?single" target="_blank" class="guide-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h6zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H5z"/><path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/></svg>
                        <span>اندروید</span>
                    </a>
                    <a href="https://t.me/SwitchVpGuide/17?single" target="_blank" class="guide-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11.182.008C11.148-.03 9.923.023 8.857 1.18c-1.066 1.156-.902 2.482-.878 2.516.024.034 1.393.118 2.394-1.21s.931-2.617.931-2.617zM10.16 6.38c.556.54.452 1.235.424 1.41s-.223.957-.693 1.36c-.47.403-1.137.455-1.32.43s-.922-.192-1.47-.682c-.556-.54-.452-1.235-.424-1.41s.223-.957.693-1.36c.47-.403 1.137-.455 1.32-.43s.922.192 1.47.682z"/><path d="M.087 4.21c.229-2.417 2.135-4.21 4.56-4.21s4.533 1.793 4.533 4.21c0 2.296-1.636 4.21-3.998 4.21a4.534 4.534 0 0 1-1.04-.153A4.346 4.346 0 0 1 3.1 7.21a4.41 4.41 0 0 1-2.229-3.693z"/></svg>
                        <span>آیفون</span>
                    </a>
                    <a href="https://t.me/SwitchVpGuide/25?single" target="_blank" class="guide-link">
                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M12 1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM4 0a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4z"/><path d="M4 1.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-1z"/></svg>
                        <span>ویندوز</span>
                    </a>
                    <a href="https://t.me/SwitchVpGuide/4" target="_blank" class="guide-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                        <span>نرم‌افزارها</span>
                    </a>
                </div>
            </div>

            <footer class="footer">
                <p>ارائه شده توسط <b>SWITCH VP</b></p>
            </footer>
        </div>

    <?php elseif ($type == "unknown"): ?>
        <div class="main-container">
            <header class="header">
                <div class="logo">SV</div>
                <h1>استعلام وضعیت سرویس</h1>
                <p>برای مشاهده اطلاعات، لینک یا شناسه کانفیگ خود را وارد کنید.</p>
            </header>
            
            <form class="search-form" action="" method="get">
                <fieldset style="padding: 0; border: none; margin: 0;">
                    <input placeholder="لینک یا شناسه کانفیگ..." type="text" id="id" name="id" autocomplete="off" required>
                </fieldset>
                <fieldset style="padding: 0; border: none; margin: 0;">
                    <button type="submit">جستجو</button>
                </fieldset>
            </form>
            <footer class="footer">
                <p>ارائه شده توسط <b>SWITCH VP</b></p>
            </footer>
        </div>
    <?php endif; ?>
    </body>
    </html>
    <?php
}

function form($msg)
{
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>خطا | SWITCH VP</title>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --danger-color: #e53e3e;
                --bg-dark: #0d1117;
                --bg-light: #161b22;
                --border-color: #30363d;
                --text-color: #c9d1d9;
                --font-family: 'Vazirmatn', sans-serif;
            }
            body {
                font-family: var(--font-family); background-color: var(--bg-dark); color: var(--text-color);
                margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh;
                padding: 1rem; box-sizing: border-box;
            }
            .main-container {
                width: 100%; max-width: 480px; padding: 2.5rem; background-color: var(--bg-light);
                border: 1px solid var(--border-color); border-radius: 16px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
                text-align: center;
            }
            .error-icon { color: var(--danger-color); margin-bottom: 1.5rem; }
            .error-message { font-size: 1.2rem; font-weight: 500; margin-bottom: 2rem; line-height: 1.7; color:#fff; }
            .back-button {
                display: inline-block; padding: 14px 35px; border: none; border-radius: 12px;
                background-color: var(--danger-color); color: white; font-size: 1rem; font-weight: 700;
                cursor: pointer; text-decoration: none; transition: all 0.3s;
            }
            .back-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 15px rgba(229, 62, 62, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="main-container">
            <div class="error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
            </div>
            <div class="error-message">
                <?= htmlspecialchars($msg) ?>
            </div>
            <a href="?" class="back-button">بازگشت و تلاش مجدد</a>
        </div>
    </body>
    </html>
    <?php
}
?>
