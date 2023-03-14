<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Comment;

use Illuminate\Support\Facades\Validator;

use Session;

class CommentsController extends Controller
{
    public $paginate = 200;

    public function Index()
    {        
        return view('comment.index');
    }

    public function List(Request $request)
    {
        $commentList = Comment::orderBy('finalStatus', 'asc')
                                ->where('description', 'like', '%'. $request->get('textSearch') .'%')
                                ->where('finalStatus', $request->get('finalStatus'))
                                ->paginate($this->paginate);
        
        return ['commentList' => $commentList];
    }

    public function GetAllFinalStatus($finalStatus)
    {
        $commentList = Comment::where('finalStatus', $finalStatus)->get();

        return ['commentList' => $commentList];
    }

    public function Insert(Request $request)
    {
        $validator = Validator::make($request->all(),

            [
                "description" => ["required", "unique:comments", "max:100"],
                "statusCode" => ["required", "max:50"],
                "finalStatus" => ["required"],
            ],
            [
                "description.unique" => "Comment already exists",
                "description.required" => "The field is required",
                "description.max"  => "You must enter a maximum of 100 digits",

                "statusCode.required" => "The field is required",
                "statusCode.max"  => "You must enter a maximum of 50 digits",

                "finalStatus.required" => "Select item",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        Comment::create($request->all());

        return ['stateAction' => true];
    }

    public function Get($id)
    {
        $comment = Comment::find($id);
        
        return ['comment' => $comment];
    }

    public function Update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),

            [
                "description" => ["required", "unique:comments,description,$id", "max:100"],
                "statusCode" => ["required", "max:50"],
                "finalStatus" => ["required"],
            ],
            [
                "description.unique" => "Comment already exists",
                "description.required" => "The field is required",
                "description.max"  => "You must enter a maximum of 100 digits",

                "statusCode.required" => "The field is required",
                "statusCode.max"  => "You must enter a maximum of 50 digits",

                "finalStatus.required" => "Select item",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "errors" => $validator->errors()], 422);
        }

        $comment = Comment::find($id);
        
        $comment->update($request->all()); 

        return ['stateAction' => true];
    }

    public function Delete($id)
    {
        $comment = Comment::find($id);

        $comment->delete();

        return ['stateAction' => true];
    }
}