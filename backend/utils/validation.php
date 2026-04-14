<?php
class Validation {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    public static function validatePassword($password) {
        return strlen($password) >= 8;
    }
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags($input));
    }
    public static function validateRequired($input) {
        return !empty(trim($input));
    }
}
?>