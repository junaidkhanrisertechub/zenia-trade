@extends('layouts.user_type.admin-app')
@section('content')

    <div class="row">
        <div class="admin-card-button">
            <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp(2)">
                Send Otp
            </button>
            <p>Note :- Otp Valid 2 Hours</p>
        </div>
        <div class="col-6 mx-auto">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Bulk Power Top Up</h4>
                </div>

                <div class="admin-card-body">
                    <form class="row g-3" id="fund_req">
                        <div class="form-group col-12">
                            <label>User Ids</label>
                            <input type="text" name="username" class="admin-form-control" id="username"
                                   placeholder="Enter multiple User Ids" onkeyup="checkBulkUserExisted(this.value)"
                                   oninput="seperateUsers(this.value)" />
                            <span class="text-danger">*Note: IDs are separated with comma ( , )</span>
                            <div v-if="bulkErr !== ''" class="tooltip1">
                                <span class="text-danger"> </span>
                            </div>

                        </div>
                        <div class="form-group col-12">
                            <label class="control-label">Enter Amount</label>
                            <br />
                            <input placeholder="Enter Amount" type="text"
                                   onpaste="return event.charCode >= 48 && event.charCode <= 57" class="admin-form-control"
                                   name="hash_unit" id="hash_unit" min="1" step="1"
                                   onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers only"
                                   v-model="topup.hash_unit" onkeyup="hashvalidation(this.event)" />
                            <div class="clearfix"></div>
                            <!-- <p class="text-danger"></p> -->
                        </div>
                        <div class="form-group col-12" v-if="otpstatus ==1">
                            <label>OTP</label>
                            <input type="text" class="admin-form-control" id="otp" placeholder="Enter Otp" name="otp"
                                   v-model="otp" data-vv-as="OTP " onblur="OtpValidation(this.value)" />
                            <!-- <div v-if="otpErr !== ''" class="tooltip1">
                                <span class="text-danger"></span>
                            </div> -->
                        </div>
                        <div class="form-group col-12 text-center">
                            <button type="button" class="btn btn-rounded btn-outline-primary" onclick="addTopUp()" />

                            Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>

        function checkBulkUserExisted(username) {
            var data = { user_id: username };
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });
            $.ajax({
                url: '{{url('/admin/checkbulkuserexist')}}',
                type: 'POST',
                data: data,
                success: function(resp) {
                    if (resp.code === 200) {
                        var bulkErr = resp.message;
                        var error = 'success';
                    } else {
                        var bulkErr = resp.message;
                        var error = 'error';
                    }

                    Command: toastr[error](bulkErr);
                },
                error: function(err) {
                    // handle error
                }
            });
        }

        function OtpValidation(OTP) {
            // const OTP = e.target.value;
            var OTPl = OTP.replace(/ /g, "");
            if (OTPl == "") {
                this.otpErr = "OTP should not be blank.";
            } else {
                this.otpErr = "";
            }
        }


        function seperateUsers(user){
            //let user=e.target.value;
            
            let userStr=user.replaceAll(/\s/g,'').replaceAll(',','');
            
            if (userStr.length % 11 == 0 && userStr.length >= 11) {
                //alert("called");
                username=userStr.replace(/(,|)/g,'').replace(/(.)(?=(.{11})+$)/g,"$1,").replace(',.', '.');
                $('#username').val(username);
            }
        }

        function hashvalidation() {
            var min_hash = 50;
            var max_hash = 10000000;
            var activeDiv = true;
            var topup = {
                hash_unit: $('#hash_unit').val() // get the value from an input field with the ID "hash_unit"
            };

            if (topup.hash_unit < min_hash || topup.hash_unit > max_hash) {
                if (max_hash == 0) {
                    var usermsg = "Amount should be on range " + min_hash + " to above";
                } else {
                    var usermsg = "Amount should be on range " + min_hash + " to " + max_hash;
                }
                var isValid = false;
            } else {
                if (topup.hash_unit % 1 != 0) {
                    var usermsg = "Amount should be multiple of 1";
                    var isValid = false;
                } else {
                    var isValid = true;
                    var usermsg = "";
                }
            }

            // send the data to the server using AJAX
            // $.ajax({
            //   type: 'POST', // use POST method
            //   url: 'validate.php', // replace with the URL of your server-side validation script
            //   data: {
            //     'hash_unit': topup.hash_unit,
            //     'isValid': isValid,
            //     'usermsg': usermsg
            //   },
            //   success: function(response) {
            //     // handle the server response
            //     console.log(response);
            //   }
            // });
        }



        function addTopUp() {
            var isDisabledBtn = false;
            Swal.fire({
                title: "Are you sure ?",
                text: "You want to topup!",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes!",
                cancelButtonText: "Cancel",
            }).then((result) => {
                if (result.value) {
                    var data = {
                        otp: $('#otp').val(), // get the value from an input field with the ID "otp"
                        user_id_arr: $('#username').val(), // get the value from an input field with the ID "username"
                        hash_unit: $('#hash_unit').val(), // get the value from an input field with the ID "hash_unit"
                        franchise_user_id: $('#franchise_user_id').val(), // get the value from an input field with the ID "franchise_user_id"
                        masterfranchise_user_id: $('#masterfranchise_user_id').val(), // get the value from an input field with the ID "masterfranchise_user_id"
                        device: "web",
                        topupfrom: "admin panel",
                    };
                    $.ajax({
                        type: 'POST', // use POST method
                        url: '{{url('/admin/store/bulktopup')}}', // replace with the URL of your server-side script
                        data: data,
                        success: function(resp) {
                            // var resp = JSON.parse(response);
                            //alert(resp.message);
                            if (resp.code === 200) {
                                // toastr.success(resp.message);
                                Command: toastr['success'](resp.message);
                                window.location.href = '{{url("/admin/top-up/bulk-power-topup-report")}}';
                            } else {
                                //toastr.error(resp.message);
                                Command: toastr['error'](resp.message);
                            }
                            $('#addTopUp').trigger("reset"); // reset the form
                            isDisabledBtn = true;
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            toastr.error(textStatus + ': ' + errorThrown);

                            Command: toastr['error'](textStatus + ': ' + errorThrown);
                        }
                    });
                } else {
                    isDisabledBtn = true;
                }
            });
        }
    </script>

@endsection
