<?php
require_once __DIR__ . '/../models/User.php';
$adminName = 'Admin';
$adminEmail = 'admin@admin.com';
$adminPassword = 'Admin1234!';
$user = new User();
$user->email = $adminEmail;
if ($user->emailExists()) {
    echo "Admin user already exists (email: {$adminEmail}).\n";
    $user->name = $adminName;
    $user->role = 'admin';
    if ($user->update()) {
        echo "Updated admin name/role to 'admin'.\n";
    } else {
        echo "Failed to update admin name/role: {$user->errorMessage}.\n";
    }
    $user->password = $adminPassword;
    if ($user->updatePassword()) {
        echo "Password reset successfully.\n";
    } else {
        echo "Failed to reset password.\n";
    }
    echo "Login: {$adminEmail}\n";
    echo "Password: {$adminPassword}\n";
    exit(0);
}
$user->name = $adminName;
$user->password = $adminPassword;
$user->role = 'admin';
if ($user->create()) {
    echo "Admin user created successfully.\n";
    echo "Login: {$adminEmail}\n";
    echo "Password: {$adminPassword}\n";
} else {
    echo "Failed to create admin user.\n";
}
