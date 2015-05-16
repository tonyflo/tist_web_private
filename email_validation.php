<?php

require_once('/home/tflorida17/vendor/autoload.php');

use SwotPHP\Facades\Native\Swot;

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

?>
