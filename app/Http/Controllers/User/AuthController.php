<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use JWTAuth;
use Tymon\JwtAuth\Exceptions\JWtException;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\UrlGenerator;


class AuthController extends Controller
{
    //

    public $loginAfterSignUp = true;

    protected $user;
    protected $base_url;

    public function __construct(UrlGenerator $url)
    {
        $this->user = new User;
        $this->base_url = $url->to("/"); 
    }




    public function RegisterUser(Request $request)
    {
       $validator = Validator::make($request->all(),
       [
         'name'=> 'required|string',
         'email'=>'required|email',
         'password' => 'required|string|min:6',
           'image' => 'required'
       ]
       );
       if($validator->fails())
       {
           return response()->json([
               "success"=>false,
               "message"=>$validator->messages()->toArray(),
           ],400);
       }
   $check_email = $this->user->where("email",$request->email)->count();
    if($check_email!==0)
    {
    return response()->json([
        "success"=>false,
        "message"=>"sorry this email is already taken",
    ],400);
    }
    $file_name = "";
    $base64encodedString = $request->image;
     $generated_name = uniqid()."_".time().date("Ymd")."_IMG";
     $fileBin = file_get_contents($base64encodedString);
     $mimeType = mime_content_type($base64encodedString);   
     if("image/png"==$mimeType)
     {
         $file_name = $generated_name.".png";
     }else if("image/jpg"==$mimeType)
     {
         $file_name = $generated_name.".jpg";
     }else if("image/jpeg"==$mimeType)
     {
         $file_name = $generated_name.".jpeg";
     }else {
        return response()->json([
            "success"=>false,
            "message"=>"invalid file type only png jpeg and jpg files are allowed",
        ],400);
     }

       $this->user->name = $request->name;
       $this->user->email = $request->email;
       $this->user->password = Hash::make($request->password);
      $this->user->image = $file_name;
       $this->user->save();
   file_put_contents("./profile_images/".$file_name, $fileBin);
    return response()->json([
        "success"=>true,
        "message"=>"user registered successfully",
    ],200);
    }


   public function login(Request $request)
   {
    $validator = Validator::make($request->only("email","password"),
       [
         'email'=>'required|email',
         'password' => 'required|string|min:6',
       ]
       );

       if($validator->fails())
       {
           return response()->json([
               "success"=>false,
               "message"=>$validator->mesages()->toArray(),
           ],400);
       }
      
   $input = $request->only("email","password");

       $jwt_token = null;
       if(!$jwt_token = auth("users")->attempt($input))
       {
        return response()->json([
            "success"=>false,
            "message"=>"invalid email or password",
        ],401); 
       }
     
       $user_image = "";
       $user = auth("users")->authenticate($jwt_token);
        $user_image = $user->image;


    $base64_content = file_get_contents("./profile_images/".$user_image);
    $encode_file = base64_encode($base64_content);

       return response()->json([
        "success"=>true,
         "image"=>'data:image/jpg;base64,'.$encode_file,
        "token"=>$jwt_token,
    ],200);
       
   }
  

}
