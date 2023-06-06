<?php
namespace App\Policies\Contracts;

Interface BinaryIncomeInterface {
	public function chkBinary($user_id);
	public function chkQualifiedBinaryIncome($user_id);
	public function insertBinaryIncome($user_id);
}
