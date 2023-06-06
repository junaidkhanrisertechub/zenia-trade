@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="admin-card-button" v-if="otpstatus == 1">
            <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp(5)">Send Otp </button>
            <p>Note :- Otp Valid 2 Hours</p>
        </div>
        <div class="col-6 mx-auto">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Remove Fund Request</h4>
                </div>

                <div class="admin-card-body">
                    <form class="row g-3" id="fund_req">
                        <input type="hidden" name="id" id="user_id" value=""/>
                        <div class="form-group col-12">

                            <label>User Id</label>
                            <input type="text" name="username" class="admin-form-control" id="username" placeholder="User Id"
                                   v-model="username" onkeyup="checkUserExistedWithBalance(this.value)" />
                            <div class="clearfix"></div>

                        </div>
                        <div class="form-group col-12">
                            <label for="balance">Balance</label>
                            <input type="text" class="admin-form-control" id="balance" name="balance" v-model="balance"
                                   placeholder="Balance" readonly="" />
                        </div>
                        <div class="form-group col-12">
                            <label for="username">Name</label>
                            <input type="text" class="admin-form-control" id="fullname" name="username" v-model="fullname"
                                   placeholder="Name" readonly="" />
                        </div>
                        <div class="form-group col-12">
                            <label for="email">Mail Id</label>
                            <input type="text" class="admin-form-control" id="email" name="email" v-model="email"
                                   placeholder="Mail Id" readonly="" />
                        </div>
                        <div class="form-group col-12">
                            <label for="remark">Remark</label>
                            <input type="text" class="admin-form-control" id="remark" name="remark" v-model="fund.remark"
                                   placeholder="Remark" />
                        </div>
                        <div class="form-group col-12">
                            <label class="control-label">Enter Amount</label>
                            <!-- <input type="text" class="admin-form-control" id="amount" name="amount" v-model="fund.amount"
                                onkeypress='return event.charCode >= 48 && event.charCode <= 57' placeholder="Enter amount"
                                onblur="AmountValidation(this.value)" /> -->
                            <input type="text" class="admin-form-control" id="amount" name="amount"
                                   onkeypress='return event.charCode >= 48 && event.charCode <= 57' placeholder="Enter amount"
                                   onblur="AmountValidation(this.value)" />
                            <div v-if="nameErr !== ''" class="tooltip1" class="text-danger" id="amount-message">

                            </div>
                        </div>
                        <div class="form-group col-12" v-if="otpstatus == 1">
                            <label>OTP</label>
                            <input type="text" class="admin-form-control" id="otp" placeholder="Enter Otp" name="otp"
                                   v-model="otp" data-vv-as="OTP " onblur="OtpValidation(this.value)" />
                            <div v-if="otpErr !== ''" class="tooltip1">
                            <span class="text-danger">
                            </span>
                            </div>
                        </div>
                        <div class="form-group col-12 text-center">
                            <button type="button" class="btn btn-rounded btn-outline-primary" onclick="fund_req()"
                                    :disabled="!isComplete || !isDisabledBtn || nameErr !== '' || otpErr !== '' ">
                                Submit
                            </button>
                        </div>


                        <input type="hidden" name="fund_status" value="0">
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

                        $('#balance').val(resp.data.balance);
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
                url: '{{route('remove_fund_request')}}',
                method: 'POST',
                data: $('#fund_req').serialize(),

                success: (response) => {
                    if (response.code == 200) {
                        // this.$toast.success(response.message);
                        Command: toastr['success'](response.message);

                        window.location.href ="{{url('/admin/addfundreport')}}";
                    } else {
                        // this.$toast.error(response.message);
                        Command: toastr['error'](response.message);
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
