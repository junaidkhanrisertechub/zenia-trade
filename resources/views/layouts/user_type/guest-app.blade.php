<!doctype html>
<html dir="ltr" lang="en">


<head>
        
        <meta charset="utf-8" />
        <title>Zenia</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="robots" content="noindex,nofollow" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{asset('user-assets/images/favicon.ico')}}">

        <!-- Bootstrap Css -->
        <link href="{{asset('user-assets/css/bootstrap.min.css')}}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="{{asset('user-assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="{{asset('user-assets/css/app.min.css')}}" id="app-style" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.min.css">

    </head>

    <body class="bg-white">

        @if (!\Request::is('sign-up-user') && !\Request::is('admin/login'))
            @include('layouts.navbars.guest.nav')
        @endif
        @yield('content')

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{asset('user-assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('user-assets/libs/metismenujs/metismenujs.min.js')}}"></script>
        <script src="{{asset('user-assets/libs/simplebar/simplebar.min.js')}}"></script>
        <script src="{{asset('user-assets/libs/feather-icons/feather.min.js')}}"></script>

        <script src="{{asset('user-assets/js/pages/pass-addon.init.js')}}"></script>
        <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

        
        <script>
            // For Password Validations
            jQuery.validator.addMethod("strongPassword", function (value) {
                if (/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]*$/.test(value)) {
                    return true;
                } else if (value.length == 0) {
                    return true;
                }
            }, "The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&)");
            // Email Validations
            jQuery.validator.addMethod("validate_email", function(value, element) {
                var urlregex = /^([a-zA-Z0-9_+\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if(urlregex.test(value)){
                    var str = value;
                    var foundString = str.substring(str.indexOf('@') + 1);
                    var count = (foundString.match(/\./g) || []).length;
                    if(parseInt(count) == 1){
                        return true;
                    }else{
                        return false;
                    }
                    return true;
                }else{
                    return false;
                }
            }, "Please enter a valid email address.");
        
            var base_url = '{{url('/')}}';
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
            var showpassword = 0;
            $("#password-addon").click(function() {
                var eye = $("#password-addon i");
        
                if (showpassword == 0) {
                    $("#password").attr("type", "text");
                    eye.removeClass("fa-solid fa-face-smile-beam").addClass("fa-solid fa-face-laugh-beam");
                    showpassword = 1;
                } else if (showpassword == 1) {
                    $("#password").attr("type", "password");
                    eye.removeClass("fa-solid fa-face-laugh-beam").addClass("fa-solid fa-face-smile-beam");
                    showpassword = 0;
                }
            });
            $("#password-addon1").click(function() {
                var eye = $("#password-addon1 i");
        
                if (showpassword == 0) {
                    $("#confirmpassword").attr("type", "text");
                    eye.removeClass("fa-solid fa-face-smile-beam").addClass("fa-solid fa-face-laugh-beam");
                    showpassword = 1;
                } else if (showpassword == 1) {
                    $("#confirmpassword").attr("type", "password");
                    eye.removeClass("fa-solid fa-face-laugh-beam").addClass("fa-solid fa-face-smile-beam");
                    showpassword = 0;
                }
            });
            function onClick(e) {
                var csrf_token = $('meta[name="csrf-token"]').attr('content');
                e.preventDefault();
                grecaptcha.ready(function() {
                    if(grecaptcha.getResponse() == ''){
                        $("#catcha_error").css('display','block')
                        return false;
                    }else{
                        $("#catcha_error").css('display','none')
                    }
                    grecaptcha.execute("6LcWpJQlAAAAACeq-KmbHb3l8lngJxneYyF4AM2F", {action: 'submit'}).then(function(token) {
                        $.post("{{url('/verify-recaptcha')}}", {token: token, action: 'submit', _token: '{{ csrf_token() }}',})
                            .done(function(data) {
                                document.getElementById('my-form').submit();
                            })
                            .fail(function(error) {
        
                                console.log('reCAPTCHA verification failed.');
                            });
        
                    });
                });
                return false;
            }
        </script>

    </body>

</html>