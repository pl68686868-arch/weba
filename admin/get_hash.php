<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Password Hash</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f0f0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; width: 100%; }
        code { display: block; background: #eee; padding: 1rem; margin: 1rem 0; word-break: break-all; border-radius: 4px; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Password Hash Generator</h2>
        <p>Password: <strong><?php echo $password; ?></strong></p>
        <p>BCrypt Hash (Copy this into your `users` table `password_hash` column):</p>
        <code><?php echo $hash; ?></code>
        <p>SQL Query:</p>
        <code>UPDATE users SET password_hash = '<?php echo $hash; ?>' WHERE username = 'admin';</code>
    </div>
</body>
</html>
