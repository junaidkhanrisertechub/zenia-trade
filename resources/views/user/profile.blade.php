@extends('layouts.user_type.auth-app')

@section('content')
    <?php
    $query = DB::table('tbl_country_new')
        ->select('iso_code', 'country', 'code')
        ->where('block_country_status', '=', 'Active');
    $getCountry = $query->orderBy('country', 'asc')->get();
    
    $selectedCountry = DB::table('tbl_country_new')
        ->select('country', 'iso_code', 'code')
        ->where('iso_code', '=', $profile->country)
        ->get();
    
    $sponser_uid = DB::table('tbl_users')
        ->select('user_id')
        ->where('id', '=', $profile->ref_user_id)
        ->get();
    $google2fa_status = Auth::user()->google2fa_status;
    ?>

<style>
    .uploadPhoto{
        position: relative;
        top: 35px;
        left: -12px;
        cursor: pointer;
    }
</style>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="mt-n5 position-relative">
                                    <div class="text-center">
                                        @if ($profile->user_image == '')
                                            <img src="{{ asset('user-assets/images/avatar-3.jpg') }}" alt="Demo-User"
                                                class="avatar-xl rounded-circle img-thumbnail">
                                        @else
                                            <img src="{{ $profile->user_image }}" alt="{{ $profile->fullname }}"
                                                class="avatar-xl rounded-circle img-thumbnail">
                                        @endif
                                        <span class="uploadPhoto">
                                            <i class="fa-solid fa-camera" onclick="phptoUpload()"></i>
                                        </span>
                                        <div class="mt-3">
                                            <h5 class="mb-1">{{ $profile->fullname }}</h5>
                                            <div>
                                                <a href="javascript:void(0)" class="badge badge-soft-success m-1"> Sponsor
                                                    User ID : <strong>{{ $sponser_uid[0]->user_id }}</strong></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <form class="card-body" method="POST" action="{{ route('update-profile', $profile->id) }}"
                                    id="updateUserData">
                                    @csrf

                                    @if (Auth::user()->topup_status == 1)
                                        @php
                                            $disabled = 'readonly';
                                            $disabledcountry = 'disabled';
                                        @endphp
                                    @else
                                        @php
                                            $disabled = '';
                                            $disabledcountry = '';
                                        @endphp
                                    @endif

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Name
                                                </label>
                                                <input type="text" class="form-control" value="{{ $profile->fullname }}"
                                                    name="fullname" id="fullname" maxlength="30"
                                                    onblur="NameValidation($event)" placeholder="Enter Name">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    User ID
                                                </label>
                                                <input type="text" class="form-control" id="userid"
                                                    value="{{ $profile->user_id }}" readonly placeholder="Enter User ID">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Email ID
                                                </label>
                                                <input type="text" class="form-control" name="email" id="emailID"
                                                    value="{{ $profile->email }}" {{ $disabled }}
                                                    placeholder="Enter Email ID">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Country Code
                                                </label>
                                                <select class="form-select" name="country" {{ $disabledcountry }}>
                                                    @if ($profile->country != '')
                                                        <option value="{{ $profile->country }}" selected>
                                                            [ {{ $selectedCountry[0]->iso_code }} ] -
                                                            {{ $selectedCountry[0]->country }} (+
                                                            {{ $selectedCountry[0]->code }})</option>
                                                    @else
                                                        <option value="null">Select Country</option>
                                                    @endif
                                                    @foreach ($getCountry as $val)
                                                        <option value="{{ $val->iso_code }}">[ {{ $val->iso_code }} ] -
                                                            {{ $val->country }} (+ {{ $val->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Mobile No
                                                </label>
                                                <input type="hidden" name="type" value="user">
                                                <input type="hidden" id="editprofileotp" name="otp">
                                                <input type="text" class="form-control" name="mobile" id="phone"
                                                    maxlength="12" value="{{ $profile->mobile }}" {{ $disabled }}
                                                    placeholder="Enter Mobile Number">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mt-4">
                                                <button type="button" onclick="updateUserData()" id="sendOtpProfile"
                                                    class="btn btn-primary w-md">Save Changes</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card card-h-100">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Change Password</h4>
                            </div>
                            <!-- end card header -->
                            <div class="card-body">
                                <div>
                                    <form id="passwordForm" method="POST"
                                        action="{{ route('update-password', $profile->id) }}">
                                        @csrf
                                        <label class="form-label">Old Password</label>
                                        <div class="input-group mb-3">
                                            <input type="password" class="form-control" name="current_password"
                                                id="old_password" placeholder="Enter Old Password"
                                                onkeyup="oldpasswordvalidate()">
                                                <span class="input-group-text" id="opass">
                                                    <i class="fa-solid fa-face-smile-beam"></i>
                                                </span>
                                        </div>
                                        <span id="oPassError" class="text-danger fw-bold"></span>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">New Password</label>
                                                <div class="input-group mb-3">
                                                    <input type="password" class="form-control" id="new_password"
                                                        name="new_password" placeholder="Enter New Password"
                                                        onkeyup="newpasswordvalidate()">
                                                        <span class="input-group-text" id="opass1">
                                                            <i class="fa-solid fa-face-smile-beam"></i>
                                                        </span>
                                                    <input type="hidden" id="passwordOtp" name="otp">
                                                </div>
                                                <span id="nPassError" class="text-danger fw-bold"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Retype Password</label>
                                                <div class="input-group mb-3">
                                                    <input type="password" class="form-control"
                                                        aria-label="Retype Password" aria-describedby="opass2"
                                                        id="confirm_password" name="confirm_password"
                                                        placeholder="Enter Retype Password"
                                                        onkeyup="confirmpasswordvalidate()">
                                                    <span class="input-group-text" id="opass2">
                                                        <i class="fa-solid fa-face-smile-beam"></i>
                                                    </span>
                                                </div>
                                                <span id="cPassError" class="text-danger fw-bold"></span>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button type="button" class="btn btn-primary w-md"
                                                onclick="sendOTPPassword()">Save Changes</button>
                                        </div>
                                    </form>
                                    <!-- end form -->
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card card-h-100">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Currency Address</h4>
                            </div>
                            <!-- end card header -->
                            <div class="card-body">
                                <div>
                                    <form method="POST" id="address_form" action="{{ url('/change-address') }}">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Bitcoin</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/bitcoin.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="hidden" id="addressOtp" name="otp">
                                                        <input type="text" class="form-control" placeholder="Bitcoin Address"
                                                            autocomplete="off" name="btc_address" id="btc_address"
                                                            value="{{ $profile->btc_address }}" minlength="26"
                                                            maxlength="50">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="btc_address_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Tron</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/tron.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="Tron Address"
                                                            autocomplete="off" name="trn_address" id="trn_address"
                                                            value="{{ $profile->trn_address }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="trn_address_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Ethereum</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/Ethereum.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="Ethereum Address"
                                                            autocomplete="off" name="ethereum" id="ethereum"
                                                            value="{{ $profile->ethereum }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="ethereum_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">DOGE</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/Dogecoin.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="Doge Address"
                                                            autocomplete="off" name="doge_address" id="doge_address"
                                                            value="{{ $profile->doge_address }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="doge_address_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">LTC</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/litecoin.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="LTC Address"
                                                            autocomplete="off" name="ltc_address" id="ltc_address"
                                                            value="{{ $profile->ltc_address }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="ltc_address_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Solana</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/solana.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="Solana Address"
                                                            autocomplete="off" name="sol_address" id="sol_address"
                                                            value="{{ $profile->sol_address }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="sol_address_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">USDT.TRC20</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/trc20.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="USDT.TRC20 Address" name="usdt_trc20_address" id="usdt_trc20_address"
                                                        value="{{ $profile->usdt_trc20_address }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="usdt_trc20_address_error"></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">USDT.ERC20</label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">
                                                            <img src="{{ asset('user-assets/images/payment-mode/erc20.png') }}"
                                                                height="40">
                                                        </div>
                                                        <input type="text" class="form-control" placeholder="USDT.ERC20 Address"
                                                            autocomplete="off" name="usdt_erc20_address"
                                                            id="usdt_erc20_address"
                                                            value="{{ $profile->usdt_erc20_address }}" minlength="26">
                                                    </div>
                                                    <p class="text-danger text-xs mt-2" id="usdt_erc20_address_error"></p>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="mt-4">
                                            <button type="button" class="btn btn-primary w-md"
                                                onclick="updateAddress()">Save Changes</button>
                                        </div>
                                    </form>
                                    <!-- end form -->
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="profileexampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-4">
                                <img src="{{ asset('user-assets/images/email-vector.png') }}" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" placeholder="Enter OTP">
                                    </div>
                                    <div class="col-md-12 text-center mt-3">
                                        <button class="btn bg-gradient-primary">Resend</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light">Submit</button>
                    </div>
                </div>
            </div>
        </div>



        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-4">
                                <img src="{{ asset('user-assets/images/email-vector.png') }}" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" placeholder="Enter OTP">
                                    </div>
                                    <div class="col-md-12 text-center mt-3">
                                        <button class="btn bg-gradient-primary">Resend</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light">Submit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Picture......... -->
        <div class="modal fade" id="profilePhoto" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Change Profile Photo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('update-profile-pic', $profile->id) }}" id="profilePic" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row align-items-center justify-content-center">
                                <div class="col-md-4">
                                    <img src="{{ asset('images/email-vector.png') }}" class="img-fluid">
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <input type="file" name="profile_image" id="popup_image"
                                                ref="popup_image" accept="image/png, image/jpeg, image/jpg"
                                                class="form-control w1000">
                                            <input type="hidden" name="type" value="photo">
                                        </div>
                                        <!-- <div class="col-md-12 text-center mt-3">
                                                            <button type="button" class="btn bg-gradient-primary" @click="sendEditOtp">Update</button>
                                                        </div> -->
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button onclick="updateUserImage()" type="button" class="btn btn-primary kbb-bbt">update</button>
                        <!-- <button type="button" class="btn btn-light">Submit</button> -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Profile Picture......... -->

        <!-- Profile Details......... -->
        <div class="modal fade" id="myeditotpmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-4">
                                <img src="{{ asset('images/email-vector.png') }}" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    @if ($google2fa_status == 'disable')
                                        <div class="col-md-12" v-if="profile.google2fa_status=='disable'">
                                            <input type="text" id="profile-otp" name="otp"
                                                class="form-control w1000" placeholder="Enter OTP" v-model="otp_edit"
                                                maxlength="6"
                                                onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                                
                                            </div>
                                            <span class="pl-4 text-success fw-bold">OTP sent to
                                                @php
                                                    $email = Auth::user()->email;
                                                    $modifiedEmail = substr_replace($email, '********', 4, 8); // Add an asterisk after the fourth character
                                                @endphp

                                                {{ $modifiedEmail }}
                                            </span>
                                    @else
                                        <div class="col-md-12 mt-3" v-else>
                                            <input type="text" name="2fa-otp" id="profile-otp"
                                                class="form-control w1000" placeholder="Enter G2FA OTP" v-model="otp_2fa"
                                                maxlength="6"
                                                onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                            <!-- <input type="text" class="form-control" placeholder="Enter OTP"> -->
                                        </div>
                                    @endif
                                    <div class="col-md-12 text-center mt-3" {{-- v-if="profile.google2fa_status=='disable'" --}}>
                                        <button type="button" class="btn btn-warning" id="resend_otp_profile"
                                            onclick="updateUserData()">Resend</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button onclick="editProfileUserData()" type="button"
                            class="btn btn-primary kbb-bbt">Submit</button>
                        <!-- <button type="button" class="btn btn-light">Submit</button> -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Profile Details......... -->

        <div class="modal fade" id="changeAddress" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-4">
                                <img src="{{ asset('images/email-vector.png') }}" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <div class="row">

                                    @if ($google2fa_status == 'disable')
                                        <div class="col-md-12" v-if="profile.google2fa_status=='disable'">
                                            <input type="text" id="address-otp" name="otp"
                                                class="form-control w1000" placeholder="Enter OTP" maxlength="6"
                                                onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                            </div>
                                            <span class="pl-4 text-success fw-bold">OTP sent to
                                                @php
                                                    $email = Auth::user()->email;
                                                    $modifiedEmail = substr_replace($email, '********', 4, 8); // Add an asterisk after the fourth character
                                                @endphp

                                                {{ $modifiedEmail }}
                                            </span>
                                    @else
                                        <div class="col-md-12 mt-3">
                                            <input type="text" name="2fa-otp" class="form-control w1000"
                                                placeholder="Enter G2FA OTP" id="address-otp" maxlength="6"
                                                onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                            <!-- <input type="text" class="form-control" placeholder="Enter OTP"> -->
                                        </div>
                                    @endif
                                    <div class="col-md-12 text-center mt-3" v-if="profile.google2fa_status=='disable'">
                                        <button type="button" class="btn btn-warning" id="resend_otp_address"
                                            onclick="updateAddress()">Resend</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button onclick="updateUserAddress()" type="button"
                            class="btn btn-primary kbb-bbt">Submit</button>
                        <!-- <button type="button" class="btn btn-light">Submit</button> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Password......... -->
        <div class="modal fade" id="editchangepassmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-4">
                                <img src="{{ asset('images/email-vector.png') }}" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    @if ($google2fa_status == 'disable')
                                        <div class="col-md-12 mb-3" v-if="profile.google2fa_status=='disable'">
                                            <input type="text" name="otp" class="form-control w1000"
                                                placeholder="Enter OTP" id="pass-otp" maxlength="6"
                                                onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                            </div>
                                            <span class="pl-4 text-success fw-bold">OTP sent to
                                                @php
                                                    $email = Auth::user()->email;
                                                    $modifiedEmail = substr_replace($email, '********', 4, 8); // Add an asterisk after the fourth character
                                                @endphp

                                                {{ $modifiedEmail }}
                                            </span>
                                    @else
                                        <div class="col-md-12 mt-3" v-else>
                                            <input type="text" name="2fa-otp" class="form-control w1000"
                                                placeholder="Enter G2FA OTP" id="pass-otp" maxlength="6"
                                                onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                        </div>
                                    @endif
                                    <div class="col-md-12 text-center mt-3" v-if="profile.google2fa_status=='disable'">
                                        <button type="button" class="btn btn-warning"
                                            onclick="sendOTPPassword()">Resend</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button onclick="updateUserPassword()" type="button"
                            class="btn btn-primary kbb-bbt">Submit</button>
                        <!-- <button type="button" class="btn btn-light">Submit</button> -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Update Password......... -->

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>

        <script>
            var google2fa = '{{ $google2fa_status }}'

            $(document).ready(function() {
                $('#editchangepassmodal').modal('hide');
                $('#changeAddress').modal('hide');
                $('#myeditotpmodal').modal('hide');
                $('#profilePhoto').modal('hide');
                $('#exampleModal').modal('hide');
                $('#profileexampleModal').modal('hide');

                // For ethereum validations
                jQuery.validator.addMethod("ethereumpattern", function(value) {
                    if (/^0x[a-fA-F0-9]{40}$/.test(value)) {
                        return true;
                    } else if (value.length == 0) {
                        return true;
                    }
                }, "Ethereum Address should be start with \'0x\'");
                // For doge validations
                jQuery.validator.addMethod("dogepattern", function(value) {
                    if (/^D[A-HJ-NP-Za-km-z1-9]{33}$/.test(value)) {
                        return true;
                    } else if (value.length == 0) {
                        return true;
                    }
                }, "Doge Coin Address should be start with D");
                // For ltc validations
                jQuery.validator.addMethod("ltcpattern", function(value) {
                    if (/^[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}$/.test(value)) {
                        return true;
                    } else if (value.length == 0) {
                        return true;
                    }
                }, "LTC Address should be start with L or l or M or m.");
                // For solana validations
                jQuery.validator.addMethod("solanapattern", function(value) {
                    if (/^([1-9A-HJ-NP-Za-km-z]{32,33}|[1-9A-HJ-NP-Za-km-z]{62})$/.test(value)) {
                        return true;
                    } else if (value.length == 0) {
                        return true;
                    }
                }, "Sol address must be in between 26 to 50 characters");
                // For usdt_trc validations
                jQuery.validator.addMethod("usdt_trcpattern", function(value) {
                    if (/^T[a-zA-HJ-NP-Za-km-z0-9]{33}$/.test(value)) {
                        return true;
                    } else if (value.length == 0) {
                        return true;
                    }
                }, "Tether TRC-20 Address should be start with T or t.");
                // For usdt_trc validations
                jQuery.validator.addMethod("usdt_ercpattern", function(value) {
                    if (/^0x[a-fA-F0-9]{40}$/.test(value)) {
                        return true;
                    } else if (value.length == 0) {
                        return true;
                    }
                }, "Tether TRC-20 Address should be start with T or t.");


            });

            var showpassword = 0;
            $("#opass").click(function() {
                var eye = $("#opass i");
                if (showpassword == 0) {
                    $("#old_password").attr("type", "text");
                    eye.removeClass("fa-solid fa-face-smile-beam").addClass("fa-solid fa-face-laugh-beam");
                    showpassword = 1;
                } else if (showpassword == 1) {
                    $("#old_password").attr("type", "password");
                    eye.removeClass("fa-solid fa-face-laugh-beam").addClass("fa-solid fa-face-smile-beam");
                    showpassword = 0;
                }
            });
            $("#opass1").click(function() {
                var eye = $("#opass1 i");

                if (showpassword == 0) {
                    $("#new_password").attr("type", "text");
                    eye.removeClass("fa-solid fa-face-smile-beam").addClass("fa-solid fa-face-laugh-beam");
                    showpassword = 1;
                } else if (showpassword == 1) {
                    $("#new_password").attr("type", "password");
                    eye.removeClass("fa-solid fa-face-laugh-beam").addClass("fa-solid fa-face-smile-beam");
                    showpassword = 0;
                }
            });
            $("#opass2").click(function() {
                var eye = $("#opass2 i");

                if (showpassword == 0) {
                    $("#confirm_password").attr("type", "text");
                    eye.removeClass("fa-solid fa-face-smile-beam").addClass("fa-solid fa-face-laugh-beam");
                    showpassword = 1;
                } else if (showpassword == 1) {
                    $("#confirm_password").attr("type", "password");
                    eye.removeClass("fa-solid fa-face-laugh-beam").addClass("fa-solid fa-face-smile-beam");
                    showpassword = 0;
                }
            });

            function copyMailAddress() {
                const ipt = document.querySelector(".copy-mail input");
                var btnVal = $("#emailCopy");
                ipt.select();
                const copyed = document.execCommand("copy");
                if (copyed) {
                    btnVal.html("Copied");
                    btnVal.removeClass("bg-gradient-primary");
                    btnVal.addClass("bg-success");
                    btnVal.addClass("text-light");
                    setInterval(function() {
                        btnVal.html("<i class='fa-regular fa-copy'></i> Copy Email");
                        btnVal.addClass("bg-gradient-primary");
                        btnVal.removeClass("bg-success");
                        btnVal.removeClass("text-light");
                    }, 7000);
                } else {
                    btnVal.html("Copy failed..!!");
                    setInterval(function() {
                        btnVal.html("<i class='fa-regular fa-copy'></i> Copy Email");
                    }, 3000);
                }
            }

            function phptoUpload() {
                $("#profilePhoto").modal("show");
            }

            function updateUserImage() {
                $("#profilePic").submit();
                setTimeout(function(){
                    location.reload();
                }, 2000);
            }

            function updateUserData() {
                //alert("called");
                //$("#myeditotpmodal").modal("show");
                if (google2fa == 'disable') {
                        var csrf_token = "{{ csrf_token() }}";
                        $.ajax({
                            url: "{{ url('/sendOtp-update-user-password') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf_token
                            },
                            success: function(response) {
                                toastr.success(response.message);
                            },
                            error: function(xhr, status, error) {
                                console.log(error);
                            }
                        });
                    }
                    $("#myeditotpmodal").modal('show');
            }

            function editProfileUserData() {
                $("#myeditotpmodal").modal('hide');
                var otp = $("#profile-otp").val();
                var getOtp = $("#editprofileotp");
                getOtp.val(otp);
                // $("#updateUserData").submit();

                var form = $('#updateUserData');
                var url = form.attr('action');
                var method = form.attr('method');
                var data = form.serialize();

                $.ajax({
                    url: url,
                    type: method,
                    data: data,
                    success: function(response) {
                        // handle success response
                        if(response.code == 200){
                            toastr.success(response.message);
                            // console.log(response.message);
                            setTimeout(function(){
                                location.reload();
                            }, 2000);
                            $("#myeditotpmodal").modal('hide');
                            $("#profile-otp").val('');
                        }else{
                            // console.log(response.message);
                            toastr.error(response.message);
                            setTimeout(function(){
                                location.reload();
                            }, 2000);
                            $("#myeditotpmodal").modal('hide');
                            $("#profile-otp").val('');

                        }

                    }
                });
            }

            function oldpasswordvalidate() {
                var oldpassword = $("#old_password").val();
                if (oldpassword.length < 6) {
                    $('#oPassError').show();
                    $('#oPassError').html("Please Enter Valid Old Password");
                    return false;
                } else {
                    $('#oPassError').hide();
                    return true;
                }
            }


            function newpasswordvalidate() {
                var newpassword = $("#new_password").val();
                if (newpassword.length < 6) {
                    $('#nPassError').show();
                    $('#nPassError').html("Please Enter Valid Password");
                    return false;
                } else {
                    $('#nPassError').hide();
                    return true;
                }
            }



            function confirmpasswordvalidate() {
                var newpassword = $("#new_password").val();
                var confirmpassword = $("#confirm_password").val();
                if (newpassword != confirmpassword) {
                    $('#cPassError').show();
                    $('#cPassError').html("New Password & confirm password doesn't match");
                    return false;
                } else {
                    $('#cPassError').hide();
                    return true;
                }
            }

            function sendOTPPassword() {
                var oldpassword_validator = this.oldpasswordvalidate();
                var newpassword_validator = this.newpasswordvalidate();
                var confirmpassword_validator = this.confirmpasswordvalidate();


                if (oldpassword_validator && newpassword_validator && confirmpassword_validator) {
                    if (google2fa == 'disable') {
                        var csrf_token = "{{ csrf_token() }}";
                        $.ajax({
                            url: "{{ url('/sendOtp-update-user-password') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf_token
                            },
                            success: function(response) {
                                console.log(response);
                                toastr.success(response.message);
                            },
                            error: function(xhr, status, error) {
                                console.log(error);
                            }
                        });
                        $("#editchangepassmodal").modal('show');
                    } else {
                        toastr.error('Something went wrong..');
                    }
                }
            }


            function updateUserPassword() {
                var otp = $("#pass-otp").val();
                var getOtp = $("#passwordOtp");
                getOtp.val(otp);
                $("#passwordForm").submit();
            }


            function btn_address_validator() {
                //bitcoin validation
                var btn_address_validator = true;
                var bitcoinAddressPattern = /^([1b3])[a-zA-Z0-9]{25,}$/;
                var bitcoinAddress = $('#btc_address').val();
                if (bitcoinAddressPattern.test(bitcoinAddress)) {
                    $('#btc_address_error').hide();
                    btn_address_validator = true;
                } else if (bitcoinAddress.length == 0) {
                    $('#btc_address_error').hide();
                    btn_address_validator = true;
                } else {
                    $('#btc_address_error').show();
                    $('#btc_address_error').html("Bitcoin Address should be start with b or 1 or 3")
                    btn_address_validator = false;
                }
                return btn_address_validator;
            }



            function trn_address_validator() {
                //trn address validation
                var trn_address_validator = true;
                var trnAddress = $('#trn_address').val();
                if (trnAddress.length == 0) {
                    $('#trn_address_error').hide();
                    trn_address_validator = true;
                } else if (trnAddress.length < 26) {
                    $('#trn_address_error').show();
                    $('#trn_address_error').html("TRN Address length is minimum 26")
                    trn_address_validator = false;
                } else {
                    $('#trn_address_error').hide();
                    trn_address_validator = true;
                }
                return trn_address_validator;
            }




            function ethereum_validator() {
                //etherium validator
                var ethereum_validator = true;
                var ethereumAddressPattern = /^([0])([x])[a-fA-F0-9]{40,}$/;
                var ethereumAddress = $('#ethereum').val();
                if (ethereumAddressPattern.test(ethereumAddress)) {
                    $('#ethereum_error').hide();
                    ethereum_validator = true;
                } else if (ethereumAddress.length == 0) {
                    $('#ethereum_error').hide();
                    ethereum_validator = true;
                } else {
                    $('#ethereum_error').show();
                    $('#ethereum_error').html("Ethereum Address should be start with \'0x\'")
                    ethereum_validator = false;
                }
                return ethereum_validator;
            }




            function doge_address_validator() {
                var doge_address_validator = true;
                //doge address validation
                var dogeAddress = $('#doge_address').val();
                if (dogeAddress.length == 0) {
                    $('#doge_address_error').hide();
                    doge_address_validator = true;
                } else if (dogeAddress.length < 26) {
                    $('#doge_address_error').show();
                    $('#doge_address_error').html("length is minimum 26")
                    doge_address_validator = false;
                } else {
                    $('#doge_address_error').hide();
                    doge_address_validator = true;
                }
                return doge_address_validator;
            }




            function ltc_address_validator() {
                var ltc_address_validator = true;
                //ltc address validation
                var ltcAddress = $('#ltc_address').val();
                if (ltcAddress.length == 0) {
                    $('#ltc_address_error').hide();
                    ltc_address_validator = true;
                } else if (ltcAddress.length < 26) {
                    $('#ltc_address_error').show();
                    $('#ltc_address_error').html("length is minimum 26")
                    ltc_address_validator = false;
                } else {
                    $('#ltc_address_error').hide();
                    ltc_address_validator = true;
                }
                return ltc_address_validator;
            }






            function sol_address_validator() {
                var sol_address_validator = true;
                //ltc address validation
                var solAddress = $('#sol_address').val();
                if (solAddress.length == 0) {
                    $('#sol_address_error').hide();
                    sol_address_validator = true;
                } else if (solAddress.length < 26) {
                    $('#sol_address_error').show();
                    $('#sol_address_error').html("length is minimum 26")
                    sol_address_validator = false;
                } else {
                    $('#sol_address_error').hide();
                    sol_address_validator = true;
                }
                return sol_address_validator;
            }




            function usdt_erc20_address_validator() {
                var usdt_erc20_address_validator = true;
                //usdt erc 20 address validation
                var usdtERCAddress = $('#usdt_erc20_address').val();
                if (usdtERCAddress.length == 0) {
                    $('#usdt_erc20_address_error').hide();
                    usdt_erc20_address_validator = true;
                } else if (usdtERCAddress.length < 26) {
                    $('#usdt_erc20_address_error').show();
                    $('#usdt_erc20_address_error').html("length is minimum 26")
                    usdt_erc20_address_validator = false;
                } else {
                    $('#usdt_erc20_address_error').hide();
                    usdt_erc20_address_validator = true;
                }
                return usdt_erc20_address_validator;
            }





            function usdt_trc20_address_validator() {
                var usdt_trc20_address_validator = true;
                //usdt_trc20_addressAddress validation
                var usdt_trc20_addressAddress = $('#usdt_trc20_address').val();
                if (usdt_trc20_addressAddress.length == 0) {
                    $('#usdt_trc20_address_error').hide();
                    usdt_trc20_address_validator = true;
                } else if (usdt_trc20_addressAddress.length < 26) {
                    $('#usdt_trc20_address_error').show();
                    $('#usdt_trc20_address_error').html("length is minimum 26")
                    usdt_trc20_address_validator = false;
                } else {
                    $('#usdt_trc20_address_error').hide();
                    usdt_trc20_address_validator = true;
                }
                return usdt_trc20_address_validator;
            }





            function updateAddress() {

                var btn_address_validator = this.btn_address_validator();
                var trn_address_validator = this.trn_address_validator();
                var ethereum_validator = this.ethereum_validator();
                var doge_address_validator = this.doge_address_validator();
                var ltc_address_validator = this.ltc_address_validator();
                var sol_address_validator = this.sol_address_validator();
                var usdt_erc20_address_validator = this.usdt_erc20_address_validator();
                var usdt_trc20_address_validator = this.usdt_trc20_address_validator();


                if (btn_address_validator && trn_address_validator && ethereum_validator && doge_address_validator &&
                    ltc_address_validator && sol_address_validator && usdt_erc20_address_validator &&
                    usdt_trc20_address_validator) {
                    if (google2fa == 'disable') {
                        var csrf_token = "{{ csrf_token() }}";
                        $.ajax({

                            url: "{{ url('/sendOtp-update-address') }}",
                            type: 'POST',
                            headers: {

                                'X-CSRF-TOKEN': csrf_token

                            },
                            success: function(response) {
                                toastr.success(response.message);
                            },

                            error: function(xhr, status, error) {
                                console.log(error);
                            }

                        });
                        $("#changeAddress").modal('show');
                    } else {
                        $("#changeAddress").modal('show');
                    }
                }


            }

            function updateUserAddress() {
                var otp = $("#address-otp").val();
                var getOtp = $("#addressOtp");
                getOtp.val(otp);
                $('#addressOtp_2fa').val(otp);
                $("#address_form").submit();
            }
        </script>
@endsection