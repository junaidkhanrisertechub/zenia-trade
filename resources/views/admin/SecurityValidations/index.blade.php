@extends('layouts.user_type.admin-app')
@section('content')
<div class="row">
        <div class="col-12">
          <div class="admin-card">
            <div class="admin-card-header">
              <h4 class="card-title">Invalid Login Report</h4>
            </div>
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
                                <input
                                  type="date"
                                  class="admin-form-control"
                                  name="frm_date"
                                  oninput="chkFromToDt('from')"
                                  format="dateFormat"
                                  placeholder="From Date"
                                  id="frm_date"
                                />
                                 
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
                                <input
                                  type="date"
                                  class="admin-form-control"
                                  oninput="chkFromToDt('to')" 
                                  name="to_date"
                                  format="dateFormat"
                                  placeholder="To Date"
                                  id="to_date"
                                />
                                
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>User ID</label>
                            <input
                              class="admin-form-control"
                              placeholder="Enter User ID"
                              type="text"
                              id="user_id"
                            />
                         
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-12">
                            <div class="text-center">
                              <button
                                type="button"
                                class="
                                  btn btn-primary
                                  waves-effect waves-light
                                  ml-4
                                "
                                id="onSearchClick"
                              >
                                Search
                              </button>
                              <button
                                type="button"
                                class="btn btn-info waves-effect waves-light ml-4"
                                @click="exportToExcel">Export To Excel</button>
                              <button
                                type="button"
                                class="
                                  btn btn-dark
                                  waves-effect waves-light
                                  ml-4
                                "
                                id="onResetClick"
                                 onclick="ResetForm" 
                              >
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
                <table
                   v-once
                  id="get-login-count-report"
                  class="display nowrap"
                  style="width: 100%"
                >
                  <thead>
                    <tr>
                       <th>Sr.No</th>
                      <th>User ID</th>
                      <th>IP Address</th>
                      <th>Remark</th>
                      <th>Action</th>
                      <th>Attempted Date</th>
                    </tr>
                  </thead>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>

<script>
                $(document).ready(function() {
                    roiIncomeReport();
                });

                function roiIncomeReport() {
                    let i = 1;
                    var csrf_token = "{{ csrf_token() }}";

                    setTimeout(function() {
                        const table = $("#get-login-count-report").DataTable({
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
                                url: "{{ url('admin/getlogincountreport') }}",
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
                                { data: 'user_id' },
      { data: 'ip_address' },
      { data: 'remark' },
      { data: null, render: function(data, type, row) {
        if (row.status == 0 || row.status == 2) {
          var fun=" changeStatus('"+row.user_id+"', '"+row.status+"') ";
          return '<a id="changeStatus_'+row.status+'" class="text-info waves-effect changeStatus" data-id="' + row.user_id + '" data-status="' + row.status + '" onclick="'+fun+'">Unblock</a>';
        } else {
          return '-';
        }
      }},
      { data: 'attempted_at', render: function(data) {
        return data ? moment(data).format('YYYY-MM-DD') : '-';
      }}
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


    function changeStatus(id, status) {
 

      new Swal({
            title: "Are you sure?",
            text: `You want to change status of this user`,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes",
        }).then((result) => {
            if (result.value) {
                var csrf_token = "{{ csrf_token() }}";
                $.ajax({
                    url: "{{ url('admin/changeuserblockstatus') }}",
                    type: "POST",
                    data: {
                        id: id,
                        status: status,
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    success: function(resp) {

                        if (resp.code == 200) {
                            toastr.success(resp.message);
                            $('#get-login-count-report').DataTable().ajax.reload();
                        } else {
                            toastr.error(resp.message);
                        }
                    },
                    error: function() {
                        toastr.error("An error occurred while changing  status.");
                    }
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
                        url: "{{ url('admin/getlogincountreport') }}",
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
                            fileLink.setAttribute('download', 'Daily-Bonus-Income-Report.xls');
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
                    $('#binary-income-report').DataTable().ajax.reload();
                });
            </script>

