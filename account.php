<?php
/*
 * @brief Functions that allow a user to interact with their data
 */

require_once "return_codes.php";
require_once "email_validation.php";

/*************************************************************************
 * Private Helper Functions
 ************************************************************************/

 /*
 * @brief Converts a string into a hash
 * @source http://alias.io/2010/01/store-passwords-safely-with-php-and-mysql/
 * @param password a password between 6 and 255 characters
 * @retval the hashed value of the password
 */
function pw_hash($password)
{
    // A higher "cost" is more secure but consumes more processing power
    $cost = 10;

    // Create a random salt
    $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');

    // Prefix information about the hash so PHP knows how to verify it later.
    // "$2a$" Means we're using the Blowfish algorithm. The following two digits are the cost parameter.
    $salt = sprintf("$2a$%02d$", $cost) . $salt;

    // Hash the password with the salt
    $hashed = crypt($password, $salt);

    return $hashed;
} //end pw_hash()

 /*
 * @brief generate a 13 character activatioin code
 * @param user_id user id
 * @return a 13 character activation code
 */
function generate_activation_code($user_id, $db)
{
   $activation_code = uniqid();

   $queryA="update CREDENTIALS set activation_code=? where user_id=?";
   $sqlA=$db->prepare($queryA);
   $sqlA->bind_param('si', $activation_code, $user_id);
   $sqlA->execute();
   $sqlA->free_result();

   //check result is TRUE meaning the insert was successful
   if($sqlA != TRUE)
   {
      //something went wrong when signing up
      return $GLOBALS['RET_ACTIVATION_CODE_GENERATION_FAILED'];
   }

   return $activation_code;
}

 /*
 * @brief Builds and sends an email to a new user
 * @param first_name first name of user
 * @param email an email address
 * @param user_id user id
 * @param db database object
 */
function send_new_user_email($first_name, $email, $user_id, $db)
{
   $to      = $email;
   $subject = 'Welcome to The Tist';
   $message = '
   <html>
   <head>
     <title>Welcome to The Tist</title>
   </head>
   <body>
     <p>Dear '.$first_name.',</p>
     <p>A common message to ever user goes here</p>
   ';

   $inst_data = is_edu_email_address($email, $db);
   $inst_id = $inst_data[0];
   $inst_name = $inst_data[1];
   if($inst_id != 0)
   {
      $activation_code = generate_activation_code($user_id, $db);
      $edu_message = '
      <p>A specific message to only users with a *.edu address goes here</p>
      <p>This user is from '.$inst_name.'</p>
      <p>Below is your activation code</p>
      <p><b>'.$activation_code.'</b></p>
      <p>Click <a href="http://the-tist.com/confirmation.php?activation_code='.$activation_code.'">this</a> link to confirm your email address</p>
      ';
      $message = $message.$edu_message;
   }

   $message_bottom = '
   <p>Cheers!</p>
   <p>The Tist Team</p>
   </body>
   </html>
   ';
   $message = $message . $message_bottom;

   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   $headers .= 'From: NickMathews@the-tist.com' . "\r\n";
   $headers .= 'Reply-To: NickMahtews@the-tist.com' . "\r\n";
   $headers .= 'Bcc: tflorida17@gmail.com, NickMahtews@the-tist.com' . "\r\n";
   mail($to, $subject, $message, $headers);
}

/*************************************************************************
 * Public MySQL Functions
 ************************************************************************/

 /*
 * @brief Allows a user to sign into their account
 * @param email User's email
 * @param password A password between 6 and 255 characters
 *
 * @param db the database object
 *
 * @retval The user's id associated with a valid email and password
 * @retval RET_EMAIL_NOT_FOUND if the email was not found in the database
 * @retval RET_INVALID_PASSWORD if the password is wrong
 */
function sign_in($email, $password, $db)
{
   //query database for provided email
   $query="select user_id, password, confirmed, activation_code from CREDENTIALS where email=?";
   $sql=$db->prepare($query);
   $sql->bind_param('s', $email);
   $sql->execute();
   $sql->bind_result($user_id, $hash, $confirmed, $activation_code);
   $sql->fetch();
   $sql->free_result();

   //try to convert user_id to an int; if fails, value will be 0; assume credentials are wrong
   $user_id=intval($user_id);

   //check valid user_id
   if($user_id > 0)
   {
      //compare provided password to hash
      $valid = strcmp(crypt($password, $hash), $hash);

      if ($valid != 0)
      {
        //the provided password was wrong
        return $GLOBALS['RET_INVALID_PASSWORD'];
      }

      //the provided password was correct so return the valid user_id
      return $user_id;
   }
   else
   {
      //the email was not found
      return $GLOBALS['RET_EMAIL_NOT_FOUND'];
   }
} //end sign_in()

/*
 * @brief Allows a guest to sign up for an account
 *
 * @param first_name The first name of the user up to 255 characters
 * @param last_name The last name of the user up to 255 characters
 * @param date_birth The birthdate of the user
 * @param gender_id the gender of account: 0 if male, 1 is female
 * @param email A valid, unique email address
 * @param password A password between 6 and 255 characters
 *
 * @param db The database object
 *
 * @retval The primary key associated with the new account
 * @retval RET_EMAIL_NOT_AVAILABLE if the email address is in use
 * @retval RET_SIGN_UP_FAILED if signing up fails
 * @retval RET_NULL_PARAM if a parameter was that's not allowed to be
 */
function sign_up($first_name, $last_name, $date_birth, $gender_id, $email, $password, $db)
{
   // Check for NULL values
   if(	$first_name == null ||
	$last_name == null ||
	$date_birth == null ||
	$gender_id == null ||
	$email == null ||
	$password == null)
   {
      return $GLOBALS['RET_NULL_PARAM'];
   }

   //check that the email doesn't exist in the db
   $queryA="select * from CREDENTIALS where email=?";
   $sqlA=$db->prepare($queryA);
   $sqlA->bind_param('s', $email);
   $sqlA->execute();
   $sqlA->store_result();
   $numrowsA=$sqlA->num_rows;
   $sqlA->free_result();

   //the email address is available if the query returns 0 matching rows
   if($numrowsA != 0)
   {
      //the email address is taken so return error code
      return $GLOBALS['RET_EMAIL_NOT_AVAILABLE'];
   }

   //get the date
   $date = date("Y-m-d H:i:s");

   //the email address is available so proceed with creating account
   $queryB="insert into USER(first_name, last_name, date_birth, gender, datetime_created) values(?, ?, ?, ?, ?)";
   $sqlB=$db->prepare($queryB);
   $sqlB->bind_param('sssis', $first_name, $last_name, $date_birth, $gender_id, $date);
   $sqlB->execute();
   $sqlB->free_result();

   //get user id
   $user_id = $db->insert_id;

   //check result is TRUE meaning the insert was successful
   if($sqlB != TRUE || $user_id <= 0)
   {
      //something went wrong when signing up
      return $GLOBALS['RET_SIGN_UP_FAILED'];
   }

   //hash the password
   $hash=pw_hash($password);

   //creating the row in the user table was successful so create a row in the credentials table
   $queryC="insert into CREDENTIALS(user_id, email, password) values(?, ?, ?)";
   $sqlC=$db->prepare($queryC);
   $sqlC->bind_param('iss', $user_id, $email, $hash);
   $sqlC->execute();
   $sqlC->free_result();

   //check result is TRUE meaning the insert was successful
   if($sqlC != TRUE)
   {
      //something went wrong when signing up
      return $GLOBALS['RET_SIGN_UP_FAILED'];
   }

   //send new user email
   send_new_user_email($first_name, $email, $user_id, $db);

   //sign in as normal to get the user id
   return sign_in($email, $password, $db);

} //end sign_up()

?>
