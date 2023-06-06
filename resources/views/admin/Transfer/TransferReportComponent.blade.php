@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Transfer Report</h4>
                </div>
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-primary">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-4 ml-4">
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
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>To Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input type="date" class="admin-form-control" name="to_date"
                                                            placeholder="To Date" id="to_date"
                                                            onkeyup="checkUserExisted(this.value)" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>From User ID</label>
                                                <input class="admin-form-control" placeholder="Enter from User ID"
                                                    type="text" id="from_user_id" onkeyup="checkUserExisted(this.value)" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>To User ID</label>
                                                <input class="admin-form-control" placeholder="Enter to User ID"
                                                    type="text" id="to_user_id" onkeyup="checkUserExisted(this.value)" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Transfer Type</label>
                                                <select class="admin-form-control" id="wallet-type">
                                                    <option value="">Select Transfer Type</option>
                                                    <option value="6">Fund Wallet</option>
                                                    <option value="1">ROI Wallet</option>
                                                    <option value="7">Working Wallet</option>
                                                    <option value="8">HSCC BONUS Wallet</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="text-center">
                                                    <button type="button"
                                                        class="btn btn-primary waves-effect waves-light ml-4"
                                                        id="onSearchClick">Search</button>
                                                    <button type="button"
                                                        class="btn btn-info waves-effect waves-light ml-4"
                                                        onclick="onExportClick()">Export To Excel</button>
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
                <div class="admin-card-body">
                    <div class="table-responsive admin-table">
                        <table v-once id="transfer-fund-report" class="display nowrap" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th>Date</th>
                                    <th>To User ID</th>
                                    <th>From User ID</th>
                                    <th>Amount</th>
                                    <th>Transfer Type</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function() {
            roiIncomeReport();
        });

        function dateFormat(date) {
            return moment(date).format("DD-MM-YYYY");
        }

        function disableFutureDate() {
            let today = new Date();
            let dd = today.getDate();
            let mm = today.getMonth() + 1; //January is 0!
            let yyyy = today.getFullYear();
            if (dd < 10) {
                dd = '0' + dd
            }
            if (mm < 10) {
                mm = '0' + mm
            }
            today = yyyy + '-' + mm + '-' + dd;
            $("#to_date").attr("max", today);
            $("#frm_date").attr("max", today);
        }

        function onExportClick() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                product_id: $("#product_id").val(),
                id: $("#user_id").val(),
                status: $("#status").val(),
                pin: $("#pin").val(),
                frm_date: $("#frm_date").val(),
                to_date: $("#to_date").val(),
                action: "export",
                responseType: "blob",
            };
            $.ajax({
                url: "{{ url('admin/fund_transfer_report') }}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                data: data,
                dataType: "json",
                success: function(resp) {
                    if (resp.code === 200) {
                        var mystring = resp.data.data;
                        var myblob = new Blob([mystring], {
                            type: "text/plain",
                        });

                        var fileURL = window.URL.createObjectURL(new Blob([myblob]));
                        var fileLink = document.createElement("a");

                        fileLink.href = fileURL;
                        fileLink.setAttribute("download", "Transfer-Report.xls");
                        document.body.appendChild(fileLink);

                        fileLink.click();
                    } else {
                        alert(resp.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                },
            });
        }

        function changeUserRoiStatus(id) {
            new Swal({
                title: "Are you sure?",
                text: `You want to change ROI status of this user`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.value) {
                    var csrf_token = "{{ csrf_token() }}";
                    $.ajax({
                        url: "{{ url('admin/topupchangroistop') }}",
                        type: "POST",
                        data: {
                            sr_no: id,
                            // status: status,
                        },
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        success: function(resp) {
                            if (resp.code == 200) {
                                toastr.success(resp.message);
                                $('#transfer-fund-report').DataTable().ajax.reload();
                            } else {
                                toastr.error(resp.message);
                            }
                        },
                        error: function() {
                            toastr.error("An error occurred while changing ROI status.");
                        }
                    });
                }
            });
        }

        function roiIncomeReport() {
            let i = 1;
            var csrf_token = "{{ csrf_token() }}";

            setTimeout(function() {
                const table = $("#transfer-fund-report").DataTable({
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
                        "pageLength"
                    ],
                    ajax: {
                        url: "{{ url('admin/fund_transfer_report') }}",
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: function(d) {
                            i = 0;
                            i = d.start + 1;
                            let params = {
                                from_user_id: $("#from_user_id").val(),
                                to_user_id: $("#to_user_id").val(),
                                wallet_type: $("#wallet-type").val(),
                                frm_date: $("#frm_date").val(),
                                to_date: $("#to_date").val(),
                            };
                            Object.assign(d, params);
                            return d;
                        },
                        dataSrc: function(json) {
                            if (json.code === 200) {
                                that.arrGetHelp = json.data.records;

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
                                if (row.entry_time === null || row.entry_time === undefined || row
                                    .entry_time === '') {
                                    return "-";
                                } else {
                                    // return moment(String(row.entry_time)).format("YYYY-MM-DD");
                                    return row.entry_time;
                                }
                            },
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.user_id + "(" + row.fullname + ")</span>";
                            },
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.from_user_id + "(" + row.from_fullname +
                                    ")</span>";
                            },
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>$" + row.amount + "</span>";
                            },
                        },
                        {
                            render: function(data, type, row) {
                                if (row.wallet_type == "1") {
                                    return "<span>ROI Wallet</span>";
                                } else if (row.wallet_type == "6") {
                                    return "<span>Fund Wallet</span>";
                                } else if (row.wallet_type == "7") {
                                    return "<span>Working Wallet</span>";
                                } else if (row.wallet_type == "8") {
                                    return "<span>HSCC BONUS Wallet</span>";
                                }
                            },
                        },
                        {
                            data: "remark"
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

                $('#binary-income-report').on('click', '#changeUserRoiStatus', function() {
                    that.changeUserRoiStatus($(this).data('id'), $(this).data('status'));
                });

                $("#onResetClick").click(function() {
                    $("#searchForm").trigger("reset");
                    $('#qualified-user-report').DataTable().ajax.reload();
                });


            });
        }

        function checkUserExisted(username) {

            if (username != '') {
                var data = {
                    to_user_id: username
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

        function checkUserExisted(username) {

            if (username != '') {
                var data = {
                    from_user_id: username
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
