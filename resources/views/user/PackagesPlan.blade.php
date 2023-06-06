@extends('layouts.user_type.auth-app')
@section('content')

<div class="page-wrapper">
<div class="container-fluid">
<div class="row">
<div class="col-md-12">
    <nav aria-label="breadcrumb ms-3">
        <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6">
            <li class="breadcrumb-item text-sm">
                <a class="opacity-5 text-dark" href="javascript:;">Pages</a>
            </li>
            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                Dashboard
            </li>
        </ol>
        <h6 class="font-weight-bolder mb-0">Packages</h6>
    </nav>
</div>

<div class="row mt-3">
    @foreach($packagelists as $packagelist)

    <div class="col-md-3" v-for="packagelist in packagelists" v-bind:key="$packagelist->id">
        <input type="hidden" name="package_id" id="package_id{{$packagelist->id}}" value="{{$packagelist->id}}">
        <div class="card">
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-12">
                        <h1 class="packageHead">
                            {{round($packagelist->roi,2)}}% <span> {{$packagelist->duration}} Days </span>
                        </h1>
                    </div>
                    <div class="col-12">
                        <div class="packageBody">
                            <h4 class="entry-title">
                                {{strtoupper($packagelist->package_type)}}
                            </h4>
                            <ul>
                                <li>
                                @if("$packagelist->max_hash == 10000000")
                                    <span >
                                        Invest ${{$packagelist->min_hash}} & ABOVE
                                    </span>
                                    @else
                                    <span >
                                        Invest ${{$packagelist->min_hash}} - ${{ $packagelist->max_hash }}
                                    </span>
                                    @endif



                                </li>
                                <li>

                                    <span>
                                        Direct {{$packagelist->direct_income}}%
                                    </span>
                                </li>
                                <li>

                                    <span>
                                        Max Benefit 210% or 10x Capping
                                    </span>
                                </li>
                                <li>

                                    <span>
                                        Binary Income up to 30%
                                    </span>
                                </li>
                                <li>

                                    <span>
                                        HSCC BONUS up to 10%
                                    </span>
                                </li>
                                <li>

                                    <span>
                                        Daily Binary Capping up to $25000
                                    </span>
                                </li>

                            </ul>
                        </div>
                    </div>
                    <div class="col-md-12">
                      <a href="{{url('/topup?package_plan_id=')}}{{$packagelist->id}}">
                        <button onclick1="gotoPackage({{$packagelist->id}})" class="btn bg-gradient-primary w-100">
                            Invest
                        </button>

                        </a>
                        <!-- <router-link class="btn bg-gradient-primary w-100" to="self-topup">
                      Invest
                    </router-link> -->
                        <!-- <button  onclick="gotoPackage($packagelist->id)" class="btn bg-gradient-primary w-100">
                    Invest
                  </button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endforeach
  </div>
</div>


@endsection