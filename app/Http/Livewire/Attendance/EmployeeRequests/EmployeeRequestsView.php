<?php

namespace App\Http\Livewire\Attendance\EmployeeRequests;

use App\Http\Livewire\Basic\Modal;
use App\Models\Attendance;
use App\Models\Employee\EmployeeReport;
use App\Models\Employee\EmployeeRequest;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftDay;
use App\Models\Status;
use App\Models\User;
use App\Notifications\MainNotification;
use App\Services\FCM\FCMService;
use App\Traits\AttendanceTrait;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Http\Request;

class EmployeeRequestsView extends Modal
{

    use AttendanceTrait;

    protected $rules = [
        'employeeRequest.status_id' => 'required|exists:statuses,id',
        'employeeRequest.response' => 'nullable|string'
    ];
    protected $listeners = ['updateLatAndLong' => 'updateLatAndLong'];
    public $employeeRequest;
    public $employeeRequest_id;
    public $statues;
    public $Oldresponse;
    public $deniedId;
    public $timezone = "Africa/Cairo";

    public function mount(Request $request, $id)
    {
        $this->employeeRequest_id = $id;
        $this->employeeRequest = EmployeeRequest::with('status', 'employee')->whereId($id)->first();
        if (!$this->employeeRequest) {
            abort(404);
        }
        $this->statues = Status::where("type", 'employee-requests')->whereIn('name', ['accepted', 'denied'])->pluck('name', 'id')->toArray();
        $this->Oldresponse = $this->employeeRequest->response;
        foreach ($this->statues as $key => $name) {
            if ($name === 'pending') {
                $this->deniedId = $key;
                break;
            }
        }

        $timezone = timezone($request->ip());
        if ($timezone != "") {
            $this->timezone = $timezone;
        }
//        dd(    $this->employeeRequest->status);
//        dd($this->deniedId);
    }

    public function render()
    {
        return view('livewire.attendance.employee-requests.employee-requests-view');
    }


    public function updatedEmployeeRequestStatusId($data)
    {
        if ($data == $this->deniedId) {
            $this->employeeRequest->response = "";
        } else {
            $this->employeeRequest->response = $this->Oldresponse;
        }
    }

    public function updateLatAndLong($data)
    {
        $coo = explode('-', $data);
        $this->employeeRequest->latitude = $coo[0];
        $this->employeeRequest->longitude = $coo[1];
        $this->employeeRequest->save();
        $this->dispatchBrowserEvent('toastr',
            ['type' => 'success', 'message' => __('message.created', ['model' => __('names.location')])]);

        //$this->dispatchBrowserEvent('initMap');
    }

    public function save(Request $request)
    {
        $validated = $this->validate();
        $this->employeeRequest->save();


        $this->dispatchBrowserEvent('toastr',
            ['type' => 'success', 'message' => __('message.updated', ['model' => __('names.employee-request')])]);
//        $statusId = Status::where("type",'tickets')->where('name','accepted')->value('id');
        $this->employeeRequest = EmployeeRequest::whereId($this->employeeRequest_id)->first();


        // approve late // checkin ll user when request created

        $statusId = Status::where('type', 'employee-requests')->where('name', 'accepted')->value('id');
        $deniedStatusId = Status::where('type', 'employee-requests')->where('name', 'denied')->value('id');

        $userTimeZone = timezone($request->ip());
        if ($userTimeZone == "") {
            $userTimeZone = "Africa/Cairo";
        }

        if ($this->employeeRequest->type == "mission") {
            $dayName = Carbon::parse($this->employeeRequest->time_form)->timezone($userTimeZone)->format("D");
            $dayWork = ShiftDay::where(['shift_id' => $this->employeeRequest->employee?->getShift()?->id, 'day_name' => $dayName])->with('shift')->first();
            $overTimeHours = $this->employeeRequest->employee?->getOverTimeHours($this->employeeRequest->created_at, $userTimeZone, $dayWork);
            $WorkHours = $this->employeeRequest->employee?->workHoursInReq($this->employeeRequest->created_at, $userTimeZone, $dayWork);
            $empReport = EmployeeReport::where('employee_id', $this->employeeRequest->employee?->id)->whereDate('created_at', Date('Y-m-d', strtotime($this->employeeRequest->created_at)))->first();
            if (!empty($empReport)) {
                $empReport->overtime_hour_value = round($this->employeeRequest->employee?->finance?->hourly_value * $dayWork->shift?->overtime_cost, 2);
                $empReport->overtime_hours = $this->employeeRequest->employee->has_overtime ? $overTimeHours : 0;
                $empReport->work_hours = $WorkHours;
                $empReport->save();
            } else {
                $empReport = new EmployeeReport([
                    'employee_id' => $this->employeeRequest->employee?->id,
                    'overtime_hours' => $overTimeHours,
                    'hourly_value' => $this->employeeRequest->employee?->finance?->hourly_value,
                    'overtime_hour_value' => round($this->employeeRequest->employee?->finance?->hourly_value * $dayWork->shift?->overtime_cost, 2),
                    'late_hour_value' => round($this->employeeRequest->employee?->finance?->hourly_value * $dayWork->shift?->late_cost, 2),
                    'currency' => $this->employeeRequest->employee?->finance?->currency?->code
                ]);
                $empReport->save();
            }
        }


        $user = User::whereHas('employee', fn($q) => $q->whereHas('requests', fn($q) => $q->where('id', $this->employeeRequest_id)))->first();
        $data = [];
        $data['from'] = config('app.name');
        $data['message'] = 'تم الرد علي طلبك ' . ",تم تغيير حالة طلبك إلي " .
            __('names.' . $this->employeeRequest->status?->name);
        $user->notify(new MainNotification($data));
        $this->close("changeStatus");
        $fcm = new FCMService();
        $fcm->sendNotification([$user->id], "تم الرد علي طلب " .
            $this->employeeRequest->name, $data['message']
            , null, null, null, "users");
    }

}
