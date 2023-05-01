<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class Staffdetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_details';

    protected $guarded = [];

    public function getEncryptidAttribute($value)
    {
        return encrypt(env('APP_KEY') . $value);
    }
    public static function getstaffdetails($page, $offset, $sort, $search_filter)
    {
        $fields = [
            'staff_details.id',
            'staff_details.uuid',
            'staff_details.id AS encryptid',
            'staff_details.name',
            'staff_details.email',
            'staff_details.phone_number',
            'staff_details.user_name',
            'staffgroups.group_name AS group_name',
            'site_info.site_name AS site_name',
            'roles.role_name AS role_name',
            'staff_details.status as status_id',
            DB::raw('CASE WHEN staff_details.status = 1 THEN "Active" ELSE "In-Active" END AS status, DATE_FORMAT(staff_details.created_at, "%d-%b-%Y %r") AS date_created'),
        ];
        $query = self::select($fields)->leftjoin('site_info','site_info.id','staff_details.site_ids')
        ->leftjoin('staffgroups','staffgroups.id','staff_details.user_groups_ids')
        //->leftjoin('roles','roles.id','staff_details.role_ids');
        ->leftjoin('roles as roles',\DB::raw("FIND_IN_SET(roles.id,staff_details.role_ids)"),">",\DB::raw("'0'"));


        if ($search_filter) {
            $query->where($search_filter);
        }

        $totalItems = $query->count();
        $totalPages = ceil($totalItems / $offset);

        if ($page <= 0) {
            $page = 0;
        } elseif ($page > $totalPages) {
            $page = $totalPages - 1;
        } else {
            $page--;
        }

        $records = $query->orderBy($sort['field'], $sort['order'])
        ->offset($page * $offset)
        ->limit($offset)
        ->get()
        ->toArray();

        return [
            'records' => $records,
            'current_page' => $page + 1,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
        ];
    }


    /**
     * Store  details
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Array $response
     */
    public static function storeRecords(Request $request)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.staff_details_management")) {
            abort(403);
        }

        $response = [];
        $response['status_code'] = config('response_code.Bad_Request');

        if ($request->has('staff_details_id')) {
            $staffdetails = self::where(['uuid' => $request->staff_details_id])->first();
            $staffdetails->updated_by = Auth::id();
            if(isset($request->created_at))
            {
                $staffdetails->updated_at = $request->created_at; 
            }
        } else {
            $staffdetails = new self();
            $staffdetails->created_by = Auth::id();
            $staffdetails->created_at = date('Y-m-d H:i:s');
            $staffdetails->uuid = \Str::uuid()->toString();
            $staffdetails->status = 1;
        }
        $staffdetails->name = $request->name;
        $staffdetails->user_name = $request->user_name;
        if($request->has('staff_details_id')){
        }else{
            $staffdetails->password = $request->password;
        }
        $staffdetails->email = $request->email;
        $staffdetails->phone_number = $request->phone_number;
        $staffdetails->user_groups_ids = $request->user_groups_id;
        $staffdetails->site_ids = $request->site_id;
        $staffdetails->sub_contractor = $request->sub_contractor;
        $staffdetails->role_ids = $request->role_id;
        $staffdetails->save();

        $response['status_code'] = '200';
        $response['staff_details_id'] = $staffdetails->id;
        if ($request->has('staff_details_id')) {
            $response['message'] = "Staff details has been updated successfully";
        } else {
            $response['message'] = "Staff details has been created successfully";
        }
        $response['data'] = [
            'redirect_url' => url(route('staff-details-list')),
        ];

        return $response;
    }
    public static function getAll(array $fields, array $filter = []): array
    {
        return self::select($fields)
        ->where($filter)
        ->get()
        ->toArray();
    }
    public static function getStaffGroupDetails($id){
    	$fields = [
            'staff_details.id',
            'staff_details.uuid',
            'staff_details.id AS encryptid',
            'staff_details.name',
            'staff_details.email',
            'staff_details.phone_number',
            'staff_details.user_name',
            'staffgroups.group_name AS group_name',
            'site_info.site_name AS site_name',
            'roles.role_name AS role_name',
            'staff_details.status as status_id',
            DB::raw('CASE WHEN staff_details.status = 1 THEN "Active" ELSE "In-Active" END AS status, DATE_FORMAT(staff_details.created_at, "%d-%b-%Y %r") AS date_created'),
        ];
        $query = self::select($fields)->leftjoin('site_info','site_info.id','staff_details.site_ids')
        ->leftjoin('staffgroups','staffgroups.id','staff_details.user_groups_ids')
        ->leftjoin('roles as roles',\DB::raw("FIND_IN_SET(roles.id,staff_details.role_ids)"),">",\DB::raw("'0'"));
        $records = $query->where('staff_details.uuid',$id)->first();
        return $records;
    }

}
