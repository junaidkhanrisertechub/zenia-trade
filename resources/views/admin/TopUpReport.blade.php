@extends('layouts.user_type.admin-app')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="card-title">Top Up Report</h4>
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
                                                    <input type="date" class="admin-form-control" name="frm_date" placeholder="From Date" id="frm_date" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>To Date</label>
                                            <div>
                                                <div class="input-group">
                                                    <input type="date" class="admin-form-control" name="to_date" placeholder="To Date" id="to_date" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>User Id</label>
                                            <input class="admin-form-control" placeholder="Enter user id" type="text" id="user_id" onkeyup="checkUserExisted(this.value)" />
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="text-center">
                                                <button type="button" class="btn btn-primary  waves-effect waves-light ml-4" id="onSearchClick">
                                                    Search
                                                </button>
                                                <button type="button" class="btn btn-info waves-effect waves-light ml-4" onclick="exportToExcel()">
                                                    Export To Excel
                                                </button>
                                                <button type="button" class="btn btn-dark waves-effect waves-light ml-4" id="onResetClick">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="admin-card-body">
                <div class="table-responsive admin-table">
                    <table id="binary-income-report" class="display nowrap" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sr.No</th>
                                <th>User Id</th>
                                <th>Deposit ID</th>
                                <th>Amount</th>
                                <th>Package</th>
                                <th>Topup by</th>
                                <th>Topup Type</th>
                                <th>Topup From</th>
                                <th>ROI Status</th>
                                <th>Date</th>
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

    function exportToExcel() {
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
            url: '{{ url('admin/gettopup') }}',
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
                    fileLink.setAttribute("download", "Topupreport.xls");
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
                            $('#binary-income-report').DataTable().ajax.reload();
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
            const table = $("#binary-income-report").DataTable({
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
                    url: '{{ url('admin/gettopup') }}',
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
                        },
                    },
                    {
                        render: function(data, type, row) {
                            return "<span>" + row.user_id + "</span>";
                        },
                    },
                    {
                        data: "pin"
                    },
                    {
                        render: function(data, type, row) {
                            return "<span>" + row.amount + "</span>";
                        },
                    },
                    {
                        data: "package_type"
                    },
                    {
                        data: "top_up_by"
                    },
                    {
                        data: "top_up_type"
                    },
                    {
                        render: function(data, type, row) {
                            if (row.topupfrom == "1" || row.topupfrom == 1) {
                                return "Admin";
                            } else {
                                return row.topupfrom;
                            }
                        },
                    },
                    {
                        render: function(data, type, row) {
                            if (row.package_type != "Zero PIN") {
                                let labelClass = "";
                                let labelStatus = "";
                                if (row.roi_stop_status == 1) {
                                    labelClass = "text-info";
                                    labelStatus = "ON";
                                } else {
                                    labelClass = "text-warning";
                                    labelStatus = "OFF";
                                }
                                return (
                                    '<label class="' + labelClass + ' waves-effect" onclick="changeUserRoiStatus(' + row.srno + ')">' + labelStatus + "</label>"
                                );
                            } else {
                                return "-";
                            }
                        },
                    },
                    {
                        render: function(data, type, row) {
                            if (
                                row.entry_time === null ||
                                row.entry_time === undefined ||
                                row.entry_time === ""
                            ) {
                                return "-";
                            } else {
                                // return moment(String(row.entry_time)).format(
                                //     "YYYY-MM-DD"
                                // );
                                return row.entry_time;
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

        });
    }

    $("#onResetClick").click(function() {
        $("#searchForm").trigger("reset");
        var startDate = $("#frm_date").val("");
        var endDate = $("#to_date").val("");
        var user_id = $("#user_id").val("");
        $('#binary-income-report').DataTable().ajax.reload();
    });
</script>

@endsection