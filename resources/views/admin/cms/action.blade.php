@if(can_access_route('cms.edit',$userPermissoins))
       <a href="{{route('cms.edit', base64_encode ($record->id))}}" title="Edit"
          class="btn btn-xs btn-info">
              <i class="fa fa-pencil-square"></i>
       </a>
@endif


