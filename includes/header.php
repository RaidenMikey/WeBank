<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - WeBank' : 'WeBank - Your Trusted Banking Partner'; ?></title>
    <link rel="icon" type="image/jpeg" href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false ? '../assets/images/wb_logo.jpg' : 'assets/images/wb_logo.jpg'; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Prevent bfcache (back/forward cache) issues
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
    <?php if (strpos($_SERVER['PHP_SELF'], '/user/') !== false): ?>
    <style>
        body {
            background-image: url('../assets/images/9517996.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
    <?php elseif (strpos($_SERVER['PHP_SELF'], '/admin/') !== false): ?>
    <style>
        body {
            background-image: url('../assets/images/9517748.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : 'bg-gray-50 min-h-screen flex flex-col'; ?>">
