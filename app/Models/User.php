<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;

class User extends Model implements Authenticatable
{
    use HasApiTokens, Notifiable;
    use HasFactory, AuthenticableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'tbl_users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->bcrypt_password;
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




}
