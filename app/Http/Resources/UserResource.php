<?php

namespace App\Http\Resources;
use App\Models\JoeyDocument;
use App\Models\AgreementUser;
use App\Models\OrderCategory;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    private $_token = '';

    public function __construct($resource, $_token = '')
    {
        parent::__construct($resource);
        if(empty($_token)) {
            $this->_token = request()->bearerToken();
        }
         else {
             $this->_token = $_token;
         }
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $basic_categories=OrderCategory::whereNUll('user_type')->where('type','basic')->get();
        $basic_categories=count($basic_categories);

        $orderCount=$this->joeySprints->count();

        $joeyDocumeentVerified=0;
        $joeyDocumeent=0;

        if ($this->joeyDocuments) {
            foreach ($this->joeyDocuments as $joeyDocuments) {

                if ($joeyDocuments->joeyMandatoryDocuments) {

                    $joeyDocumeent = 1;
                }
            }
        }
        $joeyDocument=$this->joeyDocumentsApproved;
            if($joeyDocument->count()>0){
                $joeyDocumeentVerified=1;
            }


        $joeyTrainingVerified=0;

        $joeyAttempytedQuiz=$this->joeyAttemptedQuiz;
        $countforbasic_joey=0;

        if($joeyAttempytedQuiz->count()>0){
            foreach ($joeyAttempytedQuiz as $value) {
                 if(!empty($value->orderCategory)){
                     if($value->orderCategory->type=='basic'){
                         $countforbasic_joey+=1;
                     }
                 }
             }
        }

        if($countforbasic_joey>=$basic_categories){
            $joeyTrainingVerified=1;
        }

        return [
            'id' => $this->id,
            'first_name'=> $this->first_name??'',
            'last_name'=> $this->last_name??'',
            'date_of_birth'=>$this->date_of_birth??'',
            'phone'=>$this->phone??'',
            'image'=>$this->image??'',
            'address'=>$this->address??'',
            'email' => $this->email??'',
            'location_id'=>$this->location_id??'',
            'order_count'=>$orderCount??'',
           'document_verified'=>$joeyDocumeentVerified??'',
           // 'document_verified'=>$joeyDocumeent??'',
            'is_passed'=>$joeyTrainingVerified??'',
            'token' => $this->_token,
            'is_active' => $this->is_active,
             'on_duty'=>$this->on_duty??0,
            'agreement_signed' => (AgreementUser::where('user_id',$this->id)->where('user_type','joeys')->first())?1:0

        ];
    }
}
