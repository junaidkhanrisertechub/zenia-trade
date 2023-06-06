@extends('layouts.user_type.auth-app')

@section('content')
    @php
        $userdata = $arrData['user'];
        $tree_data = $arrData['tree_data'];
        $referrallink = url('/sign-up');
    @endphp




    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="PageTitle">
                            <h2>Tree View</h2>
                        </div>
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="treeintro">
                            <div>
                                <img src="{{ asset('user-assets/images/present.png') }}">
                                <span>Active</span>
                            </div>
                            <div>
                                <img src="{{ asset('user-assets/images/no_topup.png') }}">
                                <span>Inactive</span>
                            </div>
                            <div>
                                <img src="{{ asset('user-assets/images/absent.png') }}">
                                <span>Absent</span>
                            </div>
                            <div>
                              <img src="{{ asset('user-assets/images/block.png') }}">
                              <span>Block</span>
                          </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="searchflexinto">
                            <div class="input-group">
                                <input type="search" class="form-control" placeholder="Search" id="searchuserid"
                                    onkeyup="checkUserExisted()">
                                <span class="btn btn-primary" id="btnsearch" onclick="onSearchClick()">
                                    <i class="fa fa-search"></i>
                                </span>
                            </div>
                            <button class="btn btn-primary ms-3" onclick="onBackClick()">
                                <i class="fa fa-arrow-left"></i>
                            </button>
                            <button class="btn btn-primary ms-3" onclick="onForwardClick()">
                                <i class="fa fa-arrow-right"></i>
                            </button>
                            <button class="btn btn-primary ms-3" onclick="goToTopIdClick()">
                                <i class="fa fa-arrow-up"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="Doxy-Tree">
                            <div class="row midline">
                                <div class="col-12">
                                    <div class="tree-level1">
                                        <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                            id="myTooltip1" class="mytooltip">
                                            <img src="{{ asset('user-assets/images/'.$userdata->image) }}"
                                                onclick="getMatrixTreeData('{{ $userdata->user_id }}')">
                                            <div class="Doxy-Tree-tooip-text">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <img src="{{ asset('user-assets/images/'.$userdata->image) }}"
                                                            class="tipimg">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="tph">
                                                            <h1>
                                                                {{ $userdata->fullname }}
                                                                &nbsp; / &nbsp;
                                                                {{ $userdata->user_id }}
                                                            </h1>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-12">
                                                        <div class="whiteBox">
                                                            <div>
                                                                <p>Package : {{ $userdata->package1 }}</p>
                                                                <p>Sponsor Id : {{ $userdata->sponsor_id }} </p>
                                                                {{-- <p>Placement Id : AE345j79</p> --}}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <table class="table whitetable">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Factors</th>
                                                            <th scope="col">Left Side</th>
                                                            <th scope="col">Right Side</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">Affiliates</th>
                                                            @if ($userdata->l_d_count == 'Absent')
                                                                <td align="center">Absent</td>
                                                                <td align="center">Absent</td>
                                                            @else
                                                                <td align="center">{{ $userdata->l_d_count }}</td>
                                                                <td align="center">{{ $userdata->r_d_count }}</td>
                                                            @endif
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">sales</th>
                                                            <td>{{ $userdata->l_bv }}</td>
                                                            <td>{{ $userdata->r_bv }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">balance</th>
                                                            <td>{{ $userdata->left_bv }}</td>
                                                            <td>{{ $userdata->right_bv }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Team</th>
                                                            <td>{{ $userdata->l_c_count }}</td>
                                                            <td>{{ $userdata->r_c_count }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <div class="tipfooter">
                                                    <i class="fa fa-calendar-alt"></i> join: {{ $userdata->entry_time }}
                                                </div>
                                            </div>
                                        </a>
                                        <h6>{{ $userdata->user_id }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="row midline VHline">
                                <div class="col-6 tree-2-1">
                                    <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                        id="myTooltip1" class="mytooltip">
                                        <img src="{{ asset('user-assets/images/'.$tree_data[0]['level'][0]->image) }}"
                                            onclick="getMatrixTreeData('{{ $tree_data[0]['level'][0]->user_id }}')">
                                        <div class="Doxy-Tree-tooip-text">
                                            <div class="row">
                                                <div class="col-12">
                                                    <img src="{{ asset('user-assets/images/'.$tree_data[0]['level'][0]->image) }}" class="tipimg">
                                                </div>
                                                <div class="col-12">
                                                    <div class="tph">
                                                        <h1>
                                                            {{ $tree_data[0]['level'][0]->fullname }}
                                                            &nbsp; / &nbsp;
                                                            {{ $tree_data[0]['level'][0]->user_id }}
                                                        </h1>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-12">
                                                    <div class="whiteBox">
                                                        <div>
                                                            <p>Package : {{ $tree_data[0]['level'][0]->package1 }}</p>
                                                            <p>Sponsor Id : {{ $tree_data[0]['level'][0]->sponsor_id }}
                                                            </p>
                                                            {{-- <p>Placement Id : AE345j79</p> --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="table whitetable">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Factors</th>
                                                        <th scope="col">Left Side</th>
                                                        <th scope="col">Right Side</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">Affiliates</th>
                                                        @if ($tree_data[0]['level'][0]->user_id == 'Absent')
                                                            <td align="center">Absent</td>
                                                            <td align="center">Absent</td>
                                                        @else
                                                            <td align="center">{{ $tree_data[0]['level'][0]->l_d_count }}
                                                            </td>
                                                            <td align="center">{{ $tree_data[0]['level'][0]->r_d_count }}
                                                            </td>
                                                        @endif
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">sales</th>
                                                        <td>{{ $tree_data[0]['level'][0]->l_bv }}</td>
                                                        <td>{{ $tree_data[0]['level'][0]->r_bv }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">balance</th>
                                                        <td>{{ $tree_data[0]['level'][0]->left_bv }}</td>
                                                        <td>{{ $tree_data[0]['level'][0]->right_bv }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Team</th>
                                                        <td>{{ $tree_data[0]['level'][0]->l_c_count }}</td>
                                                        <td>{{ $tree_data[0]['level'][0]->r_c_count }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="tipfooter">
                                                <i class="fa fa-calendar-alt"></i> join:
                                                {{ $tree_data[0]['level'][0]->entry_time }}
                                            </div>
                                        </div>
                                    </a>
                                    <h6>{{ $tree_data[0]['level'][0]->user_id }}</h6>
                                </div>
                                <div class="col-6 tree-2-2">
                                    <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                        id="myTooltip1" class="mytooltip">
                                        <img src="{{ asset('user-assets/images/'.$tree_data[0]['level'][1]->image) }}"
                                            onclick="getMatrixTreeData('{{ $tree_data[0]['level'][1]->user_id }}')">
                                        <div class="Doxy-Tree-tooip-text">
                                            <div class="row">
                                                <div class="col-12">
                                                    <img src="{{ asset('user-assets/images/'.$tree_data[0]['level'][1]->image) }}"
                                                        class="tipimg">
                                                </div>
                                                <div class="col-12">
                                                    <div class="tph">
                                                        <h1>
                                                            {{ $tree_data[0]['level'][1]->fullname }}
                                                            &nbsp; / &nbsp;
                                                            {{ $tree_data[0]['level'][1]->user_id }}
                                                        </h1>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-12">
                                                    <div class="whiteBox">
                                                        <div>
                                                            <p>Package : {{ $tree_data[0]['level'][1]->package1 }}</p>
                                                            <p>Sponsor Id : {{ $tree_data[0]['level'][1]->sponsor_id }}
                                                            </p>
                                                            {{-- <p>Placement Id : AE345j79</p> --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="table whitetable">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Factors</th>
                                                        <th scope="col">Left Side</th>
                                                        <th scope="col">Right Side</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">Affiliates</th>
                                                        @if ($tree_data[0]['level'][1]->user_id == 'Absent')
                                                            <td align="center">Absent</td>
                                                            <td align="center">Absent</td>
                                                        @else
                                                            <td align="center">{{ $tree_data[0]['level'][1]->l_d_count }}
                                                            </td>
                                                            <td align="center">{{ $tree_data[0]['level'][1]->r_d_count }}
                                                            </td>
                                                        @endif
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">sales</th>
                                                        <td>{{ $tree_data[0]['level'][1]->l_bv }}</td>
                                                        <td>{{ $tree_data[0]['level'][1]->r_bv }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">balance</th>
                                                        <td>{{ $tree_data[0]['level'][1]->left_bv }}</td>
                                                        <td>{{ $tree_data[0]['level'][1]->right_bv }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Team</th>
                                                        <td>{{ $tree_data[0]['level'][1]->l_c_count }}</td>
                                                        <td>{{ $tree_data[0]['level'][1]->r_c_count }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="tipfooter">
                                                <i class="fa fa-calendar-alt"></i> join:
                                                {{ $tree_data[0]['level'][1]->entry_time }}
                                            </div>
                                        </div>
                                    </a>
                                    <h6>{{ $tree_data[0]['level'][1]->user_id }}</h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="row midline VHline">
                                        <div class="col-6 tree-3-1">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][0]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[1]['level'][0]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][0]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[1]['level'][0]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[1]['level'][0]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[1]['level'][0]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[1]['level'][0]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[1]['level'][0]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][0]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][0]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[1]['level'][0]->l_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][0]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[1]['level'][0]->left_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][0]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[1]['level'][0]->l_c_count }}</td>
                                                                <td>{{ $tree_data[1]['level'][0]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[1]['level'][0]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[1]['level'][0]->user_id }}</h6>
                                        </div>
                                        <div class="col-6 tree-3-2">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][1]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[1]['level'][1]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][1]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[1]['level'][1]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[1]['level'][1]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[1]['level'][1]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[1]['level'][1]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[1]['level'][1]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][1]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][1]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[1]['level'][1]->l_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][1]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[1]['level'][1]->left_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][1]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[1]['level'][1]->l_c_count }}</td>
                                                                <td>{{ $tree_data[1]['level'][1]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[1]['level'][1]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[1]['level'][1]->user_id }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="row midline VHline">
                                        <div class="col-6 tree-3-3">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][2]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[1]['level'][2]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][2]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[1]['level'][2]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[1]['level'][2]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[1]['level'][2]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[1]['level'][2]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[1]['level'][2]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][2]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][2]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[1]['level'][2]->l_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][2]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[1]['level'][2]->left_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][2]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[1]['level'][2]->l_c_count }}</td>
                                                                <td>{{ $tree_data[1]['level'][2]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[1]['level'][2]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[1]['level'][2]->user_id }}</h6>
                                        </div>
                                        <div class="col-6 tree-3-4">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][3]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[1]['level'][3]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[1]['level'][3]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[1]['level'][3]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[1]['level'][3]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[1]['level'][3]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[1]['level'][3]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[1]['level'][3]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][3]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[1]['level'][3]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[1]['level'][3]->l_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][3]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[1]['level'][3]->left_bv }}</td>
                                                                <td>{{ $tree_data[1]['level'][3]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[1]['level'][3]->l_c_count }}</td>
                                                                <td>{{ $tree_data[1]['level'][3]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[1]['level'][3]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[1]['level'][3]->user_id }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row rowline-4">
                                <div class="col-3">
                                    <div class="row midline VHline">
                                        <div class="col-6 tree-4-1">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][0]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][0]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][0]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][0]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][0]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][0]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][0]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][0]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][0]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][0]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][0]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][0]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][0]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][0]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][0]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][0]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][0]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][0]->user_id }}</h6>
                                        </div>
                                        <div class="col-6 tree-4-2">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][1]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][1]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][1]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][1]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][1]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][1]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][1]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][1]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][1]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][1]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][1]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][1]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][1]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][1]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][1]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][1]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][1]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][1]->user_id }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="row midline VHline">
                                        <div class="col-6 tree-4-3 ">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][2]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][2]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][2]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][2]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][2]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][2]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][2]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][2]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][2]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][2]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][2]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][2]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][2]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][2]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][2]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][2]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][2]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][2]->user_id }}</h6>
                                        </div>
                                        <div class="col-6 tree-4-4">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][3]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][3]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][3]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][3]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][3]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][3]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][3]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][3]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][3]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][3]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][3]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][3]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][3]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][3]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][3]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][3]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][3]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][3]->user_id }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="row midline VHline">
                                        <div class="col-6 tree-4-5 ">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][4]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][4]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][4]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][4]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][4]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][4]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][4]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][4]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][4]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][4]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][4]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][4]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][4]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][4]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][4]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][4]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][4]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][4]->user_id }}</h6>
                                        </div>
                                        <div class="col-6 tree-4-6">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][5]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][5]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][5]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][5]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][5]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][5]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][5]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][5]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][5]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][5]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][5]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][5]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][5]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][5]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][5]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][5]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][5]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][5]->user_id }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="row midline VHline">
                                        <div class="col-6 tree-4-7 ">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][6]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][6]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][6]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][6]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][6]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][6]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][6]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][6]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][6]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][6]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][6]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][6]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][6]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][6]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][6]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][6]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][6]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][6]->user_id }}</h6>
                                        </div>
                                        <div class="col-6 tree-4-8">
                                            <a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom"
                                                id="myTooltip1" class="mytooltip">
                                                <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][7]->image) }}"
                                                    onclick="getMatrixTreeData('{{ $tree_data[2]['level'][7]->user_id }}')">
                                                <div class="Doxy-Tree-tooip-text">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <img src="{{ asset('user-assets/images/'.$tree_data[2]['level'][7]->image) }}"
                                                                class="tipimg">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tph">
                                                                <h1>
                                                                    {{ $tree_data[2]['level'][7]->fullname }}
                                                                    &nbsp; / &nbsp;
                                                                    {{ $tree_data[2]['level'][7]->user_id }}
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="whiteBox">
                                                                <div>
                                                                    <p>Package : {{ $tree_data[2]['level'][7]->package1 }}
                                                                    </p>
                                                                    <p>Sponsor Id :
                                                                        {{ $tree_data[2]['level'][7]->sponsor_id }} </p>
                                                                    {{-- <p>Placement Id : AE345j79</p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table class="table whitetable">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Factors</th>
                                                                <th scope="col">Left Side</th>
                                                                <th scope="col">Right Side</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Affiliates</th>
                                                                @if ($tree_data[2]['level'][7]->user_id == 'Absent')
                                                                    <td align="center">Absent</td>
                                                                    <td align="center">Absent</td>
                                                                @else
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][7]->l_d_count }}</td>
                                                                    <td align="center">
                                                                        {{ $tree_data[2]['level'][7]->r_d_count }}</td>
                                                                @endif
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">sales</th>
                                                                <td>{{ $tree_data[2]['level'][7]->l_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][7]->r_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">balance</th>
                                                                <td>{{ $tree_data[2]['level'][7]->left_bv }}</td>
                                                                <td>{{ $tree_data[2]['level'][7]->right_bv }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Team</th>
                                                                <td>{{ $tree_data[2]['level'][7]->l_c_count }}</td>
                                                                <td>{{ $tree_data[2]['level'][7]->r_c_count }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="tipfooter">
                                                        <i class="fa fa-calendar-alt"></i> join:
                                                        {{ $tree_data[2]['level'][7]->entry_time }}
                                                    </div>
                                                </div>
                                            </a>
                                            <h6>{{ $tree_data[2]['level'][7]->user_id }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        function myFunctionRefLeft(leftId, leftCopy) {
            //alert(leftId);
            //alert(leftCopy);
            var copyText = document.getElementById(leftId);
            //alert(copyText.value);
            copyText.select();
            document.execCommand("copy");

            var tooltip = document.getElementById(leftCopy);
            console.log(tooltip);
            tooltip.innerHTML =
                "<i class='fa-regular fa-copy color-secondary'></i>Copied !"; // + copyText.value;
            setTimeout(function() {
                tooltip.innerHTML =
                    "<i class='fa-regular fa-copy'></i>Copy";
            }, 3000);
        }

        function myFunctionRefRight(rightId, rightCopy) {
            var copyText = document.getElementById(rightId);
            copyText.select();
            document.execCommand("copy");

            var tooltip = document.getElementById(rightCopy);
            console.log(tooltip);
            tooltip.innerHTML =
                "<i class='fa-regular fa-copy color-secondary'></i>Copied !"; // + copyText.value;
            setTimeout(function() {
                tooltip.innerHTML =
                    "<i class='fa-regular fa-copy'></i>Copy";
            }, 3000);
        }


        function getMatrixTreeData(userid) {
            if (userid == "Absent") {

            } else {
                var url = '{{ url('getlevelviewtree') }}';
                var form = $('<form action="' + url + '" method="post">' +
                    '<input type="text" name="id" value="' + userid + '" />' +
                    '{{ csrf_field() }}' +
                    '</form>');
                $('body').append(form);
                form.submit();
            }
            $('#searchuserid').val('');

        }


        function onBackClick() {
            window.history.go(-1);
            return false;
        }

        function onForwardClick() {
            window.history.go(+1);
            return false;
        }


        function onSearchClick() {
            var userid = $('#searchuserid').val();
            getMatrixTreeData(userid);
        }

        function goToTopIdClick() {
            window.location.href = "{{ url('\getlevelviewtree') }}";
        }


        function checkUserExisted() {

            var user_id = $('#searchuserid').val();

            $.ajax({
                type: 'POST',
                url: "{{ url('/checkuserexist/crossleg') }}",
                data: {
                    user_id: user_id,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log(response);
                    if (response.code == '200') {
                        $('#btnsearch').prop('disabled', false)
                    } else {
                        $('#btnsearch').prop('disabled', true)
                    }
                }
            });


        }
    </script>
@endsection
