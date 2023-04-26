<?php

namespace App\Http\Controllers\master;

use App\Admin;
use App\Models\Productdetails;
use App\Models\Materials;
use App\Models\Units;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Redirect;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Validator;

class Centering_materialsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function list(Request $request)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management")) {
            abort(403);
        } else {
            if ($request->has('request_type')) {
                $searchField = [
                    'materials'      => 'materials.materials',
                    'status'    => 'materials.status',
                ];
                $sortField   = [
                    'materials'     => 'materials.materials',
                    'status'  => 'materials.status',
                    'date_created' => 'materials.created_at'
                ];
                $search_filter = [];
                $sort = [
                    'field' => 'materials.created_at',
                    'order' => 'desc'
                ];
                $page = config('pagination.page');
                if ($request->has('page')) {
                    $page = $request->page;
                }

                $offset = config('pagination.offset');
                if ($request->has('offset')) {
                    $offset = $request->offset;
                }

                if ($request->has('sort')) {
                    $name = $request->sort['field'];
                    if (isset($sortField[$name])) {
                        $sort['field'] = $sortField[$name];
                        $sort['order'] = $request->sort['order'];
                    }
                }

                $filters = $request->get('search') ? $request->get('search') : [];

                foreach ($filters as $search_field => $search_value) {
                    $search_value = strip_tags($search_value); //Sanitization
                    $table_field = $searchField[$search_field];
                    if (in_array($search_field, ["status"])) {
                        array_push($search_filter, [$table_field, '=', $search_value]);
                    } else {
                        array_push($search_filter, [$table_field, 'LIKE', '%' . addslashes($search_value) . '%']);
                    }
                }
                $search_filter['material_id'] = 2;

                $records = Materials::get_materials($page, $offset, $sort, $search_filter);
                //print_r($records);exit;

                if (!empty($records['records'])) {
                    $statusCode = '200';
                    $message    = "Data are retrieved Successfully";
                    $data       = $records;
                } else {
                    $statusCode = '400';
                    $message    = "No centering_materials found";
                    $data       = $records;
                }

                $response = response()->json([
                    'message' => $message,
                    'data'    => $data,
                    'error'   => (object)[]
                ], $statusCode);

                return $response;
            } else {
                $statuses = [['value' => 1, 'label' => 'Active'], ['value' => 0, 'label' => 'In-Active']];

                $role = session('user_role');
                $create_access     = config("roles.{$role}.centering_materials_management_access.create");
                $view_access     = config("roles.{$role}.centering_materials_management_access.view");
                $edit_access     = config("roles.{$role}.centering_materials_management_access.edit");
                $delete_access   = config("roles.{$role}.centering_materials_management_access.delete");
                $change_status_access   = config("roles.{$role}.centering_materials_management_access.change_status");

                return view('master.centering_materials.list', compact('statuses', 'create_access', 'view_access', 'edit_access', 'delete_access', 'change_status_access'));
            }
        }
    }

    public function create(Request $request)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management_access.create")) {
            abort(403);
        } else {
            $search = ['status' => 1,'category_id'=>2];
            $fields = ['id','product_name'];
            $categories = Productdetails::getAll($fields,$search);

            $search1 = ['status' => 1];
            $fields1 = ['id','unit_name'];
            $units = Units::getAll($fields1,$search1);
            return view('master.centering_materials.create',compact('categories','units'));
        }
    }
    public function store(Request $request)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management")) {
            abort(403);
        } else {

            $fieldValidation['rate_unit']       = ['required'];
            $fieldValidation['category_id']     = ['required'];
            $fieldValidation['units_id']        = ['required'];
           

            $errorMessages    = [
                'centering_materials.required'             => "Please enter the name",
                'centering_materials.regex'                => "Should include only Two Decimal Places",
            ];

            $validator = app('validator')->make($request->all(), $fieldValidation, $errorMessages);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->input())->withErrors($validator);
            }

            $request['material_id']= 2;
            if($request->has('centering_materials_id')){
                $request['created_at']=date('Y-m-d H:i:s');
                $response   = Materials::storeRecords($request);
            }
            else{
                $response   = Materials::storeRecords($request); 
            }

            $statusCode = $response['status_code'];
            $error      = isset($response['error']) ? $response['error'] : (object)[];
            $message    = $response['message'];
            $data       = isset($response['data']) ? $response['data'] : (object)[];

            return redirect('master/centering-materials/list'); 

        }
    }

    public function view($id)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management_access.view")) {
            abort(403);
        } else {

            $search = ['status' => 1,'category_id'=>2];
            $fields = ['id','product_name'];
            $categories = Productdetails::getAll($fields,$search);

            $search1 = ['status' => 1];
            $fields1 = ['id','unit_name'];
            $units = Units::getAll($fields1,$search1);

            $centering_materials  = Materials::where(['uuid' => $id])->first();
            if ($centering_materials) {

               
                $data = [
                    'units'         => $units,
                    'centering_materials' => $centering_materials,
                    'categories'     => $categories,
                ];

                return view('master.centering_materials.view', $data);
            } else {
                $data = [
                    'message' => "Invalid centering_materials"
                ];

                return view('error_view', $data);
            }
        }
    }

    public function edit($id)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management_access.edit")) {
            abort(403);
        } else {
            // $centering_materials  = Materials::find($id);
            $search = ['status' => 1,'category_id'=>2];
            $fields = ['id','product_name'];
            $categories = Productdetails::getAll($fields,$search);

            $search1 = ['status' => 1];
            $fields1 = ['id','unit_name'];
            $units = Units::getAll($fields1,$search1);

            $centering_materials  = Materials::where(['uuid' => $id])->first();
            if ($centering_materials) {

               
                $data = [
                    'units'         => $units,
                    'centering_materials' => $centering_materials,
                    'categories'     => $categories,
                ];

                return view('master.centering_materials.edit', $data);
            } else {
                $data = [
                    'message' => "Invalid centering_materials"
                ];

                return view('error_view', $data);
            }
        }
    }

    public function updateStatus($id)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management_access.edit")) {
            abort(403);
        } else {
            $centering_materials  = Materials::where(['uuid' => $id])->first();
            $centering_materials->status = ($centering_materials->status) ? 0 : 1;
            $centering_materials->save();

            $data = [
                'redirect_url' => url(route('centering-materials-list'))
            ];

            $statusCode = '200';
            $error      = (object)[];
            $message    = "Status has been changed Successfully";

            return response()->json([
                'message' => $message,
                'data'    => $data,
                'error'   => $error
            ], $statusCode);
        }
    }

    public function delete($id)
    {
        $role = session('user_role');
        if (!config("roles.{$role}.centering_materials_management_access.delete")) {
            abort(403);
        } else {
            $result = Materials::where('uuid', $id)->delete();

            $data = [
                'redirect_url' => url(route('centering-materials-list'))
            ];

            $statusCode = '200';
            $error      = (object)[];
            $message    = "Data has been deleted Successfully";

            return response()->json([
                'message' => $message,
                'data'    => $data,
                'error'   => $error
            ], $statusCode);
        }
    }
}
