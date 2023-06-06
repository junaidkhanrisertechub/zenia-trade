@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Verified Withdraw Request Report</h4>
                </div>
                <br />
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-primary">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>From Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input type="date" class="admin-form-control" name="frm_date"
                                                            placeholder="From Date" id="frm_date" />
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
                                                            placeholder="To Date" id="to_date" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>User Id</label>
                                                <input class="admin-form-control" placeholder="Enter user id" type="text"
                                                    id="user_id" onkeyup="checkUserExisted(this.value)" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label id="isAvialable"></label>
                                                <input id="fullname" class="admin-form-control d-none">
                                            </div>
                                        </div>
                                        <p id="errorMessage" style="color:red"></p>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="text-center">
                                                    <button type="button"
                                                        class="btn btn-primary waves-effect waves-light ml-4"
                                                        id="onSearchClick">Search</button>
                                                    <button type="button"
                                                        class="btn btn-info waves-effect waves-light ml-4"
                                                        onclick="exportToExcel()">Export To Excel</button>
                                                    <button type="button"
                                                        class="btn btn-dark waves-effect waves-light ml-4"
                                                        id="onResetClick">Reset</button>
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
                    <div class="row amts-rows" id="withdraw-summary">
                        <!-- The panels will be added dynamically here -->
                    </div>


                    <div class="admin-card-button ml-4" v-if="otpstatus == 1">
                        <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp()">
                            Send Otp
                        </button>
                        <p>Note :- Otp Valid 2 Hours</p>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive admin-table">
                        <table v-once id="withdraw-verifiedrequest-report" class="display nowrap" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th>
                                        <input type="checkbox" name="allCheck" class="allCheck" />Select All
                                    </th>
                                    <th>Action</th>
                                    <th>User Id</th>
                                    <th>Amount</th>
                                    <th>Deduction</th>
                                    <th>Net Amount</th>
                                    <th>Network Mode</th>
                                    <th>Wallet</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="card px-4">
                    <div class="row" style="padding-bottom: 15px">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="col-md-2">
                                    <h4>Remark:</h4>
                                </div>
                                <div>
                                    <div class="col-md-4">
                                        <textarea class="form-control rounded-0" id="remark" placeholder="Enter remark here" rows="3"
                                            v-model="remark_btm"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group" v-if="otpstatus == 1">
                                <div class="col-md-2">
                                    <h4>Otp:</h4>
                                </div>
                                <div>
                                    <div class="col-md-4">
                                        <input class="form-control" required="" placeholder="OTP" type="text"
                                            v-model="otp_btm" name="otp" aria-required="" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-info waves-effect waves-light"
                                    onclick="onMakePaymentClick()">
                                    Make Payment
                                </button>
                            </div>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" class="fa fa-times pt-3 pr-3"></span>
                    </button>
                    <h5 class="modal-title" id="exampleModalLabel">Add Remark</h5>
                </div>
                <div class="modal-body">
                    <div class="row form-group">
                        <div class="col-md-2">
                            <label>Remark</label>
                        </div>
                        <div class="col-md-10">
                            <textarea class="form-control" v-model="remark" name="remark" id="remark"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-right">
                    <button type="button" class="btn btn-info" onclick="withdrawalReject()">Submit</button>
                    <button type="button" class="btn" data-dismiss="modal" aria-label="Close">Cancel</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="add-otp-modal">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" class="fa fa-times"></span>
                    </button>
                    <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                </div>
                <div class="modal-body">
                    <div class="row form-group">
                        <div class="col-md-2">
                            <label>Enter OTP</label>
                        </div>
                        <div class="col-md-10">
                            <input type="text" class="form-control" v-model="admin_otp" name="admin_otp"
                                id="admin_otp" required />
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-right">
                    <button type="button" class="btn btn-info" onclick="onMakePaymentClick()">
                        Submit
                    </button>
                    <button type="button" class="btn" data-dismiss="modal" aria-label="Close">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="approve-Pin" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <form class="clearForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Approve Request</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    
                                    <input class="form-control" required="" placeholder="request Number"
                                        type="text" name="pinRequestIdForApprovePin" id="pinRequestIdForApprovePin" readonly>
                                </div>
                                <div class="form-group">
                                    <label>OTP</label>
                                    <input class="form-control" required="" placeholder="OTP" type="text"
                                        name="otp" id="otp">
                                </div>
                                <div class="form-group">
                                    <label>Remark</label>
                                    <textarea class="form-control" required="" placeholder="Enter remark" type="text" name="remarkForApprovePin" id="approveRemark"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary waves-effect waves-light"
                            data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary waves-effect waves-light" onclick="approvePin()" id="submitBtn"
                            disabled>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        var arrayForSelectedCheckbox = [];
        $(document).ready(function() {
            withdrawRequestReport();
        });

        function withdrawRequestReport() {
            let i = 1;
            var csrf_token = "{{ csrf_token() }}";

            setTimeout(function() {
                const table = $("#withdraw-verifiedrequest-report").DataTable({
                    responsive: true,
                    retrieve: true,
                    destroy: true,
                    processing: false,
                    serverSide: true,
                    stateSave: false,
                    ordering: false,
                    dom: "Brtip",
                    lengthMenu: [
                        [10, 50, 100],
                        [10, 50, 100]
                    ],
                    buttons: [
                        // "excelHtml5",
                        "pageLength"
                    ],
                    ajax: {
                        url: '{{ url('admin/getwithdrwalverified') }}',
                        type: "POST",
                        headers: {
                            // 'X-CSRF-TOKEN': csrf_token
                            "X-CSRF-Token": $("meta[name='csrf-token']").attr("content")
                        },
                        data: function(d) {
                            i = 0;
                            i = d.start + 1;
                            let params = {
                                id: $("#user_id").val(),
                                frm_date: $("#frm_date").val(),
                                to_date: $("#to_date").val()
                            };
                            Object.assign(d, params);
                            return d;
                        },
                        dataSrc: function(json) {
                            //console.log(json.data.records[0].sr_no);
                            if (json.code === 200) {
                                let arrGetHelp = json.data.records;
                                json["recordsFiltered"] = json.data.recordsFiltered;
                                json["recordsTotal"] = json.data.recordsTotal;
                                return json.data.records;
                            } else if (json.code === 401 || json.code === 403) {
                                localStorage.removeItem("admin_token");
                                location.reload();
                            } else {
                                json["recordsFiltered"] = 0;
                                json["recordsTotal"] = 0;
                                return json;
                            }
                        }
                    },
                    columns: [{
                            render: function() {
                                return i++;
                            },
                        },
                        {
                            render: function(data, type, row) {
                                return "<input type='checkbox' class='myCheck' value='" + row
                                    .sr_no + "'>";
                            }
                            
                        },
                        {
                            render: function(data, type, row) {
                                return "<a href='javaScript:void(0);' class='text-info waves-effect manualapproverequest' data-id='"+row.sr_no+"'>M-Approve</a> <br>"+
                                       "<a href='javaScript:void(0);' class='text-danger waves-effect changestatus' data-id='"+row.sr_no+"' data-status='"+row.status+"'>Reject</a>";
                            }
                            
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.user_id + "</span>";
                            }
                            
                        },
                        {
                            data: {
                                amount: "amount",
                                deduction: "deduction"
                            },
                            render: function(data) {
                                return `${Number(data.amount) + Number(data.deduction)}`;
                            },
                        },
                        {
                            data: {
                                deduction: "deduction"
                            },
                            render: function(data) {
                                return `$${data.deduction}`;
                            },
                        },
                        {
                            data: {
                                amount: "amount"
                            },
                            render: function(data) {
                                return `$${data.amount}`;
                            },
                        },
                        {
                            data: "network_type"
                        },
                        {
                            data: "withdraw_type"
                        },
                        {
                            data: "to_address"
                        },
                        {
                            data: "status"
                        },
                        {
                            data: "entry_time",
                            render: function(data) {
                                if (data === null || data === undefined || data === "") {
                                    return '-';
                                } else {
                                    // return moment(String(data)).format('YYYY-MM-DD');
                                    return data;
                                }
                            },
                        },
                    ],

                });

                $("#onSearchClick").click(function() {
                    var startDate = $("#frm_date").val();
                    var endDate = $("#to_date").val();
                    if (endDate < startDate) {
                        toastr.error("To date should be greater than from date");
                        return false;
                        // alert("To date should not less than from date ");
                    }
                    table.ajax.reload();
                });

                $(document).on('click', '#withdraw-verifiedrequest-report .changestatus', function() {
                    changeStatus($(this).data('id'), $(this).data('status'));
                });


                $(document).on('click', '#withdraw-verifiedrequest-report .manualapproverequest', function() {
                    var data = this.getAttribute('data-id');
                    $('#pinRequestIdForApprovePin').val(data);
                    $('#pinRequestIdForApprovePin').hide();
                    ApprovePayment(data, table);
                });


                $(document).on('click', '#withdraw-verifiedrequest-report .approverequest', function() {
                    showOTPPopup($(this).data('id'));
                });


                $(document).on('click', '#withdraw-verifiedrequest-report .myCheck', function() {
                    $('.allCheck').prop('checked', false);
                    if ($(this).is(':checked')) {
                        arrayForSelectedCheckbox.push($(this).data('id'));
                    } else {
                        arrayForSelectedCheckbox.splice(arrayForSelectedCheckbox.indexOf($(this)
                            .data('id')), 1);
                    }
                });


                $(document).on('click', '#withdraw-verifiedrequest-report .allCheck', function() {
                    arrayForSelectedCheckbox = [];
                    if ($(this).is(':checked')) {
                        $('input[type="checkbox"].myCheck').prop('checked', true);
                        $('.myCheck').each(function() {
                            arrayForSelectedCheckbox.push($(this).data('id'));
                        });
                    } else {
                        $('input[type="checkbox"].myCheck').prop('checked', false);
                        $('.myCheck').each(function() {
                            arrayForSelectedCheckbox.splice(
                                arrayForSelectedCheckbox.indexOf($(this).data('id')),
                                1
                            );
                        });
                    }
                });


            });
        }


        function exportToExcel() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                id: $("#user_id").val(),
                frm_date: $("#frm_date").val(),
                to_date: $("#to_date").val(),
                action: "export",
                responseType: "blob",
            };

            $.ajax({
                url: '{{ url('admin/getwithdrwalverified') }}',
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                data: data,
                dataType: 'json',
                success: function(resp) {
                    var mystring = resp.data.data;
                    var myblob = new Blob([mystring], {
                        type: 'text/plain'
                    });

                    var fileURL = window.URL.createObjectURL(new Blob([myblob]));
                    var fileLink = document.createElement('a');

                    fileLink.href = fileURL;
                    fileLink.setAttribute('download', 'Verify-Withdrawal-Report.xls');
                    document.body.appendChild(fileLink);

                    fileLink.click();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }

        $("#onResetClick").click(function() {
            $("#searchForm").trigger("reset");
            var startDate = $("#frm_date").val("");
            var endDate = $("#to_date").val("");
            var user_id = $("#user_id").val("");
            $('#withdraw-verifiedrequest-report').DataTable().ajax.reload();
        });

        function sendAdminOtp() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                type: 1
            };
            $.ajax({
                url: "{{ url('admin/send-admin-otp') }}",
                type: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(resp) {
                    if (resp.code === 200) {
                        $('#add-otp-modal').modal('show');
                        toastr.success(resp.message);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while sending admin OTP.');
                }
            });
        }

        function getWithdrawalSummary() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                status: 1,
                verify_status: 1
            };
            $.ajax({
                type: "POST",
                url: "{{ url('admin/getwithdrawal') }}",
                data: data,
                dataType: "json",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(resp) {
                    if (resp.code === 200) {
                        this.withdrawsummary = resp.data;
                    } else {
                        toastr.error(resp.message);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }

        function onMakePaymentClick() {
            console.log(arrayForSelectedCheckbox);
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                request_id: this.arrayForSelectedCheckbox,
                remark: this.remark_btm,
                otp: this.admin_otp,
            };

            $.ajax({
                type: "POST",
                url: "{{ url('admin/confirmWithdrawl') }}",
                data: JSON.stringify(data),
                contentType: "application/json",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(resp) {
                    if (resp.code === 200) {
                        window.$("#add-otp-modal").modal("hide");
                        this.remark_btm = "";
                        this.admin_otp = "";
                        this.$router.push({
                            name: 'confirmWithdrawl'
                        });
                        toastr.success(resp.message);
                        $("#withdraw-verifiedrequest-report").DataTable().ajax.reload();
                    } else {
                        this.remark_btm = "";
                        this.admin_otp = "";
                        toastr.error(resp.message);
                    }
                },
                error: function(err) {
                    this.remark_btm = "";
                    this.admin_otp = "";
                    toastr.error(err);
                }
            });
        }


        function getAdminOtpstatus() {
            $.ajax({
                url: "{{ url('admin/GetAdminOtpStatus') }}",
                type: 'GET',
                success: function(resp) {
                    if (resp.code === 200) {
                        this.otpstatus = resp.data.withdraw_verfied_otp_update_status;
                        toastr.success(resp.message);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(err) {
                    console.log(err);
                }
            });
        }

        function withdrawalConfirm() {
            var csrf_token = "{{ csrf_token() }}";
            var isdisabled = true;
            var data = {
                request_id: sr_no,
                admin_otp: admin_otp,
            };

            $.ajax({
                type: 'POST',
                url: "{{ url('admin/confirmWithdrawl') }}",
                data: data,
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(resp) {
                    if (resp.code == 200) {
                        $('#toast-success').text(resp.message);
                        $('#toast-success').show();
                        table.ajax.reload();
                    } else {
                        $('#toast-error').text(resp.message);
                        $('#toast-error').show();
                    }
                    sr_no = "";
                    admin_otp = "";
                    $('.close').trigger('click');
                    $('.close').trigger('click');
                },
                error: function(err) {
                    $('#toast-error').text('Error occurred while processing your request.');
                    $('#toast-error').show();
                }
            });
        }





        function changeStatus(id, status = "2") {
            var csrf_token = "{{ csrf_token() }}";
            var sr_no = id;
            var status = status;

            new Swal({
                title: "Are you sure?",
                text: `You want to reject this request`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.value) {
                    $("#add-remark-modal").modal('show');

                    // Make Ajax request
                    $.ajax({
                        type: "POST",
                        url: "{{ url('admin/changeUserWithdrawStatus') }}",
                        data: {
                            sr_no: id,
                            status: status
                        },
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        success: function(resp) {
                            if (resp.code == 200) {
                                toastr.success(resp.message);
                            } else {
                                toastr.error(resp.message);
                            }
                        },
                        error: function() {
                            toastr.error("An error occurred while processing your request.");
                        }
                    });
                }
            });
        }


        function rejectWithdraw() {
            var csrf_token = "{{ csrf_token() }}";
            this.isdisabled = true;
            var data = {
                sr_no: this.sr_no,
                remark: this.remark,
            };

            $.ajax({
                url: "{{ url('admin/reject/withdrwalrequest') }}",
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: (resp) => {
                    if (resp.code == 200) {
                        toastr.success(resp.message);
                        window.$("#add-remark-modal").modal("hide");
                        this.$router.push({
                            name: 'admin/reject/withdrwalrequest'
                        });
                        this.table.ajax.reload();
                    } else {
                        toastr.error(resp.message);
                    }
                    this.sr_no = "";
                    this.remark = "";
                    $(".close").trigger("click");
                    $(".close").trigger("click");
                },
                error: (err) => {
                    console.error(err);
                }
            });
        }

        function onManualApprove() {

            $('#pinRequestIdForApprovePin').val(arrayForSelectedCheckbox);
            var data = {
                id: this.pinRequestIdForApprovePin,
                remark: this.remarkForApprovePin,
                otp: this.otp,
            };
            $.ajax({
                url: "{{ url('admin/approve/withdrawalrequest') }}",
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.code === 200) {
                        window.$("#approve-Pin").modal("hide");
                        this.$router.push({
                            name: 'admin/approve/withdrawalrequest'
                        });
                        toastr.success(response.message);
                        this.tbl.ajax.reload();
                    } else {
                        toastr.error(response.message);
                        this.errmessage = response.message;
                    }
                },
                error: function(error) {
                    toastr.error(error.response.message);
                }
            });
        }

        function ApprovePayment(data1) {
            var sr_no = data1.sr_no;
            var pinRequestIdForApprovePin = sr_no;
            var data = {
                type: 1
            };
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                }
            });
            $.ajax({
                url: "{{url('/admin/send-otp-withdraw-mail')}}",
                type: 'POST',
                data: data,
                success: function(resp) {
                    if (resp.code === 200) {
                        $('#submitBtn').removeAttr('disabled');
                        window.$("#approve-Pin").modal("show");
                        
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(error) {
                    toastr.error(error.response.message);
                }
            });
        }


        function approvePin()
        {
            var otp = $("#otp").val();
                            var approveData = {
                                id: $('#pinRequestIdForApprovePin').val(),
                                remark: $("#approveRemark").val(),
                                otp: otp,
                            };
                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': csrf_token
                                }
                            });
                            $.ajax({
                                url: "{{url('/admin/approveWithdraw')}}",
                                type: 'POST',
                                data: approveData,
                                success: function(response) {
                                    if (response.code === 200) {
                                        window.$("#approve-Pin").modal("hide");
                                        this.$router.push({
                                            name: 'confirmwithdrawreport'
                                        });
                                        toastr.success(response.message);
                                        this.tbl.ajax.reload();
                                    } else {
                                        toastr.error(response.message);
                                        this.errmessage = response.message;
                                    }
                                },
                                error: function(error) {
                                    toastr.error(error.response.message);
                                }
                            });
        }


        function checkUserExisted(username) {

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
                            var fullname = $("#fullname");
                            var user_id = resp.data.id;
                            fullname.val(resp.data.fullname);
                            fullname.addClass('d-block');
                            fullname.removeClass('d-none');
                            fullname.removeClass('text-danger');
                            fullname.addClass('text-success');
                            var isAvialable = $("#isAvialable").html("User");

                            toastr.success(resp.message);
                        } else {
                            var fullname = $("#fullname");
                            var user_id = "";
                            var isAvialable = $("#isAvialable").html("User");
                            fullname.val("Not available");
                            fullname.addClass('d-block');
                            fullname.removeClass('d-none');
                            fullname.addClass('admin-form-control');
                            fullname.addClass('text-danger');
                            fullname.removeClass('text-success');
                            toastr.error(resp.message);
                        }

                    },
                    error: (err) => {
                        //   toastr.error(err)
                    }
                });

            }
        }
    </script>
@endsection
