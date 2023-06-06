<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\Response as Response;
use App\User;
use Illuminate\Http\Request;
use App\Models\WithdrawMode;
use App\Models\Invoice;
use DB;
use Auth;


trait WithdrawTMode
{
    /**
     * Get WITHDRAW MODE
     *
     * @return \Illuminate\Http\Response
     */
    public function getWithdrawMode(Request $request)
    {

        try {
            $arrInput = $request->all();

            $userData = Auth::User();

            if (!empty($userData)) {
                $mode1 = WithdrawMode::where('id', $userData->id)->select('network_type')->orderBy('id', 'desc')->first();
                if (empty($mode1)) {
                    $mode = Invoice::where('id', $userData->id)->select('payment_mode')->orderBy('id', 'asc')->first();
                    if (!empty($mode)) {
                        $type = $mode->payment_mode;
                    } else {
                        $type = 'BTC';
                        $mode = '1';
                    }
                } else {
                    $mode = $mode1;
                    $type = $mode->network_type;
                }

                if (!empty($mode) && $type == 'BTC') {

                    $btc_address = User::where('id', $userData->id)->pluck('btc_address')->first();
                    if (!empty($btc_address)) {

                        if (strlen($btc_address) >= 26 && strlen($btc_address) <= 42) {
                            $arrData['address'] = $btc_address;
                            $arrData['mode'] = $type;
                            $arrStatus   = Response::HTTP_OK;
                            $arrCode     = Response::$statusTexts[$arrStatus];
                            $arrMessage  = 'Bitcoin address is valid';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
                        }

                        // validate address using block chain
                        $blockchain_address = blockchain_address(trim($btc_address));
                        if ($blockchain_address['msg'] == 'failed') {
                            $blockio_address = blockio_address(trim($btc_address));
                            if ($blockio_address['msg'] == 'fail') {
                                $blockcyper_address = blockcyper_address(trim($btc_address));
                                if ($blockcyper_address['msg'] == 'failed') {
                                    $blockbitaps_address = blockbitaps_address(trim($btc_address));
                                    if ($blockbitaps_address['msg'] == 'failed') {

                                        $arrStatus   = Response::HTTP_NOT_FOUND;
                                        $arrCode     = Response::$statusTexts[$arrStatus];
                                        $arrMessage  = 'Invalid BTC Address';
                                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                                    } else {
                                        $arrData['address'] = $btc_address;
                                        $arrData['mode'] = $type;

                                        $arrStatus   = Response::HTTP_OK;
                                        $arrCode     = Response::$statusTexts[$arrStatus];
                                        $arrMessage  = 'Address found';
                                        return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
                                    }
                                } else {
                                    $arrData['address'] = $btc_address;
                                    $arrData['mode'] = $type;
                                    $arrStatus   = Response::HTTP_OK;
                                    $arrCode     = Response::$statusTexts[$arrStatus];
                                    $arrMessage  = 'Address found';
                                    return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
                                }
                            } else {
                                $arrData['address'] = $btc_address;
                                $arrData['mode'] = $type;
                                $arrStatus   = Response::HTTP_OK;
                                $arrCode     = Response::$statusTexts[$arrStatus];
                                $arrMessage  = 'Address found';
                                return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
                            }
                        } else {
                            $arrData['address'] = $btc_address;
                            $arrData['mode'] = $type;
                            $arrStatus   = Response::HTTP_OK;
                            $arrCode     = Response::$statusTexts[$arrStatus];
                            $arrMessage  = 'Address found';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
                        }
                    } else {

                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Invalid BTC Address';
                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    }
                } else if (!empty($mode) && $type == 'ETH') {
                    $eth_address = User::where('id', $userData->id)->pluck('ethereum')->first();
                    if (!empty($eth_address)) {
                        $eth_status = ETHConfirmation($eth_address);
                        if ($eth_status['msg'] == 'success') {
                            $arrData['address'] = $eth_address;
                            $arrData['mode'] = $type;

                            $arrStatus   = Response::HTTP_OK;
                            $arrCode     = Response::$statusTexts[$arrStatus];
                            $arrMessage  = 'ETH address found';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
                        } else {
                            $arrStatus   = Response::HTTP_NOT_FOUND;
                            $arrCode     = Response::$statusTexts[$arrStatus];
                            $arrMessage  = 'Invalid ETH Address';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        }
                    } else {
                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Invalid ETH Address';
                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    }
                } else {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Invalid  Address';
                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                }
            } else {
                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {

            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }
}
