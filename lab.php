<?php

require_once "return_codes.php";

/* @brief Get a list of labs for a given institution
 * @param institution_id institution id
 * @param db database object
 * @retval a json encoded array of lab ids and names
 */
function get_list_of_labs($institution_id, $db)
{
	//query database for list of institutions
	$query="select lab_id, name from LAB where institution_id=? order by name asc";
	$sql=$db->prepare($query);
	$sql->bind_param('i', $institution_id);
	$sql->execute();
	$sql->bind_result($lab_id, $lab_name);
	$labs=array();
	array_push($labs, array(-1, "Select existing lab"));
	while($sql->fetch())
	{
		array_push($labs, array($lab_id, $lab_name));
	}
	echo json_encode($labs);
	$sql->free_result();
}


/*
 * @brief Get a list of labs in alphabetical that the user is a confirmed member of
 */
function get_list_of_user_labs($user_id, $db) {
	//query database for list of institutions
	$query="select LAB.lab_id, LAB.name from USER join LAB_MEMBER on USER.user_id = LAB_MEMBER.user_id join LAB on LAB.lab_id = LAB_MEMBER.lab_id where USER.user_id=? order by LAB.name asc";
	$sql=$db->prepare($query);
	$sql->bind_param('i', $user_id);
	$sql->execute();
	$sql->bind_result($lab_id, $lab_name);
	echo "<option value='-1'>Select one of your labs</option>\n";
	while($sql->fetch())
	{
		echo "\t<option value=".$lab_id.">".$lab_name."</option>\n";
	}
	$sql->free_result();
}

/*
 * @brief Allows a user to create a new lab
 * @param lab_name Name of lab
 * @param date_founded Date lab was founded
 * @param mission_statement Mission statement for the lab
 * @param lab_link A link to the labs website
 * @param institution_id The id of the associated institution
 * @param user_id The user id of the user who's creating the lab
 *
 * @param db the database object
 *
 * @retval The user's id associated with a valid email and password
 * @retval RET_LAB_ALREADY_EXISTS if the lab already exists by name
 * @retval RET_LAB_CREATION_FAILED if an unknown error occured when inserting the new lab
 */
function new_lab($lab_name, $date_founded, $mission_statement, $lab_link, $institution_id, $user_id, $db)
{
   // Check for NULL values
   if(	$lab_name == null ||
	$date_founded == null ||
	$mission_statement == null)
   {
      return $GLOBALS['RET_NULL_PARAM'];
   }

   // query database to see if a lab with the same name exists at this institution
   $queryA="select * from LAB where name=? and institution_id=?";
   $sqlA=$db->prepare($queryA);
   $sqlA->bind_param('si', $lab_name, $institution_id);
   $sqlA->execute();
   $sqlA->store_result();
   $numrowsA=$sqlA->num_rows;
   $sqlA->free_result();

   //the lab is available if the query returns 0 matching rows
   if($numrowsA != 0)
   {
      //the lab already exists so return error code
      return $GLOBALS['RET_LAB_ALREADY_EXISTS'];
   }

   //get the date
   $datetime = date("Y-m-d H:i:s");

   //the lab can be created so proceed with creating it
   $queryB="insert into LAB(institution_id, lab_creator_id, name, date_founded, mission_statement, link_website, datetime_created) values(?, ?, ?, ?, ?, ?, ?)";
   $sqlB=$db->prepare($queryB);
   $sqlB->bind_param('iisssss', $institution_id, $user_id, $lab_name, $date_founded, $mission_statement, $lab_link, $datetime);
   $sqlB->execute();
   $sqlB->free_result();

   //get lab id
   $lab_id = $db->insert_id;

   //check result is TRUE meaning the insert was successful
   if($sqlB != TRUE || $lab_id <= 0)
   {
      //something went wrong when creating lab
      return $GLOBALS['RET_LAB_CREATION_FAILED'];
   }
} //end new_lab()

/* @brief Query the database for information about an lab
 * @param lab_id an lab id
 * @param db database object
 * @retval a json encoded array of info about the lab, or error code
 */
function get_lab_data($lab_id, $db)
{
	//query database for lab info  TODO Join to get the institution name
	$query="select INSTITUTION.name, LAB.name, LAB.date_founded, LAB.picture_logo, LAB.mission_statement, LAB.link_website, LAB.nickname from LAB inner join INSTITUTION on LAB.institution_id = INSTITUTION.institution_id where lab_id=?;";
	$sql=$db->prepare($query);
	$sql->bind_param('i', $lab_id);
	$sql->execute();
	$sql->bind_result($institution_name, $lab_name, $date_founded, $picture_logo, $mission_statement, $link_website, $nickname);
	$lab=array();
	while($sql->fetch())
	{
		array_push($lab, $institution_name, $lab_name, $date_founded, $picture_logo, $mission_statement, $link_website, $nickname);
	}
	$sql->free_result();
	return $lab;
	//TODO: no error checking
}

?>
