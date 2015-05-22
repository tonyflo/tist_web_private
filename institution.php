<?php

include "credentials.php";

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

?>
