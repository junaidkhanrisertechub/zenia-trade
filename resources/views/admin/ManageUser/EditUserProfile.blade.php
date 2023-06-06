@extends('layouts.user_type.admin-app')
@section('content')

<div class="admin-card">
        <div class="admin-card-body">
          <div class="row">
                <div class="col-md-12">
                  <h4 class="page-title">Edit Profile</h4>
                </div>
                <div class="col-md-12 col-sm-3">
                  <a class="btn btn-primary waves-effect waves-light pull-right" href="{{url('/admin/user/manage-user-account')}}">
                        <i class="fa fa-mail-reply"></i> &nbsp;Back
</a>
                </div>
                <div class="col-md-12">
                  <div v-if="profileotpstatus == 1">
                      <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp(3)"> Send Otp </button>
                      <p>Note :- Otp Valid 2 Hours</p>
                  </div>
                </div>
              </div>
              
              
                  <form class="row g-3"  id="updatefrm"  name="updatefrm">
                    <div class="col-md-12">
                      <h4>Personal Information</h4>
                    </div>
                    <input type="hidden" name="id" value="{{$editUser['id']}}" />
                    <div class="col-md-6">
                        <label> User Id </label>
                        <input class="admin-form-control" placeholder="User ID" readonly type="text"  name="user_id" value="{{$editUser['user_id']}}" />
                    </div>
                    <div class="col-md-6">
                        <label> Sponsor  ID </label>
                        <input class="admin-form-control" placeholder="Sponsor  ID" readonly type="text" name ="sponsor_id" value="{{$editUser['sponsor_id']}}" />
                    </div>
                    <div class="col-md-6">
                        <label> Full Name </label>
                        <input name="fullname" class="admin-form-control" placeholder="Update Full Name" type="text" value="{{$editUser['fullname']}}" />
                    </div>
                    <div class="col-md-6">
                        <label> Mobile </label>
                        <input name="mobile" class="admin-form-control" id="mobile" placeholder="Enter Mobile" required type="text" value="{{$editUser['mobile']}}" />
                    </div>
                    <div class="col-md-6">
                        <label> Email </label>
                        <input class="admin-form-control" name="email" required placeholder="Update Email" type="text" value="{{$editUser['email']}}" />
                    </div>
                      <div class="col-md-6">
                        <label> Bitcoin </label>
                         <div class="input-group">

                         <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/bitcoin.png" width="25">
                    </span>
                        <input class="admin-form-control" name="btc_address"  placeholder="Bitcion Address" type="text" value="{{$editUser['btc_address']}}"  v-on:input="checkBTCAddress" minlength="26" maxlength="50"  />
                      </div>
                        <div v-if="!btcactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.btcmsg --}}</span>
                    </div>
                    </div>
                      <div class="col-md-6">
                        <label> Tron </label>
                         <div class="input-group">

                          <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/tron.png" width="25">
                    </span>
                        <input class="admin-form-control" name="trn_address"  placeholder="Tron Address" type="text" value="{{$editUser['trn_address']}}"  maxlength="50" v-on:input="checkTRXAddress(1)" />
                      </div>
                       <div v-if="!trxactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.trxmsg --}}</span>
                    </div>
                    </div>
                      <div class="col-md-6">
                        <label> Ethereum </label>
                         <div class="input-group">
                        <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/Ethereum.png" width="25">
                    </span>
                        <input class="admin-form-control" name="ethereum"  placeholder="Ethereum Address" type="text" value="{{$editUser['ethereum']}}"  maxlength="50" v-on:input="checkETHAddress" />
                      </div>
                       <div v-if="!ethereumactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.ethereummsg --}}</span>
                    </div>
                    </div>
                     <div class="col-md-6">
                        <label> DOGE </label>
                         <div class="input-group">
                        <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/Dogecoin.png" width="25">
                    </span>
                        <input class="admin-form-control" name="doge_address"  placeholder="Doge Address" type="text" value="{{$editUser['doge_address']}}"  maxlength="50" v-on:input="checkDOGEAddress"/>
                      </div>
                      <div v-if="!dogeactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.dogemsg --}}</span>
                    </div>
                    </div>
                     <div class="col-md-6">
                        <label> LTC </label>
                         <div class="input-group">
                           <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/litecoin.png" width="25">
                    </span>
                        <input class="admin-form-control" name="ltc_address"  placeholder="Ltc Address" type="text" value="{{$editUser['ltc_address']}}" maxlength="50" v-on:input="checkLTCAddress"/>
                      </div>
                      <div v-if="!rippleactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.ripplemsg --}}</span>
                    </div>
                    </div>
                     <div class="col-md-6">
                        <label> SOL </label>
                         <div class="input-group">
                        <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/solana.png" width="25">
                    </span>
                        <input class="admin-form-control" name="sol_address"  placeholder="Sol Address" type="text" value="{{$editUser['sol_address']}}" maxlength="50" v-on:input="checkSolAddress" />
                      </div>
                        <div v-if="!solanactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.solmsg --}}</span>
                    </div>
                    </div>
                     <div class="col-md-6">
                        <label> USDT.TRC20 </label>
                         <div class="input-group">
                       <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/tether.png" width="25">
                    </span>

                        <input class="admin-form-control" name="usdt_trc20_address"  placeholder="USDT.TRC20 Address" type="text" maxlength="50" value="{{$editUser['usdt_trc20_address']}}" v-on:input="checkUSDTAddress" />
                      </div>
                           <div v-if="!usdtactive" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.usdtmsg --}}</span>
                    </div>
                    </div>
                    <div class="col-md-6">
                        <label> USDT.ERC20 </label>
                         <div class="input-group">
                       <span class="input-group-text">
                      <img src="/admin-assets/images/payment-mode/tether.png" width="25">
                    </span>

                        <input class="admin-form-control" name="usdt_trc20_address"  placeholder="USDT.ERC20 Address" type="text" maxlength="50" value="{{$editUser['usdt_erc20_address']}}" v-on:input="checkUSDTERCAddress" />
                      </div>
                           <div v-if="!usdt_erc_active" class="tooltip2">
                      <span class="text-danger error-msg-size tooltip-inner">{{-- this.usdt_erc_msg --}}</span>
                    </div>
                    </div>
                    <div class="col-md-6" v-if="profileotpstatus == 1">
                        <label> Enter Otp </label>
                        <input class="admin-form-control" name="otp" placeholder="otp" type="text" value="" data-vv-as="otp" />
                    </div>
                    <div class="col-md-6">
                        <label> Country </label>
                        <select class="admin-form-control" name="country">
                          <option value="null">Select</option>
                          @foreach($cntry as $c)
                          <option value="{{$c->iso_code}}" @if( $editUser['country'] == $c->iso_code) selected @endif >
                            {{ $c->country }}                          
                          </option>
                          @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mx-auto mt-5 mb-3 text-center">
                        <button class="btn btn-primary ps-5 pe-5" name="submit" onclick="onUpdateUserClick()" type="button">
                          <i class="ace-icon fa fa-check bigger-110"></i> Submit </button>
                      </div>
                  </form>
        </div>
      </div>




<script>
    var base_url = '{{url(' / ')}}'
var csrftoken = $('meta[name="csrf-token"]').attr('content');

function onUpdateUserClick() {

     new Swal({
        title: "Are you sure?",
        text: `You want to update this user`,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
      }).then((result) => {
        if (result.value) {
                    $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrftoken
            }
        });
                    $.ajax({

                    url: "{{url('/admin/user/update-profile')}}",
                    type: 'POST',
                    cache:false,
                    data: $('#updatefrm').serialize(),
                   
                    success: function(resp) {
                    if (resp.code === 200) {
                    window.location.href = "{{url('admin/user/manageuseraccount')}}";
                    // Show success message using a toast library
                    toastr.success(resp.message);
                    } else {
                    // Show error message using a toast library
                    toastr.error(resp.message);
                    }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                    // Show error message using a toast library
                    toastr.error('An error occurred while updating the user profile.');
                    }
                    });

                }
            })
            
        }
     

    function checkTRXAddress(addFor) {
      var trx_address = "";
      if (addFor == "1") {
        trx_address = "" + this.editUser.trn_address + "";
      } else {
        trx_address = "" + this.editUser.tether_address + "";
      }
      if (trx_address.charAt(0) == 't' || trx_address.charAt(0) == 'T' || trx_address == '') {
        this.trxactive = true;
        this.tetheractive = true;
        this.trxmsg = "";
        this.tethermsg = "";
        if (addFor == 1) {
          if (trx_address.length < 26 || trx_address.length > 50) {
            this.trxactive = false;
            this.trxmsg = "TRON Address length should be 26 to 50 characters";
          }
        } else {
          if (trx_address.length < 26 || trx_address.length > 50) {
            this.tetheractive = false;
            this.tethermsg = "TETHER Address length should be 26 to 50 characters";
          }
        }
      } else {
        if (addFor == 1) {
          this.trxactive = false;
          this.trxmsg = "TRON Address should be start with 'T or t'";
        } else {
          this.tetheractive = false;
          this.tethermsg = "Tether Address should be start with 'T or t'";
        }
      }
    }


  function  checkDOGEAddress() {
      let doge_address = "" + this.editUser.doge_address + "";
      if (doge_address.charAt(0) == 'D'  || doge_address == '') {
        this.dogeactive = true;
        this.dogemsg = "";
        if (doge_address.length < 26 || doge_address.length > 50) {
          this.dogeactive = false;
          this.dogemsg = "DOGE Address length should be 26 to 50 characters";
        }
      } else {
        this.dogeactive = false;
        this.dogemsg = "DOGE Address should be start with 'D'";
      }
    }

    function  checkLTCAddress() {
      let ltc_address = "" + this.editUser.ltc_address + "";
      if (ltc_address.charAt(0) == 'L' || ltc_address.charAt(0) == 'l' || ltc_address.charAt(0) == 'M' || ltc_address.charAt(0) == 'm' ||ltc_address == '') {
        this.rippleactive = true;
        this.ripplemsg = "";
        if (ltc_address.length < 26 || ltc_address.length > 50) {
          this.rippleactive = false;
          this.ripplemsg = "LTC Address length should be 26 to 50 characters";
        }
      } else {
        this.rippleactive = false;
        this.ripplemsg = "LTC Address should be start with 'L','l' or 'M','m'";
      }
    }


    function  checkSolAddress() {
      let sol_address = "" + this.editUser.sol_address + "";
      if (sol_address.charAt(0) == 's' || sol_address == '') {
        this.solanactive = true;
        this.solmsg = "";
        if (sol_address.length < 26 || sol_address.length > 50) {
          this.solanactive = false;
          this.solmsg = "Solana Address length should be 26 to 50 characters";
        }
      } else {
        this.solanactive = false;
        this.solmsg = "Solana Address should be start with 's'";
      }
    }
    function checkUSDTAddress() {
      let usdt_trc20_address = "" + this.editUser.usdt_trc20_address + "";
      if (usdt_trc20_address.charAt(0) == 't' || usdt_trc20_address.charAt(0) == 'T') {
        this.usdtactive = true;
        this.usdtmsg = "";
        if (usdt_trc20_address.length < 26 || usdt_trc20_address.length > 50) {
          this.usdtactive = false;
          this.usdtmsg = "USDT_TRC20 Address length should be 26 to 50 characters";
        }
      } else {
        this.usdtactive = false;
        this.usdtmsg = "USDT_TRC20 Address should be start with 't' or 'T'";
      }
    }
    function checkETHAddress() {
      let ethereum_address = "" + this.editUser.ethereum + "";
      if ((ethereum_address.charAt(0) == '0') && (ethereum_address.charAt(1) == 'x')) {
        this.ethereumactive = true;
        this.ethereummsg = "";
        if (ethereum_address.length < 26 || ethereum_address.length > 50) {
          this.ethereumactive = false;
          this.ethereummsg = "ETH Address length should be 26 to 50 characters";
        }
      } else {
        this.ethereumactive = false;
        this.ethereummsg = "ETH Address should be start with '0x'";
      }
    }
    function checkUSDTERCAddress() {
      let usdt_erc20_address = "" + this.editUser.usdt_erc20_address + "";
      if ((usdt_erc20_address.charAt(0) == '0') && (usdt_erc20_address.charAt(1) == 'x')) {
        this.usdt_erc_active = true;
        this.usdt_erc_msg = "";
        if (usdt_erc20_address.length < 26 || usdt_erc20_address.length > 50) {
          this.usdt_erc_active = false;
          this.usdt_erc_msg = "USDT_ERC20 Address length should be 26 to 50 characters";
        }
      } else {
        this.usdt_erc_active = false;
        this.usdt_erc_msg = "USDT_ERC20 Address should be start with '0x'";
      }
    }
    function checkBTCAddress() {
      let btc_address = "" + this.editUser.btc_address + "";
      if (btc_address.charAt(0) == 'b' || btc_address.charAt(0) == '1' || btc_address.charAt(0) == '3') {
        this.btcactive = true;
        this.btcmsg = "";
        if (btc_address.length < 26 || btc_address.length > 50) {
          this.btcactive = false;
          this.btcmsg = "BTC Address length should be 26 to 50 characters";
        }
      } else {
        this.btcactive = false;
        this.btcmsg = "BTC Address should be start with 'b', '1' or '3'";
      }
    }

</script>

@endsection