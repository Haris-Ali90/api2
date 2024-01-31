<a href="{!! URL::route('admin.events.show', $record->id) !!}" title="Detail"
   class="btn btn-xs btn-primary">
    <i class="fa fa-eye"></i>
</a>


  <a href="{!! URL::route('admin.events.edit', $record->id) !!}" title="Edit"
     class="btn btn-xs btn-primary">
       <i class="fa fa-pencil-square"></i>
   </a>

<a class="btn btn-xs btn-danger delete" type="button" title="Delete" data-toggle="modal"
   data-target="#deleteModal{{ $record->id }}">
    <i class="fa fa-trash-o"></i>
</a>


<div id="deleteModal{{ $record->id }}" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this event?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                {!! Form::model($record, ['method' => 'delete',  'url' => 'admin/events/'.$record->id, 'class' =>'form-inline form-delete']) !!}
                {!! Form::hidden('id', $record->id) !!}
                {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>




