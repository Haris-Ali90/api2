<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;


use App\Http\Resources\JoeyAttemptQuizResource;
use App\Http\Resources\JoeyOrderCategoryListResource;
use App\Http\Resources\OrderCategoryTrainingResource;
use App\Http\Resources\QuizAnswersResource;
use App\Http\Resources\QuizResource;
use App\Http\Resources\VendorListResource;
use App\Http\Resources\VendorTrainingResource;

use App\Models\JoeyAttemptQuiz;
use App\Models\JoeyOrderCategory;
use App\Models\JoeyQuiz;
use App\Models\JoeyQuizScore;
use App\Models\JoeyTrainingSeen;
use App\Models\OrderCategory;
use App\Models\QuizQuestion;
use App\Models\Training;
use App\Models\Vendor;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class QuizController extends ApiBaseController
{

    private $userRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    public function quiz_score(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);


            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


            $JoeyQuizScoreData=[
                'joey_id' => $joey->id,
                'form_id' => $data['form_id'],
                'score' => $data['score'],
                'total' => $data['total']
            ];
            JoeyQuizScore::insert($JoeyQuizScoreData);

               DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Quiz score saved.');

    }




    public function joey_category_questions(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }

            if ($data['type'] == 'category') {

                $categoryid = OrderCategory::where('id',$data['id'])->first();

                if(!empty($categoryid)) {

                    $QuizQuestions = QuizQuestion::where('order_category_id', $data['id'])
                        ->inRandomOrder()
                        ->limit($categoryid->quiz_limit)
                        ->whereNull('deleted_at')
                        ->get();
                }else{
                    return RestAPI::response('Incorrect Category Id', false);
                }
              }
            else{
                
            
                $QuizQuestions = QuizQuestion::where('vendor_id', $data['id'])
                    ->inRandomOrder()
                    ->whereNull('deleted_at')
                    ->get();

            }
            $response=[];
            $response['score']=$categoryid->score;
            $response['question'] =QuizResource::collection($QuizQuestions);

           // }

                 DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, 'Answers to Questions');

    }




/*
 *
 *  Joey Quiz Attempt values saved
 */

    public function joeyAttemptQuiz(Request $request){

        $date=$request->all();

        DB::beginTransaction();
        try {

            $joey = $this->userRepository->find(auth()->user()->id);
            if (empty($joey)) {
                return RestAPI::response('Joey  record not found', false);
            }


             $insertion=JoeyQuiz::create(['joey_id'=>$joey->id,'is_passed'=>$date['is_passed'],'category_id'=>$date['category_id']]);

            foreach ($date['quiz'] as $key=>$value){
                $record=QuizQuestion::where('id',$value['question_id'])->first();
                if(isset($record)) {

                    if ($record->correct_answer_id == $value['answer_id']) {
                        $correct=1;
                    }
                    else {
                        $correct=0;
                    }
                    JoeyAttemptQuiz::create([
                        'joey_id' => $joey->id,
                        'question_id' => $value['question_id'],
                        'answer_id' => $value['answer_id'],
                        'quiz_id'=>$insertion->id,
                        'is_correct' => $correct
                    ]);
                }
            }
            DB::commit();
        }catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response(new \stdClass(), true, 'Joey Attempted Quiz');

    }


}
