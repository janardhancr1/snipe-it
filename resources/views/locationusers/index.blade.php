@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general247.locationusers') }}
@parent
@stop

{{-- Page content --}}
@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="box box-default">
      <div class="box-body">
        <div class="table-responsive">

          <table
                  data-columns="{{ \App\Presenters\LocationUsersPresenter::dataTableLayout() }}"
                  data-cookie-id-table="locationuserTable"
                  data-pagination="true"
                  data-id-table="locationuserTable"
                  data-search="true"
                  data-show-footer="true"
                  data-side-pagination="server"
                  data-show-columns="true"
                  data-show-export="true"
                  data-show-refresh="true"
                  data-sort-order="asc"
                  data-filterControl="true"
                  id="locationTable"
                  class="table table-striped snipe-table"
                  data-url="{{ route('api.locationusers.index') }}"
                  data-export-options='{
              "fileName": "export-locations-{{ date('Y-m-d') }}",
              "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
              }'>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@stop

@section('moar_scripts')
@include ('partials.bootstrap-table247', ['exportFile' => 'locations-export', 'search' => true])

@stop
