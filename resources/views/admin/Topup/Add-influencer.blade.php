@extends('layouts.user_type.admin-app')
@section('content')
@php

$product_list_array = json_decode($product_list, true);

@endphp

<style>
    .hideTargetVal{display: none;}
</style>

<div class="row">
    <div class="admin-card-button" v-if="otpstatus ==1">
        <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp(2)">
            Send Otp
        </button>
        <p>Note :- Otp Valid 2 Hours</p>
    </div>
    <div class="col-6 mx-auto">
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="card-title">Add Influencer Top Up</h4>
            </div>

            <div class="admin-card-body">
                <form class="row g-3">
                    <input type="hidden" name="user_id" value="" id="topup_user_id" />
                    <div class="form-group col-12">
                        <label>User Id</label>
                        <input type="text" name="username" class="admin-form-control" id="username"
                            placeholder="User Id" v-model="username" onkeyup="checkUserExist(this.value)" />


                        <div class="clearfix"></div>
                      </div>
                    <div class="form-group col-12">
                        <label>Select Package</label>
                        <select name="product_id" class="admin-form-control" v-model="topup.product_id" id="product_id"
                            onchange1="changeSelect(this.value)">
                   
                            @foreach($product_list_array as $product_id)
                            <option value="{{$product_id['id']}}">{{$product_id['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Target Type</label>
                        <select name="role_type" class="admin-form-control" onchange="changeRole(this.value)">
                            <option selected value="">Select Target Type</option>
                            <option value="1">Influencer</option>
                            <option value="2">Leader</option>
                        </select>
                        <div v-if="roleErr !== ''" class="tooltip1">
                            <span class="text-danger"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Select Type</label>
                        <select name="target_type" id="target_type" class="admin-form-control"
                            onchange="changeTarget(this.value)">
                            <option selected value="">Select Type</option>
                            <option value="3" id="targetTypeVal3x">3X</option>
                            <option value="5">5X</option>
                            <option value="8">8X</option>
                            <option value="10">10X</option>
                            <option value="15">15X</option>
                            <option value="20">20X</option>
                        </select>
                        <div v-if="targetErr !== ''" class="tooltip1">
                            <span class="text-danger"></span>
                        </div>
                    </div>
                    <div class="form-group col-12">
                        <label class="control-label">Enter Amount</label>
                        <br />
                        <input placeholder="Enter Amount" type="text" class="admin-form-control" id="hash_unit"
                            name="hash_unit" min="1" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers"
                            onkeyup="hashvalidation(this)" oninput="hashvalidationTarget(this)" />
                        <div class="clearfix"></div>
                        <p class="text-danger"></p>
                    </div>
                    <div class="form-group col-12">
                        <label class="control-label">Target Amount</label>
                        <br />
                        <input placeholder="Target Amount" type="text" class="admin-form-control"
                            name="target_hash_unit" id="target_amount" readonly />
                        <div class="clearfix"></div>
                        <p class="text-danger"></p>
                    </div>
                    <div class="form-group col-12" v-if="otpstatus ==1">
                        <label>OTP</label>
                        <input type="text" class="admin-form-control" id="otp" placeholder="Enter Otp" name="otp"
                            value="" onblur="OtpValidation(this.value)" />
                        <div v-if="otpErr !== ''" class="tooltip1">
                            <span class="text-danger"> </span>
                        </div>
                    </div>
                    <div class="form-group col-12 text-center">
                        <button id="signup1" type="button" class="btn btn-rounded btn-outline-primary"
                            onclick="addTopUp()">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
        var base_url = '{{url('/')}}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');

        function changeRole(value) {
            if(value == 2){
            $('#targetTypeVal3x').addClass('hideTargetVal');
            }
            else{
            $('#targetTypeVal3x').removeClass('hideTargetVal');
            }
        }

        function changeTarget(target_multiplier) {

        var hash_unit = $("#hash_unit").val();
        if (target_multiplier == "") {
        $("#targetErr").html("Please select type of target");
        } else {
        $("#target_amount").val(Number(target_multiplier) * hash_unit);
        }
        }
        function AmountValidation(amount){

            var fullname = amount;
            var fullnamel = fullname.replace(/ /g, "");
            if (fullname == "") {
                $("#amount-message").text("Amount should not be blank.");
            } else if (fullnamel < 10) {
                $("#amount-message").text("Enter amount must be at least 10");
            } else {
                $("#amount-message").text("");
            }
        }
        function  submitButtonVisiblity(isAvialable, user_id){
            if(isAvialable =='Available'){
                $('#signup1').removeAttr('disabled');
            }
            else{
                $('#signup1').prop('disabled','true');
            }

        }

     
function hashvalidation() {
    var min_hash = Number($("#min_hash").val());
    var max_hash = Number($("#max_hash").val());
    var hash_unit = Number($("#hash_unit").val());

    if (hash_unit < min_hash || hash_unit > max_hash) {
        if (max_hash == 0) {
            $("#usermsg").text("Amount should be on range " + min_hash + " to above");
        } else {
            $("#usermsg").text("Amount should be on range " + min_hash + " to " + max_hash);
        }
        $("#isValid").val("false");
    } else {
        if (hash_unit % 1 !== 0) {
            $("#usermsg").text("Amount should be multiple of 1");
            $("#isValid").val("false");
        } else {
            $("#isValid").val("true");
            $("#usermsg").text("");
        }
    }
}

   
function hashvalidationTarget(event) {
    var hash_amt = $(event).val();
    var target_type = $("#target_type").val();

    console.log(hash_amt +'--'+target_type);
    if (target_type == '') {
        $("#targetErr").html("Please select type of target");
    } else {
        $("#target_amount").val(Number(hash_amt) * Number(target_type));
    }
}

        function checkUserExist(username) {
            var data = { user_id: username };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });
            $.ajax({
                type: "POST",
                url: '{{url('/admin/checkuserexist')}}', 
                data: data,
                dataType: "json",
                success: (resp) => {
                    if (resp.code === 200) {
                        var user_id = resp.data.id;
                        var fullname = resp.data.fullname;
                        var isAvialable = "Available";
                        toastr.success(resp.message);
                    } else {
                        var user_id = "";
                        var isAvialable = "Not Available";
                        toastr.error(resp.message);
                    }

                    $('#topup_user_id').val(user_id);
                    submitButtonVisiblity(isAvialable, user_id);

                },
                error: (err) => {
                    toastr.error(err);
                }
            });
        }

        function OtpValidation(OTP) {

            console.log(OTP);
            var OTPl = OTP.replace(/ /g, "");
            if (OTPl == "") {
                this.otpErr = "OTP should not be blank.";
            }else {
                this.otpErr = "";
            }
        }


        function addTopUp() {
            var isDisabledBtn = false;
            // Swal.fire({
            //   title: "Are you sure?",
            //   text: "You want to topup!",
            //   type: "warning",
            //   showCancelButton: true,
            //   confirmButtonText: "Yes!",
            //   cancelButtonText: "Cancel",
            // }).then(function (result) {
            //   if (result.value) {
            //     var data = {
            //       otp: $("#otp").val(),
            //       id: $("#topup_user_id").val(),
            //       user_id: $("#username").val(),
            //       product_id: $("#product_id").val(),
            //       hash_unit: $("#hash_unit").val(),
            //       target_business: $("#target_amount").val(),
            //       payment_type: $("#payment_type").val(),
            //       franchise_user_id: $("#franchise_user_id").val(),
            //       masterfranchise_user_id: $("#masterfranchise_user_id").val(),
            //       device: "web",
            //       topupfrom: "admin panel",
            //     };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });


            var data = {'otp': $("#otp").val(),
                id: $("#topup_user_id").val(),
                // user_id: $("#username").val(),
                product_id: $("#product_id").val(),
                hash_unit: $("#hash_unit").val(),
                target_business: $("#target_amount").val(),
                payment_type: $("#payment_type").val(),
                franchise_user_id: $("#franchise_user_id").val(),
                masterfranchise_user_id: $("#masterfranchise_user_id").val(),
                device: "web",
                topupfrom: "admin panel",
            };
            console.log(data);
            $.ajax({
                url: "{{url('/admin/store/topup-store')}}",
                type: "POST",
                data: data,

                success: function (resp) {
                    if (resp.code === 200) {
                        toastr.success(resp.message);
                        window.location.href = "{{url('/admin/top-up/influencer-topup-report')}}";
                    } else {
                        toastr.error(resp.message);
                    }
                    // $("#username").val("");
                    // $("#fullname").val("");
                    // $("#isAvialable").val("");
                    // $("#otp").val("");
                    // $("#topup_user_id").val("");
                    // $("#pin").val("");
                    // $("#product_id").val(7);
                    // $("#hash_unit").val("");
                    // $("#target_amount").val("");
                    // $("#target_type").val("");
                    isDisabledBtn = true;
                },
                error: function (err) {
                    toastr.error(err.responseText);
                },
            });
        }

    </script>

@endsection