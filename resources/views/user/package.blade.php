@extends('layouts.user_type.auth-app')

@section('content')

<style type="text/css">
    .tab-content.newborder.iframeclass iframe {
        width: 100%;
        margin-bottom: 10px;
    }
</style>

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
      </div>
      <div class="row mt-3">
        <div class="col-md-3" v-for="packagelist in packagelists" v-bind:key="packagelist.id">
          <input type="hidden" name="package_id" v-bind:id="'package_id'+packagelist.id" v-model="packagelist.id">
          <div class="card">
            <div class="card-body text-center">
              <div class="row">
                <div class="col-12">
                  <h1 class="packageHead">
                    {{(packagelist.roi).toFixed(2)}}% <span> {{packagelist.duration}} Days </span>
                  </h1>
                </div>
                <div class="col-12">
                  <div class="packageBody">
                    <h4 class="entry-title"> 
                      {{(packagelist.package_type).toUpperCase()}}
                    </h4>
                    <ul>
                      <li>
                        <span v-if="packagelist.max_hash == 10000000">
                            Invest ${{packagelist.min_hash}} & ABOVE
                        </span>
                        <span v-else>
                            Invest ${{packagelist.min_hash}} - ${{ packagelist.max_hash }}
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Direct {{packagelist.direct_income}}%
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Max Benefit 210%
                        </span>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-12">
                  <button  @click="gotoPackage(packagelist.id)" class="btn bg-gradient-primary w-100">
                    Invest
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- <div class="col-md-3">
          <div class="card">
            <div class="card-body text-center">
              <div class="row">
                <div class="col-12">
                  <h1 class="packageHead">
                    0.80% <span> 263 Days </span>
                  </h1>
                </div>
                <div class="col-12">
                  <div class="packageBody">
                    <h4 class="entry-title"> 
                      PRO
                    </h4>
                    <ul>
                      <li>
                        
                        <span>
                            Invest $5,050 - $10,000
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Direct 5%
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Max Benefit 210%
                        </span>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-12">
                <router-link to="/self-topup" class="btn bg-gradient-primary w-100">
                    Invest
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card">
            <div class="card-body text-center">
              <div class="row">
                <div class="col-12">
                  <h1 class="packageHead">
                    0.90% <span> 234 Days </span>
                  </h1>
                </div>
                <div class="col-12">
                  <div class="packageBody">
                    <h4 class="entry-title"> 
                      ADVANCED
                    </h4>
                    <ul>
                      <li>
                        
                        <span>
                            Invest $10,050 - $25,000
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Direct 5%
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Max Benefit 210%
                        </span>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-12">
                  <router-link to="/self-topup" class="btn bg-gradient-primary w-100">
                    Invest
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card">
            <div class="card-body text-center">
              <div class="row">
                <div class="col-12">
                  <h1 class="packageHead">
                    1% <span> 210 Days </span>
                  </h1>
                </div>
                <div class="col-12">
                  <div class="packageBody">
                    <h4 class="entry-title"> 
                      HSCC VIP
                    </h4>
                    <ul>
                      <li>
                        
                        <span>
                            Invest $25,050 & Above
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Direct 5%
                        </span>
                      </li>
                      <li>
                        
                        <span>
                            Max Benefit 210%
                        </span>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-12">
                 <router-link to="/self-topup" class="btn bg-gradient-primary w-100">
                    Invest
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div> -->
      </div>
    </div>
  </div>

<script>
    function getTools(getid) {
        var tool_type = getid;
        var csrf_token = "{{ csrf_token() }}";
        $.ajax({

            url: "{{ url('/marketing-tools') }}",
            type: 'POST',
            data    :{ tool_type:tool_type },
            headers: {
                'X-CSRF-TOKEN': csrf_token
            },
            success: function(response) {
                console.log(response);
            },

            error: function(xhr, status, error) {
                console.log(error);
            }

        });

    }
</script>

@endsection
