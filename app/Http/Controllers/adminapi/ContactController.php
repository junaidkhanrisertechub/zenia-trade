<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use Illuminate\Http\Response as Response;
use App\Http\Requests;
use App\Models\Contact;
use App\Models\ReplyEnquiryReport;
use App\User;
use DB;
use Config;
use Mail;

class ContactController extends Controller
{

	/**
     * define property variable
     *
     * @return
     */
    public $statuscode,$settings,$commonController;

   	/**
	 * Add Fund report
	 *
	 * @return void
	 */

	public function ContactReport(Request $request)
	{
		$arrInput = $request->all();
		// dd($arrInput);
		
		$fundreport = Contact::select('*')->orderBy('id','desc');


				$totalRecord = $fundreport->count();
				$arrPendings = $fundreport->skip($request->input('start'))->take($request->input('length'))->get();

				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrPendings;

				if (!empty($arrPendings) && count($arrPendings) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data Found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not Found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
		
		
	}


   
}