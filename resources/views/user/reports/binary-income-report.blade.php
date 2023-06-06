@extends('layouts.user_type.auth-app')
@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Binary Income Report</h4>
                            </div>
                            <!-- end card header -->
                            <div class="card-header">
                                <div class="searchFormWrap position-relative">
                                    <form id="searchForm">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>From Date</label>
                                                    <input type="date" class="form-control" name="frm_date"
                                                        format="dateFormat" placeholder="From Date" id="from-date">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>To Date</label>
                                                    <input type="date" class="form-control" name="to_date"
                                                        format="dateFormat" placeholder="To Date" id="to-date">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                              <div class="form-group">
                                                <label>Pin</label>
                                                <input type="text" class="form-control" name="user_id" id="pin" placeholder="Pin" />
                                              </div>
                                            </div>
                                            <div class="col-md-3 d-flex justify-content-center mt-2">
                                                <div class="searchFormButwrap">
                                                    <button type="button" name="signup1" value="Sign up" id="onSearchClick"
                                                        class="btn btn-success btn-block">
                                                        Find </button>
                                                    <button type="button" name="signup1" value="Sign up" id="onResetClick"
                                                        class="btn btn-primary btn-block">
                                                        Reset </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="table-gridjs">
                                    <div class="table-responsive">
                                        <table v-once id="structure-balance-receive" class="display nowrap table-striped"
                                            style="width: 100%">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Date</th>
                                                    <th>Designation</th>
                                                    <th>Pin</th>
                                                    <th>Amount</th>
                                                    <th>Lapse Amount</th>
                                                    <th>Left Business</th>
                                                    <th>Right Business</th>
                                                    <th>Percentage</th>
                                                    <th>Remark</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                    <!-- end col -->
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {

            var topUpTable = $('#structure-balance-receive').DataTable({
                processing: true,
                serverSide: true,
                order: [0, 'ASC'],
                dom: 'lrtip',
                buttons: [],

                ajax: "{{ url('/reports/binary-reports-data') }}",
                "columns": [
                    //return moment(String(row.entry_time)).format("YYYY-MM-DD")
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'entry_time',
                        name: 'entry_time',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'designation',
                        name: 'designation',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'pin',
                        name: 'pin',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'laps_amount',
                        name: 'laps_amount',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'left_bv',
                        name: 'left_bv',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'right_bv',
                        name: 'right_bv',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'percentage',
                        name: 'percentage',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'remark',
                        name: 'remark',
                        orderable: false,
                        searchable: true
                    },

                ]
            });
        });
    </script>
@endsection
