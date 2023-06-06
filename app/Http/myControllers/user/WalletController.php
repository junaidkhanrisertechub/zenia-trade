<?php

namespace App\Http\Controllers\user;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Config;
use DB;
use Exception;
use PDOException;
use Auth;
use URL;
use DataTables;
use App\Models\Currency;
use App\Models\Packages;
use App\Models\TransactionInvoice;
use App\Models\TransactionInfo;
use App\Models\Dashboard;

use Illuminate\Http\Response as Response;
use App\Models\FundRequest;


class WalletController extends Controller {

	public function __construct(Google2FAController $google2facontroller) {
		$this->linkexpire = Config::get('constants.linkexpire');
		$date             = \Carbon\Carbon::now();
		$this->today      = $date->toDateTimeString();
		$this->statuscode = Config::get('constants.statuscode');
		$this->google2facontroller = $google2facontroller;
	}

    public function create()
    {

        	$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = Dashboard::where('id', $id)->select('fund_wallet', 'fund_wallet_withdraw')->first();
            //dd($getDetails);

			$total_fund_wallet_val = $getDetails['fund_wallet'] - $getDetails['fund_wallet_withdraw'];


        $currency = Currency::where([['status', '=', '1']])->get();
        return view('user.fund.addfund',compact('currency', 'total_fund_wallet_val'));
    }
    public function store(Request $request)
    {

      $validator = $request->validate([
        'currency_code' => 'required',

			'hash_unit'     => 'required|numeric',
      ],
      [
			'currency_code.regex' => 'Currency code should be in capital letter',
			"hash_unit.required"  => "Amount is required",
      ]);

		$user = Auth::user();
		$id   = $user->id;
		if (!empty($id)) {
			//User is not registered with this email id
			$packageExist = Packages::select('min_hash', 'max_hash', 'name', 'currency_code', 'cost')->where([['id', '=', 1], ['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->first();

			if (!empty($packageExist)) {

				/*if($request->hash_unit >= $packageExist->min_hash && $request->hash_unit <= $packageExist->max_hash){*/
					//dd(fmod($request->hash_unit, 1) !== 0.00);
				if (fmod($request->hash_unit, 1) !== 0.00) {
					dd('error');
					return redirect()->back()->with('error','Deposit amount should be multiple of 1.');
				}

				$currency_code = $request->input('currency_code');
				$currency_name = $packageExist->name;
				//echo $id;
				//exit();
				// TransactionInfo::select('reciever_public_key','reciever_private_key','sender_public_key')->where('status','=','1')->first();
				$CheckuserAddressExist = TransactionInvoice::where([['id', '=', $id], ['payment_mode', '=', $currency_code], ['plan_id', '=', $request->Input('product_id')], ['hash_unit', '=', $request->Input('hash_unit')], ['price_unit', '=', $packageExist->cost], ['in_status', '=', 0]])->first();

				if (!empty($CheckuserAddressExist)) {
					$arrData                      = array();
					$arrData['address']           = $CheckuserAddressExist->address;
					$arrData['name']              = $currency_name;
					$arrData['exists']            = 1;
					$arrData['network_type']      = $currency_code;
					$arrData['price_in_usd']      = $CheckuserAddressExist->price_in_usd;
					$arrData['price_in_currency'] = $CheckuserAddressExist->currency_price;
					$arrData['received_amount']   = $CheckuserAddressExist->rec_amt;
					$arrData['status_url']        = $CheckuserAddressExist->status_url;

					//return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address found', $arrData);
				} else {

					$req = array(
						'amount'      => $request->hash_unit,
						'currency1'   => 'USD',
						'currency2'   => $currency_code,
						'buyer_email' => $user->email,
					);
					// function coinpayments_api_call call  to generate address which is in helper/message.php
					//dd(122);

					$req_data = coinpayments_api_call('create_transaction', $req);
					//dd($req_data);
					// dd($req_data);
					if ($req_data['msg'] == 'success') {
						$result = $this->insertcoinaddress($req_data['address'], $id, $currency_code, 'coinpayment', $request, $req_data["data"]);

						return redirect($req_data['status_url']);
						//return $result;
					}
					// else if ($req_data['msg'] == 'failed' && ($request->currency_code == 'POLYGON' || $request->currency_code == 'CARDANO' )) {
					// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Selected currency is not supported by Coinpayment', $this->emptyArray);
					// }
					else if ($req_data['msg'] == 'failed') {
						return redirect()->route('addfund')
						->with('error','Selected currency is not supported by Coinpayment');
						//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Selected currency is not supported by Coinpayment', $this->emptyArray);
					}
					else {
						return redirect()->route('addfund')
						->with('error','Something went wrong,Please try again');
						//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
					}
				}
				/*} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Amount must be between '.$packageExist->min_hash.' and '.$packageExist->max_hash, $this->emptyArray);
			}*/
			}
		} else {
			return redirect()->route('addfund')
						->with('error','Invalid package');
			//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid package', $this->emptyArray);
		}

    }




	public function fundRequest(Request $request) {

		// dd($request);
		/*
		payment_mode
		trn_ref_no
		holder_name
		bank_name
		deposit_date
		 */
		$rules = array('amount' => 'required', 'payment_mode' => 'required');
		if (!empty($rules)) {
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];
				return back()->withErrors($validator)->withInput();

				//return sendResponse($intCode, $strStatus, $err, '');
			}
		}
		$fileName = 'no_image_available.png';
		if ($request->hasFile('file')) {

			$file = $request->file('file');

			$fileName = time().'.'.$file->getClientOriginalExtension();

			// $file->move(public_path('uploads/files'), $fileName);

			// $PinRequest->attachment = $fileName;
		}

		$id = Auth::user()->id;
		if (!empty($id)) {

			$insertfundreq               = new FundRequest;
			$insertfundreq->user_id      = $id;
			$insertfundreq->amount       = $request->amount;
			$insertfundreq->product_id   = $request->product_id;
			$insertfundreq->pay_slip     = $fileName;
			$insertfundreq->payment_mode = $request->payment_mode;
			$insertfundreq->trn_ref_no   = $request->trn_ref_no;
			$insertfundreq->holder_name  = $request->holder_name;
			$insertfundreq->bank_name    = $request->bank_name;
			$insertfundreq->deposit_date = $request->deposit_date;
			$insertfundreq->save();

			$intCode   = Response::HTTP_OK;
			$strStatus = Response::$statusTexts[$intCode];
			return back()->withSuccess("Fund request sent Successfully")->withInput();
			//return sendResponse($intCode, $strStatus, '', '');

		} else {
			$intCode   = Response::HTTP_NOT_FOUND;
			$strStatus = Response::$statusTexts[$intCode];
			return back()->withErrors("User invalid")->withInput();
			//return sendResponse($intCode, $strStatus, '', '');
		}

	}



    public function fundRequestReportOld(Request $request) {
        $id = Auth::user()->id;
        if (!empty($id)) {
            $url   = url('uploads/files');
            $query = FundRequest::select('tbl_fund_request.*', 'user.fullname', 'user.user_id', DB::raw('IF(tbl_fund_request.pay_slip IS NOT NULL,CONCAT("'.$url.'","/",tbl_fund_request.pay_slip),NULL) attachment'), DB::raw("DATE_FORMAT(tbl_fund_request.deposit_date,'%Y/%m/%d') as deposit_date"), DB::raw("DATE_FORMAT(tbl_fund_request.entry_time,'%Y/%m/%d') as entry_time"), 'tp.name_rupee as product_name')
                ->join('tbl_users as user', 'user.id', '=', 'tbl_fund_request.user_id')
                ->join('tbl_product as tp', 'tp.id', '=', 'tbl_fund_request.product_id')
                ->where('tbl_fund_request.user_id', $id)
                ->orderBy('tbl_fund_request.id', 'DESC');

            if (isset($arrInput['deposit_id'])) {
                $query->where('invoice_id', $arrInput['deposit_id']);
            }

            if (isset($arrInput['payment_mode'])) {

               $query->where('payment_mode', $arrInput['payment_mode']);
            }

            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
            }
//            if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
//                //searching loops on fields
//                $fields = getTableColumns('tbl_fund_request');
//                $search = $request->input('search')['value'];
//                $query  = $query->where(function ($query) use ($fields, $search) {
//                    foreach ($fields as $field) {
//                        $query->orWhere('tbl_fund_request.'.$field, 'LIKE', '%'.$search.'%');
//                    }
//                    $query->orWhere('user.user_id', 'LIKE', '%'.$search.'%');
//                    //->orWhere('prod.name', 'LIKE', '%' . $search . '%');
//                });
//            }

            $data = setPaginate($query, $request->start, $request->length);
            if (!empty($data)) {

                $intCode   = Response::HTTP_OK;
                $strStatus = Response::$statusTexts[$intCode];

                return sendresponse($intCode, $strStatus, 'Data found Successfully!', $data);
            } else {

                $intCode   = Response::HTTP_INTERNAL_SERVER_ERROR;
                $strStatus = Response::$statusTexts[$intCode];

                return sendresponse($intCode, $strStatus, 'Data not found', '');
            }
        } else {

            $intCode   = Response::HTTP_NOT_FOUND;
            $strStatus = Response::$statusTexts[$intCode];

            return sendresponse($intCode, $strStatus, 'User id does not exist', '');

        }
    }
    public function fundRequestReport(Request $request) {
        try {
            $arrInput = $request->all();
            $id       = Auth::user()->id;
            // ini_set('memory_limit', '-1');
            $arrInput      = $request->all();
            // $pendingReport = FundTransactionInvoice::select('srno', 'invoice_id', 'price_in_usd', 'payment_mode', 'address', 'entry_time', 'status_url','in_status')
            // 	->where([['id', '=', $id]])
            // 	->orderBy('entry_time', 'desc');
            $pendingReport = FundRequest::select('id', 'invoice_id', 'amount', 'payment_mode', 'entry_time', 'status')
                ->where([['user_id', '=', $id]])
                ->orderBy('entry_time', 'desc');
            if (isset($arrInput['deposit_id'])) {
                $pendingReport = $pendingReport->where('invoice_id', $arrInput['deposit_id']);
            }

            if (isset($arrInput['payment_mode'])) {

                $pendingReport = $pendingReport->where('payment_mode', $arrInput['payment_mode']);
            }

            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $pendingReport = $pendingReport->whereBetween(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
            }

            $totalRecord = $pendingReport->count('id');
            $arrPendings = $pendingReport->skip($request->input('start'))->take($request->input('length'))->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $arrPendings;

            if (!empty($arrPendings) && count($arrPendings) > 0) {
                $arrStatus  = Response::HTTP_OK;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Pending data found';
                return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
            } else {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Data not found';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

	public function insertcoinaddress($getaddress, $id, $currency_code, $getMethodname, Request $request, $req_data = array())
	{

		$random       = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
		$packageExist = Packages::select('cost', 'name', 'hash_rate')->where([['id', '=', $request->Input('product_id')], ['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->first();
		//dd($packageExist);

		$costextra = $request->input('hash_unit');

		/*$currencyValue = currency_convert($currency_code, $costextra);

		$request->request->add(['usd' => $costextra]);
		$getvalue = $this->currencyConvertor->currenyConverter($request);

		if (!empty($getvalue->original['data'])) {
		if (array_key_exists($currency_code, $getvalue->original['data'])) {
		$currencyValue = $getvalue->original['data'][$currency_code];
		}
		} else {
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
		}*/
		$transaction_data          = TransactionInfo::select('reciever_public_key', 'reciever_private_key', 'sender_public_key')->where('status', '=', '1')->first();
		$Invoicedata               = array();
		$Invoicedata['invoice_id'] = substr(number_format(time() * rand(), 0, '', ''), 0, '15');

		$Invoicedata['hash_rate']            = $packageExist->hash_rate;
		$Invoicedata['hash_unit']            = $request->input('hash_unit');
		$Invoicedata['price_unit']           = $packageExist->cost;
		$Invoicedata['price_in_usd']         = $costextra;
		$Invoicedata['id']                   = $id;
		$Invoicedata['currency_price']       = custom_round($req_data['amount'], 7);
		$Invoicedata['payment_mode']         = $currency_code;
		$Invoicedata['address']              = $getaddress;
		$Invoicedata['plan_id']              = $request->Input('product_id');
		$Invoicedata['entry_time']           = $this->today;
		$Invoicedata['product_url']          = $getMethodname;
		$Invoicedata['trans_id']             = $req_data['txn_id'];
		$Invoicedata['status_url']           = $req_data['checkout_url'];
		$Invoicedata['reciever_public_key']  = $transaction_data->reciever_public_key;
		$Invoicedata['reciever_private_key'] = $transaction_data->reciever_private_key;
		$insertAdd                           = TransactionInvoice::create($Invoicedata);
		if (!empty($insertAdd)) {
			$arrData['address'] = $getaddress;
			$arrData['name']    = $packageExist->name;

			$request->request->add(['usd' => $costextra]);
			$valuewithExtra = $this->currencyConvertor->currenyConverter($request);

			if (!empty($valuewithExtra->original['data'])) {

				if (array_key_exists($currency_code, $valuewithExtra->original['data'])) {
					$currencyValuwithextra = $valuewithExtra->original['data'][$currency_code];
				} else {
					$currencyValuwithextra = 0;
				}
			} else {
				$currencyValuwithextra = 0;
			}

			$arrData['network_type']      = $currency_code;
			$arrData['price_in_usd']      = $costextra;
			$arrData['price_in_currency'] = $currencyValuwithextra;
			$arrData['received_amount']   = 0;
			$arrData['exists']            = 0;
			$arrData['status_url']        = $req_data['checkout_url'];

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Address not found', $this->emptyArray);
		}
	}

	public function fundreport()
	{
		$currency = Currency::where([['status', '=', '1']])->get();
		return view('user.fund.fundreport',compact('currency'));
	}
	public function submitreport(Request $request)
	{
		try {
			$arrInput = $request->all();

			$id = Auth::user()->id;
			// ini_set('memory_limit', '-1');
			$arrInput = $request->all();
			// $pendingReport = FundTransactionInvoice::select('srno', 'invoice_id', 'price_in_usd', 'payment_mode', 'address', 'entry_time', 'status_url','in_status')
			// 	->where([['id', '=', $id]])
			// 	->orderBy('entry_time', 'desc');
			$pendingReport = TransactionInvoice::select('srno', 'invoice_id', 'price_in_usd', 'payment_mode', 'address', 'entry_time', 'status_url', 'in_status')
				->where('id',$id)
				->orWhere('invoice_id', $arrInput['deposit_id'])
				->orWhere('payment_mode', $arrInput['payment_mode'])
				->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))])
				->orderBy('entry_time', 'desc')->get();


			/*if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {

			$fields = getTableColumns('tbl_transaction_invoices');
			$search = $request->input('search')['value'];
			$pendingReport = $pendingReport->where(function ($pendingReport) use ($fields, $search) {
			foreach ($fields as $field) {
			$pendingReport->orWhere('tbl_transaction_invoices.' . $field, 'LIKE', '%' . $search . '%');
			}
			$pendingReport->orWhere('tp.name', 'LIKE', '%' . $search . '%');
			});
			}*/

			/*if (isset($arrInput['deposit_id'])) {
				$pendingReport = $pendingReport->where('invoice_id', $arrInput['deposit_id']);
			}*/

			/*if (isset($arrInput['payment_mode'])) {

				$pendingReport = $pendingReport->where('payment_mode', $arrInput['payment_mode']);
			}*/

			/*if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
				$pendingReport = $pendingReport->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
			}*/

			//$totalRecord = $pendingReport->count('id');
			//$arrPendings = $pendingReport->skip($request->input('start'))->take($request->input('length'))->get();

			//$arrData['recordsTotal']    = $totalRecord;
			//$arrData['recordsFiltered'] = $totalRecord;
			//$arrData['records']         = $arrPendings;

			if ($pendingReport) {
				//$arrStatus  = Response::HTTP_OK;
				//$arrCode    = Response::$statusTexts[$arrStatus];
				//$arrMessage = 'Pending data found';
				return view('user.fund.report',compact('pendingReport'));
			} else {
				//$arrStatus  = Response::HTTP_NOT_FOUND;
				//$arrCode    = Response::$statusTexts[$arrStatus];
				//$arrMessage = 'Data not found';
				return redirect()->route('fundreport')->with('error','Data not found');
			}
		} catch (Exception $e) {
			dd($e);
			//$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			//$arrCode    = Response::$statusTexts[$arrStatus];
			//$arrMessage = 'Something went wrong,Please try again';
			//return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

	}
	public function completedreport()
	{

		$id = Auth::user()->id;
		$pendingReport = TransactionInvoice::select('srno', 'invoice_id', 'price_in_usd', 'payment_mode', 'address', 'entry_time', 'status_url', 'in_status')
				->where('id',$id)
				->where('in_status',1)
				->orderBy('entry_time', 'desc')->get();
				if ($pendingReport) {
					//$arrStatus  = Response::HTTP_OK;
					//$arrCode    = Response::$statusTexts[$arrStatus];
					//$arrMessage = 'Pending data found';
					return view('user.fund.report',compact('pendingReport'));
				} else {
					//$arrStatus  = Response::HTTP_NOT_FOUND;
					//$arrCode    = Response::$statusTexts[$arrStatus];
					//$arrMessage = 'Data not found';
					return redirect()->route('fundreport')->with('error','Data not found');
				}
	}
}
