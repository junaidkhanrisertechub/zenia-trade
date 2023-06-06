@extends('layouts.user_type.admin-app')
@section('content')

<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
<div class="page-wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
          <nav aria-label="breadcrumb ms-3">
            <!-- <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6">
              <li class="breadcrumb-item text-sm">
                <a class="opacity-5 text-dark" href="javascript:;">Pages</a>
              </li>
              <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                Binary Income

              </li>
            </ol> -->
            <h6 class="font-weight-bolder mb-0">Add Power Report</h6>
          </nav>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-md-12">
          <div class="card RepotPage">
            <div class="card-header">
              <div class="searchFormWrap position-relative">
                <form id="searchForm">
                  <div class="row align-items-center">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>From Date</label>
                        <input type="date" class="form-control" name="frm_date" :format="dateFormat"
                          placeholder="From Date" id="from-date" />
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>To Date</label>
                        <input type="date" class="form-control" name="to_date" :format="dateFormat"
                          placeholder="To Date" id="to-date" />
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Pin</label>
                        <input type="text" class="form-control" name="user_id" id="pin" placeholder="Pin" />
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="text-center searchFormButwrap">
                        <button type="button" name="signup1" value="Sign up" id="onSearchClick" class="btn btn-find">
                          Find </button>
                        <button type="button" name="signup1" value="Sign up" id="onResetClick" class="btn btn-reset">
                          Reset </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table v-once id="structure-balance-receive" class="display nowrap table-striped" style="width: 100%">
                  <thead>
                    <tr>
                      <th>Sr. No.</th>
                      <th>Date</th>
                      <th>user</th>
                      <th>fullname</th>
                      <th>power_bv</th>                     
                    </tr>
                  </thead>
                  <tbody>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<!-- 
  <script type="text/javascript">
       $(document).ready(function(){

var topUpTable = $('#structure-balance-receive').DataTable({
    processing: true,
    // serverSide: true,
    order: [0, 'DESC'],
    dom: 'Bfrtip',
    buttons: [ 'excel' ],
  
    ajax: "{{ url('/admin/manage-power/power-report')}}",
    "columns": [ 
      //return moment(String(row.entry_time)).format("YYYY-MM-DD")
      {data: 'DT_RowIndex',orderable:false, searchable: false},
       {data: 'entry_time',name:'entry_time', orderable:false, searchable: false},  
       {data: 'user', name: 'user', orderable: false, searchable: true},       
       {data: 'fullname', name: 'fullname', orderable: false, searchable: true},                          
        {data: 'before_rbv', name: 'before_rbv', orderable: false, searchable: true},
    ]

});
});

</script> -->

@endsection