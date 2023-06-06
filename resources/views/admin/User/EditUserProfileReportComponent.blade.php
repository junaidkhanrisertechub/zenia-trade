@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Edit User Profile Report</h4>
                </div>
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
                        <table id="edit-user-profile-report" class="display nowrap" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th>User Id</th>
                                    <th>New Full Name</th>
                                    <th>New Mobile</th>
                                    <th>New BTC Address</th>
                                    <th>New ETH Address</th>
                                    <th>New TRON Address</th>
                                    <th>New LTC Address</th>
                                    <th>New DOGE Address</th>
                                    <th>New SOL Address</th>
                                    <th>New USDT.TRC20 Address</th>
                                    <th>New USDT.ERC20 Address</th>
                                    <th>Updated By</th>
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

        function roiIncomeReport() {
            // let i = 1;
            var csrf_token = "{{ csrf_token() }}";

            setTimeout(function() {
                const table = $("#edit-user-profile-report").DataTable({
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
                        url: '{{ url('admin/getuserlogs') }}',
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
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
                                return "<span>" + row.user_id + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.fullname + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.mobile + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_btc_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_ethereum + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_trn_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_ltc_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_doge_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_sol_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_usdt_trc20_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.new_usdt_erc20_address + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.updated_by + "</span>";
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

            });
        }

        function changeUserRoiStatus(id) {
            new Swal({
                title: "Are you sure?",
                text: "You want to change ROI status of this user",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.value) {
                    var csrf_token = "{{ csrf_token() }}";
                    $.ajax({
                        type: "POST",
                        url: "{{ url('admin/topuproistop') }}",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: {
                            sr_no: id,
                            // status: status,
                        },
                        success: function(resp) {
                            if (resp.code == 200) {
                                toastr.success(resp.message);
                                $('#edit-user-profile-report').DataTable().ajax.reload();
                            } else {
                                toastr.error(resp.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        },
                    });
                }
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
                url: '{{ url('admin/getuserlogs') }}',
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
                    fileLink.setAttribute('download', 'Edit-User-Profile-Report.xls');
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
            $('#edit-user-profile-report').DataTable().ajax.reload();
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
