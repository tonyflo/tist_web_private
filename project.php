<?php

require_once "return_codes.php";

/* @brief Get a list of projects for a given lab
 * @param lab_id lab id
 * @param db database object
 * @retval a json encoded array of project ids and names
 */
function get_list_of_projects($lab_id, $db)
{
	//query database for list of institutions
	$query="select PROJECT.project_id, PROJECT.name from PROJECT join PROJECT_LAB on PROJECT.project_id = PROJECT_LAB.project_id where PROJECT_LAB.lab_id=? order by PROJECT.name asc";
	$sql=$db->prepare($query);
	$sql->bind_param('i', $lab_id);
	$sql->execute();
	$sql->bind_result($project_id, $project_name);
	$projects=array();
	array_push($projects, array(-1, "Select existing project"));
	while($sql->fetch())
	{
		array_push($projects, array($project_id, $project_name));
	}
	echo json_encode($projects);
	$sql->free_result();
}

/*
 * @brief Allows a user to create a new project
 * @param project_name Name of project
 * @param date_start Date project was started
 * @param summary_science Science summary for the project
 * @param summary_impact Science impact for the project
 * @param state_id The id of the phase of the project
 * @param link_website A link to the projects website
 * @param institution_id The id of the associated institution
 * @param user_id The user id of the user who's creating the lab
 * @param lab_id The lab id of the project to be created
 *
 * @param db the database object
 *
 * TODO
 * @retval The user's id associated with a valid email and password
 * @retval RET_EMAIL_NOT_FOUND if the email was not found in the database
 * @retval RET_INVALID_PASSWORD if the password is wrong
 */
function new_project($project_name, $date_start, $summary_science, $summary_impact, $state_id, $link_website, $institution_id, $user_id, $lab_id, $db)
{
   // Check for NULL values
   if(	$project_name == null ||
	$date_start == null ||
	$summary_science == null ||
	$summary_impact == null ||
	$link_website == null ||
	$institution_id == null ||
	$user_id == null ||
	$lab_id == null ||
	$state_id == null)
   {
      return $GLOBALS['RET_NULL_PARAM'];
   }

   // query database to see if a project with the same name exists at this lab
   $queryA="select PROJECT.name, PROJECT_LAB.lab_id from PROJECT join PROJECT_LAB on PROJECT.project_id = PROJECT_LAB.project_id where PROJECT.name=? and PROJECT_LAB.lab_id=?";
   $sqlA=$db->prepare($queryA);
   $sqlA->bind_param('si', $project_name, $lab_id);
   $sqlA->execute();
   $sqlA->store_result();
   $numrowsA=$sqlA->num_rows;
   $sqlA->free_result();

   //the lab is available if the query returns 0 matching rows
   if($numrowsA != 0)
   {
      //the project already exists so return error code
      return $GLOBALS['RET_PROJECT_ALREADY_EXISTS'];
   }

   //get the date
   $datetime = date("Y-m-d H:i:s");

   //the lab can be created so proceed with creating it
   $queryB="insert into PROJECT(project_creator_id, name, date_start, summary_science, summary_impact, state_id, link_website, datetime_created) values(?, ?, ?, ?, ?, ?, ?, ?)";
   $sqlB=$db->prepare($queryB);
   $sqlB->bind_param('issssiss', $user_id, $project_name, $date_start, $summary_science, $summary_impact, $state_id, $link_website, $datetime);
   $sqlB->execute();
   $sqlB->free_result();

   //get project id
   $project_id = $db->insert_id;

   //check result is TRUE meaning the insert was successful
   if($sqlB != TRUE || $project_id <= 0)
   {
      //something went wrong when creating project
      return $GLOBALS['RET_PROJECT_CREATION_FAILURE'];
   }

   // associate project with lab
   $project_lab_id = associate_project_with_lab($project_id, $lab_id, $db);
   if($project_lab_id <= 0)
   {
      // return error code
      return $project_lab_id;
   }

   // associate user with lab
   $lab_member_id = associate_user_with_lab($user_id, $lab_id, $db);
   if($lab_member_id <= 0)
   {
      // return error code
      return $lab_member_id;
   }
} //end new_project()

/*
 * @brief Associates a project with a lab
 */
function associate_project_with_lab($project_id, $lab_id, $db) {
   //get the date
   $datetime = date("Y-m-d H:i:s");

   $queryB="insert into PROJECT_LAB(project_id, lab_id, datetime_created) values(?, ?, ?)";
   $sqlB=$db->prepare($queryB);
   $sqlB->bind_param('iis', $project_id, $lab_id, $datetime);
   $sqlB->execute();
   $sqlB->free_result();

   //get project lab id
   $project_lab_id = $db->insert_id;

   //check result is TRUE meaning the insert was successful
   if($sqlB != TRUE || $project_lab_id <= 0)
   {
      //something went wrong when creating project
      return $GLOBALS['RET_PROJECT_LAB_FAILURE'];
   }
   return $project_lab_id;
}

/*
 * @brief Associates a user with a lab
 */
function associate_user_with_lab($user_id, $lab_id, $db) {
   //get the date
   $datetime = date("Y-m-d H:i:s");

   $queryB="insert into LAB_MEMBER(user_id, lab_id, datetime_created) values(?, ?, ?)";
   $sqlB=$db->prepare($queryB);
   $sqlB->bind_param('iis', $user_id, $lab_id, $datetime);
   $sqlB->execute();
   $sqlB->free_result();

   //get project lab id
   $lab_member_id = $db->insert_id;

   //check result is TRUE meaning the insert was successful
   if($sqlB != TRUE || $lab_member_id <= 0)
   {
      return $GLOBALS['RET_LAB_MEMBER_FAILURE'];
   }
   return $lab_member_id;
}



/* @brief Get a list of project states
 * @param db Database object
 * @echo a list of options for the select element
 */
function get_list_of_project_states($db)
{
	//query database for list states
	$query="select * from PROJECT_STATE order by state_id asc";
	$sql=$db->prepare($query);

	$sql->execute();
	$sql->bind_result($state_id, $state);
	echo "<option value='-1'>Select the phase</option>\n";
	while($sql->fetch())
	{
		echo "\t<option value=".$state_id.">".$state."</option>\n";
	}
	$sql->free_result();
}


?>
