<?php

namespace App;

use Auth;
use DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;

class User extends Authenticatable {
	use  Notifiable;
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'tbl_users';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'id', 'user_id', 'fullname', 'email', 'type', 'google2fa_secret', 'remember_token', 'status',
	];
	//protected $primaryKey = 'id';
	public $timestamps = false;
	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'google2fa_secret',
	];

	public function setGoogle2faSecretAttribute($value) {
		$this->attributes['google2fa_secret'] = encrypt($value);
	}
	/**
	 * Decrypt the user's google_2fa secret.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function getGoogle2faSecretAttribute($value) {
		return decrypt($value);
	}
	public static function loginValidationRules() {
		$arrRulesData = [];
		$arrRulesData['arrMessage'] = array(
			'user_id.required' => 'Username Required',
			'password.required' => 'Password Required',
		);

		$arrRulesData['arrRules'] = array(
			'user_id' => 'required',
			'password' => 'required',
		);
		return $arrRulesData;

	}

	public static function registrationValidationRules() {

		$arrRulesData = [];
		$arrRulesData['arrMessage'] = array('password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *', 'email.email' => 'Email should be in format abc@abc.com', 'fullname.regex' => 'Special characters not allowed in fullname');
		// |regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{7,}/
		// |regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{7,}/
		$arrRulesData['arrRules'] = array(
			//'fullname'      => 'required|min:3|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/',
			'email' => 'required|email|max:70',
			// 'country' => 'required',
			//'password'      => 'required|min:6|max:30',
			'password' => ['string',
				'min:8', // must be at least 10 characters in length
				// 'regex:/[a-z]/', // must contain at least one lowercase letter
				// 'regex:/[A-Z]/', // must contain at least one uppercase letter
				// 'regex:/[0-9]/', // must contain at least one digit
				// 'regex:/[@$!%*#?&]/', // must contain a special character',
			],
			// 'password_confirmation' => 'required|min:6|max:30|same:password',
			'ref_user_id' => 'required',
			// 'mobile'        => 'required|numeric|min:10',
			// 'btc_address'        => 'nullable|regex:/^\S*$/',
			'position' => ['required',Rule::in(['1','2'])],
			//'user_id' => 'required|',
			//'mode' => 'required',
		);
		return $arrRulesData;

	}
	/**
	 * Get the login user_id to be used by the controller.
	 *
	 * @return string
	 */
	public static function username() {
		return 'user_id';
	}
	/**
	 *
	 *
	 * @return string
	 */
	public function findForPassport($username) {
		return $this->where('user_id', $username)->first();
	}
	/**
	 * Override the password field for the passport authentication of the user .
	 *
	 * @return string
	 */
	public function getAuthPassword() {
		return $this->bcrypt_password;
	}

	public function getSelfTopupAttribute() {
        $topup = DB::table('tbl_topup')->where('id', Auth::user()->id)->sum('amount');
		return $topup;
	}
	protected $appends = ['selftopup'];

}
