
    @if($record->is_active === 1)
    <a  class="btn btn-xs" type="button" data-toggle="modal"
        data-target="#statusModal{{ $record->id }}">
        <span class="label label-success">Active</span>
    </a>
    @if(can_access_route(['sub-admin.active','sub-admin.inactive'],$userPermissoins))
    <div id="statusModal{{  $record->id  }}" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Confirm Status?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to change the status?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                    {!! Form::model($record, ['method' => 'get',  'url' => 'sub-admin/inactive/'.$record->id, 'class' =>'form-inline form-edit']) !!}
                    {!! Form::hidden('id', $record->id) !!}
                    {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @endif

    @else
    <a  class="btn btn-xs" type="button" data-toggle="modal"
        data-target="#statusModal{{ $record->id }}">
    <span class="label label-warning">In Active</span>
    </a>

    @if(can_access_route(['sub-admin.active','sub-admin.inactive'],$userPermissoins))
    <div id="statusModal{{ $record->id }}" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Confirm Status?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to change the status?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                    {!! Form::model($record, ['method' => 'get',  'url' => 'sub-admin/active/'.$record->id, 'class' =>'form-inline form-edit']) !!}
                    {!! Form::hidden('id', $record->id) !!}
                    {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    @endif

    @endif
