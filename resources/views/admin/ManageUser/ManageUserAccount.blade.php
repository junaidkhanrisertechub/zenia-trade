@extends('layouts.user_type.admin-app')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="card-title">Manage user Account</h4>
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
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>User Id</label>
                                            <input class="admin-form-control" placeholder="Enter user id" type="text"
                                                id="user_id" v-model="username"
                                                onblur="checkUserExistedNew(1, this.value)" />
                                            <!-- <p
                              :class="{
                                'text-success': isAvialable == 'Available',
                                'text-danger': isAvialable == 'Not Available',
                              }"
                              v-if="isAvialable != '' && username != ''"
                            >

                            </p>
                            <span
                              :class="{
                                'text-success': isAvialable == 'Available',
                                'text-danger': isAvialable == 'Not Available',
                              }"
                              v-if="isAvialable == 'Available'"
                            >
                            username fullname</span
                            > -->
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Sponsor ID</label>
                                            <input class="admin-form-control" placeholder="Enter Sponsor  ID"
                                                type="text" id="sponser_user_id" v-model="username2"
                                                onblur="checkUserExistedNew(2, this.value)" />
                                            <!-- <p
                              :class="{
                                'text-success': isAvialable2 == 'Available',
                                'text-danger': isAvialable2 == 'Not Available',
                              }"
                              v-if="isAvialable2 != '' && username2 != ''"
                            >
                            isAvialable2
                            </p>
                            <span
                              :class="{
                                'text-success': isAvialable2 == 'Available',
                                'text-danger': isAvialable2 == 'Not Available',
                              }"
                              v-if="isAvialable2 == 'Available'"
                            >
                            username2 fullname2</span
                            > -->
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Status</label>
                                            <select class="admin-form-control" id="status">
                                                <option value="">Select status</option>
                                                <option value="">All</option>
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
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
                                " onclick="exportToExcel()">
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
            <div class="admin-card-body">
                <div class="table-responsive admin-table">
                    <table v-once id="manage-user-report" class="display nowrap" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sr.No</th>
                                <th>Click to login</th>
                                <th>User Id</th>
                                <th>Full Name</th>
                                <th>Mobile</th>
                                <th>Country</th>
                                <th>Email</th>
                                <!-- <th>paypal address</th> -->
                                <th>Sponsor</th>
                                <!-- <th>Position</th> -->
                                <th>Entry Date</th>
                                <th>Status</th>
                                <th>Withdraw Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var base_url = '{{url('/')}}'
var csrftoken = $('meta[name="csrf-token"]').attr('content');
$(document).ready(function() {

    let i = 1;
    var csrf_token = "{{ csrf_token() }}";

     var table = $("#manage-user-report").DataTable({
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
            [10, 50, 100],
        ],
        buttons: [
            // 'copyHtml5',
            /*'excelHtml5',
            'csvHtml5',
            'pdfHtml5',*/
            "pageLength",
        ],
        ajax: {
            url: "{{ url('admin/getusers') }}",
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': csrf_token
            },
            data: function (d) {
                i = 0;
                i = d.start + 1;

                let params = {
                    frm_date: $("#frm_date").val(),
                    to_date: $("#to_date").val(),
                    id: $("#user_id").val(),
                    sponser_user_id: $("#sponser_user_id").val(),
                    // product_id:$('#product_id').val(),
                    status: $("#status").val(),
                };
                Object.assign(d, params);
                return d;
            },
            dataSrc: function (json) {
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
            render: function () {
                //return meta.row + 1;
                return i++;
            },
        },
            {
                render: function (data, type, row) {
                    return `<label class="text-info waves-effect" id="login" data-id="${row.user_id}" >
                            <a data-id='${row.id}' class='org_login text-primary text-blue' title='Organization Login'>Click to login</a>
                                            </label>`;
                }

            },
            // {
            //   data: { user_id: "user_id", login_url: "login_url" },
            //   render: function (data) {
            //     return `<a target="_blank" href="${data.login_url}${data.user_id}">Click to login</a>`;
            //   }
            // },
            {
                data: "user_id"
            },

            {
                data: "fullname"
            },
            {
                data: "mobile"
            },
            {
                data: "country"
            },
            {
                data: "email"
            },
            {
                data: "sponser_id"
            },
            // { data: "position" },

            {
                data: "entry_time",
                render: function (data) {
                    if (data === null || data === undefined || data === "") {
                        return `-`;
                    } else {
                        return moment(String(data)).format("YYYY-MM-DD");
                    }
                },
            },
            {
                data: "status"
            },
            {
                render: function (data, type, row) {
                    return `<label class="${(row.withdraw_block_by_admin == 0) ? 'text-info' : 'text-warning'} waves-effect" class="changeUserWithdrawStatus" onclick="changeUserWithdrawStatus(${row.id},${row.withdraw_block_by_admin})" data-id="${row.id}" data-status="${row.withdraw_block_by_admin}">${(row.withdraw_block_by_admin == 0) ? 'ON' : 'OFF'}</label>`
                }
            },
            {
                data: {
                    id: "id",
                    status: "status"
                },
                render: function (data) {
                    return `<a class="myProfile" title="Profile" data-id="${
                        data.id
                    }">
                                                <i class="fa fa-user font-16"></i>
                                            </a>&nbsp;
                                            <a class="editmyProfile" data-id="${
                        data.id
                    }" title="Edit" href="javascript:void(0)">
                                                <i class="fa fa-pencil font-16"></i>
                                            </a><br>
                                            <label class="${
                        data.status == "Active"
                            ? "text-info"
                            : "text-warning"
                    } waves-effect" id="changeStatus" data-id="${
                        data.id
                    }" data-status="${data.status}">${
                        data.status == "Active" ? "Block" : "Unblock"
                    }
                                            </label>`;
                },
            },
        ],
    });
    $("#onSearchClick").click(function () {
        table.ajax.reload();
    });
    $("#onSearchClick").click(function () {
        var startDate = $("#frm_date").val();
        var endDate = $("#to_date").val();
        if (endDate < startDate) {
            toastr.error("To date should be greater than from date");
            return false;
            // alert("To date should not less than from date ");
        }
        table.ajax.reload();
    });

    $("#onResetClick").click(function () {
        $("#searchForm").trigger("reset");
        username = "";
        isAvialable = "";
        table.ajax.reload();
    });

    $("#manage-user-report").on("click", "#changeStatus", function () {
        changeStatus($(this).data("id"), $(this).data("status"));
    });

    // $('#manage-user-report').on('click', '#login', function () {
    //     loginnn($(this).data("id"));
    // });

    $("#manage-user-report").on("click", ".editmyProfile", function () {
        window.location.href = "{{url('/admin/user/edit-user-profile/')}}/" + $(this).data("id");
    });

    $("#manage-user-report").on("click", ".myProfile", function () {
        window.location.href = "{{url('/admin/user/user-profile/')}}/" + $(this).data("id");
    });

});
function checkUserExistedNew(para, val) {


    if (val != '') {
        if (para == 1) {
            user_id = val;
        } else {
            user_id = val;
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrftoken
            }
        });


        $.ajax({
            url: "{{url('/admin/checkuserexist')}}",
            type: "POST",
            data: {
                user_id: user_id
            },
            success: function (resp) {
                if (resp.code === 200) {
                    if (para == 1) {
                        user_id = resp.data.user_id;
                        fullname = resp.data.fullname;
                        isAvailable = "Available";
                    } else {
                        user_id2 = resp.data.user_id;
                        fullname2 = resp.data.fullname;
                        isAvailable2 = "Available";
                    }

                    toastr.success(resp.message);

                } else {
                    if (para == 1) {
                        user_id = "";
                        isAvailable = "Not Available";
                        fullname = "";
                    } else {
                        user_id2 = "";
                        isAvailable2 = "Not Available";
                        fullname2 = "";
                    }
                    toastr.error(resp.message);
                }
            },
            error: function () {
                // handle error
            }
        });
    }
}

function changeUserWithdrawStatus(id, status) {

    Swal.fire({
        title: "Are you sure?",
        text: "You want to change Withdraw status of this user",
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
                type: "POST",
                url: "{{url('/admin/changeUserWithdrawStatus')}}",
                data: {
                    id: id,
                    status: status,
                },
                success: function (resp) {
                    if (resp.code == 200) {
                        toastr.success(resp.message);
                        let table = $('#manage-user-report').DataTable();
                        table.ajax.reload();
                    } else {
                        toastr.error(resp.message)
                    }
                },
                error: function (xhr, status, error) {
                    console.error(error);
                }
            });
        }
    });
}


function changeStatus(id, status) {
    Swal.fire({
        title: "Are you sure?",
        text: "You want to change status of this user",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
    }).then((result) => {
        if (result.value) {
            var data = {
                id: id,
                status: status
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrftoken
                }
            });
            $.ajax({
                url: "{{url('/admin/blockuser')}}",
                method: "POST",
                data: data,
                success: function (resp) {
                    if (resp.code == 200) {
                        toastr.success(resp.message);
                        let table = $('#manage-user-report').DataTable();
                        table.ajax.reload();
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error(error);
                }
            });
        }
    });
}


function exportToExcel() {
    var params = {
        frm_date: $("#frm_date").val(),
        to_date: $("#to_date").val(),
        id: $("#user_id").val(),
        status: $("#status").val(),
        sponser_user_id: $("#sponser_user_id").val(),
        action: "export",
        responseType: "blob",
    };

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrftoken
        }
    });

    $.ajax({
        url: "{{ url('admin/getusers') }}",
        type: "POST",
        data: params,
        dataType: "json",
        success: function (resp) {
            if (resp.code === 200) {
                var mystring = resp.data.data;
                var myblob = new Blob([mystring], {
                    type: "text/plain",
                });

                var fileURL = window.URL.createObjectURL(new Blob([myblob]));
                var fileLink = document.createElement("a");

                fileLink.href = fileURL;
                fileLink.setAttribute("download", "AllUsers.xls");
                document.body.appendChild(fileLink);

                fileLink.click();
            } else {
                alert(resp.message);
            }
        },
        error: function (xhr, status, error) {
            alert("An error occurred while exporting data.");
        }
    });


}


function loginnn(user_id) {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrftoken
        }
    });
    $.ajax({
        type: "POST",
        url: "{{url('/admin/user_login')}}",
        data: {
            "user_id": user_id,
            "password": '123456',
        },
        success: function (resp) {
            let userinfo = resp.data;
            if (resp.code === 200) {
                const token = resp.data.access_token;
                if (userinfo.google2faauth == "TRUE") {
                    verify2fa = true;
                } else {
                    localStorage.setItem('type', "user");
                    window.open("" + resp.data.validPath + "dashboard", '_blank');
                }
            } else {
                // Handle error response
                console.error(resp.message);
            }
        },
        error: function (err) {
            // Handle error
            console.error(err);
        }
    });
}
</script>
@endsection
