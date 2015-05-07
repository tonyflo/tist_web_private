<?php
/* @file return_codes.php
 * @date 2014-09-22
 * @author Tony Florida
 * @brief Return codes for PHP functions
 */

$RET_SUCCESS = 0;
$RET_ERROR = -1;
$RET_EMAIL_NOT_AVAILABLE = -2;
$RET_SIGN_UP_FAILED = -3;
$RET_NULL_PARAM = -4;
$RET_EMAIL_NOT_FOUND = -5;
$RET_INVALID_PASSWORD = -6;

$status = array(
    $RET_SUCCESS => "Success",
    $RET_ERROR => "Error",
    $RET_EMAIL_NOT_AVAILABLE => "Email not available",
    $RET_SIGN_UP_FAILED => "Signing up failed",
    $RET_NULL_PARAM => "Missing information",
    $RET_EMAIL_NOT_FOUND => "Email not found",
    $RET_INVALID_PASSWORD => "Invalid password",
);

?>
