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
        $links = [];
        if(isset($request->birth_year) || isset($request->birth_month)){
            $page=1; //Default value
            if($request->get('page')){
                $page = $request->get('page');
            }
            // Check In Redis
            $birthYear=  isset($request->birth_year)?$request->birth_year:0;
            $birthMonth=  isset($request->birth_month)?$request->birth_month:0;
            $redisSearchKey =$birthYear.'_'.$birthMonth;
            $users = $this->get_page_info($redisSearchKey, $page, 20);
            if(count($users['data'])==0):
                $users = DB::table('users');
                if(isset($request->birth_year) && isset($request->birth_month)){
                    $usersObject = $users->whereYear('birthday', '=', $birthYear)
                        ->whereMonth('birthday', '=', $birthMonth);
                }elseif (isset($request->birth_month)){
                    $usersObject = $users->whereMonth('birthday', '=', $request->birth_month);
                }else{
                    $usersObject = $users->whereYear('birthday', '=', $request->birth_year);
                }
                $users = $usersObject->get()->toArray();
                if($users==""){
                    return redirect()->back()->with('danger', 'No data found');
                }
                $this->clear_db(0);
                if(count($users)>0){
                    foreach ($users as $row){
                        //dd($row);
                        $this->set_page_info($redisSearchKey, $row->id, (array) $row);
                    }
                }
                $usersPaginationDetails = $usersObject->paginate(20);
                Redis::set("{$redisSearchKey}:pagination", serialize($usersPaginationDetails) );
                Redis::expire("{$redisSearchKey}:pagination", 60);
                $users = $this->get_page_info($redisSearchKey, $page, 20);
            else:
                $users = $this->get_page_info($redisSearchKey, $page, 20);
            endif;
            $links = unserialize(Redis::get("{$redisSearchKey}:pagination"));
            return view('user.index', [
                'users' => $users,
                'redisLink' => $links->appends(request()->query())
            ]);
        }else{
            $users = User::paginate(20);
            return view('user.index', [
                'users' => $users->appends(request()->query()),
                'redisLink' => $links
            ]);
        }
    }

    /**
     * @param $redis_key
     * @param $id
     * @param $data
     * @return bool
     */
    public function set_page_info($redis_key, $id,$data){
        if(!is_numeric($id) || !is_array($data)) return false;
        $hashName = $redis_key.'_'.$id;
        Redis::hMSet($hashName,$data);
        Redis::zAdd($redis_key.'_sort',$id,$id);
        Redis::expire($redis_key.'_sort', 60);
        Redis::expire($hashName, 60);
        return true;
    }

    /**
     * @param $redis_key
     * @param $page
     * @param $pageSize
     * @param array $key
     * @return array|bool
     */
    public function get_page_info($redis_key, $page,$pageSize,$key = []){
        if(!is_numeric($page) || !is_numeric($pageSize)) return false;
        $limit_s = ($page - 1) * $pageSize;
        $limit_e = ($limit_s + $pageSize) - 1;
        // Get all bands in the interval  score  Ordered set member list
        $range = Redis::zRange($redis_key.'_sort',$limit_s,$limit_e);
        $count = Redis::zCard($redis_key.'_sort'); // Count the total//dd($count);
        $pageCount = ceil($count / $pageSize); // Total number of pages
        $page_data = [];
        foreach($range as $item){
            if(count($key) > 0){
                $page_data[] = Redis::hMGet($redis_key.'_'.$item,$key); // obtain hash table  All set data in
            }else{
                $page_data[] = Redis::hGetAll($redis_key.'_'.$item);
            }
        }
        $return_data = [
            'data' => $page_data, //  Returned data
            'page' => $page, // The current page
            'pageSize' => $pageSize,  // Records per page
            'total' => $count, // Total entries
            'pageCount' => $pageCount, // Total number of pages
        ];
        return $return_data;
    }

    /**
     * @param int $db
     * @return bool
     */
    public function clear_db($db = 0){
        if ($db >= 0 ) {
            Redis::flushDB();
        }elseif ($db == -1 ){
            Redis::flushAll();
        }else{
            //  Illegal database parameter
            return false;
        }
        return true;
    }
}
