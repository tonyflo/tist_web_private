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
$RET_ACTIVATION_CODE_GENERATION_FAILED = -7;
$RET_UNABLE_TO_CONFIRM_EMAIL = -8;
$RET_ALREADY_CONFIRM_EMAIL = -9;
$RET_NOT_AN_INSTITUTION_EMAIL = -10;
$RET_EMAIL_INSTITUTION_MISMATCH = -11;
$RET_PROJECT_LAB_FAILURE = -12;
$RET_LAB_MEMBER_FAILURE = -13;
$RET_PROJECT_CREATION_FAILURE = -14;
$RET_PROJECT_ALREADY_EXISTS = -15;
$RET_LAB_ALREADY_EXISTS = -16;
$RET_LAB_CREATION_FAILED = -17;
$RET_PROJECT_MEMBER_FAILURE = -18;
$RET_PROJECT_LAB_CON_FAILURE = -19;

$status = array(
    $RET_SUCCESS => "Success",
    $RET_ERROR => "Error",
    $RET_EMAIL_NOT_AVAILABLE => "Email not available",
    $RET_SIGN_UP_FAILED => "Signing up failed",
    $RET_NULL_PARAM => "Missing information",
    $RET_EMAIL_NOT_FOUND => "Email not found",
    $RET_INVALID_PASSWORD => "Invalid password",
    $RET_ACTIVATION_CODE_GENERATION_FAILED => "Activation code generation failed",
    $RET_UNABLE_TO_CONFIRM_EMAIL => "Unable to confirm email",
    $RET_ALREADY_CONFIRM_EMAIL => "Email is already confirmed",
    $RET_NOT_AN_INSTITUTION_EMAIL => "Not a valid institutional email",
    $RET_EMAIL_INSTITUTION_MISMATCH => "Email does not match selected institution",
    $RET_PROJECT_LAB_FAILURE => "Unable to associate project with lab",
    $RET_LAB_MEMBER_FAILURE => "Unable to associate user with lab",
    $RET_PROJECT_CREATION_FAILURE => "Unable to create the project",
    $RET_PROJECT_ALREADY_EXISTS => "Project already exists at this lab",
    $RET_LAB_ALREADY_EXISTS => "Lab already exists at this institution",
    $RET_LAB_CREATION_FAILED => "Unable to create the lab",
    $RET_PROJECT_MEMBER_FAILURE => "Unable to associate the user with project",
    $RET_PROJECT_LAB_CON_FAILURE => "No relationship between project and lab",
);

?>
