<?php
namespace App\Policies\Contracts;

Interface TreeViewInterface {
	public function findFirstLevelChild($user_id,$isExistObj);
	public function findFirstLevelSubChild($user_id,$isExistObj);  
}