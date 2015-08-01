<?php
/* @brief Generates a list of disciplines for a drop down box
 */

//query database for a sub tree in the academic discipline hierarchy given a parent discipline id
//note: the root element is academia and has an id of 1
function get_list_of_disciplines($discipline_id, $db)
{
	$query="SELECT node.discipline_id, node.name, (COUNT(parent.discipline_id) - (sub_tree.depth + 1)) AS depth FROM DISCIPLINE AS node, DISCIPLINE AS parent, DISCIPLINE AS sub_parent, ( SELECT node.discipline_id, (COUNT(parent.discipline_id) - 1) AS depth FROM DISCIPLINE AS node, DISCIPLINE AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.discipline_id = ? GROUP BY node.discipline_id ORDER BY node.lft )AS sub_tree WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt AND sub_parent.discipline_id = sub_tree.discipline_id GROUP BY node.discipline_id HAVING depth = 1 ORDER BY node.lft;";
	$sql=$db->prepare($query);
	$sql->bind_param('i', $discipline_id);
	$sql->execute();
	$sql->bind_result($sub_dis_id, $name, $depth);

	$disciplines=array();
	//the first option in the select element
	array_push($disciplines, array(-1, "Select a discipline"));
	//populate the select elements
	while($sql->fetch())
	{
		array_push($disciplines, array($sub_dis_id, $name));
	}
	echo json_encode($disciplines);
	$sql->free_result();
}

?>
