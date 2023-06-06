@extends('layouts.user_type.admin-app')
@section('content')

    <div class="row">
        <div class="page-header-title">
            <h4 class="page-title">Add Power Upline</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-sm-6">
            <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp(6)">Send Otp </button>
            <p>Note :- Otp Valid 2 Hours</p>
        </div>
    </div>



    <div class="row">
        <div class="col-6 mx-auto">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Add Power Upline</h4>
                </div>

                <div class="admin-card-body">
                    <form id="change-user-password" >
                        <!-- <div class="col-md-5 col-md-offset-3"> -->
                        <input type="hidden" name="user_id" id="user_id" value="">

                        <div class="form-group">
                            <label>User Id</label>
                            <input type="text" class="admin-form-control" id="username" placeholder="User Id"
                                   name="username"  onkeyup="checkUserExisted(this.value)" data-vv-as="User Id" value="">

                            <!-- <p :class="{'text-success': isAvialable == 'Available','text-danger': isAvialable == 'Not Available'}"
                                v-if="isAvialable!='' && username!=''">isAvialable</p>
                            <span
                                :class="{'text-success': isAvialable == 'Available','text-danger': isAvialable == 'Not Available'}"
                                v-if="isAvialable == 'Available'"> user_id fullname</span> -->

                        </div>

                        <div class="form-group">
                            <label for="balance">R_BV Amount</label>
                            <input type="text" class="form-control" id="r_bv" name="r_bv"  placeholder="r_bv" readonly="">
                        </div>

                        <div class="form-group">
                            <label for="balance">L_BV Amount</label>
                            <input type="text" class="form-control" id="l_bv" name="balance" placeholder="l_bv" readonly="">
                        </div>

                        <div class="form-group">
                            <label>Position</label>
                            <select name="position" class="admin-form-control">
                                <option selected value="">Select</option>
                                <option value="2">Right</option>
                                <option value="1">Left</option>
                                <!--  <option v-for = "opt in optArr" value="opt."></option> -->
                                <!--  <option  value="2">Right</option>
                                          <option  selected value="1">Left</option> -->

                            </select>

                        </div>

                        <input type="hidden" name="upline_id" id="upline_id" value="">
                        <div class="form-group">
                            <label>Upline Id</label>
                            <input type="text" class="admin-form-control" id="uplineusername" placeholder="Upline User Id"
                                   name="uplineusername" onkeyup="checkUplineUserExisted(this.value)">
                            <!-- <div class="tooltip2" v-show="uplinePresent == 'Not Available'">
                                <div class="tooltip-inner">
                                    <span>uplinePresent</span>
                                </div>
                            </div> -->

                        </div>


                        <div class="form-group">
                            <label for="power bv">Enter Business</label>
                            <input type="text" class="admin-form-control" id="power_bv" name="power_bv" v-model="power_bv"
                                   placeholder="Enter BV">

                        </div>

                        <div class="form-group">
                            <label for="balance">Enter Remark</label>
                            <input type="text" class="admin-form-control" id="remark" name="remark" value="remark"
                                   placeholder="remark">
                        </div>

                        <div class="form-group">
                            <label>OTP</label>
                            <input type="text" class="admin-form-control" id="otp" placeholder="Enter Otp" name="otp"
                                   v-model="otp" data-vv-as="OTP ">
                        </div>

                        <div class="col-md-offset-5">
                            <!--
                                                    <button :disabled="!isCompleteForm || disablebtn == true" type="button" class="btn btn-primary"
                                                        name="signup1" value="Sign up" @click="addPower"  id="signup1">Submit</button> -->
                            <button disabled type="button" class="btn btn-primary"
                                    name="signup1" value="Sign up" onclick="addPower()"  id="signup1">Submit</button>
                        </div>
                        <!-- </div> -->

                        <input type="hidden" id="id" name="id" value="" />
                        <input type="hidden" id="fullname" name="fullname" value="" />
                        <input type="hidden"  name="type" value="2" />
                        <!-- <input type="hidden" id="l_bv" name="l_bv" value="" />
                        <input type="hidden" id="r_bv" name="r_bv" value="" />       -->
                    </form>
                </div>
            </div>
        </div>
    </div>



    <script>

        //var base_url = '{{url('/')}}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        $(document).ready(function() {

            var data = {
                username:'',
                user_id:'',
                l_bv:'',
                r_bv:'',
                position:'',
                power_bv:'',
                isAvialable:'',
                user:'',
                id:'',
                disablebtn: false,
                pos_avl:'',
                optArr:{},
                optall: 0,
                type:'2',
                remark:'',
                upline_id:'',
                uplineusername:'',
                uplinePresent:'',
                uplinefullname:'',
                otp:'',
            }
        });


        function isCompleteForm() {
            var power_bv = $('#power_bv').val();
            var type = $('#type').val();
            var username = $('#username').val();
            var uplineusername = $('#uplineusername').val();

            return power_bv && type && username && uplineusername;
        }


        function checkUserExisted() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });
            $.ajax({
                url: '{{url('/admin/checkuserexist')}}',
                method: 'POST',
                data: {
                    user_id: $('#username').val()
                },
                success: function(resp) {
                    if (resp.code === 200) {

                        $('#id').val(resp.data.id);
                        $('#user_id').val(resp.data.user_id);
                        $('#fullname').val(resp.data.fullname);
                        $('#l_bv').val(resp.data.l_bv);
                        $('#r_bv').val(resp.data.r_bv);
                        $('#isAvialable').val('Available');

                        if (resp.data.power_lbv > 0) {
                            $('#optArr').val({'value': 1, 'pos': 'Left'});
                        } else if (resp.data.power_rbv > 0) {
                            $('#optArr').val({'value': 2, 'pos': 'Right'});
                            $('#position').val(2);
                        } else {
                            $('#optall').val(0);
                        }
                        var isAvialable = 'Available';
                        var type_name = "success";
                    } else {
                        $('#user_id').val('');
                        $('#isAvialable').val('Not Available');
                        var isAvialable = 'Not Available'
                        var type_name = "error";
                    }
                    submitButtonVisiblity(isAvialable,$('#user_id').val());
                    Command: toastr[type_name](isAvialable);

                },
                error: function(err) {
                    alert(err);
                    Command: toastr["error"](err);
                }
            });
        }

        //check user
        function  submitButtonVisiblity(isAvialable, user_id){
            if(isAvialable =='Available'){
                $('#signup1').removeAttr('disabled');
            }
            else{
                $('#signup1').prop('disabled','true');
            }

        }

        function checkUplineUserExisted(uplineusername) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });
            $.ajax({
                type: "POST",
                url: "{{url('admin/checkuplineuserexist')}}",
                data: {
                    user_id: $('#user_id').val(),

                    upline_user_id: uplineusername,
                },
                success: function(response) {
                    if (response.code === 200) {
                        $('#upline_id').val(response.data.id);
                        $('#uplineusername').val(response.data.user_id);
                        $('#uplinefullname').val(response.data.fullname);
                        $('#uplinePresent').val('Available');


                        Command: toastr['success'](response.message);
                    } else {
                        $('#upline_id').val('');
                        $('#uplinePresent').val('Not Available');;
                        Command: toastr['error'](response.message);
                    }
                },
                error: function(err) {
                    console.log(err);
                }
            });
        }



        function addPower() {
            if(this.type == '') {
                this.disablebtn = 0;
                alert("Please Select Type");
                return false;
            }
            this.disablebtn = true;
            var that = this; // save 'this' context
            Swal.fire({
                title: 'Are you sure ?',
                text: "You want to Change Bussiness!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (result.value) {
                    $.ajax({
                        url: "{{url('/admin/manage-power/add-bussiness-upline')}}",
                        type: 'POST',
                        data: $('#change-user-password').serialize(),
                        success: function(response) {
                            if(response.code == 200) {
                                alert(response.message);
                                that.otp = '';
                                that.disablebtn = false;
                                window.location.href = "upline-power-report";

                            } else {
                                alert(response.message);
                                that.otp = '';
                                that.pinvalid = false;
                                that.disablebtn = false;
                                that.message = response.message;
                            }
                        },
                        error: function(error) {
                            alert('An error occurred while processing the request. Please try again later.');
                            that.otp = '';
                            that.disablebtn = false;
                        }
                    });
                } else {
                    that.otp = '';
                    return false;
                }
            });
        }

    </script>


@endsection
