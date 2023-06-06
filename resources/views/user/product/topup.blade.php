@extends('layouts.user_type.auth-app')

@section('content')
@php

$topupfundbalance = $getBalance['fund_wallet'] - $getBalance['fund_wallet_withdraw'];
    $topuproibalance = $getBalance['roi_wallet'] - $getBalance['roi_wallet_withdraw'];
    $topuphsccbalance = $getBalance['hscc_bonus_wallet'] - $getBalance['hscc_bonus_wallet_withdraw'];
    $topupworkingbalance = $getBalance['working_wallet'] - $getBalance['working_wallet_withdraw'];

    if(is_numeric($topupfundbalance))
    {
      $topupfundbalance = number_format($topupfundbalance, 2);
    }
    else{
      $topupfundbalance = 0;
    }

    if(is_numeric($topuproibalance))
    {
      $topuproibalance = number_format($topuproibalance, 2);
    }
    else{
      $topuproibalance = 0;
    }

    if(is_numeric($topuphsccbalance))
    {
      $topuphsccbalance = number_format($topuphsccbalance, 2);
    }
    else{
      $topuphsccbalance = 0;
    }

    if(is_numeric($topupworkingbalance))
    {
      $topupworkingbalance = number_format($topupworkingbalance, 2);
    }
    else{
      $topupworkingbalance = 0;
    }

@endphp
  <div class="page-wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
          <nav aria-label="breadcrumb ms-3">
            <h6 class="font-weight-bolder mb-0">Activation Account</h6>
          </nav>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-md-12">
          <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" value="fund_wallet" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true" onclick="changeInvestType()">
                Invest from Fund wallet
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" value="multi_wallet" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false" onclick="changeInvestType1()">Invest from Multiple wallets</button>
            </li>

          </ul>
          <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">
              <div class="card">
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-6">
                            <div
                              class="bg-gradient-primary border-radius-lg h-100 p-3 d-flex align-items-center justify-content-center">
                              <div class="row">
                                <div class="col-md-12 mt-3">
                                  <div class="numbers text-center">
                                    <img src="{{asset('images/TopupAccount.png')}}" class="img-fluid">
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <form class="row g-3" id="form1">
                              <div class="col-md-12 ">
                                <label>Investment Type</label>
                                <select id="topup_type" value="{{ old('topup_type')}}" aria-label="Default select example" name="topup_type" class="form-select" onChange="topupTypeValidation()">
                                  <option selected value="">Select Investment</option>
                                  <option value="self">Self-Investment</option>
                                  <option value="downline">Invest for Others</option>
                                </select>
                              </div>
                              <div class="col-md-12 drack-arrow">
                                <label>User ID</label>
                                <input type="text" class="form-control upline" placeholder="Enter User ID of team" name="user_id" id="user_id" maxlength="12" oninput="checkUserExistedNew()">
                                <input type="text" class="form-control auth" placeholder="User Id" name="user_id" id="user_id" disabled="" style="background-color: #ffffff;">
                                 <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="user_id_msg"></span>
                                </div>
                              </div>
                              <div class="col-md-12 drack-arrow">
                                <label>Package</label>
                                 <select class="form-select" value="{{ old('product_id')}}" onchange="changeSelect()" id="product_id" name="product_id">
              							      <option value="">Select Package</option>
              							      @foreach($all_products as $get_all_products)
              							      <option value="{{$get_all_products->id}}">{{$get_all_products->package_name}}</option>
              							      @endforeach
                                  <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="product_id_err"></span>
                                </div>
                              </div>
                              <div class="col-md-12">
                                <label>Personal Note (Optional)</label>
                                <input type="text" class="form-control" placeholder="Personal Note(Optional)" id="topupfrom" name="topupfrom"
                                  onkeypress="return (event.charCode > 64 && event.charCode < 91) || (event.charCode > 96 && event.charCode < 123) || (event.charCode >= 48 && event.charCode <= 57) ||(event.charCode = 32) ">
                              </div>
                              <div class="col-md-12">
                                <div class="input-group">
                                  <span class="input-group-text bg-gradient-primary font-size-14">
                                    Fund Wallet : $ {{$topupfundbalance}}
                                  </span>
                                  <input onpaste="return false;" placeholder="Enter Amount" id="hash_unit" type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                    name="hash_unit" class="form-control" oninput="hashvalidation()" maxlength="9">
                                </div>
                                 <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="hash_unit_err"></span>
                                </div>
                              </div>
                              <div class="col-md-12">
                                <p class="pass-note boldnew text-danger">
                                  NOTE:- Make sure your email address is correct because once your account is invested then it can’t be changed.
                                </p>
                              </div>
                              <div class="col-md-12">
                                <button id="selftopup" type="button" onclick="sendOTP()" class="btn bg-gradient-primary w-100 text-uppercase p-2">Submit</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
            </div>
            <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
              <div class="card">
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-6">
                            <div
                              class="bg-gradient-primary border-radius-lg h-100 p-3 d-flex align-items-center justify-content-center">
                              <div class="row">
                                <div class="col-md-12 mt-3">
                                  <div class="numbers text-center">
                                    <img src="{{asset('images/TopupAccount.png')}}" class="img-fluid">
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <form class="row g-3" id="form2">
                              <div class="col-md-12 ">
                                <label>Investment Type</label>
                               <select id="topup_types" aria-label="Default select example" name="topup_types" class="form-select" onChange="topupTypeValidationMW()">
                                  <option selected value="">Select Investment</option>
                                  <option value="self">Self-Investment</option>
                                  <option value="downline">Invest for Others</option>
                                </select>
                              </div>
                              <div class="col-md-12 drack-arrow">
                                <label>User ID</label>
                               <input type="text" class="form-control uplines" placeholder="Enter User ID of team" name="user_ids" id="user_ids" maxlength="12" oninput="checkUserExisteds()">
                                <input type="text" class="form-control auths" placeholder="User Id" name="user_ids" id="user_ids" disabled="" style="background-color: #ffffff;">
                                 <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="user_id_msgs"></span>
                                </div>
                              </div>
                              <div class="col-md-12 drack-arrow">
                                <select class="form-select" value="{{ old('product_ids')}}" onchange="changeSelects()" id="product_ids" name="product_ids">
                                  <option value="">Select Package</option>
                                  @foreach($all_products as $get_all_products)
                                  <option value="{{$get_all_products->id}}">{{$get_all_products->package_name}}</option>
                                  @endforeach
                                  <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="product_id_errs"></span>
                                </div>
                              </div>
                              <div class="col-md-12">
                                <label>Personal Note (Optional)</label>
                                <input type="text" class="form-control" placeholder="Personal Note(Optional)" id="topupfroms" name="topupfroms"
                                  onkeypress="return (event.charCode > 64 && event.charCode < 91) || (event.charCode > 96 && event.charCode < 123) || (event.charCode >= 48 && event.charCode <= 57) ||(event.charCode = 32) ">
                              </div>
                              <div class="col-md-12">
                                <div class="input-group">
                                  <span class="input-group-text bg-gradient-primary font-size-14">
                                    Wallet Selected
                                  </span>
                                  <select class="form-select" aria-label="Default select example"
                                   id="wallet_type" name="wallet_type" onchange="walletSelect()">
                                    <option selected value="">Select Wallet</option>
                                   <option value="roi">ROI Wallet <b class="boldnew">${{ $topuproibalance }}</b></option>
                                    <option value="working">Working Wallet <b class="boldnew">${{$topupworkingbalance}}</b></option>
                                    <option value="hscc">HSCC BONUS Wallet <b class="boldnew">${{ $topuphsccbalance}}</b></option>
                                  </select>
                                </div>
                              </div>
                              <div class="col-md-12">
                                <label><b class="boldnew">Enter Total Investment Amount</b></label>
                               <input onpaste="return false;" placeholder="Enter Amount" id="hash_units" type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                    name="hash_units" class="form-control" oninput="hashvalidations()" maxlength="9">
                                 <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="hash_unit_errs"></span>
                                </div>
                                </div>
                              <div class="col-md-12">
                                <label><b class="boldnew">Enter Amount to use from Fund Wallet</b></label>
                               <input onpaste="return false;"  placeholder="Enter Fund Wallet Amount" type="text"
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                    name="fund_amount" id="fund_amount" class="form-control" maxlength="9">
                                    <div class="tooltip2">
                                  <span class="error-msg-size tooltip-inner text-danger" id="fund_amount_errs"></span>
                                </div>
                              </div>
                              <div class="col-md-12" id="type_show">
                                <label>
                                  <b class="boldnew" id="roi">Enter Amount to use from ROI Wallet</b>
                                  <b class="boldnew" id="working">Enter Amount to use from Working Wallet</b>
                                  <b class="boldnew" id="hscc">Enter Amount to use from HSCC BONUS Wallet</b>
                                </label>
                                <!-- <input type="text" class="form-control" placeholder="Remark"> -->
                               <input onpaste="return false;" id="roi_wallet_amount" placeholder="Enter Wallet Amount" type="text"
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                    name="roi_wallet_amount" class="form-control" maxlength="9">
                              <input onpaste="return false;" id="working_wallet_amount" placeholder="Enter Wallet Amount" type="text"
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                    name="working_wallet_amount" class="form-control" maxlength="9">
                              <input onpaste="return false;" id="hscc_wallet_amount" placeholder="Enter Wallet Amount" type="text"
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                    name="hscc_wallet_amount" class="form-control" maxlength="9">
                              <input type="hidden" name="wallet_amount" id="wallet_amount"  >



                              </div>
                              <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                              <div class="col-md-12">
                                <p class="pass-note boldnew text-danger">
                                  NOTE:- <br> - Make sure your email address is correct because once your account is invested then it can’t be changed.
                                  <br> - A minimum of 50% of the top-up amount must be taken from the fund wallet for top-up.
                                </p>
                              </div>
                              <div class="col-md-12">
                                <button id="selftopups" onclick="sendOTPs()"
                                  type="button" class="btn bg-gradient-primary w-100 text-uppercase p-2">Submit</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                    </div>
                  </div>
                  </div>
                </div>
              </div>


    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row align-items-center justify-content-center">
              <div class="col-md-4">
                <img src="{{asset('images/otp.png')}}" class="img-fluid">
              </div>
              <div class="col-md-8">
                <div class="row">

                  @if(Auth::user()->google2fa_status == "disable")
                  <div class="col-md-12 otp">
                    <input type="text" class="form-control" placeholder="Enter OTP" name="otp" id="otp" maxlength="6"
                      onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                      <span class="error-msg-size tooltip-inner text-white">OTP sent to
                                                @php
                                                    $email = Auth::user()->email;
                                                    $modifiedEmail = substr_replace($email, "********", 4, 8); // Add an asterisk after the fourth character
                                                @endphp

                          {{ $modifiedEmail }}</span>
                        <div class="tooltip2">
                          <span class="error-msg-size tooltip-inner text-danger" id="otp_err"></span>
                        </div>
                  </div>
                  <div class="col-md-12 text-center mt-3" id="resend_otp">
                    <button class="btn bg-gradient-primary" id="resend_otp_btn" onclick="sendOTP()" type="button">Resend</button>
                  </div>
                  @else
                  <div class="col-md-12 mt-3 G2FA">
                    <input type="text" name="2fa-otp" id="otp_2fa" class="form-control w1000" placeholder="Enter G2FA OTP" maxlength="6"
                      onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                    <!-- <input type="text" class="form-control" placeholder="Enter OTP"> -->
                    <div class="tooltip2">
                      <span class="error-msg-size tooltip-inner text-danger" id="otp_err"></span>
                    </div>
                  </div>
                  @endif

                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer fund_wallet">
            <button type="button" id="selftopupo" class="btn btn-light" onclick="storeTopup()">Submit</button>
          </div>
          <div class="modal-footer other_wallet">
            <button type="button" id="selftopupos" class="btn btn-light" onclick="storeTopups()">Submit</button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="http://code.jquery.com/jquery-latest.js"></script>

<script>
$(document).ready(function(){
topupTypeValidation();
topupTypeValidationMW();
getproduct();
changeInvestType();
});

function topupTypeValidation() {
  var topup_type = $('#topup_type').val();
  if (topup_type == ""){
  $('.upline').hide();
  $('.auth').show();
  $('.auth').val('');
   $('#selftopup').prop('disabled', true);
  }else if (topup_type == "self"){
	$('.upline').hide();
  $('.auth').show();
  var selfuserid = '{{Auth::user()->user_id}}';
  $('#user_id').val(selfuserid);
  $('.auth').val(selfuserid);
  $("#user_id_msg").fadeIn().html('');
   $('#selftopup').prop('disabled', false);
  }else if (topup_type == "downline"){
  $('.auth').hide();
  $('.upline').show();
  $('#user_id').val('');
   $('#selftopup').prop('disabled', false);
  }
}

function topupTypeValidationMW() {
  var topup_types = $('#topup_types').val();
  if (topup_types == ""){
  $('.uplines').hide();
  $('.auths').show();
  $('.auths').val('');
   $('#selftopups').prop('disabled', true);
  }else if (topup_types == "self"){
  $('.uplines').hide();
  $('.auths').show();
  var selfuserid = '{{Auth::user()->user_id}}';
  $('#user_ids').val(selfuserid);
  $('.auths').val(selfuserid);
  $("#user_ids_msg").fadeIn().html('');
   $('#selftopups').prop('disabled', false);
  }else if (topup_types == "downline"){
  $('.auths').hide();
  $('.uplines').show();
  $('#user_ids').val('');
   $('#selftopups').prop('disabled', false);
  }
}

var invest_mode;
function changeInvestType(){
      invest_mode = $('#pills-home-tab').val();
      //$('.G2FA').hide();
       $('#type_show').hide();
      $('.fund_wallet').show();
      $('.other_wallet').hide();
  }

function changeInvestType1(){
    invest_mode = $('#pills-profile-tab').val();
    $('.fund_wallet').hide();
    $('.other_wallet').show();
}

var packagelists;
var min_hash;
var min_hashs;
var max_hashs;
var max_hash;
var i;

function getproduct(){
  $.ajax({
    type:'GET',
    url:"{{url('/get-product')}}",
    success:function(response){
           packagelists = response.data;
          for (i in packagelists) {
            packagelists[i].id;
          }
    }
});
}

function walletSelect() {
 let wallet_type = $('#wallet_type').val();
if(wallet_type != ''){
  $('#type_show').show();
}if(wallet_type == ''){
  $('#type_show').hide();
}
else if (wallet_type == 'roi') {
 $('#roi').show();
 $('#working').hide();
 $('#hscc').hide();

 $('#roi_wallet_amount').show();
 $('#hscc_wallet_amount').hide();
 $('#working_wallet_amount').hide();

}else if (wallet_type == 'working') {
 $('#roi').hide();
 $('#working').show();
 $('#hscc').hide();

 $('#roi_wallet_amount').hide();
 $('#hscc_wallet_amount').hide();
 $('#working_wallet_amount').show();

}else if (wallet_type == 'hscc') {
 $('#hscc').show();
 $('#working').hide();
 $('#roi').hide();

 $('#roi_wallet_amount').hide();
 $('#hscc_wallet_amount').show();
 $('#working_wallet_amount').hide();

}
}

function changeSelect() {

      let id = $('#product_id').val();
      if(id == 1){
        id = '0';
      }else if(id == 2){
        id = 1;
      }else if(id == 3){
        id = 2;
      }else if(id == 4){
        id = 3;
      }else if(id == 5){
        id = 4;
      }else if(id == 6){
        id = 5;
      }else if(id == 7){
        id = 6;
      }
      if (id == '') {
         min_hash = ''
         max_hash = ''
      } else {
        min_hash = packagelists[id].min_hash;
        max_hash = packagelists[id].max_hash;
      }
      hashvalidation();
    }

    function hashvalidation() {
     let package_id = $('#product_id').val();
     var hash_unit = $('#hash_unit').val();
     if(package_id == 1){
        package_id = '0';
      }else if(package_id == 2){
        package_id = 1;
      }else if(package_id == 3){
        package_id = 2;
      }else if(package_id == 4){
        package_id = 3;
      }else if(package_id == 5){
        package_id = 4;
      }else if(package_id == 6){
        package_id = 5;
      }else if(package_id == 7){
        package_id = 6;
      }
       min_hash=packagelists[package_id].min_hash;
       max_hash=packagelists[package_id].max_hash;
      if (
        hash_unit < min_hash || hash_unit > max_hash
      ) {
        $("#hash_unit_err").fadeIn().html("Amount should be on range " + min_hash + " to " + max_hash);
           $("#hash_unit_err").focus();
            $('#selftopup').prop('disabled', true);
      } else {
        $("#hash_unit_err").html("");
        $('#selftopup').prop('disabled', false);
      }
    }

    function changeSelects() {

      let id = $('#product_ids').val();
      if(id == 1){
        id = '0';
      }else if(id == 2){
        id = 1;
      }else if(id == 3){
        id = 2;
      }else if(id == 4){
        id = 3;
      }else if(id == 5){
        id = 4;
      }else if(id == 6){
        id = 5;
      }else if(id == 7){
        id = 6;
      }
      if (id == '') {
         min_hashs = ''
         max_hashs = ''
      } else {
        min_hashs = packagelists[id].min_hash;
        max_hashs = packagelists[id].max_hash;
      }
      hashvalidations();
    }

    function hashvalidations() {

     let package_id = $('#product_ids').val();
     var hash_unit = $('#hash_units').val();
     if(package_id == 1){
        package_id = '0';
      }else if(package_id == 2){
        package_id = 1;
      }else if(package_id == 3){
        package_id = 2;
      }else if(package_id == 4){
        package_id = 3;
      }else if(package_id == 5){
        package_id = 4;
      }else if(package_id == 6){
        package_id = 5;
      }else if(package_id == 7){
        package_id = 6;
      }
       min_hashs=packagelists[package_id].min_hash;
       max_hashs=packagelists[package_id].max_hash;
      if (
        hash_unit < min_hashs || hash_unit > max_hashs
      ) {
        $("#hash_unit_errs").fadeIn().html("Amount should be on range " + min_hashs + " to " + max_hashs);
           $("#hash_unit_errs").focus();
            $('#selftopups').prop('disabled', true);
      } else {
        $("#hash_unit_errs").html("");
        $('#selftopups').prop('disabled', false);
      }
    }

function checkUserExistedNew(){

var user_id = $('#user_id').val();
$.ajax({
    type:'POST',
    url:"{{url('/downline')}}",
    data:{user_id:user_id, "_token": $('#token').val()},
    success:function(response){
        if(response.code == '200'){
            $('#selftopup').prop('disabled', false);
            $("#user_id_msg").fadeIn().html('');
        }
        else{
           $("#user_id_msg").fadeIn().html(response.message);
           // var selfuserid = '{{Auth::user()->user_id}}';
           //  if(selfuserid == user_id)
           //  {
           //      $('#selftopup').prop('disabled', false);
           //  }

                $('#selftopup').prop('disabled', true);
        }
    }
});
}

function checkUserExisteds(){

var user_id = $('#user_ids').val();
$.ajax({
    type:'POST',
    url:"{{url('/downline')}}",
    data:{user_id:user_id, "_token": $('#token').val()},
    success:function(response){
      console.log(response);
        if(response.code == '200'){
            $('#selftopups').prop('disabled', false);
            $("#user_id_msgs").fadeIn().html('');
        }
        else{
           $("#user_id_msgs").fadeIn().html(response.message);
           // var selfuserid = '{{Auth::user()->user_id}}';
           //  if(selfuserid == user_id)
           //  {
           //      $('#selftopups').prop('disabled', false);
           //  }

                $('#selftopups').prop('disabled', true);
        }
    }
});
}

function sendOTP(){
  var user_id =  $("#user_id").val();
  var product_id =  $("#product_id").val();
  var hash_unit =  $("#hash_unit").val();
  var topupfrom=  $("#topupfrom").val();
  var tfastatus = "{{Auth::user()->google2fa_status}}";


  if(user_id == ''){
   return $("#user_id_msg").fadeIn().html('The user id field is required');
  }

if(user_id != '' && product_id != '' && hash_unit != '')
{
   $('#selftopup').prop('disabled', true);

   if(tfastatus == "disable")
   {
$.ajax({
    type:'POST',
    url:"{{url('/sendOtp-For-SelfTopup')}}",
    data:{
      user_id: user_id,
      product_id: product_id,
      transcation_type: 2,
      hash_unit: hash_unit,
      device: 'web',
      topupfrom: topupfrom,
       "_token": $('#token').val(),
    },
    success:function(response){
    if(response.code == 200){
          // OTP sent successfully
         
          $("#exampleModal").modal("show");
          toastr['success'](response.message);
           $('#selftopup').prop('disabled', false);
      }else{
         $('#selftopup').prop('disabled', false);
          toastr['error'](response.message)
      }
    }
});
   }
   else{
      $("#exampleModal").modal("show");
   }
}
}

function sendOTPs(){
  var user_id =  $("#user_ids").val();
  var product_id =  $("#product_ids").val();
  var hash_unit =  $("#hash_units").val();
  var topupfrom=  $("#topupfroms").val();
  var wallet_type=  $("#wallet_type").val();
  var wallet_amount=  $("#wallet_amount").val();

  var roi_wallet_amount= $('#roi_wallet_amount').val();
  var hscc_wallet_amount= $('#hscc_wallet_amount').val();
  var working_wallet_amount= $('#working_wallet_amount').val();
  var tfastatus = "{{Auth::user()->google2fa_status}}";
  wallet_amount = roi_wallet_amount + hscc_wallet_amount + working_wallet_amount;
  $("#wallet_amount").val(wallet_amount);


  var fund_amount=  $("#fund_amount").val();

  if(user_id == ''){
   return $("#user_id_msgs").fadeIn().html('The user id field is required');
  }

if(user_id != '' && product_id != '' && hash_unit != '' && fund_amount != ''  && wallet_type != '' && wallet_amount != '')
{
   $('#selftopups').prop('disabled', true);
   if(tfastatus == "disable")
   {
$.ajax({
    type:'POST',
    url:"{{url('/sendOtp-For-SelfTopup')}}",
    data:{
      user_id: user_id,
      product_id: product_id,
      transcation_type: 2,
      hash_unit: hash_unit,
      device: 'web',
      topupfrom: topupfrom,
       "_token": $('#token').val(),
    },
    success:function(response){
    if(response.code == 200){
          // OTP sent successfully
      $("#exampleModal").modal("show");
      toastr['success'](response.message);
      $('#selftopups').prop('disabled', false);
      }else{
         $('#selftopups').prop('disabled', false);
          toastr['error'](response.message)
      }
    }
});
}
else{
      window.$("#exampleModal").modal("show");
   }
}
}

function storeTopup(){

  var user_id =  $("#user_id").val();
  var product_id =  $("#product_id").val();
  var hash_unit =  $("#hash_unit").val();
  var topupfrom=  $("#topupfrom").val();
  var otp=  $("#otp").val();

  if(user_id == ''){
   return $("#user_id_msg").fadeIn().html('The user id field is required');
  }

  if(otp == ''){
   return $("#otp_err").fadeIn().html('The user id field is required');
  }

if(user_id != '' && product_id != '' && hash_unit != '' && otp != '')
{
   $('#selftopupo').prop('disabled', true);
$.ajax({
    type:'POST',
    url:"{{url('/store-self-topup')}}",
    data:{
      user_id: user_id,
      product_id: product_id,
      transcation_type: 2,
      hash_unit: hash_unit,
      device: 'web',
      topupfrom: topupfrom,
      otp: otp,
      otp_2fa: $("#otp_2fa").val(),
       "_token": $('#token').val(),
    },
    success:function(response){
    if(response.code == 200){
          // OTP sent successfully
            $('#exampleModal').modal('hide');
        toastr.success(response.message);
        if($("#topup_type").val() == 'self') {
            new Swal({
                title: `${response.message}!!`,
                // text: `Go to Transfer report`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                // cancelButtonText: "No",
                confirmButtonText: "Go to Report",
            }).then((result) => {
                if (result.value) {
                    setTimeout(function () {
                        location.reload();
                        window.location.replace("{{url('/self-topup-report')}}");
                    }, 50);
                } else {
                    $('#selftopup').prop('disabled', false);
                    $('#selftopupo').prop('disabled', false);
                    $("#otp").val('');
                    var form1 = $('#form1');
                    form1.trigger('reset');

                    var form2 = $('#form2');
                    form2.trigger('reset');
                    //location.reload();
                }
            });
        }else{
            new Swal({
                title: `${response.message}!!`,
                // text: `Go to Transfer report`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                // cancelButtonText: "No",
                confirmButtonText: "Go to Report",
            }).then((result) => {
               $('#selftopup').prop('disabled', false);
               $('#selftopupo').prop('disabled', false);
               $("#otp").val('');
                if (result.value) {
                    setTimeout(function () {
                        location.reload();
                        window.location.replace("{{url('/downline-topup-report')}}");
                    }, 50);
                } else {
                    $("#otp").val('');
                    var form1 = $('#form1');
                    form1.trigger('reset');

                    var form2 = $('#form2');
                    form2.trigger('reset');
                    //location.reload();
                }
            });
        }
           $('#selftopup').prop('disabled', false);
      }else{
         $('#selftopup').prop('disabled', false);
         $('#selftopupo').prop('disabled', false);
         $("#otp").val('');
          toastr['error'](response.message)
      }
    }
});
}
}

function storeTopups(){

  var user_id =  $("#user_ids").val();
  var product_id =  $("#product_ids").val();
  var hash_unit =  $("#hash_units").val();
  var topupfrom=  $("#topupfroms").val();
  var wallet_type=  $("#wallet_type").val();
  var wallet_amount=  $("#wallet_amount").val();
  var fund_amount=  $("#fund_amount").val();
  var otp=  $("#otp").val();

  var hash_unit =  $("#hash_units").val();

  var roi_wallet_amount= $('#roi_wallet_amount').val();
  var hscc_wallet_amount= $('#hscc_wallet_amount').val();
  var working_wallet_amount= $('#working_wallet_amount').val();

  wallet_amount = (+roi_wallet_amount) + (+hscc_wallet_amount) + (+working_wallet_amount);
  $("#wallet_amount").val(wallet_amount);

  var fund_amount=  $("#fund_amount").val();

  if(user_id == ''){
   return $("#user_id_msgs").fadeIn().html('The user id field is required');
  }

  if(otp == ''){
   return $("#otp_err").fadeIn().html('The user id field is required');
  }

  var halfcalculate = hash_unit / 2;

  var totalchk = (+wallet_amount) +  (+fund_amount) ;

  if(halfcalculate > fund_amount)
  {
    $("#exampleModal").modal("hide");
    return $("#fund_amount_errs").fadeIn().html('50% Amount must be from your Fund Wallet');
  }
  else{
    if(totalchk != hash_unit)
    {
      $("#exampleModal").modal("hide");
      return $("#hash_unit_errs").fadeIn().html('Addition of Amount is Must be same as your topup amount');

    }
    else{




if(user_id != '' && product_id != '' && hash_unit != '' && otp != '' && fund_amount != ''  && wallet_type != '' && wallet_amount != '')
{
   $('#selftopupos').prop('disabled', true);
      $.ajax({
          type:'POST',
          url:"{{url('/self-topup-multiple-wallet')}}",
          data:{
            user_id: user_id,
            product_id: product_id,
            transcation_type: 2,
            hash_unit: hash_unit,
            device: 'web',
            topupfrom: topupfrom,
            wallet_amount: wallet_amount,
            roi_wallet_amount: roi_wallet_amount,
            hscc_wallet_amount: hscc_wallet_amount,
            working_wallet_amount: working_wallet_amount,
            wallet_type: wallet_type,
            fund_amount: fund_amount,
            otp: otp,
            otp_2fa: $("#otp_2fa").val(),
            "_token": $('#token').val(),
          },
          success:function(response){
          if(response.code == 200){
                // OTP sent successfully
            $("#exampleModal").modal("hide");
                toastr['success'](response.message);
                // location.reload();
                $('#selftopups').prop('disabled', false);
                $('#selftopupos').prop('disabled', false);
                $("#otp").val('');
                if (topup_types == "self"){
                  window.location.replace("{{url('/self-topup-report')}}");
                }
                else{
                  window.location.replace("{{url('/downline-topup-report')}}");
                }

            }else{
              $('#selftopups').prop('disabled', false);
              $('#selftopupos').prop('disabled', false);
              $("#otp").val('');
                toastr['error'](response.message)
            }
          }
      });
      }
  }
}
}

</script>
