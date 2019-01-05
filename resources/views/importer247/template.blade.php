@extends('layouts/default')

{{-- Page title --}}
@section('title')
Custom Templates for import
@parent
@stop

{{-- Page content --}}
@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="box box-default">
    <div class="box-body">
        {{ Form::open(['method' => 'POST', 'files' => true, 'class' => 'form-horizontal', 'autocomplete' => 'off']) }}
        <table
                data-cookie-id-table="customFieldsetsTable"
                data-id-table="customFieldsetsTable"
                data-click-to-select="true"
                data-search="true"
                data-side-pagination="client"
                data-show-columns="false"
                data-show-export="false"
                data-show-refresh="true"
                data-sort-order="asc"
                data-sort-name="name"
                id="customFieldsTable"
                class="table table-striped snipe-table"
                data-export-options='{
                "fileName": "export-fieldsets-{{ date('Y-m-d') }}",
                "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                }'>
          <thead>
            <tr>
              <th><input class="bt247SelectAll" id="bt247SelectAll" type="checkbox"></th>
              <th>{{ trans('general.name') }}</th>
              <th>{{ trans('admin/custom_fields/general.qty_fields') }}</th>
              <th>{{ trans('admin/custom_fields/general.used_by_models') }}</th>
            </tr>
          </thead>

          @if(isset($custom_fieldsets))
          <tbody>
            @foreach($custom_fieldsets AS $fieldset)
            <tr>
              <td><input class="bt247SelectItem" type="checkbox" name="filedsets[]" value="{{$fieldset->id}}" name="id"></td>
              <td>
                {{ link_to_route("fieldsets.show",$fieldset->name,['id' => $fieldset->id]) }}
              </td>
              <td>
                {{ $fieldset->fields->count() }}
              </td>
              <td>
                @foreach($fieldset->models as $model)
                  <a href="{{ route('models.show', $model->id) }}" class="label label-default">{{ $model->name }}</a>

                @endforeach
              </td>
            </tr>
            @endforeach
          </tbody>
          @endif
        </table>
        <br/>
        {{ Form::submit(trans('general247.template'), ['class' => 'btn btn-primary btn-sm'])}}
        {{ Form::close() }}
      </div><!-- /.box-body -->
    </div><!-- /.box.box-default -->

  </div> <!-- .col-md-12-->
</div>

@stop

@section('moar_scripts')
  @include ('partials.bootstrap-table247')
@stop