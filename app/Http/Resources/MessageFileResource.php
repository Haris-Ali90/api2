<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class MessageFileResource extends JsonResource
{
    private $_token = '';

    public function __construct($resource)
    {

        parent::__construct($resource);

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        

        return [
            'id' => $this->id,
            'file_name'=> $this->file_name,
            'file_type'=>$this->file_type,
            'created_at' => Carbon::parse($this->created_at)->format('Y/m/d - H:i:s'),

        ];
    }
}
