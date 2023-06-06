@extends('layouts.user_type.admin-app')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="card-title">Withdraw Request Report</h4>
            </div>
            <br />
            <form id="searchForm">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-primary">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3 ml-5">
                                        <div class="form-group">
                                            <label>From Date</label>
                                            <div>
                                                <div class="input-group">
                                                    <input type="date" class="admin-form-control" name="frm_date"
                                                        :format="dateFormat" placeholder="From Date" id="frm_date" />
                                                    <!-- <input type="text" class="admin-form-control" placeholder="From Date" id="datepicker"> -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>To Date</label>
                                            <div>
                                                <div class="input-group">
                                                    <input type="date" class="admin-form-control" name="to_date"
                                                        :format="dateFormat" placeholder="To Date" id="to_date" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>User Id</label>
                                            <input class="admin-form-control" placeholder="Enter user id" type="text"
                                                    id="user_id" onkeyup="checkUserExisted(this.value)" />
                                                    
                                        </div>
                                    </div>
                                    <p id="error_flag" style="color:red"></p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="text-center">
                                                <button type="button" class="
                                  btn btn-primary
                                  waves-effect waves-light
                                  ml-4
                                " id="onSearchClick">
                                                    Search
                                                </button>
                                                <button type="button" class="
                                  btn btn-info
                                  waves-effect waves-light
                                  ml-4
                                " onclick="exportToExcel">
                                                    Export To Excel
                                                </button>
                                                <button type="button" class="
                                  btn btn-dark
                                  waves-effect waves-light
                                  ml-4
                                " id="onResetClick">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- panel-body -->
                        </div>
                        <!-- panel -->
                    </div>
                    <!-- col -->
                </div>
            </form>

            <div class="row amts-rows">
                <div class="col-md-6 col-sm-6" v-for="summary in withdrawsummary" v-bind:key="summary.currency">
                    <div class="panel">
                        <div class="panel-body">
                            <h5 class="panel-title text-muted">
                             //   Total summary.currency  Amount :
                                <span class="pull-right">$ summary.total_amount </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="admin-card-button ml-4" v-if="otpstatus == 1">
                <button
                  type="button"
                  class="btn btn-primary waves-effect waves-light"
                 onclick="sendAdminOtp"
                >
                  Send Otp
                </button>
                <p>Note :- Otp Valid 2 Hours</p>
              </div> -->
            <div class="admin-card-body">
                <div class="table-responsive admin-table">
                    <table v-once id="withdraw-request-report" class="display nowrap" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sr.No</th>
                                <th><input type="checkbox" id="allCheck" />Select All</th>
                                <th>User Id</th>
                                <th>Amount</th>
                                <!-- <th>UserIP</th> -->
                                <th>Service Charges</th>
                                <th>Net Amount</th>
                                <th>Currency Type</th>
                                <th>Wallet Type</th>
                                <th>Address</th>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="row" style="padding-bottom: 15px">
                <div class="col-md-12">
                    <div class="col-md-2"></div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-info waves-effect waves-light" onclick="showOTPPopup"
                            :disabled="arrayForSelectedCheckbox.length == 0">
                            Verify Withdrawals
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="add-remark-modal">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="CloseModal()">
                    <span aria-hidden="true" class="fa fa-times"></span>
                </button>
                <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
            </div>
            <div class="modal-body">
                <div class="row form-group" v-if="otpstatus == 1">
                    <div class="col-md-4">
                        <label>Enter OTP</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control" v-model="admin_otp" name="admin_otp" id="admin_otp"
                            required />
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-4">
                        <!-- <div class="col-md-12"> -->
                        <p>permanent otp</p>
                        <!-- </div>
                  <label>permanent &nbsp; Otp:&nbsp;&nbsp;&nbsp;&nbsp;</label> -->
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control" v-model="otp_btm" name="otp" id="otp" required />
                    </div>
                </div>
            </div>
            <div class="modal-footer text-right">
                <button type="button" class="btn btn-info" onclick="withdrawalVerify()">
                    Submit
                </button>
                <button type="button" class="btn" data-dismiss="modal" aria-label="Close" onclick="closeOTPPopup">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>

    var arrayForSelectedCheckbox = array();

getWithdrawalSummary() {
  var data = { status: 0, verify_status: 0 };
  $.ajax({
    type: "POST",
    url: "{{url('/getWithdrawalSummary')}}",
    data: data,
    dataType: "json",
    success: function(resp) {
      if (resp.code === 200) {
        this.withdrawsummary = resp.data;
      } else {
        // Assuming that you have already included a toast library
        // like Toastr for displaying error messages
        toastr.error(resp.message);
      }
    }.bind(this),
    error: function(xhr, status, error) {
      // Assuming that you have already included a toast library
      // like Toastr for displaying error messages
      toastr.error(error);
    }
  });
}


        $("#withdraw-request-report tbody").on(
          "click",
          ".myCheck",
          function () {
            $("#allCheck").prop("checked", false);
            if ($(this).is(":checked")) {
              arrayForSelectedCheckbox.push($(this).data("id"));
            } else if (!$(this).is(":checked")) {
              arrayForSelectedCheckbox.splice(
                arrayForSelectedCheckbox.indexOf($(this).data("id")),
              );
            }
          }
        );

</script>

@endsection