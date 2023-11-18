<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\EmployeeRequestResource;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Status;
use App\Models\User;
use App\Notifications\MainNotification;
use App\Traits\AttendanceTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EmployeeRequestsController extends ApiController
{
    use AttendanceTrait;
    public function __construct()
    {
        parent::__construct();
    }

    public function requests(Request $request)
    {
        $user = auth('api')->user();
        $id = Employee::where('user_id', auth('api')->id())->value('id');
        $requests = EmployeeRequest::with('status:id,name')->where('employee_id', $id)->get();
//        return $requests ;
        return $this->successResponse(EmployeeRequestResource::collection($requests));
    }

    public function createRequest(Request $request)
    {

        $rules = [
            'type' => 'required|string|in:remote,mission,overtime,late,checkoutPermission',
            'name' => 'required_if:type,mission|string',
            'responsible' => 'required_if:type,mission|string',
            'time_from' => 'required_if:type,mission|date',
            'time_to' => 'required_if:type,mission|date',

            'latitude' => 'required_if:type,mission|numeric',
            'longitude' => 'required_if:type,mission|numeric',
            'address' => 'required_if:type,mission|string',

//            'imei' => 'nullable|string',
//            'device_token' => 'nullable|string',
//            'device_type' => 'nullable|string|in:android,ios',
        ];

        $validator = validator()->make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $userTimeZone = timezone($request->ip());
        if ($userTimeZone == "") {
            $userTimeZone = "Africa/Cairo";
        }

        $input = $request->all();
        $employee = Employee::draft(0)->where('user_id', auth('api')->id())->with('workAt')->first();
        if (!$employee) {
            return $this->errorResponse('Employee not found');
        }


        if ($input['type'] != 'mission'){
            $input['name'] = __('names.'. $input['type']);
//          $input['responsible'] =    auth('api')->user()->name;// until migrate
        }

        $statusId = Status::where('type','employee-requests')->where('name','pending')->value('id');

        $DeniedstatusId = Status::where('type','employee-requests')->where('name','denied')->value('id');
        $oldReq = EmployeeRequest::where(['employee_id' => $employee->id, 'type' => $input['type'] , ['status_id' , '<>' , $DeniedstatusId]])->whereDate('created_at', Carbon::today())->first();
        if(!empty($oldReq)) {
            return $this->errorResponse('Request Already Created Before', 500);
        }
        if($input['type'] == 'mission') {
//            return $this->errorResponse($input, 500);

            $input['time_from'] = Carbon::parse($input['time_from'], $userTimeZone)->utc()->format('Y-m-d H:i:s');
            $input['time_to'] = Carbon::parse($input['time_to'], $userTimeZone)->utc()->format('Y-m-d H:i:s');
            // $input['time_from'] = Date('Y-m-d h:i:s', strtotime($input['time_from']));
            // $input['time_to'] = Date('Y-m-d h:i:s', strtotime($input['time_to']));

            $dest = $input['latitude'] .','. $input['longitude'];
            $latAndLong = $this->getLatAndLong($employee);

            if(gettype($latAndLong) != 'array') {
                return $this->errorResponse('No Location Detected For Employee', 500);
            }

            $origin = $latAndLong['lat'] . ',' . $latAndLong['long'];



            $timeBetween = $this->getTimeBetweenTwoLocations($dest, $origin);
            if($timeBetween == "Error") {
                return $this->errorResponse('Cannot Detect Time To Destnation', 500);
            }

            $input['time_valid_to'] = date('Y-m-d H:i:s', strtotime($input['time_from']) + $timeBetween * 2);
            $input['time_valid_in_seconds'] = $timeBetween * 2;

        } else if ($input['type'] == 'overtime') {
            $input['time_from'] = Date('Y-m-d h:i:s');
            $input['time_to'] = null;
        }  else {
            $input['time_from'] = Date('Y-m-d h:i:s');
            $input['time_to'] = Date('Y-m-d h:i:s',strtotime("+10 hours"));
        }
        $input['from'] = 'application';
        $input['employee_id'] = $employee->id;
        $input['status_id'] = $statusId;
        EmployeeRequest::create($input);
        $permissions = ['attendance.requests.changeStatus','attendance.requests.view'];
        $users = User::whereHas('permissions',fn($q)=>$q->whereIn('name',$permissions))
        ->orWhereHas('roles',fn($q)=>$q->whereHas('permissions',fn($q)=>$q->where('name',$permissions)))->get();
        $data = [];
        $data['from'] =  config('app.name');
        $data['message'] = 'الموظف ' . $employee->first_name .' أرسل طلب ' .  __('names.'.$input['type']);
        $data['url'] =      route('admin.attendance.requests.index');
        Notification::send($users, new MainNotification($data));

        return $this->successResponse('Request Received Successfully ');
    }

    public function employees(Request $request){
        $employees = Employee::draft(0)->get(['id','first_name','second_name','last_name']);
        $employees->map(fn($emp)=> $emp->full_name = $emp->first_name . ' '. $emp->second_name . ' '.$emp->last_name  );
        return $this->successResponse($employees);
    }


    //
}
