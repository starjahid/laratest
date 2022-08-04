<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request){

        if(isset($request->birth_year) || isset($request->birth_month)){
                $users = DB::table('users');
            if(isset($request->birth_year) && isset($request->birth_month)){
                $users = $users->whereYear('birthday', '=', $request->birth_year)
                    ->whereMonth('birthday', '=', $request->birth_month);
            }elseif (isset($request->birth_month)){
                $users = $users->whereMonth('birthday', '=', $request->birth_month);
            }else{
                $users = $users->whereYear('birthday', '=', $request->birth_year);
            }
            $users = $users->paginate(20);
        }else{
            $users = User::paginate(20);
        }
        //return view('user.index',compact('users'));
        return view('user.index', [
            'users' => $users->appends(request()->query())
        ]);
    }
}
