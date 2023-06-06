@extends('layouts.user_type.auth-app')

@section('content')
    @php
        $topupfundbalance = $getBalance['fund_wallet'] - $getBalance['fund_wallet_withdraw'];
        $topuproibalance = $getBalance['roi_income'] - $getBalance['roi_wallet_withdraw'];
        $topuphsccbalance = $getBalance['hscc_bonus_wallet'] - $getBalance['hscc_bonus_wallet_withdraw'];
        //$topupworkingbalance = ($getBalance['direct_income'] - $getBalance['direct_income_withdraw']) + ($getBalance['binary_income'] - $getBalance['binary_income_withdraw']);
        $topupworkingbalance = $getBalance['working_wallet'] - $getBalance['working_wallet_withdraw'];
    @endphp
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
               
                <div class="row mt-3">
                    <div class="col-md-12">
                      <div class="card">
                        <div class="card-header justify-content-between d-flex align-items-center">
                          <h4 class="card-title">Place Withdrawal</h4>
                      </div>
                      <div class="card-body">
                        <ul class="nav nav-pills mb-3  pl-4" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                                    aria-selected="true" onclick="tab('working')">Working</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-profile" type="button" role="tab"
                                    aria-controls="pills-profile" aria-selected="false" onclick="tab('roi')">ROI</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-bonus-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-bonus" type="button" role="tab" aria-controls="pills-bonus"
                                    aria-selected="false" onclick="tab('bonus')">ZENIA BONUS</button>
                            </li>
                        </ul>
                      </div>
                      </div>
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                aria-labelledby="pills-home-tab" tabindex="0">
                                <div class="card">
                                    <div class="card-header pb-0 mt-2 text-center">
                                        <h6 class="font-weight-bolder text-denger text-gradient">Place Your Withdrawal
                                            Request
                                            Here</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="bg-primary border-radius-lg h-100 p-3">
                                                    <div class="row">
                                                        <div class="col-md-12 mt-3">
                                                            <div class="numbers text-center">
                                                                <p class="text-sm text-light mb-0 text-capitalize font-weight-bold">
                                                                    Working Wallet
                                                                </p>
                                                                <h5 class="font-weight-bolder mb-0 text-light">
                                                                    ${{ $topupworkingbalance }}
                                                                </h5>
                                                                <img src="{{ asset('images/ROIWithdrawal.png') }}"
                                                                    class="img-fluid mt-3">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                              <div class="card p-4 h-100">
                                                <form class="row g-3" id="working_form">
                                                    <div class="col-md-12">
                                                        <label>Amount In (USD)</label>
                                                        <input type="number" step="any" id="working_amount"
                                                            name="working_amount" class="form-control" value=""
                                                            formcontrolname="working_amount" placeholder="Enter Amount"
                                                            onKeyPress="if(this.value.length==10) return false;"
                                                            oninput="working_amount_preview.value=value" />
                                                    </div>
                                                    <div class="col-md-12 drack-arrow">
                                                        <label>Preview Amount</label>
                                                        <input type="number" id="working_amount_preview"
                                                            class="form-control" value=""
                                                            formcontrolname="working_amountt" placeholder="Preview Amount"
                                                            disabled style="background-color: #ffffff;"
                                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57" />
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label>Deduction</label>
                                                        <input type="text" class="form-control" id="deduction"
                                                            value="10%" disabled style="background-color: #ffffff;">
                                                    </div>
                                                    <div class="col-md-12 PaymentModeSelect">
                                                        <div class="mb-3">
                                                            <label>Select Payment Mode</label>
                                                        </div>
                                                        @foreach ($getAllCurrency as $cur)
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="wcurrency_type" id="wcurrency_type"
                                                                    value="{{ $cur->currency_code }}"
                                                                    onclick="change('{{ $cur->currency_code }}')">
                                                                <label class="form-check-label" for="wcurrency_type">
                                                                    {{ $cur->currency_name }} </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <input type="hidden" name="_token" id="token"
                                                        value="{{ csrf_token() }}">
                                                    <div class="col-md-12 mt-4 d-flex justify-content-center">
                                                        <button class="btn btn-primary w-50 text-uppercase p-2"
                                                            id="working_otp_btn" type="button"
                                                            onclick="sendOTPForWIthdraw()">Withdraw</button>
                                                    </div>
                                                </form>
                                              </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                aria-labelledby="pills-profile-tab" tabindex="0">
                                <div class="card">
                                    <div class="card-header pb-0 mt-2 text-center">
                                        <h6 class="font-weight-bolder text-denger text-gradient">Place Your Withdrawal
                                            Request
                                            Here</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="bg-primary border-radius-lg h-100 p-3">
                                                    <div class="row">
                                                        <div class="col-md-12 mt-3">
                                                            <div class="numbers text-center">
                                                                <p class="text-sm mb-0 text-capitalize text-light font-weight-bold">
                                                                    ROI Wallet
                                                                </p>
                                                                <h5 class="font-weight-bolder mb-0 text-light">
                                                                    ${{ $topuproibalance }}
                                                                </h5>
                                                                <img src="{{ asset('images/ROIWithdrawal.png') }}"
                                                                    class="img-fluid mt-3">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                              <div class="card p-4 h-100">
                                                <form class="row g-3" id="roi_form">
                                                    <div class="col-md-12">
                                                        <label>Amount In (USD)</label>

                                                        <input type="number" min="10" step="any"
                                                            id="roi_amount" name="roi_amount" value=""
                                                            onKeyPress="if(this.value.length==10) return false;"
                                                            oninput="roi_amount_preview.value=value" class="form-control"
                                                            formcontrolname="set-roi-wallet" placeholder="Enter Amount" />
                                                    </div>

                                                    <div class="col-md-12 drack-arrow">
                                                        <label>Preview Amount</label>
                                                        <input type="number" id="roi_amount_preview"
                                                            class="form-control" value=""
                                                            formcontrolname="set-roi-wallet" placeholder="Preview Amount"
                                                            disabled style="background-color: #ffffff;"
                                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57" />
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label>Deduction</label>
                                                        <input type="text" class="form-control" value="10%"
                                                            disabled style="background-color: #ffffff;">
                                                    </div>
                                                    <div class="col-md-12 PaymentModeSelect">
                                                        <div class="mb-3">
                                                            <label>Select Payment Mode</label>
                                                        </div>
                                                        @foreach ($getAllCurrency as $cur)
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="rcurrency_type" id="rcurrency_type"
                                                                    value="{{ $cur->currency_code }}"
                                                                    onclick="changer('{{ $cur->currency_code }}')">
                                                                <label class="form-check-label" for="inlineRadio1">
                                                                    {{ $cur->currency_name }} </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="col-md-12 mt-4 d-flex justify-content-center">
                                                        <button class="btn btn-primary w-50 text-uppercase p-2"
                                                            type="button" id="roi_otp_btn"
                                                            onclick="sendOTPForWIthdraw()">Withdraw</button>
                                                    </div>
                                                </form>
                                              </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-bonus" role="tabpanel"
                                aria-labelledby="pills-bonus-tab" tabindex="0">
                                <div class="card">
                                    <div class="card-header pb-0 mt-2 text-center">
                                        <h6 class="font-weight-bolder text-denger text-gradient">Place Your Withdrawal
                                            Request
                                            Here</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="bg-primary border-radius-lg h-100 p-3">
                                                    <div class="row">
                                                        <div class="col-md-12 mt-3">
                                                            <div class="numbers text-center">
                                                                <p class="text-sm mb-0 text-capitalize text-light font-weight-bold">
                                                                    ZENIA BONUS Wallet
                                                                </p>
                                                                <h5 class="font-weight-bolder mb-0 text-light">
                                                                    ${{ $topuphsccbalance }}
                                                                </h5>
                                                                <img src="{{ asset('images/ROIWithdrawal.png') }}"
                                                                    class="img-fluid mt-3">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                              <div class="card p-4 h-100">
                                                <form class="row g-3" id="bonus_form">
                                                    <div class="col-md-12">
                                                        <label>Amount In (USD)</label>
                                                        <input type="number" min="10" step="any"
                                                            id="bonus_amount" name="bonus_amount" value=""
                                                            onKeyPress="if(this.value.length==10) return false;"
                                                            oninput="bonus_amount_preview.value=value"
                                                            class="form-control" formcontrolname="set-bonus-wallet"
                                                            placeholder="Enter Amount" />
                                                    </div>

                                                    <div class="col-md-12 drack-arrow">
                                                        <label>Preview Amount</label>
                                                        <input type="number" value="" id="bonus_amount"
                                                            class="form-control" formcontrolname="set-bonus-wallet"
                                                            placeholder="Preview Amount" disabled
                                                            style="background-color: #ffffff;"
                                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57" />

                                                    </div>
                                                    <div class="col-md-12">
                                                        <label>Deduction</label>
                                                        <input type="text" class="form-control" value="10%"
                                                            disabled style="background-color: #ffffff;">
                                                    </div>
                                                    <div class="col-md-12 PaymentModeSelect">
                                                        <div class="mb-3">
                                                            <label>Select Payment Mode</label>
                                                        </div>
                                                        @foreach ($getAllCurrency as $cur)
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="bcurrency_type" id="bcurrency_type"
                                                                    value="{{ $cur->currency_code }}"
                                                                    onclick="changeb('{{ $cur->currency_code }}')">
                                                                <label class="form-check-label" for="inlineRadio1">
                                                                    {{ $cur->currency_name }} </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="col-md-12 mt-4 d-flex justify-content-center">
                                                        <button class="btn btn-primary w-50 text-uppercase p-2"
                                                            type="button" id="bonus_otp_btn"
                                                            onclick="sendOTPForWIthdraw()">Withdraw</button>
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
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" toggle="modal" aria-labelledby="exampleModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-md-4">
                            <img src="{{ asset('images/otp.png') }}" class="img-fluid">
                        </div>
                        <div class="col-md-8">
                            <div class="row">

                                @if (Auth::user()->google2fa_status == 'disable')
                                    <div class="col-md-12">
                                        <input type="text" name="otp" class="form-control"
                                            placeholder="Enter OTP" id="otp"
                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57"
                                            maxlength="6" />
                                        <div class="col-md-12 text-center mt-3" id="resend_otp"
                                            v-if="google2fa_status=='disable'">
                                            <button class="btn bg-gradient-primary" onclick="sendOTPForWIthdraw()"
                                                type="button">Resend</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-12 mt-3" v-else>
                                        <input type="text" name="2fa-otp" id="otp_2fa" class="form-control w1000"
                                            placeholder="Enter G2FA OTP" v-model="otp_2fa" maxlength="6"
                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                    </div>
                                @endif

                                <div class="col-md-12 text-center mt-3">
                                    <button onclick="withdrawsucess()" type="button" id="working"
                                        class="btn btn-primary">Submit</button>
                                    {{-- <button  onclick="withdrawsucessroi()" type="button" id="roi" class="btn btn-primary">Submit</button> --}}
                                    {{-- <button  onclick="withdrawsucessBonus()" type="button" id="bonus" class="btn btn-primary">Submit</button> --}}
                                </div>
                            </div>
                        </div>
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
    $(document).ready(function() {
        tab();
    });
    var nav_type;

    function tab(val) {
        if (val == undefined) {
            nav_type = 'working';
        } else {
            nav_type = val;
        }

        if (nav_type == 'working') {
            // $('#working').show();
            // $('#roi').hide();
            // $('#bonus').hide();
            $('#wcurrency_type').val('');
            $("#working_form").trigger("reset");
        } else if (nav_type == 'roi') {
            // $('#working').hide();
            // $('#roi').show();
            // $('#bonus').hide();
            $("#roi_form").trigger("reset");
            $('#rcurrency_type').val('');
        } else if (nav_type == 'bonus') {
            // $('#working').hide();
            // $('#roi').hide();
            // $('#bonus').show();
            $("#bonus_form").trigger("reset");
            $('#bcurrency_type').val('');
        }
    }

    function change(val) {
        $('#wcurrency_type').val(val);
    }

    function changer(val) {
        $('#rcurrency_type').val(val);
    }

    function changeb(val) {
        $('#bcurrency_type').val(val);
    }

    function sendOTPForWIthdraw() {
        var working_wallet;
        var Currency_type;

        if (nav_type == 'working') {
            working_wallet = $("#working_amount").val();
            Currency_type = $("#wcurrency_type").val();
        }
        if (nav_type == 'roi') {
            working_wallet = $("#roi_amount").val();
            Currency_type = $("#rcurrency_type").val();
        }
        if (nav_type == 'bonus') {
            working_wallet = $("#bonus_amount").val();
            Currency_type = $("#bcurrency_type").val();
        }

        if (working_wallet != '' && Currency_type != '') {
            $('#working_otp_btn').prop('disabled', true);
            $('#roi_otp_btn').prop('disabled', true);
            $('#bonus_otp_btn').prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: "{{ url('/sendOtp-For-Withdraw') }}",
                data: {
                    type: 'Withdrawal',
                    Currency_type: Currency_type,
                    working_wallet: working_wallet,
                    "_token": $('#token').val(),
                },
                success: function(response) {
                    if (response.code == 200) {
                        // OTP sent successfully
                        window.$("#exampleModal").modal("show");
                        toastr['success'](response.message);
                        $('#working_otp_btn').prop('disabled', false);
                        $('#roi_otp_btn').prop('disabled', false);
                        $('#bonus_otp_btn').prop('disabled', false);
                    } else {
                        $('#working_otp_btn').prop('disabled', false);
                        $('#roi_otp_btn').prop('disabled', false);
                        $('#bonus_otp_btn').prop('disabled', false);
                        toastr['error'](response.message)
                    }
                }
            });
        }
    }

    function withdrawsucess() {
        var working_wallet;
        var Currency_type;
        var url;
        if (nav_type == 'working') {
            working_wallet = $("#working_amount").val();
            Currency_type = $("#wcurrency_type").val();
            url = "{{ url('/withdrawal-working') }}";
        }
        if (nav_type == 'roi') {
            working_wallet = $("#roi_amount").val();
            Currency_type = $("#rcurrency_type").val();
            url = "{{ url('/withdraw-income-roi') }}";
        }
        if (nav_type == 'bonus') {
            working_wallet = $("#bonus_amount").val();
            Currency_type = $("#bcurrency_type").val();
            url = "{{ url('/withdraw-income-bonus') }}";
        }

        var otp = $("#otp").val();
        var otp_2fa = $("#otp_2fa").val();

        if (working_wallet != '' && Currency_type != '' && otp != '' && url != '') {
            $('#working').prop('disabled', true);
            // $('#roi').prop('disabled', true);
            // $('#bonus').prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    Currency_type: Currency_type,
                    otp: otp,
                    otp_2fa: otp_2fa,
                    working_wallet: working_wallet,
                    "_token": $('#token').val(),
                },
                success: function(response) {
                    if (response.code == 200) {
                        window.$("#exampleModal").modal("hide");
                        window.location.replace("{{ url('/withdrawal-report') }}");
                        toastr['success'](response.message);
                        $('#working').prop('disabled', false);
                        // $('#roi').prop('disabled', false);
                        // $('#bonus').prop('disabled', false);
                    } else {
                        $('#working').prop('disabled', false);
                        // $('#roi').prop('disabled', false);
                        // $('#bonus').prop('disabled', false);
                        toastr['error'](response.message)
                    }
                }
            });
        }
    }
</script>
