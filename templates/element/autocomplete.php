<?php

use Cake\Core\Configure;

$results = [];
$displayField = $this->get('displayField');

if (isset($records)):
	$outputArray = [];
	foreach ($records as $record) {
		$outputArray = [];
		$outputArray[] = '<div class="row">';
			$outputArray[] = '<div class="col">';
				$outputArray[] = $record->get($displayField);
			$outputArray[] = '</div>';
		$outputArray[] = '</div>';
		
		$outputString = implode("\n", $outputArray);
		unset($outputArray);
		$results[] = [
			'value'=>$record->id,
			'text'=> $record->get($displayField),
			'label'=> $outputString,
		];
	}
	unset($records);
endif;
echo Configure::read('debug') ? json_encode($results, JSON_PRETTY_PRINT) : json_encode($results);
unset($results);