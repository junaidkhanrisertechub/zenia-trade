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
                                                <p></p>
                                                <span></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label id="isAvialable"></label>
                                                <input id="fullname" class="admin-form-control d-none">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="text-center">
                                                <button type="button" class="btn btn-primary waves-effect waves-light ml-4"
                                                    id="onSearchClick">
                                                    Search
                                                </button>
                                                <button type="button" class="btn btn-info waves-effect waves-light ml-4"
                                                    onclick="exportToExcel()">
                                                    Export To Excel
                                                </button>
                                                <button type="button" class="btn btn-dark waves-effect waves-light ml-4"
                                                    id="onResetClick">
                                                    Reset
                                                </button>
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


                <div class="admin-card-body">
                    <div class="table-responsive admin-table">
                        <table id="withdraw-request-report" class="display nowrap" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th><input type="checkbox" id="allCheck" />Select All</th>
                                    <th>User Id</th>
                                    <th>Amount</th>
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
                <div class="row" style="padding: 15px 25px">

                    <div class="col-md-12 px-4">
                        <div class="col-md-2"></div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-info waves-effect waves-light" onclick="showOTPPopup()">
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
                            <input type="text" class="form-control" v-model="admin_otp" name="admin_otp" id="otp_btm"
                                required />
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-right">
                    <button type="button" class="btn btn-info" onclick="withdrawalVerify()">
                        Submit
                    </button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal" aria-label="Close"
                        onclick="closeOTPPopup()">
                        Cancel
                    </button>
                </div>
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
            // let i = 1;
            var csrf_token = "{{ csrf_token() }}";

            setTimeout(function() {
                const table = $("#withdraw-request-report").DataTable({
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
                        url: '{{ url('admin/getwithdrwalverify') }}',
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: function(d) {
                            i = 0;
                            i = d.start + 1;
                            let params = {
                                user_id: $("#user_id").val(),
                                frm_date: $("#frm_date").val(),
                                to_date: $("#to_date").val()
                            };
                            Object.assign(d, params);
                            return d;
                        },
                        dataSrc: function(json) {
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
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<input type='checkbox' class='myCheck' value='" + row
                                    .sr_no + "'>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.user_id + "</span><span>(" + row.fullname +
                                    ")</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>$" + Number(row.amount) + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>$" + (Number(row.amount)) - (Number(row.deduction)) +
                                    "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.network_type + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.withdraw_type + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.to_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                if (row.ip_address === null || row.ip_address === undefined || row
                                    .ip_address === '') {
                                    return "-";
                                } else {
                                    return row.ip_address;
                                }
                            }
                        },
                        {
                            render: function(data, type, row) {
                                if (row.entry_time === null || row.entry_time === undefined || row
                                    .entry_time === '') {
                                    return "-";
                                } else {
                                    return row.entry_time;
                                }
                            }
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

                $("#withdraw-request-report tbody").on("click", ".myCheck", function() {
                    $("#allCheck").prop("checked", false);

                    if ($(this).is(":checked")) {
                        // Add the value of the 'data-id' attribute to the arrayForSelectedCheckbox array
                        arrayForSelectedCheckbox.push($(this).val());
                        
                    } else {
                        // Remove the value of the 'data-id' attribute from the arrayForSelectedCheckbox array
                        arrayForSelectedCheckbox.splice(arrayForSelectedCheckbox.indexOf($(this)
                            .val()), 1);
                            //console.log($(this).data("id"));
                    }
                });

                $("#withdraw-request-report thead").on("click", "#allCheck", function() {
                    

                    if ($("#allCheck").is(":checked")) {
                        $('input[type="checkbox"].myCheck').prop("checked", true);
                        $(".myCheck").each(function() {
                            arrayForSelectedCheckbox.push($(this).val());
                            //console.log(arrayForSelectedCheckbox);
                        });
                    } else {
                        $('input[type="checkbox"].myCheck').prop("checked", false);
                        $(".myCheck").each(function() {
                            arrayForSelectedCheckbox.splice(arrayForSelectedCheckbox
                                .indexOf($(this).val()), 1);
                                //console.log(arrayForSelectedCheckbox);
                        });
                    }
                });

            }, 0);
        }

        function showOTPPopup() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                user_id: 'TOPADMIN'
            };
            $.ajax({
                url: "{{ url('admin/send-otp') }}",
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.code === 200) {
                        toastr.success(resp.message);
                        $("#add-remark-modal").modal("show");
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    toastr.error('An error occurred while processing your request. Please try again later.');
                }
            });
        }

        var admin_otp = '';
        var otp_btm = '';


        function closeOTPPopup() {
            admin_otp = '';
            otp_btm = '';
            $("#add-remark-modal").modal("hide");
        }

        function withdrawalVerify() {
            console.log(arrayForSelectedCheckbox);
            var csrf_token = "{{ csrf_token() }}";
            otp_btm = $("#otp_btm").val();
            admin_otp = otp_btm;
            if (this.otp_btm !== "") {
                var data = {
                    request_id: arrayForSelectedCheckbox,
                    admin_otp: $("#otp_btm").val(),
                    otp: admin_otp,
                };

                $.ajax({
                    type: "POST",
                    url: "{{ url('admin/verify/withdrwalrequest') }}",
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    success: function(resp) {
                        if (resp.code === 200) {
                            toastr.success(resp.message);
                            $("#add-remark-modal").modal("hide");
                            $router.push({
                                name: "withdrwalrequest"
                            });
                        } else {
                            toastr.error(resp.message);
                            $("#add-remark-modal").modal("hide");
                            admin_otp = "";
                            otp_btm = "";
                        }
                        $(".close").trigger("click");
                        $(".close").trigger("click");
                    },
                    error: function() {
                        // Handle error here
                    }
                });

            } else {
                toastr.error("OTP is required");
            }
        }


        function exportToExcel() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                user_id: $("#user_id").val(),
                frm_date: $("#frm_date").val(),
                to_date: $("#to_date").val(),
                action: "export",
                responseType: "blob",
            };

            $.ajax({
                url: '{{ url('admin/getwithdrwalverify') }}',
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
                    fileLink.setAttribute('download', 'Pending-Withdrawal-Report.xls');
                    document.body.appendChild(fileLink);

                    fileLink.click();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }

        function CloseModal() {
            $("#add-remark-modal").modal("hide");
        }

        $("#onResetClick").click(function() {
            $("#searchForm").trigger("reset");
            var startDate = $("#frm_date").val("");
            var endDate = $("#to_date").val("");
            var user_id = $("#user_id").val("");
            $('#withdraw-request-report').DataTable().ajax.reload();
        });

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
