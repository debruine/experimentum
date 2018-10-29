<?php
exit;
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(8);

$q = new myQuery("SELECT chain.*, MAX(user_id) AS user_id FROM chain LEFT JOIN access ON chain.id=access.id AND access.type='chain' GROUP BY chain.id");
$chains = $q->get_assoc();
$chains = my_clean($chains);
foreach ($chains as $chain) {
	// inset data into sets table
	$q = new myQuery(sprintf("INSERT INTO sets (name, res_name, status, create_date, labnotes, type, feedback_general) VALUES ('%s', '%s', 'archive', '%s', '%s', '%s', '%s')",
		$chain['name'],
		$chain['res_name'],
		$chain['create_date'],
		$chain['labnotes'],
		$chain['subgroup_rand']=='yes' ? 'random' : 'fixed',
		$chain['feedback_general']
	));
	
	// get new set id
	$set_id = $q->get_insert_id();
	
	$q = new myQuery("INSERT INTO access (type, id, user_id) 
						VALUES ('sets', $set_id, {$chain['user_id']})");
	
	// get links from chain_link table
	$q = new myQuery("SELECT * FROM chain_link WHERE chain_id=" . $chain['id'] . " ORDER BY subgroup, n");
	$links = $q->get_assoc();
	
	// organise links
	$chain_links = array();
	foreach ($links as $link) {
		$chain_links[$link['subgroup']][$link['n']] = array( 
			'type' => $link['type'],
			'id' => $link['id']
		);
	}
	
	$set_items = array();
	if (count($chain_links) == 1) {
		// only 1 subgroup, so just add it
		$set_items = array_pop($chain_links);
	} else {
		foreach ($chain_links as $sg) {
			if (count($sg) == 0) {
				// no nothing for empty 
			} else if (count($sg) == 1) {
				// subgroup count is only 1, so just add this to the set_items individually
				$set_items[] = array_pop($sg);
			} else {
				// more than one item in the subgroup, so create a set and add it to set items
				$setq = new myQuery(sprintf("INSERT INTO sets (name, res_name, status, create_date, labnotes, type) VALUES ('%s', '%s', 'archive', '%s', '%s', '%s')",
					$chain['res_name'] . ' subset',
					$chain['res_name'] . ' subset',
					$chain['create_date'],
					'Subset for Set ' . $set_id,
					$chain['link_rand'] == 'yes' ? 'random' : 'fixed'
				));
				$subset_id = $setq->get_insert_id();
				$q = new myQuery("INSERT INTO access (type, id, user_id) 
						VALUES ('sets', $subset_id, {$chain['user_id']})");
				
				
				$set_items[] = array('type' => 'set', 'id' => $subset_id);
				$n = 0;
				foreach ($sg as $item) {
					$n++;
					$subq = new myQuery(sprintf("INSERT INTO set_items (set_id, item_type, item_id, item_n) VALUES ('%s','%s','%s','%s')",
						$subset_id,
						$item['type'],
						$item['id'],
						$n
					));
				}
			}
		}
	}
	
	// add set items to set_items table
	$n = 0;
	foreach ($set_items as $item) {
		$n++;
		$subq = new myQuery(sprintf("INSERT INTO set_items (set_id, item_type, item_id, item_n) VALUES ('%s','%s','%s','%s')",
			$set_id,
			$item['type'],
			$item['id'],
			$n
		));
	}
	
	echo "<h2>" . $chain['name'] . "</h2>";
	htmlArray($set_items);
}
?>

