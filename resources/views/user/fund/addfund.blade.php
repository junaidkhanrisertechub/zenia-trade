@extends('layouts.user_type.auth-app')

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Add Fund</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                      <div class="card p-4 h-100">
                                        <form class="row g-3" id="user_add_fund_form">
                                            @csrf
                                            <div class="col-md-12">
                                                <label>Amount In (USD)</label>
                                                <input id="amount" name="amount" class="form-control"
                                                    placeholder="Enter Amount" type="text" min="10" step="1"
                                                    maxlength="9" value=""
                                                    onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">

                                                <span class="pl-4 fw-bold text-danger">
                                                </span>

                                            </div>
                                            <div class="col-md-12 drack-arrow">
                                                <label>Currency</label>

                                                <select id="inputState" class="form-select"
                                                    aria-label="Default select example" name="payment_mode">
                                                    <option selected="" value="">Select Currency</option>
                                                    @foreach ($currency as $cur)
                                                        <option value="{{ $cur->currency_code }}">
                                                            {{ $cur->currency_name }} ({{ $cur->currency_code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="product_id" value="1" />
                                                @if ($errors->has('payment_mode'))
                                                    <div class="tooltip22">
                                                        <span class="error-msg-size tooltip-inner text-danger">
                                                            {{ $errors->first('payment_mode') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-12 d-flex justify-content-center">
                                                <div class="btn btn-primary w-md text-uppercase p-2"
                                                    onclick="payment_call()">Make Payment</div>
                                            </div>
                                        </form>
                                      </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="bg-primary border-radius-lg h-100 p-3">
                                            <div class="row">
                                                <div class="col-md-12 mt-3">
                                                    <div class="numbers text-center">
                                                        <p class="text-sm mb-0 text-capitalize font-weight-bold">
                                                            Total Fund Amount
                                                        </p>
                                                        <h5 class="font-weight-bolder mb-0 text-light">
                                                            ${{ $total_fund_wallet_val }}
                                                        </h5>
                                                        <img src="{{ asset('images/addfund.png') }}" class="img-fluid">
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
            <!-- Perfect Money Code(Shreemant Mirkute) -->
            <form class="hidden" id="pmpayment" action="https://perfectmoney.com/api/step1.asp" method="POST">
                <input type="hidden" name="PAYEE_ACCOUNT" id="payee_account" />
                <input type="hidden" name="PAYEE_NAME" id="payee_name" />
                <input type="hidden" name="PAYMENT_ID" id="payment_id" />
                <input type="hidden" name="PAYMENT_AMOUNT" id="payment_amount" />
                <input type="hidden" name="PAYMENT_UNITS" id="payment_units" />
                <!-- <input type="hidden" name="STATUS_URL" v-model="perfectmoney.STATUS_URL"> -->
                <input type="hidden" name="PAYMENT_URL" id="payment_url" />
                <input type="hidden" name="NOPAYMENT_URL" id="nopayment_url" />
                <input type="hidden" name="PAYMENT_URL_METHOD" id="payment_url_method" />
                <input type="hidden" name="NOPAYMENT_URL_METHOD" id="nopayment_url_method" />
            </form>
            <!-- End of the code for perfect money -->
        </div>
    </div>

    
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script> --}}
    <script>
        var base_url = '{{ url('/') }}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');


        // $(document).ready(function() {
        //     $("#user_add_fund_form").validate({
        //         rules: {
        //             amount: {
        //                 'required': true,
        //                 digits: true,
        //                 min: 10
        //             },
        //             payment_mode: 'required',

        //         },
        //         messages: {
        //             amount: {
        //                 'required': "Please enter amount",
        //                 digits: "Please enter valid amount",
        //                 min: "Amount should be more than 10"
        //             },
        //             payment_mode: 'Please select an option',
        //         },
        //     });
        // });


        function payment_call() {
            // if ($("#user_add_fund_form").valid()) {

                var currency_code = $('#inputState').find(":selected").val();
                var hash_unit = $('#amount').val();

                var data = {
                    product_id: 1,
                    currency_code: currency_code,
                    hash_unit: hash_unit
                };

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    }
                });
                $.ajax({
                    url: '{{ url('/purchase-package') }}',
                    type: 'POST',
                    data: data,
                    success: function(resp) {

                        var url = resp.data.status_url;

                        window.open(url, '_blank');



                    },
                    error: function() {
                        // show error message
                    }
                });


            // }
        }
    </script>
@endsection
