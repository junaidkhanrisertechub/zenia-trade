@extends('layouts.user_type.admin-app')
@section('content')

    <div class="row">
        <div class="admin-card-button" id="otp-button">
            <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp(4)">Send Otp</button>
            <p>Note :- Otp Valid 2 Hours</p>
        </div>
        <div class="col-6 mx-auto">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Add Fund</h4>
                </div>
                <div class="admin-card-body">
                    <form class="row g-3" id="fund_req"  >


                        <input type="hidden" name="fund_status"  value="0"/>
                        <input type="hidden" name="id" id="user_id"  value=""/>
                        <div class="form-group col-12">
                            <label>User Id</label>
                            <input type="text" name="user_id" class="admin-form-control" id="username" placeholder="User Id"  onkeyup="checkUserExistedWithBalance(this.value);"/>
                            <div class="clearfix"></div>
                            <p id="username-message"></p>
                            <span id="username-available"></span>
                        </div>
                        <div class="form-group col-12">
                            <label for="balance">Balance</label>
                            <input type="text" class="admin-form-control" id="balance" name="balance" placeholder="Balance"  value="" readonly="" />
                        </div>
                        <div class="form-group col-12">
                            <label for="username">Name</label>
                            <input type="text" class="admin-form-control" id="fullname" name="username" placeholder="Name" value="" readonly="" />
                        </div>
                        <div class="form-group col-12">
                            <label for="email">Mail Id</label>
                            <input type="text" class="admin-form-control" id="email" name="email" placeholder="Mail Id" value=""readonly="" />
                        </div>
                        <div class="form-group col-12">
                            <label for="remark">Remark</label>
                            <input type="text" class="admin-form-control" id="remark" name="remark" placeholder="Remark" />
                        </div>
                        <div class="form-group col-12">
                            <label class="control-label">Enter Amount</label>
                            <input type="text" class="admin-form-control" id="amount" name="amount" placeholder="Enter amount"  onblur="AmountValidation(this.value)"/>
                            <div id="amount-message"></div>
                        </div>


                        <div class="form-group col-12"  v-if="otpstatus == 1">
                            <label>OTP</label>
                            <input
                                type="text"
                                class="admin-form-control"
                                id="otp"
                                placeholder="Enter Otp"
                                name="otp"
                                v-model="otp"
                                data-vv-as="OTP "
                                onblur="OtpValidation(this.value)"
                            />
                            <div v-if="otpErr !== ''" class="tooltip1">
                    <span class="text-danger"> </span
                    >
                            </div>
                        </div>
                        <div class="form-group col-12 text-center">
                            <button type="button" class="btn btn-rounded btn-outline-primary" id="signup1" disabled  onclick="fund_req()" >Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        var base_url = '{{url('/')}}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');



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

        function checkUserExistedWithBalance(username) {

            var data = { user_id: username };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });
            $.ajax({
                url: '{{url('/admin/checkuserexist')}}',
                type: 'POST',
                data: data,
                success: function(resp) {
                    if (resp.code === 200) {
                        id = resp.data.id;
                        user_id = resp.data.user_id;
                        fullname = resp.data.fullname;
                        l_bv = resp.data.l_bv;
                        r_bv = resp.data.r_bv;
                        l_business = resp.data.l_business;
                        r_business = resp.data.r_business;
                        isAvialable = 'Available';
                        console.log("wallet balance = "+resp.data);

                        $('#balance').val(resp.data.fund_wallet_balance);
                        $('#fullname').val(fullname);
                        $('#email').val(resp.data.email);
                        $('#user_id').val(id);

                        // if (resp.data.power_lbv > 0) {
                        //     optArr = { 'value': 1, 'pos': 'Left' };
                        // } else if (resp.data.power_rbv > 0) {
                        //     optArr = { 'value': 2, 'pos': 'Right' };
                        //     position = 2;
                        // } else {
                        //     optall = 0;
                        // }
                    } else {
                        user_id = '';
                        isAvialable = 'Not Available';
                    }
                    submitButtonVisiblity(isAvialable, user_id);
                },
                error: function() {
                    // show error message
                }
            });
        }

        function fund_req() {
            this.isDisabledBtn = false;
            // let formData = new FormData();

            // formData.append("user_id", this.fund.user_id);
            // formData.append("amount", this.fund.amount);
            // formData.append("remark", this.fund.remark);
            // formData.append("fund_status", "0");
            // formData.append("otp", this.otp);

            $.ajax({
                url: '{{route('add-fund')}}',
                method: 'POST',
                data: $('#fund_req').serialize(),

                success: (response) => {
                    if (response.code == 200) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            window.location.href ="{{url('admin/admin-add-fundreport')}}";
                        }, 5000);
                    } else {
                        toastr.error(response.message);
                    }
                    this.otp = "";
                    this.username="";
                    this.isAvialable="";
                    this.balance=0;
                    this.email="";
                    this.fullname="";

                    this.fund = {
                        user_id: "",
                        amount: "",
                    };
                    $("#fund_req").trigger("reset");
                    this.isDisabledBtn = true;
                },
                error: () => {
                    // Handle error
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
    </script>

@endsection
