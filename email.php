<?php

//$PRIVATE_ROOT=$_SERVER['DOCUMENT_ROOT']."/tist_web_private/";
//include $PRIVATE_ROOT."return_codes.php";
include "return_codes.php";
require_once('/home/'.get_current_user().'/vendor/autoload.php');

use SwotPHP\Facades\Native\Swot;

/*
 * @brief Compare activation code with the value in the db
 * @param user_id user id
 * @param activation_code activation code
 * @param db database object
 * @retval user_id if confirmed, error code otherwise
 */
function confirm_email($user_id, $activation_code, $db)
{
   if(	$user_id == null ||
	$activation_code == null)
   {
      return $GLOBALS['RET_NULL_PARAM'];
   }

   // check if activation code has already been confirmed
   $queryA="select * from INSTITUTION_MEMBER where user_id=? and activation_code=? and confirmed=1";
   $sqlA=$db->prepare($queryA);
   $sqlA->bind_param('is', $user_id, $activation_code);
   $sqlA->execute();
   $sqlA->store_result();
   $numrowsA=$sqlA->num_rows;
   $sqlA->free_result();

   //the code has not been used if the query returns 0 matching rows
   if($numrowsA != 0)
   {
      return $GLOBALS['RET_ALREADY_CONFIRM_EMAIL'];
   }

   // confirm the activation code
   $query="update INSTITUTION_MEMBER set confirmed=1, datetime_confirmed=? where user_id=? and activation_code=?";
   $sql=$db->prepare($query);
   $sql->bind_param('sis', $datetime, $user_id, $activation_code);
   $sql->execute();
   $sql->store_result();
   $numrows=$sql->affected_rows;
   $sql->free_result();

   //check if update was successful
   if($numrows == 1)
   {
      //something went wrong when updating table
      return $user_id;
   }
   return $GLOBALS['RET_UNABLE_TO_CONFIRM_EMAIL'];
}

/*
 * @brief Lookup institution in the database by name
 * @param institution_name Institution name
 * @param db Database object
 * @retval institution id if found; 0 otherwise
 */
function is_in_db($institution_name, $db) {
	$queryA="select institution_id from INSTITUTION where name=?";
	$sqlA=$db->prepare($queryA);
	$sqlA->bind_param('s', $institution_name);
   	$sqlA->execute();
	$sqlA->bind_result($institution_id);
	$sqlA->fetch();
	$sqlA->free_result();

	//the school isn't in the db if the query returns 0 matching rows
	if($institution_id)
	{
		return $institution_id;
	}
	return 0;
}

/*
 * @brief Checks for an academic email address using the SWOTPHP database
 * @param email an email address
 * @param db database object
 * @return The instituion name if email domain is found; false otherwise
 */
function is_edu_email_address($email, $db)
{
	if(Swot::isAcademic($email)) {
		// there can be potentially many school names. for example:
		// Johns Hopkins University, The Johns Hopkins University
		// So let's loop over them to find a match in the db
		$school_name = explode("\n", Swot::schoolName($email));
		foreach ($school_name as &$name) {
			// loopup the name in the db
			$id = is_in_db($name, $db);
			// if we get an id for the inst, return
			if($id != 0) {
	       			return array($id, $name);
			}
		}
	} else {
		// email is not academic
	        return 0;
	}
	// email was academic according to SWOT but not in the db
	return 0;
}


/*
 * @brief generate a 13 character activatioin code
 * @param inst_id institution id
 * @param user_id user id
 * @return a 13 character activation code
 */
function generate_activation_code($inst_id, $user_id, $email, $db)
{
   //check that the email doesn't exist in the db
   $queryA="select * from INSTITUTION_MEMBER where email=?";
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

   $activation_code = uniqid();

   $datetime = date("Y-m-d H:i:s");

   $queryB="insert into INSTITUTION_MEMBER(user_id, institution_id, email, activation_code, datetime_created) values(?, ?, ?, ?, ?)";
   $sqlB=$db->prepare($queryB);
   $sqlB->bind_param('iisss', $user_id, $inst_id, $email, $activation_code, $datetime);
   $sqlB->execute();
   $sqlB->free_result();

   //check result is TRUE meaning the insert was successful
   if($sqlB != TRUE)
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
 * @retval 0 on success; failure otherwise
 */
function send_new_user_email($first_name, $email, $user_id, $db)
{
   $to      = $email;
   $subject = 'Welcome to The Tist';
   $message = '
   <html>
   <head>
     <title>Welcome to The Tist</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
   </head>
	<body style="margin: 0px; padding: 0px;" class=" hasGoogleVoiceExt">
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tbody><tr>
			<td style="padding: 10px 0 30px 0;">
				<table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border: 1px solid #cccccc; border-collapse: collapse;">
					<tbody><tr>
						<td align="center" bgcolor="#060" style="padding: 40px 0 30px 0; color: #153643; font-size: 28px; font-weight: bold; font-family: Arial, sans-serif;">
							<img src="http://www.the-tist.com/tist_web_private/img/The_Tist_Logo_1.png" alt="The Tist Logo" width="230" height="230" style="display: block;">
						</td>
					</tr>
					<tr>
						<td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tbody><tr>
									<td style="color: #153643; font-family: Arial, sans-serif; font-size: 24px;">
										<b>Welcome to The Tist!</b>
									</td>
								</tr>
								<tr>
									<td style="padding: 20px 0 30px 0; color: #153643; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px;">
										'.$first_name.', you are now part of a growing community of scientists communicating with the public about their research.
										This website allows the general public an opportunity to hear about science news and methods from the scientists themselves, and it provides all of the tools necessary for scientists to communicate their research clearly.
									</td>
								</tr>
   ';

   $inst_data = is_edu_email_address($email, $db);
   $inst_id = $inst_data[0];
   $inst_name = $inst_data[1];
   if($inst_id != 0)
   {
      $activation_code = generate_activation_code($inst_id, $user_id, $email, $db);
      if(strlen($activation_code) != 13)
      {
         $retval = $activation_code;
         return $retval;
      }
      $edu_message = '
								<tr>
									<td>
										<table border="0" cellpadding="0" cellspacing="0" width="100%">
											<tbody><tr>
												<td width="260" valign="top">
													<table border="0" cellpadding="0" cellspacing="0" width="100%">
														<tbody><tr>
															<td>
																<img src="http://www.the-tist.com/tist_web_private/img/rocket.png" alt="Rocket Ship Picture" height="180" style="display: block;">
															</td>
														</tr>
														<tr>
															<td style="padding: 25px 0 0 0; color: #153643; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px; display: block;">
															Congratulations!  Your account is almost verified.
															You simply need to verify your '.$inst_name.' email address here.
															Your Activation Code is <a href="http://the-tist.com/confirmation.php?user_id='.$user_id.'&activation_code='.$activation_code.'">'.$activation_code.'</a>
															</td>
														</tr>
													</tbody></table>
												</td>
												<td style="font-size: 0; line-height: 0;" width="20">
													&nbsp;
												</td>
												<td width="260" valign="top">
													<table border="0" cellpadding="0" cellspacing="0" width="100%">
														<tbody><tr>
															<td>
																<img src="http://www.the-tist.com/tist_web_private/img/profile.png" alt="Profile Picture" height="180" style="display: block;">
															</td>
														</tr>
														<tr>
															<td style="padding: 25px 0 0 0; color: #153643; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px;">
															Next, you can create a profile page where other colleagues, friends, family and fans can find you.  Go ahead! Start building on your Tist profile!
															</td>
														</tr>
													</tbody></table>
												</td>
											</tr>
										</tbody></table>
									</td>
								</tr>
      ';
      $message = $message.$edu_message;
   }
   $message_bottom = '
								</tbody></table>
						</td>
					</tr>
					<tr>
						<td bgcolor="#060" style="padding: 30px 30px 30px 30px;">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tbody><tr>
									<td style="color: #ffffff; font-family: Arial, sans-serif; font-size: 14px;" width="75%">
										 <a style="color:white;" href="http://www.science-share.com">Science Share, LLC,</a><br>
										<br>
									 A Nick Mathews Production
									</td>
									<td align="right" width="25%">
										<table border="0" cellpadding="0" cellspacing="0">
											<tbody><tr>
												<td style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">
													<a href="https://plus.google.com/+ScienceShareReading/posts" style="color: #ffffff;">
														<img src="http://www.the-tist.com/tist_web_private/img/googleplus.png" alt="Google Plus" width="38" height="38" style="display: block;" border="0">
													</a>
												</td>
												<td style="font-size: 0; line-height: 0;" width="20">&nbsp;</td>
												<td style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">
													<a href="https://www.facebook.com/itsallinthetist" style="color: #ffffff;">
														<img src="http://www.the-tist.com/tist_web_private/img/facebook.png" alt="Facebook" width="38" height="38" style="display: block;" border="0">
													</a>
												</td>
											</tr>
										</tbody></table>
									</td>
								</tr>
							</tbody></table>
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>
	</tbody></table>
</body></html>
   ';
   $message = $message . $message_bottom;

   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   $headers .= 'From: NickMathews@the-tist.com' . "\r\n";
   $headers .= 'Reply-To: NickMathews@the-tist.com' . "\r\n";
   $headers .= 'Bcc: tflorida17@gmail.com, NickMathews@the-tist.com' . "\r\n";
   mail($to, $subject, $message, $headers);

   return 0;
}

 /*
 * @brief Builds and sends an email to a user who wants to join an institution
 * @param selected_inst_id id of the institution than the user wants to join
 * @param first_name first name of user
 * @param email an email address
 * @param user_id user id
 * @param db database object
 * @retval 0 on success; failure otherwise
 */
function join_institution($selected_inst_id, $first_name, $email, $user_id, $db)
{
   if(	$selected_inst_id == null ||
        $first_name == null ||
	$email == null ||
	$user_id == null)
   {
      return $GLOBALS['RET_NULL_PARAM'];
   }

   $inst_data = is_edu_email_address($email, $db);
   $inst_id = $inst_data[0];
   $inst_name = $inst_data[1];

   // We have a valid institutional email
   if($inst_id != 0) {
      // Check that institution id that was selected on the form matches the
      // institution id that corresponds to the domain of the email address
      if($inst_id != $selected_inst_id) {
         return $GLOBALS['RET_EMAIL_INSTITUTION_MISMATCH'];
      }

      // Generate activation code
      $activation_code = generate_activation_code($inst_id, $user_id, $email, $db);
      if(strlen($activation_code) != 13)
      {
         $retval = $activation_code;
         return $retval;
      }
      $title   = "Join Institution";
      $to      = $email;
      $subject = $title;
      $message = '
      <html>
      <head>
        <title>'.$title.'</title>
      </head>
      <body>
      <p>Hi '.$first_name.',</p>
      <p>Click <a href="http://the-tist.com/confirmation.php?user_id='.$user_id.'&activation_code='.$activation_code.'">here</a> to join '.$inst_name.'</p>
      <p>Thanks!</p>';
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
      $headers .= 'From: NickMathews@the-tist.com' . "\r\n";
      $headers .= 'Reply-To: NickMathews@the-tist.com' . "\r\n";
      $headers .= 'Bcc: tflorida17@gmail.com, NickMathews@the-tist.com' . "\r\n";
      mail($to, $subject, $message, $headers);
   } else {
      return $GLOBALS['RET_NOT_AN_INSTITUTION_EMAIL'];
   }

   return $user_id;
}

?>
