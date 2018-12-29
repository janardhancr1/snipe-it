<div id="assigned_user" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">

    {{ Form::label($fieldname, $translated_name, array('class' => 'col-md-3 control-label')) }}

    <div class="col-md-7">
        @if (($snipeSettings->full_multiple_companies_support=='1') && (!Auth::user()->isSuperUser()))
            <p class="form-control-static">{{ Auth::user()->department->name }}</p>
            <input type="hidden" name="department_id" value="{{ Auth::user()->department->id }}">
        @else
        <select class="js-data-ajax" data-endpoint="departments" data-placeholder="{{ trans('general.select_department') }}" name="{{ $fieldname }}" style="width: 100%" id="department_select">
            @if ($department_id = Input::old($fieldname, (isset($item)) ? $item->{$fieldname} : ''))
                <option value="{{ $department_id }}" selected="selected">
                    {{ (\App\Models\Department::find($department_id)) ? \App\Models\Department::find($department_id)->name : '' }}
                </option>
            @else
                <option value="">{{ trans('general.select_department') }}</option>
            @endif
        </select>
        @endif
    </div>


    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fa fa-times"></i> :message</span></div>') !!}

</div>
