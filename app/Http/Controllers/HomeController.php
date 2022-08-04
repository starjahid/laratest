<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class HomeController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request){
        //dd(  $users = User::paginate(20));
        if(isset($request->birth_year) || isset($request->birth_month)){
            $page=1; //Default value
            if($request->get('page')){
                $page = $request->get('page');
            }
            // Check In Redis
            $birthYear=  isset($request->birth_year)?$request->birth_year:0;
            $birthMonth=  isset($request->birth_month)?$request->birth_month:0;
            $redisSearchKey =$birthYear.'_'.$birthMonth;
            $users = unserialize(Redis::get("{$redisSearchKey}:{$page}"));
            if(!$users):
                $users = DB::table('users');
                $users = $users->whereYear('birthday', '=', $birthYear)
                    ->whereMonth('birthday', '=', $birthMonth);
                $users = $users->paginate(20);
                $userData = serialize($users);
                Redis::set("{$redisSearchKey}:{$page}",$userData);
                Redis::expire("{$redisSearchKey}:{$page}", 60);
                $users = unserialize(Redis::get("{$redisSearchKey}:{$page}"));
                //dd($users);
            else:
                $users = unserialize(Redis::get("{$redisSearchKey}:{$page}"));
            endif;
            //dd($users);
            //return view('user.index',compact('users'));
        }else{
            $users = User::paginate(20);
        }
        return view('user.index', [
            'users' => $users->appends(request()->query())

        ]);
    }
}
