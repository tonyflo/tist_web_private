<?php
/*
 * @brief Functions that allow a user to interact with their data
 */

require_once "return_codes.php";

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

/*************************************************************************
 * Public MySQL Functions
 ************************************************************************/

 /*
 * @brief Allows a user to sign into their account
 * @param email User's email
 * @param password A password between 6 and 255 characters
 *
 * @param db the database object
 * @param table the table name
 *
 * @retval The user's id associated with a valid email and password
 * @retval RET_EMAIL_NOT_FOUND if the email was not found in the database
 * @retval RET_INVALID_PASSWORD if the password is wrong
 */
function sign_in($email, $password, $db, $table)
{
   //query database for provided email
   $query="select user_id, password from ".$table." where email=?";
   $sql=$db->prepare($query);
   $sql->bind_param('s', $email);
   $sql->execute();
   $sql->bind_result($user_id, $hash);
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
 * @brief Allows a user to sign up for an account
 *
 * @param title A perfix to a user's name (Mr. Mrs. Ms. Miss Dr.)
 * @param first_name The first name of the user up to 255 characters
 * @param last_name The last name of the user up to 255 characters
 * @param role A user's role on the website
 * @param email A valid, unique email address. Will act as an email address
 * @param phone User's phone number
 * @param password A password between 6 and 255 characters
 * @param dob The birthdate of the user
 * @param topic_id The academic discipline that the user is associated with
 * @param gender the gender of account: 0 if male, 1 is female
 *
 * @param db The database object
 * @param table The table name
 *
 * @retval The primary key associated with the new account
 * @retval RET_EMAIL_NOT_AVAILABLE if the email address is in use
 * @retval RET_SIGN_UP_FAILED if signing up fails
 * @retval RET_NULL_PARAM if a parameter was that's not allowed to be
 */
function sign_up($title, $first_name, $last_name, $role, $email, $phone, $password, $dob, $topic_id, $gender, $db, $table)
{

   // Check for NULL values
   if($first_name == null ||
      $last_name == null ||
	  $email == null ||
	  $password == null)
   {
      return $GLOBALS['RET_NULL_PARAM'];
   }
   //check that the email doesn't exist in the db
   $queryA="select * from ".$table." where email=?";
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
   //hash the password
   $hash=pw_hash($password);

   //get the date
   $date = date("Y-m-d H:i:s");

   //the email address is available so proceed with creating account
   $query2="insert into ".$table."(title, first_name, last_name, role, email, account_creation_date_time, phone, password, dob, last_logon_date_time, topic_id, gender) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
   $sql2=$db->prepare($query2);
   $sql2->bind_param('ssssssssssii', $title, $first_name, $last_name, $role, $email, $date, $phone, $hash, $dob, $date, $topic_id, $gender);
   $sql2->execute();
   $sql2->free_result();

   //check result is TRUE meaning the insert was successful
   if($sql2 == TRUE)
   {
      //sign in as normal to get the user id
      return sign_in($email, $password, $db, $table);
   }
   else
   {
      //something went wrong when signing up
      return $GLOBALS['RET_SIGN_UP_FAILED'];
   }

} //end sign_up()

?>
