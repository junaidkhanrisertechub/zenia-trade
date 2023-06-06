@extends('layouts.user_type.admin-app')
@section('content')
    @php

        $product_list_array = json_decode($product_list, true);

    @endphp

    <style>
        .hideType,
        .hideTargetVal {
            display: none;
        }
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
                    <h4 class="card-title">Add Top Up</h4>
                </div>

                <div class="admin-card-body">
                    <form class="row g-3" id="fund_req">
                        <input type="text" class="d-none" id="user_id" name="id" value="" />
                        <div class="form-group col-12">
                            <label>User Id</label>
                            <input type="text" name="user_id" class="admin-form-control" id="username"
                                placeholder="User Id" onblur="checkUserExistedtopup(this.value)" />
                            <div class="clearfix"></div>
                            <!-- <p id="isAvialable" class=""></p>
                                <span id="fullname" class=""></span> -->
                        </div>
                        <div class="form-group col-12">
                            <label>Select Package</label>
                            <select name="product_id" class="admin-form-control" v-model="topup.product_id" id="product_id"
                                onchange="changeSelect(this.value)">
                                <option value="null">Select Package</option>
                                @foreach ($product_list_array as $product_id)
                                    <option value="{{ $product_id['id'] }}">{{ $product_id['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="tooltip1">
                                <span class="text-danger" id="result"> </span>
                            </div>
                        </div>
                        <div class="form-group hideType classforinflu" v-show="topup.product_id == 7">
                            <label>Target Type</label>
                            <select name="role_type" id="role_type" class="admin-form-control"
                                onchange="changeRole(this.value)">
                                <option selected value="">Select Target Type</option>
                                <option value="1">Influencer</option>
                                <option value="2">Leader</option>
                            </select>
                            <div class="tooltip1">
                                <span class="text-danger" id="roleErr"> </span>
                            </div>
                        </div>
                        <div class="form-group  hideType classforinflu" v-show="topup.product_id == 7">
                            <label>Select Type</label>
                            <select name="target_type" id="target_type" class="admin-form-control"
                                onchange="changeTarget(this.value)">
                                <option selected value="">Select Type</option>
                                <option value="3" id="targetTypeVal3x">3X</option>
                                <option value="5" id="targetTypeVal5x">5X</option>
                                <option value="8">8X</option>
                                <option value="10">10X</option>
                                <option value="15">15X</option>
                                <option value="20">20X</option>
                            </select>
                            <div v-if="targetErr !== ''" class="tooltip1">
                                <span class="text-danger" id="targetErr"> </span>
                            </div>
                        </div>
                        <div class="form-group col-12">
                            <label class="control-label">Enter Amount</label>
                            <br />
                            <input placeholder="Enter Amount" type="text" class="admin-form-control" id="hash_unit"
                                name="hash_unit" min="1" step="1"
                                onkeypress="return event.charCode >= 48 && event.charCode <= 57" title="Numbers"
                                onkeyup="hashvalidation(this)" oninput="hashvalidationTarget(this)" />
                            <div class="clearfix"></div>
                            <p class="text-danger" id="usermsg"></p>
                        </div>
                        <div class="form-group col-12 hideType classforinflu" v-show="topup.product_id == 7">
                            <label class="control-label">Target Amount</label>
                            <br />
                            <input placeholder="Target Amount" type="text" class="admin-form-control"
                                name="target_hash_unit" id="target_amount" readonly />
                            <div class="clearfix"></div>
                            <p class="text-danger" id="targetErr"></p>
                        </div>
                        <div class="form-group col-12" v-if="otpstatus ==1">
                            <label>OTP</label>
                            <input type="text" class="admin-form-control" id="otp" placeholder="Enter Otp"
                                name="otp" onblur="OtpValidation(this)" />
                            <div class="tooltip1">
                                <span class="text-danger" id="otpError"> </span>
                            </div>
                        </div>
                        <div class="form-group col-12 text-center">
                            <button type="button" class="btn btn-rounded btn-outline-primary" onclick="addTopUp()">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <script>
        var base_url = '{{ url(' / ') }}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');



        function addTopUp() {
            var csrf_token = "{{ csrf_token() }}";
            var isDisabledBtn = false;
            Swal.fire({
                title: "Are you sure?",
                text: "You want to topup!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes!",
                cancelButtonText: "Cancel",
            }).then(function(result) {
                if (result.isConfirmed) {
                    var data = {
                        otp: $('#otp').val(),
                        id: $('#user_id').val(),
                        // user_id: $('#username').val(),
                        // pin: $('#pin').val(),
                        product_id: $('#product_id').val(),
                        hash_unit: $('#hash_unit').val(),
                        target_business: $('#target_amount').val(),
                        payment_type: $('#payment_type').val(),
                        franchise_user_id: $('#franchise-user-id').val(),
                        masterfranchise_user_id: $('#masterfranchise-user-id').val(),
                        device: "web",
                        topupfrom: "admin panel",
                    };
                    $.ajax({
                        type: "POST",
                        url: "{{ url('/admin/admin-topup') }}",
                        data: data,
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        success: function(resp) {
                            if (resp.code === 200) {
                                toastr.success(resp.message);
                                // window.location.href = "/topupreport";
                                window.location.href = '{{ url('admin/top-up/top-up-report') }}';
                            } else {
                                toastr.error(resp.message);
                            }
                            $('#addTopUp')[0].reset();
                            $('#username').val("");
                            $('#fullname').val("");
                            $('#isAvialable').val("");
                            $('#otp').val("");
                            $('#user_id').val("");
                            $('#pin').val("");
                            $('#product_id').val("");
                            $('#hash_unit').val("");
                            $('#target_amount').val("");
                            $('#payment_type').val("");
                            // $('#franchise-user-id').val("");
                            // $('#masterfranchise-user-id').val("");
                            isDisabledBtn = true;
                        },
                        error: function(err) {
                            toastr.error(err);
                        },
                    });
                } else {
                    isDisabledBtn = true;
                }
            });
        }


        function changeRole(value) {

            // let role = $(event).val();
            // let target_type = $('#target_type').val();
            // let roleErr = $('#roleErr');

            // if (!role) {
            //     roleErr.text('Please select type of target');
            // } else {
            //     if (target_type === '3' && role === '2') {
            //         $('#role_type').val('1');
            //     }

            //     roleErr.text('');
            // }
            if (value == 2) {
                $('#targetTypeVal3x').addClass('hideTargetVal');
            } else {
                $('#targetTypeVal3x').removeClass('hideTargetVal');
            }

        }

        function changeTarget(target_multiplier) {
            //var target_multiplier = $(event).val();
            var hash_unit = $("#hash_unit").val();
            if (target_multiplier == "") {
                $("#targetErr").html("Please select type of target");
            } else {
                $("#target_amount").val(Number(target_multiplier) * hash_unit);
            }
        }

        function changeSelect(value) {
            if (value == 7) {

                $('.classforinflu').removeClass('hideType');

            } else {
                $('.classforinflu').addClass('hideType');
            }

        }

        function changeTarget(value) {
            let target_multiplier = $(event).val();
            let hash_unit = $("#hash_unit").val();
            let targetErr = $('#targetErr');

            if (!target_multiplier) {
                $('#target-amount').val('');
                targetErr.text('Please select type');
            } else {
                $('#target_amount').val(Number(target_multiplier) * hash_unit);
                targetErr.text('');
            }


        }


        function hashvalidationTarget(event) {
            var hash_amt = $(event).val();
            var target_type = $("#target_type").val();
            if (target_type == '') {
                $("#targetErr").html("Please select type of target");
            } else {
                $("#target_amount").val(Number(hash_amt) * Number(target_type));
            }
        }


        function OtpValidation(inptotp) {
            let otp = $(inptotp).val();
            let otpErr = $('#otpError');
            let otpTrimmed = otp.replace(/ /g, '');

            if (!otpTrimmed) {
                otpErr.text('OTP should not be blank.');
            } else {
                otpErr.text('');
            }
        }


        function checkUserExistedtopup(username) {
                            // alert('user_id')

            if (username != '') {
                var data = {
                    user_id: username
                };

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    }
                });
                $.ajax({
                    type: "POST",
                    url: '{{ url('/admin/checkuserexist') }}', // replace with the actual URL for the API endpoint
                    data: data,
                    dataType: "json",
                    success: (resp) => {

                        console.log(resp);
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
                        $('#user_id').val(user_id);
                        submitButtonVisiblity(isAvialable, user_id);

                    },
                    error: (err) => {
                        //   toastr.error(err)
                    }
                });

            }
        }

        function submitButtonVisiblity(isAvialable, user_id) {
            if (isAvialable == 'Available') {
                $('#signup1').removeAttr('disabled');
            } else {
                $('#signup1').prop('disabled', 'true');
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


        function sendAdminOtp() {
            var csrf_token = "{{ csrf_token() }}";
            var user_id = 'TOPADMIN';
            $.ajax({
                type: "POST",
                url: "{{ url('admin/send-admin-otp') }}",
                data: 'user_id=' + user_id,
                // contentType: "application/json",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(resp) {
                    if (resp.code === 200) {
                        alert(resp.message);
                    } else {
                        alert(resp.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert(error);
                }
            });
        }

        function getAdminOtpstatus() {
            var csrf_token = "{{ csrf_token() }}";
            $.ajax({
                url: "{{ url('admin/get-otp-status') }}",
                type: 'POST',
                // dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(resp) {
                    if (resp.code === 200) {
                        $('#otpError').text(resp.data.topup_update_status);
                        // You can replace this with the toast message plugin that you are using
                        alert(resp.message);
                    } else {
                        // You can replace this with the toast message plugin that you are using
                        alert(resp.message);
                    }
                },
                error: function() {
                    // You can replace this with the toast message plugin that you are using
                    alert('There was an error while retrieving the OTP status.');
                }
            });
        }
    </script>
@endsection
