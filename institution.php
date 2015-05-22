<?php

include "credentials.php";

/* @brief Get a list of state abbreviations
 * @param db Database object
 * @echo a list of options for the select element
 */
function get_list_of_states($db)
{
	//query database for list states
	$query="select distinct state_abbrev from INSTITUTION order by state_abbrev asc";
	$sql=$db->prepare($query);

	$sql->execute();
	$sql->bind_result($state_abbrev);
	echo "<option value='-1'>Select a State</option>\n";
	while($sql->fetch())
	{
		echo "\t<option value=".$state_abbrev.">".$state_abbrev."</option>\n";
	}
	$sql->free_result();
}

/* @brief Get a list of institutions for a given state
 * @param state_abbrev a state abbreviation
 * @param db database object
 * @retval a json encoded array of institution ids and names
 */
function get_list_of_institutions($state_abbrev, $db)
{
	//query database for list of institutions
	$query="select institution_id, name from INSTITUTION where state_abbrev=? order by name asc";
	$sql=$db->prepare($query);
	$sql->bind_param('s', $state_abbrev);
	$sql->execute();
	$sql->bind_result($inst_id, $inst_name);
	$insts=array();
	while($sql->fetch())
	{
		array_push($insts, array($inst_id, $inst_name));
	}
	echo json_encode($insts);
	$sql->free_result();
}

/* @brief Query the database for information about an institution
 * @param institution_id an institutions id
 * @param db database object
 * @retval a json encoded array of info about the institution, or error code
 */
function get_institution_data($institution_id, $db)
{
	//query database for list of institutions
	$query="select name, link_website, latitude, longitude, street_address, state_abbrev, zip_code, city from INSTITUTION where institution_id=?";
	$sql=$db->prepare($query);
	$sql->bind_param('i', $institution_id);
	$sql->execute();
	$sql->bind_result($name, $link_website, $latitude, $longitude, $street_address, $state_abbrev, $zip_code, $city);
	$inst=array();
	while($sql->fetch())
	{
		array_push($inst, $name, $link_website, $latitude, $longitude, $street_address, $state_abbrev, $zip_code, $city);
	}
	$sql->free_result();
	return $inst;
	//TODO: no error checking
}
?>
