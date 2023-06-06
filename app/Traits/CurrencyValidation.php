<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\Response as Response;
use App\User;
use Illuminate\Http\Request;
use App\Models\Dashboard;
use App\Models\CurrentAmountDetails;
use App\Models\TodayDetails;
use App\Models\LevelView;
use App\Models\Activitynotification;
use App\Models\Promotionals;
use App\Models\PromotionalSocialIncome;
use App\Models\PromotionalType;
use App\Models\QuesAns;
use App\Models\Packages;
use App\Models\Topup;
use App\Models\Rank;
use App\Models\UserStructureModel;
use App\Models\ProjectSettings;
use App\Models\DirectIncome;
use App\Traits\Income;
use Illuminate\Support\Facades\Auth;
use DB;
use bcrypt;
use Config;
use App\Models\supermatching;

trait CurrencyValidation
{

    public function checkcurrencyvalidaion($currency,$address)
    {
        if ($currency == 'btc_address' && $address != "null") 
        {
            $arrMessage = "";
                $split_array = str_split(trim($address));
                if ($split_array[0] == 3 || $split_array[0] == 1 || $split_array[0] == 'b')
                { 
                    if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                    {
                    } 
                    else 
                    {
                        $arrMessage  = 'Bitcoin address must be in between 26 to 50 characters';
                    }
                    
                } 
                else 
                {
                    $arrMessage  = 'Bitcoin Address should be start with b or 1 or 3.';
                }
                return $arrMessage;
        }
        if ($currency == 'ethereum' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == '0' && $split_array[1] == 'x') 
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                }
                else 
                {
                    $arrMessage  = 'Ethereum address must be in between 26 to 50 characters';
                }
            } 
            else 
            {
                $arrMessage  = 'Ethereum Address should be start with "0x"';
            }
            return $arrMessage;
		}
        if ($currency == 'usdt_erc20_address' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == '0' && $split_array[1] == 'x') 
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                }
                else 
                {
                    $arrMessage  = 'USDT-ERC20 address must be in between 26 to 50 characters';
                }
            } 
            else 
            {
                $arrMessage  = 'USDT-ERC20 Address should be start with "0x"';
            }
            return $arrMessage;
        }
        if ($currency == 'trn_address' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == 't' ||  $split_array[0] == 'T')
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                } 
                else 
                {
                    $arrMessage  = 'TRX address must be in between 26 to 50 characters';
                }
            } 
            else 
            {
                $arrMessage  = 'TRX Address should be start with T or t';
            }
            return $arrMessage;
		}
        if ($currency == 'bnb_address') 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == 'b' ||  $split_array[0] == 'B')
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                } 
                else 
                {
                    $arrMessage  = 'Binace address must be in between 26 to 50 characters';
                }
                    
            } 
            else 
            {
                $arrMessage  = 'Binace Address should be start with B or b';
            }
            return $arrMessage;
		}
        if ($currency == 'doge_address' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == 'D')
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {

                } 
                else 
                {
                    $arrMessage  = 'Dogecoin address must be in between 26 to 50 characters';
                }
                    
            } 
            else 
            {
                $arrMessage  = 'Dogecoin Address should be start with D';
            }
            return $arrMessage;
		}
        if ($currency == 'ltc_address' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == 'L'  ||  $split_array[0] == 'l'  ||  $split_array[0] == 'M' || $split_array[0] == 'm')
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {

                }
                else 
                {
                    $arrMessage  = 'LTC address must be in between 26 to 50 characters';
                 }
                    
            } 
            else 
            {
                $arrMessage  = 'LTC Address should be start with L or l or M or m';
                   
            }
            return $arrMessage;
		}
        if ($currency == 'sol_address' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            /*if ($split_array[0] == 's')
            { */
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                } 
                else 
                {
                    $arrMessage  = 'sol address must be in between 26 to 50 characters';
                }
            /*} 
            else 
            {
                $arrMessage  = 'sol Address should be start with s';
            }*/
            return $arrMessage;
		}
        if ($currency == '') 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == '0' &&  $split_array[1] == 'x')
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                } 
                else 
                {
                     $arrMessage  = 'Tether ERC-20 address must be in between 26 to 50 characters';
                }
                    
            } 
            else 
            {
                $arrMessage  = 'Tether ERC-20 Address should be start with 0x';
            }
            return $arrMessage;
		}
        if ($currency == 'usdt_trc20_address' && $address != "null") 
        {
            $arrMessage = "";
            $split_array = str_split(trim($address));
            if ($split_array[0] == 't' ||  $split_array[0] == 'T')
            { 
                if (strlen(trim($address)) >= 26 && strlen(trim($address)) <= 50) 
                {
                } 
                else 
                {
                    $arrMessage  = 'Tether TRC-20 address must be in between 26 to 50 characters';
                }
                    
            } 
            else 
            {
                $arrMessage  = 'Tether TRC-20 Address should be start with T or t';
            }
            return $arrMessage;
		}
    }

  
}
