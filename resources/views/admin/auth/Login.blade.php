@extends('layouts.user_type.guest-app')

@section('content')
    <link href="{{asset('admin-assets/css/style.css')}}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <div class="authincation">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-4">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
                                    <div class="text-center mb-4">
                                        <a href="#">
                                            <img src="{{asset('admin-assets/images/logo.png')}}" class="img-fluid" width="">
                                        </a>
                                    </div>
                                    <h4 class="text-center mb-4">Sign in your account</h4>
                                    <form>
                                        <div class="form-group">
                                            <input autocomplete="off" class="admin-form-control" type="text" name="user_id" id="user_id" placeholder="User Id"
                                                   onkeypress="return (event.charCode > 64 && event.charCode < 91) || (event.charCode > 96 && event.charCode < 123) ||  (event.charCode >= 48 && event.charCode <= 57) "><br/>


                                        </div>
                                        <div class="form-group">
                                            <input type='password' class="admin-form-control"  name="password" id="password" placeholder="Password"  autocomplete="off">
                                            <br>

                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <input type="password" id="otp" name="otp" placeholder="Enter OTP"  class="admin-form-control" >

                                            </div>
                                            <br>

                                            <div class="text-center" >

                                                <button id="send_otp" class="btn btn-primary btn-block btn-lg waves-effect waves-light"  onclick="SendOtp()"  type="button">Send Otp</button>

                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="button" class="btn admin-btn-primary btn-block" onclick="login()">Sign Me In</button>
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

@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>

    var csrf_token = "{{ csrf_token() }}";

        function login() {
            var  user_id = $("#user_id").val();
            var  password = $("#password").val();
            var  otp = $("#otp").val();
            if(otp == ''){
                toastr.error('Please Enter OTP send to your email or click on send otp')
                return false;
            }
                var data = {
                    user_id: user_id,
                    password: password,
                    admin: "admin",
                    otp: otp
                };
                $.ajax({
                    url: '{{url('/admin/login-store')}}',
                    type: "POST",
                    data: data,
                    headers: {

                        'X-CSRF-TOKEN': csrf_token

                    },
                    success: function(resp) {
                        console.log(resp)
                        if (resp.code == 200) {
                                if (resp.data.mailotp == "TRUE") {
                                    sendotp = false;
                                    toastr.success(resp.message);
                                } else if (resp.data.google2faauth == "TRUE") {
                                    sendotp = false;
                                    google2fa = true;
                                    sendotp = "none";
                                } else {
                                    if (resp.data.admin_type == "Admin") {
                                        window.location.href = '{{url('/admin/dashboard')}}';
                                    } else {
                                        // alert(window.location.href)
                                        window.location.href = "{{url('/admin')}}/"+ resp.data.path;
                                    }
                                    toastr.success(resp.message);
                                }

                        } else {
                            toastr.error(resp.message);
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }

        // $("#login-form").submit(function(e) {
        //     e.preventDefault();
        //     login();
        // });
        //
        // getProSettings();
    // function getProSettings() {
    //     $.ajax({
    //         url: 'url_to_project_settings_endpoint',
    //         type: 'GET',
    //         success: function(resp) {
    //             if(resp.code === 200){
    //                 this.objProSettings = resp.data;
    //             }
    //         },
    //         error: function(err) {
    //             console.log(err);
    //         }
    //     });
    // }

    function SendOtp() {
        var  user_id = $("#user_id").val();
        if(user_id == ''){
             toastr.error('Please Enter User Id')
             return false;
         }
        $.ajax({
            url: '{{url('/admin/send-otp')}}',
            type: 'POST',
            headers: {

                'X-CSRF-TOKEN': csrf_token

            },
            data: {
                user_id: user_id
            },
            success: function(response) {
                if (response.code == 200) {
                    toastr.success(response.message)
                    $('#send_otp').hide();
                    setTimeout(function() { jQuery("#send_otp").show(); },30000);
                } else {
                    toastr.error(response.message)
                }
            }
        });
    }


</script>
