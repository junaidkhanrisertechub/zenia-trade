@extends('layouts.user_type.admin-app')
@section('content')


<style>

#activeUser {
    height: auto !important;
}
</style>

    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-primary admin-text-primary">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1">User</p>
                            <h4 class="mb-0" id="totalUser"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-warning admin-text-warning">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1"> Unblock Users</p>
                            <h4 class="mb-0" id="unblockUser"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-danger admin-text-danger">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1"> Block Users</p>
                            <h4 class="mb-0" id="blockUser"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-success admin-text-success">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1">Active User</p>
                            <h4 class="mb-0" id="activeUser"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-info admin-text-info">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1">Inactive User</p>
                            <h4 class="mb-0" id="inActiveUser"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-warning text-warning">
                                <i class="fa-solid fa-money-bill-transfer"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1">Pending Withdrawal</p>
                            <h4 class="mb-0" id="pendingWithdrawls"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-warning text-warning">
                                <i class="fa-solid fa-money-bill-transfer"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1">Confirmed Withdrawal</p>
                            <h4 class="mb-0" id="confirmedWithdrawl"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-dark admin-text-dark">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1"> Total account wallet balance</p>
                            <h4 class="mb-0" id="totalAccountWalletBalance"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-pink admin-text-pink">
                                <i class="fa-solid fa-piggy-bank"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1"> Total Income </p>
                            <h4 class="mb-0" id="totalIncome"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-pink admin-text-pink">
                                <i class="fa-solid fa-piggy-bank"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1"> Direct Income </p>
                            <h4 class="mb-0" id="directIncome"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="widget-stat admin-card">
                <div class="admin-card-body p-4">
                    <div class="media ai-icon">
                            <span class="me-3 admin-bgl-pink admin-text-pink">
                                <i class="fa-solid fa-piggy-bank"></i>
                            </span>
                        <div class="media-body">
                            <p class="mb-1"> Binary Income </p>
                            <h4 class="mb-0" id="binaryIncome"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var base_url = '{{url('/')}}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        $(document).ready(function() {

            getDashboardData()
        });
        function getDashboardData() {
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
          ////  var frm_date = ($('#frm_date').val() !== '') ? moment($('#frm_date').val()).format('DD-MM-YYYY') : '';
           // var to_date = ($('#to_date').val() !== '') ? moment($('#to_date').val()).format('DD-MM-YYYY') : '';
            var id = $('#hiddenUserId').val();
            var user_id = $('#user_id').val();

            var data = {
               // frm_date: frm_date,
               // to_date: to_date,
                id: id,
                user_id: user_id,
            };
            $.ajax({
                type: "POST",
                url: "{{url('/admin/dashboard-data')}}",
                data: data,
                headers: {

                    'X-CSRF-TOKEN': csrf_token

                },
                success: function(response){


                    console.log(response);
                    $('#totalUser').text(response.data.total_users);
                    $('#unblockUser').text(response.data.total_unblock_users);
                    $('#blockUser').text(response.data.total_block_users);
                    $('#activeUser').text(response.data.total_active_user);
                    $('#inActiveUser').text(response.data.total_inactive_user);
                    $('#pendingWithdrawls').text(response.data.total_withdraw_pending);
                    $('#confirmedWithdrawl').text(response.data.total_withdraw_confirm);
                    $('#totalAccountWalletBalance').text(response.data.total_balance);
                    $('#totalIncome').text(response.data.total_income);
                    $('#directIncome').text(response.data.total_direct_income);
                    $('#binaryIncome').text(response.data.total_binary_income);
                }
            });
        }

    </script>
@endsection
