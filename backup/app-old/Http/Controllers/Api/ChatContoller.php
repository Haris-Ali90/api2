<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use Illuminate\Http\Request;
use App\Models\Joey;
use App\Models\Message;
use App\Models\Onboarding;
use App\Models\Thread;
use App\Models\Participants;
use App\Models\MessageFile;
use App\Models\ThreadReasonList;
use App\Models\MessageGroups;
use App\Models\UserMessages;

use App\Http\Resources\JoeyLIstResource;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\ChatUnseenMessageResource;
use App\Http\Resources\MessageListRessource;
use App\Http\Resources\ThreadReasonListResource;
use App\Http\Resources\ThreadUserResource;
use App\Http\Resources\SenderResource;
use App\Http\Resources\MessageGroupsResource;
use App\Http\Resources\GroupMessageDetailResource;
use App\Http\Resources\GroupDetailResource;

use Carbon\Carbon;

use App\Http\Resources\OnboardingLIstResource;

use App\Http\Resources\ZonesResource;
use Illuminate\Support\Facades\DB;

class ChatContoller extends Controller
{
    //
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Get Chat api
     *
     */
  
    public function createThreads(Request $request)
    {
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'other_user_id'=>'int',
            'user_type'=>'string|required',
            'other_user_type'=>'string',
            'thread_reason_id'=>'int|required'
        ]);
        DB::beginTransaction();
        try {
            if($request->user_type != 'Joey' && $request->other_user_id!=null && $request->other_user_type!=null)
            {
                $thread_id=Thread::
                  where('threads.user_id','=',$request->get('user_id'))
                ->where('threads.other_user_id','=',$request->get('other_user_id'))
                ->where('threads.creator_type','=',$request->get('user_type'))
                ->where('threads.other_user_type','=',$request->get('other_user_type'))
                ->where('threads.is_thread_end','=',0)
                ->first();
                if($thread_id==null)
                {
                    $thread_id=Thread::
                      where('threads.user_id','=',$request->get('other_user_id'))
                    ->where('threads.other_user_id','=',$request->get('user_id'))
                    ->where('threads.creator_type','=',$request->get('other_user_type'))
                    ->where('threads.other_user_type','=',$request->get('user_type'))
                    ->where('threads.is_thread_end','=',0)
                    ->first();
                }
                if($thread_id==null)
                {
                    $thread_id=new Thread();
                    $thread_id->user_id=$request->get('user_id');
                    $thread_id->creator_type=$request->get('user_type');
                    $thread_id->other_user_id=$request->get('other_user_id');
                    $thread_id->other_user_type=$request->get('other_user_type');
                    $thread_id->is_accepted=1;
                    $thread_id->thread_reason_id=$request->get('thread_reason_id');
                    $thread_id->save();
                }
            }
            elseif($request->user_type == 'Joey')
            {
                $thread_id=Thread::where('user_id',$request->get('user_id'))
                ->where('creator_type',$request->user_type)
                ->where('is_thread_end',0)
                ->Where('is_accepted',0)
                ->first();
                if($thread_id==null)
                {
                    $thread_id=new Thread();
                    $thread_id->user_id=$request->get('user_id');
                    $thread_id->creator_type=$request->get('user_type');
                    $thread_id->thread_reason_id=$request->get('thread_reason_id');
                    $thread_id->save();
                    
                }
                else
                {
                    return RestAPI::response('Already Thread Created', false, 'error_exception');
                }
                
            }
            else
            {
                    $this->validate($request,
                    [
                        'user_id'=>'int|required',
                        'other_user_id'=>'int',
                        'user_type'=>'string|required',
                        'other_user_type'=>'string'
                    ]);
            }

      
        DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        $response=new  ChatMessageResource($thread_id);
        return RestAPI::response($response, true, "Thread Data");
        

    }

   
    public function verifyToken(Request $request)
    {
        $response=[];
        DB::beginTransaction();
        try {

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Onboarding User List ");

    }
    public function threadChat(Request $request)
    {
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'other_user_id'=>'int|required',
            'user_type'=>'string|required',
            'other_user_type'=>'string|required'
        ]);
        DB::beginTransaction();
        try {

            $thread_id=Thread::
            where('threads.user_id','=',$request->get('user_id'))
            ->where('threads.other_user_id','=',$request->get('other_user_id'))
            ->where('threads.creator_type','=',$request->get('user_type'))
            ->where('threads.other_user_type','=',$request->get('other_user_type'))
            ->where('threads.is_thread_end','=',0)
            ->first();
        if($thread_id==null)
        {
                    $thread_id=Thread::
                    where('threads.user_id','=',$request->get('other_user_id'))
                    ->where('threads.other_user_id','=',$request->get('user_id'))
                    ->where('threads.creator_type','=',$request->get('other_user_type'))
                    ->where('threads.other_user_type','=',$request->get('user_type'))
                    ->where('threads.is_thread_end','=',0)
                    ->first();
        }
        if($thread_id==null)
        {
            return RestAPI::response("No Thread Found", false, 'error_exception');
        }
        DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
       
        $response=new  ChatMessageResource($thread_id);
        return RestAPI::response($response, true, "Thread Data");
      
    }
    public function endThreads(Request $request)
    {
        $response=[];
        $this->validate($request,
        [
            'thread_id'=>'int|required'
        ]);
        DB::beginTransaction();
        try {
            $thread_id=Thread::where('id','=',$request->get('thread_id'))->first();
            $thread_id->is_thread_end=1;
            $thread_id->deleted_at=date("Y-m-d H:i:s");
            $thread_id->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($thread_id, true, "Thread End Successfully.");

    }
    public function messageChat(Request $request)
    {
        $response=[];
        if($request->get('thread_id')!=null)
        {
            $this->validate($request,
            [
                'thread_id'=>'int|required'
            ]);
        }
        else
        {
            $this->validate($request,
            [
                'user_id'=>'int|required',
                'other_user_id'=>'int',
                'user_type'=>'string|required',
                'other_user_type'=>'string'
            ]);
        }
       
        DB::beginTransaction();
        try {
            if(($request->get('thread_id')!=null))
            {
                $thread_id=Thread::where('id','=',$request->get('thread_id'))->first();
            }
            else
            {
                $thread_id=Thread::
                where('threads.user_id','=',$request->get('user_id'))
                ->where('threads.other_user_id','=',$request->get('other_user_id'))
                ->where('threads.creator_type','=',$request->get('user_type'))
                ->where('threads.other_user_type','=',$request->get('other_user_type'))
                ->where('threads.is_thread_end','=',0)
                ->first();
                if($thread_id==null)
                {
                    $thread_id=Thread::
                    where('threads.user_id','=',$request->get('other_user_id'))
                    ->where('threads.other_user_id','=',$request->get('user_id'))
                    ->where('threads.creator_type','=',$request->get('other_user_type'))
                    ->where('threads.other_user_type','=',$request->get('user_type'))
                    ->where('threads.is_thread_end','=',0)
                    ->first();
                       if($thread_id==null)
                       {
                        return RestAPI::response('No data found', false, 'error_exception');
                       }
                }
                Message::where('thread_id',$thread_id->id)->
                where('sender_id','!=',$request->get('user_id'))->
                where('creator_type','!=',$request->get('user_type'))->where('is_read','=',0)->update(['is_read'=>1]);
            }
          

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        if($thread_id!=null)
        {
            $response= new ChatMessageResource($thread_id);
            return RestAPI::response($response, true, "Thread Data");
        }

           
            return RestAPI::response('No data found', false, 'error_exception');
     

    }
    public function addParticipants(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'thread_id'=>'int|required',
            'user_type'=>'string|required'
        ]);

        try {
            $thread=Thread::where('threads.creator_type','=','Joey')
            ->where('threads.is_thread_end','=',0)
            ->whereNull('threads.deleted_at')
            ->where('is_accepted','=',0)
            ->where('id',$request->thread_id)
            ->first();
            if($thread==null)
            {
                return RestAPI::response('Thread Already Accepted', false, 'error_exception');
            }
    
            $thread->other_user_id=$request->get('user_id');
            $thread->other_user_type=$request->get('user_type');
            $thread->is_accepted=1;
            $thread->save();

          
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        $response=new  ChatMessageResource($thread);
        return RestAPI::response($response, true, "Thread Data");
    }
    public function sendMessage(Request $request)
    {
        $response=[];
        
        $this->validate($request,[
            'user_id'=>'int|required',
            'thread_id'=>'int|required|exists:threads,id',
            'message'=>'required',
            'user_type'=>'string|required',
            'message_type'=>'string|required',
            'files.*'=>'mimes:doc,pdf,docx,zip,jpeg,png,jpg'
        ]);
        DB::beginTransaction();
        try {
          
            $thread=Thread::where('id',$request->thread_id)->first();
            if($thread->is_accepted==0)
            {
                return RestAPI::response('Thread is not accepted by user.', false, 'error_exception');
            }
           
            if($thread->is_thread_end==1)
            {
                return RestAPI::response('Thread is completed.', false, 'error_exception');
            }
            $message=new Message();
            $message->sender_id=$request->get('user_id');
            $message->thread_id=$request->get('thread_id');
            $message->message_type=$request->get('message_type');
            $message->creator_type=$request->get('user_type');
            $message->body=$request->get('message');
            $message->save();
        
            if($request->hasfile('files'))
            {
            
                foreach($request->file('files') as $file)
                {
                    
                    $message_file =new MessageFile();
                  
                    $path_org = "public/";
                    $name=$file->getClientOriginalName();
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    
                    $allow_file = ["pdf","xlx","csv","doc","docx","JPEG","PNG","JPG", "jpg", "jpeg","png"];
                
                    if(!in_array($extension,$allow_file) ){
                        return RestAPI::response('Only pdf,xlx,csv,doc,docx,jpeg,png,jpg file allow to process', false, 'error_exception');     
                    }
                    $rand_num = uniqid();
                    $name=$rand_num.'-opimg-'.$name;
                    $file->move($path_org,$name);
                    $filepath="public/".$name;
                    $message_file->file_name=$filepath;
                    if(in_array($extension,["pdf","xlx","csv"]))
                    {
                        $message_file->file_type="PDF";
                    }
                    elseif(in_array($extension,["doc","docx"]))
                    {
                        $message_file->file_type="DOC";
                    }
                    else
                    {
                        $message_file->file_type="Image";
                    }
                    $message_file->message_id=$message->id;
                    $message_file->save();
                }
            }
            $response=new  MessageListRessource($message);
          
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Message Send Successfully");

    }

    public function threadList(Request $request)
    {
       
        try {
            $threads=Thread::where('threads.creator_type','=','Joey')
            ->where('threads.is_thread_end','=',0)
            ->whereNull('threads.deleted_at')
            ->where('is_accepted','=',0)
            ->get();

            $response=  ThreadResource::collection($threads);

         
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Threats list");
    }
    public function unseenMessage(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'user_type'=>'string|required'
        ]);

        try {
            $thread=Thread::where('other_user_id',$request->user_id)->
            where('other_user_type',$request->user_type)->
            where('threads.is_thread_end','=',0)->
            where('is_accepted','=',1)->
            pluck('id')->toArray();
           
            $thread_id=Thread::where('threads.creator_type','=',$request->user_type)->where('user_id',$request->user_id)
            ->where('threads.is_thread_end','=',0)
            ->where('is_accepted','=',1)->pluck('id')->toArray();
            
           $thread_ids= array_merge($thread,$thread_id);
        //    $thread_id=Thread::withCount(['chatUnseenMessage'])->whereIn('id',$thread_ids)->get();
           $thread_id=Thread::withCount(['chatUnseenMessage' => function ($query) use ($request){
                $query->where('sender_id','!=',$request->user_id)
                ->where('creator_type','!=',$request->user_type);
            }])->whereIn('id',$thread_ids)->get();
           
            $response=  ChatUnseenMessageResource::collection($thread_id);

         
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Unseen Message list");
    }

    public function activeThreadList(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        [
            'user_id'=>'int|required|exists:onboarding_users,id',
        ]);
        try {
            $threads=Thread::where('threads.creator_type','=','Joey')
            ->where('threads.is_thread_end','=',0)
            ->whereNull('threads.deleted_at')
            ->where('is_accepted','=',1)
            ->get();

            $response=  ThreadResource::collection($threads);

         
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Active Threats list");
    }

    public function joeyActiveThreadList(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        [
            'joey_id'=>'int|required|exists:joeys,id',
        ]);
        try {
            $threads=Thread::withCount(['chatUnseenMessage' => function ($query) use ($request){
                $query->where('sender_id','!=',$request->joey_id)
                ->where('creator_type','!=','Joey');
            }])->where('threads.creator_type','=','Joey')
            ->where('threads.user_id','=',$request->joey_id)
            ->orderBy('is_accepted','desc')
            ->get();

            $response=  ThreadResource::collection($threads);

         
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Active Threats list");
    }
    public function onboardingUser(Request $request)
    {

        DB::beginTransaction();
        try {
                $onboarding=Onboarding::orderBy('first_name')->get();

            $response = OnboardingLIstResource::collection($onboarding);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Onboarding User List ");
    }
    public function markReadMessage(Request $request)
    {
        $this->validate($request,
        [
            'message_id'=>'required|present|array'
        ]);
        DB::beginTransaction();
        try {
                Message::whereIn('id',$request->message_id)->update(['is_read'=>1]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response([], true, "Message Mark Read Successfully");
    }
    public function joeyUser(Request $request)
    {
        DB::beginTransaction();
        try {
                $joeys=Joey::orderBy('first_name')->get();

            $response = JoeyLIstResource::collection($joeys);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Joey List ");
    }

    public function saveMessage(Request $request)
    {
        $this->validate($request,
        [
            'email'=>'string|required',
            'password'=>'string|required',
            'type'=>'required|in:Vendor,Onboarding'
        ]);
    }

    public function checkmorph(Request $request)
    {
        $Joey=Joey::find(35);
        
        $Joey->message()->create(['body'=>'sda','thread_id'=>'34','message_type'=>'text']);
    }
    public function userAllThreads(Request $request)
    { 
        $response=[];
        
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'user_type'=>'string|required'
        ]);
        DB::beginTransaction();
        try {
            
            $participantData=Thread::where('other_user_id',$request->user_id)->
            where('other_user_type',$request->user_type)->
            pluck('id')->toArray();
            $threadData=Thread::where('threads.creator_type','=',$request->user_type)->where('user_id',$request->user_id)->pluck('id')->toArray();
            $thread_ids= array_merge($participantData,$threadData);
            $thread_id=Thread::withCount(['chatUnseenMessage' => function ($query) use ($request){
                $query->where('sender_id','!=',$request->joey_id)
                ->where('creator_type','!=',$request->user_type);
            }])->whereIn('id',$thread_ids)->orderBy('is_accepted','desc')->get();
            $response = ThreadResource::collection($thread_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "User Threads List ");
    }
    public function threadReasonList()
    {
        $response=[];
        DB::beginTransaction();
        try {
            
            $threadReasonList=ThreadReasonList::whereNull('thread_reason_list_id')->get();
         
    
            $response = ThreadReasonListResource::collection($threadReasonList);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Threads Reason List ");
    }

    public function alluserChatOnboarding(Request $request)
    {
        $response=[];
        $this->validate($request,
        [
            'user_id'=>'required|exists:onboarding_users,id'
        ]);

        DB::beginTransaction();
        try {
                $threadUser         =   Thread::where('threads.creator_type','=','onboarding')->where('user_id',$request->user_id)->pluck('id')->toArray();
                $otherThreadUser    =   Thread::where('threads.other_user_type','=','onboarding')->where('other_user_id',$request->user_id)->pluck('id')->toArray();
                $thread_ids         =   array_unique(array_merge($threadUser,$otherThreadUser));
                $threadUserData     =   Thread::whereIn('id',$thread_ids)->get();
        
                $response           =   $this->allChatUserResource($threadUserData,$request->user_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Onboarding User List ");
    }

    public function messageGroupsCreate(Request $request)
    {
        $response=[];
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'user_type'=>'string|required',
            'group_name'=>'string|required'
            ,
            'message_group_pic.*'=>'mimes:doc,pdf,docx,jpeg,png,jpg'
        ]);
        DB::beginTransaction();
        try {

            $groupNameExist=MessageGroups::where('user_id',$request->user_id)->where('creator_type',$request->user_type)->where('name',trim($request->group_name))->first();
            if($groupNameExist!=null)
            {
                $response=new  MessageGroupsResource($groupNameExist);
                // return RestAPI::response('Group Name All Ready Exist', false, 'error_exception');
            }
            else
            {
                $messageGroup=new MessageGroups();
                $messageGroup->name=$request->group_name;
                $messageGroup->user_id=$request->user_id;
                $messageGroup->creator_type=$request->user_type;
                if($request->hasfile('message_group_pic'))
                {
                    $image=$request->file('message_group_pic');
                    $path_org = "public/";
                    $name=$image->getClientOriginalName();
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    
                    $allow_file = ["pdf","xlx","csv","doc","docx","JPEG","PNG","JPG","png"];
                
                    if(!in_array($extension,$allow_file) ){
                        return RestAPI::response('Only pdf,xlx,csv,doc,docx,jpeg,png,jpg file allow to process', false, 'error_exception');     
                    }
                    $rand_num = uniqid();
                    $name=$rand_num.'-opimg-'.$name;
                    $image->move($path_org,$name);
                    $filepath="public/".$name;
                    $messageGroup->message_group_pic=$filepath;
                }
                else
                {
                    $messageGroup->message_group_pic='image file path';
                }

                $messageGroup->save();
                $response=new  MessageGroupsResource($messageGroup);
            }
            

        DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Group Created Successfully");
    }
    public function addGroupMember(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'group_id'=>'int|required|exists:message_groups,id',
            'user_type'=>'string|required'
        ]);

        try {
            $Participants=Participants::where('creator_type','=',$request->user_type)
            ->where('user_id',$request->user_id)
            ->where('message_group_id',$request->group_id)
            ->first();
            if($Participants!=null)
            {
               
                return RestAPI::response('User Already Added In this Group', false, 'error_exception');
            }
            $participants=new Participants();
            $participants->user_id=$request->get('user_id');
            $participants->creator_type=$request->get('user_type');
            $participants->message_group_id=$request->get('group_id');
            $participants->save();
          
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
       
        return RestAPI::response($response, true, "User Add Successfully");
    }
    public function groupSendMessage(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        [
            'user_id'=>'int|required',
            'group_id'=>'int|required|exists:message_groups,id',
            'message'=>'required',
            'user_type'=>'string|required',
            'message_type'=>'string|required',
            'files.*'=>'mimes:doc,pdf,docx,zip,jpeg,png,jpg'
        ]);
        DB::beginTransaction();
        try {
            $message=new Message();
            $message->sender_id=$request->get('user_id');
            $message->message_type=$request->get('message_type');
            $message->creator_type=$request->get('user_type');
            $message->body=$request->get('message');
            $message->group_id=$request->get('group_id');
            $message->save();
        
            if($request->hasfile('files'))
            {
            
                foreach($request->file('files') as $file)
                {
                    
                    $message_file =new MessageFile();
                  
                    $path_org = "public/";
                    $name=$file->getClientOriginalName();
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    
                    $allow_file = ["pdf","xlx","csv","doc","docx","JPEG","PNG","JPG","png"];
                
                    if(!in_array($extension,$allow_file) ){
                        return RestAPI::response('Only pdf,xlx,csv,doc,docx,jpeg,png,jpg file allow to process', false, 'error_exception');     
                    }
                    $rand_num = uniqid();
                    $name=$rand_num.'-opimg-'.$name;
                    $file->move($path_org,$name);
                    $filepath="public/".$name;
                    $message_file->file_name=$filepath;
                    if(in_array($extension,["pdf","xlx","csv"]))
                    {
                        $message_file->file_type="PDF";
                    }
                    elseif(in_array($extension,["doc","docx"]))
                    {
                        $message_file->file_type="DOC";
                    }
                    else
                    {
                        $message_file->file_type="Image";
                    }
                    $message_file->message_id=$message->id;
                    $message_file->save();
                }
            }
            $GroupUser=MessageGroups::where('id',$request->group_id)
            ->first();
            $groupMembers=[];

            if(!($GroupUser->user_id==$request->user_id && $GroupUser->creator_type == $request->user_type))
            {
                $userMessages=new UserMessages();
                $userMessages->message_id=$message->id;
                $userMessages->receiver_id=$GroupUser->user_id;
                $userMessages->receiver_type=$GroupUser->creator_type;
                $userMessages->type=1;
                $userMessages->save();
            }
            $groupMembers=$GroupUser->getGroupMember;
            if($groupMembers!=null)
            {
                $groupMembers= $groupMembers->where('user_id','!=',$request->user_id)->where('creator_type','!=',$request->creator_type);
                
            }
            foreach ($groupMembers as $groupMember)
            {
                $userMessages=new UserMessages();
                $userMessages->message_id=$message->id;
                $userMessages->receiver_id=$groupMember->user_id;
                $userMessages->receiver_type=$groupMember->creator_type;
                $userMessages->type=1;
                $userMessages->save();
            }
            $response=new  MessageListRessource($message);
          
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Message Send Successfully");
    }
    public function getGroupMessages(Request $request)
    {
        $response=[];
      
            $this->validate($request,
            [
                'group_id'=>'int|required'
            ]);

       
        DB::beginTransaction();
        try {
            
                $messageGroup=MessageGroups::where('id','=',$request->get('group_id'))->first();

                $response=new  MessageGroupsResource($messageGroup);
          
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
           
        return RestAPI::response($response, true, "Message Group Detail.");
     
    }
    public function getGroupMessageDetail(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        ['message_id'=>'int|required|exists:messages,id']);

        try {
                $messageDetail=UserMessages::where('message_id',$request->message_id)
                ->get();
                $response=GroupMessageDetailResource::collection($messageDetail);
          
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
       
        return RestAPI::response($response, true, "Message Detail.");
    }
    public function userAllgroupDetail(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        ['user_id'=>'int|required',
         "user_type"=>'string|required'
        ]);

        try {
                $messageGroup=MessageGroups::where('user_id','=',$request->get('user_id'))->
                where('creator_type',$request->user_type)->pluck('id')->toArray();
                $Participants=Participants::where('creator_type','=',$request->user_type)
                ->where('user_id',$request->user_id)
                ->pluck('message_group_id')->toArray();
                $group_ids=array_merge($messageGroup,$Participants);
                $messageGroups=MessageGroups::whereIn('id',$group_ids)->get();
             
               
                $response=GroupDetailResource::collection($messageGroups);
          
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
       
        return RestAPI::response($response, true, "Group Detail.");
    }

    

    public function groupMessageMarkDelivered(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        ['user_id'=>'int|required',
         "user_type"=>'string|required',
         "group_id"=>'int|required|exists:message_groups,id'
        ]);

        try {
              
                $messageids=Message::where('sender_id','!=',$request->user_id)->
                    where('group_id',$request->group_id)
                    ->pluck('id')->toArray();
                    UserMessages::whereIn('message_id',$messageids)
                    ->where('receiver_id','=',$request->user_id)->
                    where('receiver_type','=',$request->user_type)->
                    whereNull('deliver_at')->
                    update(['deliver_at'=>date('Y-m-d H:i:s')]);
                
          
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
       
        return RestAPI::response($response, true, "Message Delivered Mark Succesfully.");
    }
    public function groupMessageMarkSeen(Request $request)
    {
        $response=[];
        
        $this->validate($request,
        ['user_id'=>'int|required',
         "user_type"=>'string|required',
         "group_id"=>'int|required|exists:message_groups,id'
        ]);

        try {
              
                $messageids=Message::where('sender_id','!=',$request->user_id)->
                    where('group_id',$request->group_id)
                    ->pluck('id')->toArray();
                    $userMessages=UserMessages::whereIn('message_id',$messageids)
                    ->where('receiver_id','=',$request->user_id)->
                    where('receiver_type','=',$request->user_type)->
                    whereNull('seen_at')->get();
                $date_time=date('Y-m-d H:i:s');
                foreach ($userMessages as $userMessage)
                {
                    if($userMessage->deliver_at==null)
                    {
                        $userMessage->deliver_at=$date_time;
                    }
                    $userMessage->seen_at=$date_time;
                    $userMessage->save();
                }
                    
                
          
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
       
        return RestAPI::response($response, true, "Message Seen Mark Succesfully.");
    }

    public function allChatUserResource($threadUserData,$user_id) 
    {
        $response=[];
      
        foreach($threadUserData as $threadUser)
        {
            if($threadUser->user_id!=$user_id)
            {
                if($threadUser->creator_type=='onboarding')
                {
                    $sender=Onboarding::find($threadUser->user_id);
                }
                elseif($threadUser->creator_type=='Joey')
                {
                    $sender=Joey::find($threadUser->user_id);
                }
                $response[$threadUser->user_id]= [
                    'user'=>new SenderResource($sender),
                    'creator_type'=>$threadUser->creator_type,
                    'created_at' => Carbon::parse($threadUser->created_at)->format('Y/m/d - H:i:s'),
                ];
            }
            else
            {
                if($threadUser->other_user_type=='onboarding')
                {
                    $sender=Onboarding::find($threadUser->other_user_id);
                }
                elseif($threadUser->other_user_type=='Joey')
                {
                    $sender=Joey::find($threadUser->other_user_id);
                }
                $response[$threadUser->other_user_id]= [
                    'user'=>new SenderResource($sender),
                    'creator_type'=>$threadUser->other_user_type,
                    'created_at' => Carbon::parse($threadUser->created_at)->format('Y/m/d - H:i:s'),
                ];
            }
           
        }
        return array_values($response);
    }
    
}