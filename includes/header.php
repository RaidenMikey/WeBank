<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - WeBank' : 'WeBank - Your Trusted Banking Partner'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : 'bg-gray-50 min-h-screen flex flex-col'; ?>">
