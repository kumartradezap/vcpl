<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class Architect_site extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'architect_site';

    protected $guarded = [];
    /**
     * Get list of admin users
     *
     * @param  int   $page   current page
     * @param  int   $offset page limit
     * @param  Array   $sort sort
     * @param Array $search_filter
     *
     * @return array $response
     */
    public function getEncryptidAttribute($value)
    {
        return encrypt(env('APP_KEY') . $value);
    }
    public static function get_materials($page, $offset, $sort, $search_filter)
    {
        $fields = [
            'Architect_site.id',
            'Architect_site.uuid',
            'Architect_site.id AS encryptid',
            'Architect_site.architect_name',
            'Architect_site.cader',
            'Architect_site.mobile_number',
            'Architect_site.email_id',
            'Architect_site.address',
            'Architect_site.status as status_id',
             DB::raw('CASE WHEN Architect_site.status = 1 THEN "Active" ELSE "In-Active" END AS status,
                CASE WHEN Architect_site.is_company = 1 THEN "Company" ELSE "" END AS is_company,
              DATE_FORMAT(Architect_site.created_at, "%d-%b-%Y %r") AS date_created'),
           
           
        ];
        $query = self::select($fields);

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
     * Store admin details
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Array $response
     */
    public static function storeRecords(Request $request)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management")) {
            abort(403);
        }

        $response = [];
        $response['status_code'] = config('response_code.Bad_Request');

        if ($request->has('architect_name_id')) {
            $materials = self::where(['uuid' => $request->centering_vehicle_id])->first();
            $materials->updated_by = Auth::id();
            if(isset($request->created_at)) {
                $materials->updated_at = $request->created_at; 
            }
        } else {
            $materials = new self();
            $materials->created_by = Auth::id();
            $materials->created_at = date('Y-m-d H:i:s');
            $materials->uuid = \Str::uuid()->toString();
            $materials->status = 1;
        }
      

        $materials->vehicle_name                = ucfirst($request->architect_name);
        $materials->site_id                     = $request->site_id;
        $materials->carer                       = $request->cader;
        $materials->mobile_number               = $request->mobile_number;
        $materials->email_id                    = $request->email_id;
        $materials->address                     = $request->address;
       
        $materials->save();

        $response['status_code'] = '200';
        $response['subcategories_id'] = $materials->id;
        if ($request->has('subcategories_id')) {
            $response['message'] = "Data has been updated successfully";
        } else {
            $response['message'] = "Data has been created successfully";
        }

        return $response;
    }
    public static function getAll(array $fields, array $filter = []): array
    {
        return self::select($fields)
        ->where($filter)
        ->get()
        ->toArray();
    }
    
    public static function getsubcategoriesCount(){
        $fields = [
            DB::raw('Count(id) AS subcategories_count'),
        ];
        $result = self::select($fields)
        ->get()
        ->toArray();
        return isset($result[0]['subcategories_count'])?$result[0]['subcategories_count']:0;
    }

    public static function updateRecord(array $data, $id = 0): int
    {
        DB::table('subcategories')->where('id', $id)->update($data);
        return $id;
    }
    public static function updateDetails($where,$updateDetails)
    {
        self::where($where)->update($updateDetails);
        return true;
    }
    public static function getdashboardcount()
    {

        $fields = [
            DB::raw('COUNT(*) AS total_count'),
            DB::raw('COUNT(id) AS subcategoriess_count')           
        ];
        $query = Self::select($fields);
        $records = $query->first()->toArray();
        return $records;
    }
}