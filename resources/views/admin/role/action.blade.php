@if(can_access_route('role.show',$userPermissoins))
<a href="{!! URL::route('role.show', $record->id) !!}" title="Detail"
   class="btn btn-xs btn-primary orange">
    <i class="fa fa-eye"></i>
</a>
@endif

@if(can_access_route('role.edit',$userPermissoins))
<a href="{!! URL::route('role.edit', $record->id) !!}" title="Edit"
     class="btn btn-xs btn-primary">
       <i class="fa fa-pencil-square"></i>
</a>
@endif




