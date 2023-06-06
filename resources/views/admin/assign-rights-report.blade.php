@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-12 mx-auto">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Assign Rights Report</h4>
                </div>
                <div class="admin-card-body">
                    <div class="col-md-6 col-md-offset-3 col-xs-12">
                        <div class="form-group">
                            <label>Select Sub Admin</label>
                            <select class="admin-form-control" name="department" id="subadmin-select">
                                {{-- <option selected="" value="">Select SubAdmin</option> --}}
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <div class="admin-card d-none" id="adminCard">
        <div class="admin-card-header">
            <h4 class="card-title">Sub Admin Details</h4>
        </div>
        <div class="admin-card-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-offset-3 col-md-10">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong>Name</strong>
                                        </td>
                                        <td id="fullname"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <strong>Mobile No</strong>
                                        </td>
                                        <td id="mobile"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <strong>Email Id</strong>
                                        </td>
                                        <td id="email"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <strong>Sub Admin ID</strong>
                                        </td>
                                        <td id="userId"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="admin-card d-none" id="adminCard2">
        <div class="admin-card-header">
            <h4 class="card-title">Sub Admin Rights</h4>
        </div>
        <div class="admin-card-body">
            <div class="col-md-offset-3 col-md-12">
                <form>
                    <div class="row" id="parentMenus">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered navTable">
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>





    <script>
        $(document).ready(function() {
            getSubadmins();
        });

        function getSubadmins() {
            $.ajax({
                url: '{{ url('admin/getsubadmins') }}',
                type: 'GET',
                dataType: 'json',
                success: function(resp) {

                    if (resp.code === 200) {
                        var arrSubadmins = resp.data;
                        var subadminSelect = document.getElementById('subadmin-select');
                        subadminSelect.innerHTML = '<option selected="" value="">Select SubAdmin</option>';
                        arrSubadmins.forEach((Subadmins) => {
                            const option = document.createElement('option');
                            option.value = Subadmins.id;
                            option.textContent = Subadmins.fullname;
                            subadminSelect.appendChild(option);
                        });
                    }
                },
                error: function(err) {
                    $.toast({
                        text: err.responseText,
                        type: 'success'
                    }); 
                }
            });
        }

        $(document).ready(function() {
            jQuery('#subadmin-select').change(function() {
                var csrf_token = "{{ csrf_token() }}";
                var selctedUserId = $(this).val();
            
                $.ajax({
                    url: '{{ url('admin/getsubadminsdetails') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    data: {
                        id: selctedUserId
                    },
                    success: function(response) {
                        var subadminDetails = null;
                        if (response.code === 200) {
                            var data = response.data;
                            // console.log(data);
                            if (data.length > 0) {
                                $("#fullname").html(data[0].fullname);
                                $("#mobile").html(data[0].mobile);
                                $("#email").html(data[0].email);
                                $("#userId").html(data[0].user_id);
                                $("#adminCard").addClass('d-block');
                                $("#adminCard").removeClass('d-none');
                                $("#adminCard2").addClass('d-block');
                                $("#adminCard2").removeClass('d-none');
                                getSubadminNavigation();
                            } else {
                                $("#adminCard").removeClass('d-block');
                                $("#adminCard").addClass('d-none');
                                toastr.error(response.message);
                            }

                        } else {
                            toastr.error(response.message);
                        }
                    }.bind(this),
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });

                function getSubadminNavigation() {
                    var data = {
                        id: selctedUserId,
                        status: "active"
                    };
                    $.ajax({
                        type: "GET",
                        url: '{{ url('admin/getsubadminnavigation') }}',
                        data: data,
                        success: function(resp) {
                            if (resp.code === 200) {
                                arrSubadminsNavigations = resp.data.navigations;
                                
                                updateSubadminNavigation();
                            } else {
                                alert(resp.message);
                            }
                        },
                        error: function(err) {
                            alert(err);
                        }
                    });
                }

                function updateSubadminNavigation() {
                
                    var table = $("table.table.table-striped.table-bordered.navTable tbody");
                    
                    table.empty();
                    
                    for (var i = 0; i < arrSubadminsNavigations.length; i++) {
                        var parent = arrSubadminsNavigations[i].parentmenu;
                        var childmenu = arrSubadminsNavigations[i].childmenu;
                        var row = "<tr><td><strong>" + parent.parent_menu + "</strong></td><td></td></tr>";
                        for (var j = 0; j < childmenu.length; j++) {
                            var child = childmenu[j];
                            row += "<tr><td>" + child.menu + "</td><td>" + child.is_assign + "</td></tr>";
                        }
                        table.append(row);
                    }
                }
            })
        });
    </script>
@endsection
