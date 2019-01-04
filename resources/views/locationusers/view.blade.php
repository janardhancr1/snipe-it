@extends('layouts/default')

{{-- Page title --}}
@section('title')

 {{ trans('general247.locationusers') }}:
 {{ $location->name }}
 
@parent
@stop


{{-- Page content --}}
@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <div class="box-heading">
                    <h3 class="box-title">{{ trans('general.users') }}</h3>
                </div>
            </div>
            <div class="box-body">
                <div class="table table-responsive">
                    <table
                        data-columns="{{ \App\Presenters\UserPresenter::dataTableLayout() }}"
                        data-cookie-id-table="usersTable"
                        data-pagination="true"
                        data-id-table="usersTable"
                        data-search="true"
                        data-side-pagination="server"
                        data-show-columns="true"
                        data-show-export="true"
                        data-show-refresh="true"
                        data-sort-order="asc"
                        id="usersTable"
                        class="table table-striped snipe-table"
                        data-url="{{route('api.locationusers.users', ['location_userid' => $location->id])}}"
                        data-export-options='{
                                "fileName": "export-locations-{{ str_slug($location->name) }}-users-{{ date('Y-m-d') }}",
                                "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                }'>

                    </table>
                    {{ Form::open(['route' =>
                    ["locationusers.associate",$location->id],
                    'class'=>'form-horizontal',
                    'id' => 'ordering']) }}
                    <select class="js-data-ajax" data-endpoint="users" data-placeholder="{{ trans('general.select_user') }}" name="users" style="width:100%" id="users_select">
                    </select>
                    {!! $errors->first('users', '<span class="alert-msg"><i class="fa fa-times"></i> :message</span>') !!}
                    <br/><br/>
                    {{ Form::submit(trans('general247.adduser'), ['class' => 'btn btn-primary btn-sm'])}}
                    {{ Form::close() }}
                </div><!-- /.table-responsive -->
            </div><!-- /.box-body -->
        </div> <!--/.box-->
    </div><!--/.col-md-12-->
</div>

@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', [
    'exportFile' => 'locations-export',
    'search' => true
 ])

@stop
