<?php

namespace App\Http\Controllers\userapi;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\User;
use Auth;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Validator;

class SupportController extends Controller {

	public function __construct() {
		$this->statuscode = Config::get('constants.statuscode');
		$date = \Carbon\Carbon::now();
		$this->today = $date->toDateTimeString();
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

				'message' => 'required_without:files|',
				'to_user' => 'required|',
				'files' => 'required_without:message|',
				'type' => 'required',
			);
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err = '';
				foreach ($message->all() as $error) {
					if (count($message->all()) > 1) {
						$err = $err . ' ' . $error;
					} else {
						$err = $error;
					}
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}

			$users = Auth::user();
			if (!empty($users)) {
				$type = $request->input('type');
				$toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
				if (!empty($toUser_id)) {
					$fileNames = [];
					$files = null;
					if ($request->hasFile('files')) {
						foreach ($request->file('files.*') as $key => $file) {
							// $file=$request->file('files');
							$fileNames[$key] = time() . $file->getClientOriginalName();
							// $file->move(public_path('uploads/support'), $fileNames[$key]);
						}
						$files = implode(',', $fileNames);
					}
					$message = $request->input('message');
					// $translated_message=$this->translate($message);
					if ($type == 'emoji') {
						$message = ($message);
					}
					$data = array();
					$data['from_user_id'] = $users->id;
					$data['to_user_id'] = $toUser_id;
					$data['message'] = $message;
					// $data['translated_message']=$translated_message;
					if ($request->hasFile('files')) {
						$data['attachment'] = $files;
					}
					$data['type'] = $type;
					$data['status'] = 'unread';
					$data['entry_time'] = $this->today;
					$messageInsert = Chat::create($data);
					if (!empty($messageInsert)) {
						$id = $messageInsert->id;
						$arrStatus = Response::HTTP_OK;
						$arrCode = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Message sent !';
						broadcast(new MessageSent($users, $messageInsert));
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
			dd($e);
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function fetchMessagesMob(Request $request) {
		try {
			$messsages = array(
				'to_user.required' => 'To User is required',
			);
			$rules = array(

				'to_user' => 'required|',
			);
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err = '';
				foreach ($message->all() as $error) {
					if (count($message->all()) > 1) {
						$err = $err . ' ' . $error;
					} else {
						$err = $error;
					}
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}
			// if (!empty($validator)) {
			// $arrStatus   = Response::HTTP_NOT_FOUND;
			// $arrCode     = Response::$statusTexts[$arrStatus];
			// $arrMessage  = $validator;
			// return sendResponse($arrStatus,$arrCode,$arrMessage,'');
			// }

			$from_id = Auth::user()->id;
			$toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
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
			$path = Config::get('constants.settings.domainpath');
			$data_arr = [];
			$query = Chat::leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_chat.from_user_id')
				->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_chat.to_user_id')
				->select('tbl_chat.*', DB::RAW("CONCAT('" . $path . "/public/uploads/support/',tbl_chat.attachment) as attachment"), DB::RAW("TIME(tbl_chat.entry_time) as time"), 'tu1.user_id as fromuser_id', 'tu2.user_id as touser_id');
			$getMessage = $query->where([['tbl_chat.from_user_id', '=', $from_id], ['tbl_chat.to_user_id', '=', $toUser_id]])->orwhere([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->orderBy('tbl_chat.id', 'asc')->get();
			$count = Chat::where([['tbl_chat.status', '=', 'unread'], ['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->count();
			$files = array();

			foreach ($getMessage as $msg) {
				if ($msg->type == 'file') {
					array_push($files, $path . '/public/uploads/support/' . $msg->attachment);
				}
			}
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
						$difference = 'Today';
					} else if ($dateDiffrence > 0 && $dateDiffrence <= 1) {
						$difference = 'Yesterday';
					} else if ($dateDiffrence > 1 && $dateDiffrence <= 6) {
						$difference = $created->format('l');
					} else if ($dateDiffrence > 6) {
						$difference = $created->format('j F y');
					}
					$value->timing = $difference;
					$value->current_timing = $created->format('g:i A');
					$value->files = collect(explode(',', $getMessage[$k]->attachment));
					// $showMessage['msgdata'][] = $value->timing;
					if ($from_id == $getMessage[$k]->from_user_id) {
						$value->position = 'right';
						$showMessage['msgdata']['msgdata'][] = ['right' => $value];
					} else {
						$value->position = 'left';
						$showMessage['msgdata']['msgdata'][] = ['left' => $value];
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
					$finalMessage['files'] = $files;
					$updateData = array();
					$updateData['status'] = 'read';
					$updateData = Chat::where([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->update($updateData);
					$arrFinaldata['total_unread'] = $count;
					$arrFinaldata['messages'] = $finalMessage;

					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Message found !';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $getMessage);

				} else {
					$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Message not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $getMessage);
				}
			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Message not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $getMessage);
			}
		} catch (Exception $e) {
			dd($e);
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

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err = '';
				foreach ($message->all() as $error) {
					if (count($message->all()) > 1) {
						$err = $err . ' ' . $error;
					} else {
						$err = $error;
					}
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}
			// if (!empty($validator)) {

			// $arrStatus   = Response::HTTP_NOT_FOUND;
			// $arrCode     = Response::$statusTexts[$arrStatus];
			// $arrMessage  = $validator;
			// return sendResponse($arrStatus,$arrCode,$arrMessage,'');

			// }
			// dd(Auth::user());
			$from_id = Auth::user()->id;

			$toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
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
			$data_arr = [];
			$query = Chat::leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_chat.from_user_id')
				->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_chat.to_user_id')
				->select('tbl_chat.*', DB::RAW("TIME(tbl_chat.entry_time) as time"), 'tu1.user_id as fromuser_id', 'tu2.user_id as touser_id');
			$getMessage = $query->where([['tbl_chat.from_user_id', '=', $from_id], ['tbl_chat.to_user_id', '=', $toUser_id]])->orwhere([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->orderBy('tbl_chat.id', 'asc')->get();
			$count = Chat::where([['tbl_chat.status', '=', 'unread'], ['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->count();
			//dd($count);
			// foreach($getMessage as $message)
			// {
			//     $data_arr=[];
			//     if($message['from_user_id']==$from_id)
			//         $message['author']='me';
			//     else
			//         $message['author']=$message['from_user_id'];

			//     if($message['type']=='text')
			//     {
			//         $data_arr['text']=$message['message'];
			//         $message['type']='text';
			//     }
			//     else if($message['type']=='file')
			//     {
			//         $data_arr['file']['name']=$message['message'];
			//         $data_arr['file']['url']='uploads/support/'.$message['attachment'];
			//         $message['type']='file';
			//     }
			//     else if($message['type']=='emoji')
			//     {
			//         $data_arr['emoji']=$message['message'];
			//         $message['type']='emoji';
			//     }
			//     $data_arr['meta']=$message['time'];
			//     $message['data']=$data_arr;
			//     $message['unread']=$count;
			// }

			$files = array();
			foreach ($getMessage as $msg) {
				if ($msg->type == 'file') {
					array_push($files, 'public/uploads/support/' . $msg->attachment);
				}
			}
			$finalMessage = [];
			//$finalMessage['total_unread']=$count;
			//dd($finalMessage['total_unread']);

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

						$difference = 'Today';
					} else if ($dateDiffrence > 0 && $dateDiffrence <= 1) {
						$difference = 'Yesterday';
					} else if ($dateDiffrence > 1 && $dateDiffrence <= 6) {
						$difference = $created->format('l');
					} else if ($dateDiffrence > 6) {
						$difference = $created->format('j F y');
					}

					$value->timing = $difference;
					$value->current_timing = $created->format('g:i A');
					$value->files = collect(explode(',', $getMessage[$k]->attachment));
					//dd(11);
					$showMessage[$value->timing]['showtime'] = $value->timing;
					//$showMessage['showtime'] = $value->timing;
					if ($from_id == $getMessage[$k]->from_user_id) {
						$value->position = 'right';
						$showMessage[$value->timing]['msgdata'][] = ['right' => $value];
						//$showMessage['msgdata'][] = ['right' => $value];
					} else {
						$value->position = 'left';
						$showMessage[$value->timing]['msgdata'][] = ['left' => $value];
						//$showMessage['msgdata'][] = ['left' => $value];
					}
				}

				if (!empty($showMessage)) {
					foreach ($showMessage as $timedata) {
						$finalMessage[] = $timedata;
					}
				} else {
					$finalMessag = '';
				}
				$newcount = $count;
				//dd()

				if (!empty($finalMessage)) {
					$finalMessage['files'] = $files;
					$updateData = array();
					/* $updateData['status'] = 'read';
                        $updateData = Chat::where([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->update($updateData);*/

					//dd($count);
					//dd($count,$newcount);

					$arrFinaldata['total_unread'] = $count;
					$arrFinaldata['messages'] = $finalMessage;
					$finalMessage['total_unread'] = $count;

					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Message found !';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $finalMessage);

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
			dd($e);
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
	public function fetchMessagesRead(Request $request) {

		try {
			$messsages = array(
				'to_user.required' => 'To User is required',
			);
			$rules = array(

				'to_user' => 'required|',
			);

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err = '';
				foreach ($message->all() as $error) {
					if (count($message->all()) > 1) {
						$err = $err . ' ' . $error;
					} else {
						$err = $error;
					}
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}
			// if (!empty($validator)) {

			// $arrStatus   = Response::HTTP_NOT_FOUND;
			// $arrCode     = Response::$statusTexts[$arrStatus];
			// $arrMessage  = $validator;
			// return sendResponse($arrStatus,$arrCode,$arrMessage,'');

			// }
			// dd(Auth::user());
			$from_id = Auth::user()->id;

			$toUser_id = User::where([['id', '=', $request->input('to_user')], ['status', '=', 'Active']])->pluck('id')->first();
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
			$data_arr = [];
			$query = Chat::leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_chat.from_user_id')
				->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_chat.to_user_id')
				->select('tbl_chat.*', DB::RAW("TIME(tbl_chat.entry_time) as time"), 'tu1.user_id as fromuser_id', 'tu2.user_id as touser_id');
			$getMessage = $query->where([['tbl_chat.from_user_id', '=', $from_id], ['tbl_chat.to_user_id', '=', $toUser_id]])->orwhere([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->orderBy('tbl_chat.id', 'asc')->get();
			$count = Chat::where([['tbl_chat.status', '=', 'unread'], ['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->count();
			//dd($count);
			// foreach($getMessage as $message)
			// {
			//     $data_arr=[];
			//     if($message['from_user_id']==$from_id)
			//         $message['author']='me';
			//     else
			//         $message['author']=$message['from_user_id'];

			//     if($message['type']=='text')
			//     {
			//         $data_arr['text']=$message['message'];
			//         $message['type']='text';
			//     }
			//     else if($message['type']=='file')
			//     {
			//         $data_arr['file']['name']=$message['message'];
			//         $data_arr['file']['url']='uploads/support/'.$message['attachment'];
			//         $message['type']='file';
			//     }
			//     else if($message['type']=='emoji')
			//     {
			//         $data_arr['emoji']=$message['message'];
			//         $message['type']='emoji';
			//     }
			//     $data_arr['meta']=$message['time'];
			//     $message['data']=$data_arr;
			//     $message['unread']=$count;
			// }

			$files = array();
			foreach ($getMessage as $msg) {
				if ($msg->type == 'file') {
					array_push($files, 'public/uploads/support/' . $msg->attachment);
				}
			}
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

						$difference = 'Today';
					} else if ($dateDiffrence > 0 && $dateDiffrence <= 1) {
						$difference = 'Yesterday';
					} else if ($dateDiffrence > 1 && $dateDiffrence <= 6) {
						$difference = $created->format('l');
					} else if ($dateDiffrence > 6) {
						$difference = $created->format('j F y');
					}

					$value->timing = $difference;
					$value->current_timing = $created->format('g:i A');
					$value->files = collect(explode(',', $getMessage[$k]->attachment));

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
				$newcount = $count;
				//dd()

				if (!empty($finalMessage)) {
					$finalMessage['files'] = $files;
					$updateData = array();
					$updateData['status'] = 'read';
					$updateData = Chat::where([['tbl_chat.to_user_id', '=', $from_id], ['tbl_chat.from_user_id', '=', $toUser_id]])->update($updateData);

					//dd($count);
					//dd($count,$newcount);

					$arrFinaldata['total_unread'] = $count;
					$arrFinaldata['messages'] = $finalMessage;
					$finalMessage['total_unread'] = $count;

					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Message found !';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $finalMessage);

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
			dd($e);
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function translate($text) {
		$apiKey = 'AIzaSyD-MP-rPzjrBclTBx-SUCHbKBJdL1BxA0I';
		$url = 'https://translation.googleapis.com/language/translate/v2?target=ru&key=' . $apiKey . '&q=' . rawurlencode($text) . '&source=en';

		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($handle);
		$responseDecoded = json_decode($response, true);
		curl_close($handle);
		dd($responseDecoded);
		$trans_word = $responseDecoded['data']['translations'][0]['translatedText'];
		return $trans_word;
	}

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

}
