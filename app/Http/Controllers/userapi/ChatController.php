<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\User;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller {

	public function __construct() {
		$date = \Carbon\Carbon::now();
		$this->today = $date->toDateTimeString();
		$this->statuscode = Config::get('constants.statuscode');
	}

	/**
	 * SEnd message to admin
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function sendMessage(Request $request) {
		try {
			$messsages = array(
				'message.required' => 'Please enter message',
				'to_user.required' => 'To User is required',
			);
			$rules = array(

				'message' => 'required|',
				'to_user' => 'required|',
			);

			$validator = checkvalidation($request->all(), $rules, $messsages);
			if (!empty($validator)) {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$users = Auth::user();
			if (!empty($users)) {

				/*  $toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();*/
				$toUser_id = 1;
				if (!empty($toUser_id)) {
					$data = array();
					$data['from_user_id'] = $users->id;
					$data['to_user_id'] = $toUser_id;
					$data['message'] = trim($request->input('message'));
					$data['entry_time'] = $this->today;
					$messageInsert = Chat::create($data);
					if (!empty($messageInsert)) {

						$arrStatus = Response::HTTP_OK;
						$arrCode = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Message sent !';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					} else {

						$arrStatus = Response::HTTP_NOT_FOUND;
						$arrCode = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Something went wrong,Please try again';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					}
				} else {

					$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User is not exist';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} else {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid User';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
		} catch (Exception $e) {

			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Fetch all messages
	 *
	 * @return \Illuminate\Http\Response
	 */
	/* public function fetchMessages(Request $request) {

		        try{
		                $messsages = array(
		                 'to_user.required' => 'To User is required'
		                );
		                $rules = array(

		                    'to_user' => 'required|'
		                );

		                $validator = checkvalidation($request->all(), $rules,$messsages);
		                if (!empty($validator)) {

		                $arrStatus   = Response::HTTP_NOT_FOUND;
		                $arrCode     = Response::$statusTexts[$arrStatus];
		                $arrMessage  = $validator;
		                return sendResponse($arrStatus,$arrCode,$arrMessage,'');

		                }

		                $from_id = Auth::user()->id;
		                $toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
		                // print_r($toUser_id);
		                // exit();
		                if (empty($from_id)) {

		                    $arrStatus   = Response::HTTP_NOT_FOUND;
		                    $arrCode     = Response::$statusTexts[$arrStatus];
		                    $arrMessage  ='Invalid user';
		                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');

		                }

		                if (empty($toUser_id)) {
		                    $arrStatus   = Response::HTTP_NOT_FOUND;
		                    $arrCode     = Response::$statusTexts[$arrStatus];
		                    $arrMessage  ='To user is not exist';
		                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');

		                }
		                $query = Chat::leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_chat.from_user_id')
		                        ->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_chat.to_user_id')
		                        ->select('tbl_chat.*', 'tu1.fullname as fromuser_id', 'tu2.fullname as touser_id');
		                $getMessage = $query->where([['tbl_chat.from_user_id', '=', $from_id], ['tbl_chat.to_user_id', '=', $toUser_id]])->orwhere([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->orderBy('tbl_chat.id', 'asc')->get();

		                $count = Chat::where([['tbl_chat.status', '=', 'unread'], ['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->count();

		                $finalMessage = [];
		                if (!empty($getMessage) && count($getMessage) > 0) {
		                    $showMessage = [];
		                    $timmingArr = array();
		                    foreach ($getMessage as $k => $value) {
		                        $end = $getMessage[$k]->entry_time;
		                        $now = \Carbon\Carbon::now();
		                        $created = \Carbon\Carbon::parse($end);
		                        $curntDate = $now->toDateString();
		                        $createdDate = $created->toDateString();
		                        $date2 = date_create($curntDate);
		                        $date1 = date_create($createdDate);
		                        $diff = date_diff($date1, $date2);
		                        $dateDiffrence = $diff->format("%R%a ");

		                        if ($dateDiffrence <= 0) {

		                            $difference = 'today';
		                        } else if ($dateDiffrence > 0 && $dateDiffrence <= 1) {
		                            $difference = 'yesterday';
		                        } else if ($dateDiffrence > 1 && $dateDiffrence <= 6) {
		                            $difference = $created->format('l');
		                        } else if ($dateDiffrence > 6) {
		                            $difference = $created->format('j F y');
		                        }

		                        $value->timing = $difference;
		                        $value->current_timing = $created->format('g:i A');

		                        $showMessage[$value->timing]['showtime'] = $value->timing;
		                        if ($from_id == $getMessage[$k]->from_user_id) {
		                            $value->position  = 'right';
		                            $showMessage[$value->timing]['msgdata'][] = ['right' => $value];
		                        } else {
		                            $value->position  = 'left';
		                            $showMessage[$value->timing]['msgdata'][] = ['left' => $value];
		                        }
		                    }

		                    if (!empty($showMessage)) {
		                        foreach ($showMessage as $timedata) {
		                            $finalMessage[] = $timedata;
		                        }
		                    } else {
		                        $finalMessag = '';
		                    }

		                    if (!empty($finalMessage)) {
		                        $updateData = array();
		                        $updateData['status'] = 'read';
		                        $updateData = Chat::where([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->update($updateData);

		                        $arrFinaldata['total_unread'] = $count;
		                        $arrFinaldata['messages'] = $finalMessage;

		                    $arrStatus   = Response::HTTP_OK;
		                    $arrCode     = Response::$statusTexts[$arrStatus];
		                    $arrMessage  ='Message found !';
		                    return sendResponse($arrStatus,$arrCode,$arrMessage,$arrFinaldata);

		                    } else {

		                        $arrStatus   = Response::HTTP_NOT_FOUND;
		                        $arrCode     = Response::$statusTexts[$arrStatus];
		                        $arrMessage  ='Message not found';
		                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');

		                    }
		                } else {

		                        $arrStatus   = Response::HTTP_NOT_FOUND;
		                        $arrCode     = Response::$statusTexts[$arrStatus];
		                        $arrMessage  ='Message not found';
		                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');

		                }
		        }catch(Exception $e){

		                   $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
		                   $arrCode     = Response::$statusTexts[$arrStatus];
		                   $arrMessage  = 'Something went wrong,Please try again';
		                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
		        }
		    }
	*/
	/**
	 * Fetch count of  messages
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function fetchCountMessages(Request $request) {
		// try{
		$messsages = array(
			'to_user.required' => 'To User is required',
		);
		$rules = array(

			'to_user' => 'required|',
		);
		$validator = checkvalidation($request->all(), $rules, $messsages);
		if (!empty($validator)) {
			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = $validator;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

		$from_id = Auth::user()->id;
		$toUser_id = User::where([['user_id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
		if (empty($from_id)) {
			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Invalid user';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');

		}

		if (empty($toUser_id)) {

			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Invalid To user';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');

		}

		$count = Chat::where([['tbl_chat.status', '=', 'unread'], ['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->count();

		if (!empty($count)) {

			$arrStatus = Response::HTTP_OK;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Count found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $count);

		} else {

			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Count not found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');

		}
		/*}catch(Exception $e){

			                   $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
			                   $arrCode     = Response::$statusTexts[$arrStatus];
			                   $arrMessage  = 'Something went wrong,Please try again';
			                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
		*/

	}

	/**
	 * Get admin chatting list
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getAdminChattingUser(Request $request) {

		try {

			$user_id = User::Auth()->id;

			if (empty($user_id)) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$query = Chat::join('tbl_users as tu', 'tu.id', '=', 'tbl_chat.from_user_id')
				->select(DB::RAW("distinct(tbl_chat.from_user_id) as form_user_id"), 'tu.user_id as fromuser', DB::RAW("count(*) as count"))
				->where('tbl_chat.to_user_id', '=', $user_id)
				->groupBy('tbl_chat.from_user_id')
				->get();

			if (!empty($query) && count($query) > 0) {

				$arrStatus = Response::HTTP_OK;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Users found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $query);

			} else {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
		} catch (Exception $e) {

			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

	}
/**
 * Fetch all messages
 *
 * @return \Illuminate\Http\Response
 */
	public function fetchMessages(Request $request) {

		try {
			$messsages = array(
				'to_user.required' => 'To User is required',
			);
			$rules = array(

				'to_user' => 'required|',
			);

			$validator = checkvalidation($request->all(), $rules, $messsages);
			if (!empty($validator)) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}

			$from_id = Auth::user()->id;
			$toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
// print_r($toUser_id);
			// exit();
			if (empty($from_id)) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}

			if (empty($toUser_id)) {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'To user is not exist';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
			$query = Chat::leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_chat.from_user_id')
				->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_chat.to_user_id')
				->select('tbl_chat.*', 'tu1.fullname as fromuser_id', 'tu2.user_id as touser_id');
			$getMessage = $query->where([['tbl_chat.from_user_id', '=', $from_id], ['tbl_chat.to_user_id', '=', $toUser_id]])->orwhere([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->orderBy('tbl_chat.id', 'asc')->get();

			$count = Chat::where([['tbl_chat.status', '=', 'unread'], ['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->count();

			$finalMessage = [];
			if (!empty($getMessage) && count($getMessage) > 0) {
				$showMessage = [];
				$timmingArr = array();
				foreach ($getMessage as $k => $value) {
					$end = $getMessage[$k]->entry_time;
					$now = \Carbon\Carbon::now();
					$created = \Carbon\Carbon::parse($end);
					$curntDate = $now->toDateString();
					$createdDate = $created->toDateString();
					$date2 = date_create($curntDate);
					$date1 = date_create($createdDate);
					$diff = date_diff($date1, $date2);
					$dateDiffrence = $diff->format("%R%a ");

					if ($dateDiffrence <= 0) {

						$difference = 'today';
					} else if ($dateDiffrence > 0 && $dateDiffrence <= 1) {
						$difference = 'yesterday';
					} else if ($dateDiffrence > 1 && $dateDiffrence <= 6) {
						$difference = $created->format('l');
					} else if ($dateDiffrence > 6) {
						$difference = $created->format('j F y');
					}

					$value->timing = $difference;
					$value->current_timing = $created->format('g:i A');

					$showMessage[$value->timing]['showtime'] = $value->timing;
					if ($from_id == $getMessage[$k]->from_user_id) {
						$value->position = 'right';
						$showMessage[$value->timing]['msgdata'][] = ['right' => $value];
					} else {
						$value->position = 'left';
						$showMessage[$value->timing]['msgdata'][] = ['left' => $value];
					}
				}

				if (!empty($showMessage)) {
					foreach ($showMessage as $timedata) {
						$finalMessage[] = $timedata;
					}
				} else {
					$finalMessag = '';
				}

				if (!empty($finalMessage)) {
					$updateData = array();
					$updateData['status'] = 'read';
					$updateData = Chat::where([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->update($updateData);

					$arrFinaldata['total_unread'] = $count;
					$arrFinaldata['messages'] = $finalMessage;

					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Message found !';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrFinaldata);

				} else {

					$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Message not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');

				}
			} else {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Message not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
		} catch (Exception $e) {

			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

}
