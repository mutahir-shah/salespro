<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Employee, HrmSetting, Attendance, Warehouse,GeneralSetting};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Auth};
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    public function indexOld()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('attendance')) {
            $lims_employee_list = Employee::where('is_active', true)->get();
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $general_setting = DB::table('general_settings')->latest()->first();
            if (Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
                $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                    ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                    ->orderBy('attendances.date', 'desc')
                    ->where('attendances.user_id', Auth::id())
                    ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name', 'users.warehouse_id as warehouse_id'])
                    ->get()
                    ->groupBy(['date', 'employee_id']);
            else
                $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                    ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                    ->orderBy('attendances.date', 'desc')
                    ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name', 'users.warehouse_id as warehouse_id'])
                    ->get()
                    ->groupBy(['date', 'employee_id']);

            $lims_attendance_all = [];
            foreach ($lims_attendance_data as  $attendance_data) {
                foreach ($attendance_data as $data) {
                    $checkin_checkout = '';
                    foreach ($data as $key => $dt) {
                        $date = $dt->date;
                        $employee_name = $dt->employee_name;
                        $checkin_checkout .= (($dt->checkin != null) ? $dt->checkin : 'N/A') . ' - ' . (($dt->checkout != null) ? $dt->checkout : 'N/A') . '<br>';
                        $status = $dt->status;
                        $user_name = $dt->user_name;
                        $employee_id = $dt->employee_id;
                    }
                    $lims_attendance_all[] = [
                        'date' => $date,
                        'employee_name' => $employee_name,
                        'checkin_checkout' => $checkin_checkout,
                        'status' => $status,
                        'user_name' => $user_name,
                        'employee_id' => $employee_id,
                        'warehouse_id' => $dt->warehouse_id // warehouse_id
                    ];
                }
            }

            return view('backend.attendance.index', compact('lims_employee_list', 'lims_hrm_setting_data', 'lims_attendance_all', 'lims_warehouse_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('attendance')) {
            $lims_employee_list   = Employee::where('is_active', true)->get();
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            $lims_warehouse_list  = Warehouse::where('is_active', true)->get();

            return view('backend.attendance.index', compact(
                'lims_employee_list',
                'lims_hrm_setting_data',
                'lims_warehouse_list'
            ));
        }

        return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }
    public function datatable(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('attendance')) {
            return response()->json(['error' => 'Not permitted'], 403);
        }
        $general_setting = GeneralSetting::latest()->first();
        $query = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
            ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
            ->select([
                'attendances.date',
                'attendances.employee_id',
                'attendances.status',
                'attendances.checkin',
                'attendances.checkout',
                'attendances.note',
                'employees.name as employee_name',
                'users.name as user_name',
                'users.warehouse_id as warehouse_id',
            ]);

        if (Auth::user()->role_id > 2 && $general_setting->staff_access == 'own') {
            $query->where('attendances.user_id', Auth::id());
        }

        // Filters from request
        if ($request->filled('date')) {
            $query->where('attendances.date', $request->date);
        }
        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $status = $request->status === 'Present' ? 1 : 0;
            $query->where('attendances.status', $status);
        }
        if ($request->filled('warehouse')) {
            $query->where('users.warehouse_id', $request->warehouse);
        }

        // Group by date + employee, then aggregate checkin/checkout
        // We need all rows per date+employee grouped
        $allRows = $query->orderBy('attendances.date', 'desc')->get();

        // Group by date+employee_id
        $grouped = $allRows->groupBy(function ($row) {
            return $row->date . '_' . $row->employee_id;
        });

        $records = $grouped->map(function ($rows) use ($general_setting) {
            $first  = $rows->first();
            $latest = $rows->last();

            // Calculate total hours from first checkin to last checkout
            $totalHours = null;
            if ($first->checkin && $latest->checkout) {
                $in  = Carbon::parse($first->date . ' ' . $first->checkin);
                $out = Carbon::parse($latest->date . ' ' . $latest->checkout);
                if ($out->gt($in)) {
                    $diff       = $in->diff($out);
                    $totalHours = sprintf('%02d:%02d:%02d', $diff->h + ($diff->days * 24), $diff->i, $diff->s);
                }
            }

            return [
                'id'             => $first->id,
                'date'           => $first->date,
                'date_formatted' => date($general_setting->date_format, strtotime($first->date)),
                'employee_name'  => $first->employee_name,
                'checkin'        => $first->checkin ?? 'N/A',
                'checkout'       => $latest->checkout ?? null,
                'total_hours'    => $totalHours ?? '-',
                'status'         => $first->status,
                'user_name'      => $first->user_name,
                'employee_id'    => $first->employee_id,
                'warehouse_id'   => $first->warehouse_id,
            ];
        })->values();

        // DataTables manual pagination
        $totalRecords = $records->count();

        // Global search across employee_name, date, user_name
        if ($request->filled('search') && !empty($request->input('search.value'))) {
            $search = strtolower($request->input('search.value'));
            $records = $records->filter(function ($row) use ($search) {
                return str_contains(strtolower($row['employee_name']), $search)
                    || str_contains(strtolower($row['date']), $search)
                    || str_contains(strtolower($row['user_name']), $search);
            })->values();
        }

        $filteredRecords = $records->count();

        // Ordering
        $orderCol   = $request->input('order.0.column', 0);
        $orderDir   = $request->input('order.0.dir', 'desc');
        $columnMap  = [1 => 'date', 2 => 'employee_name', 4 => 'status', 5 => 'user_name'];
        if (isset($columnMap[$orderCol])) {
            $col = $columnMap[$orderCol];
            $records = $orderDir === 'asc'
                ? $records->sortBy($col)->values()
                : $records->sortByDesc($col)->values();
        }

        // Pagination — -1 length means "All"
        $length = (int) $request->input('length', 10);
        $start  = (int) $request->input('start', 0);

        $paginated = $length === -1
            ? $records
            : $records->slice($start, $length)->values();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $paginated,
        ]);
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $employee_ids = $data['employee_id'];
        $lims_hrm_setting_data = HrmSetting::latest()->first();
        if ($request->checkin) {
            $checkin = $request->checkin;
        } elseif ($lims_hrm_setting_data->checkin) {
            $checkin = $lims_hrm_setting_data->checkin;
        }

        foreach ($employee_ids as $id) {
            $data['date'] = date('Y-m-d', strtotime(str_replace('/', '-', $data['date'])));
            $data['user_id'] = Auth::id();
            $data['employee_id'] = $id;

            // Check if a record with the same date, employee_id, and checkin exists
            $existingAttendance = Attendance::where('date', $data['date'])
                ->where('employee_id', $id)
                ->where('checkin', $data['checkin'])
                ->first();

            if ($existingAttendance) {
                // If duplicate is found, return an error message
                return redirect()->back()->with('error', "Duplicate entry: Check-in time '{$data['checkin']}' for Employee ID $id on {$data['date']} is not permissible.");
            }

            // Find existing attendance for the employee on that date
            $lims_attendance_data = Attendance::whereDate('date', $data['date'])
                ->where('employee_id', $id)
                ->first();

            if (!$lims_attendance_data) {
                // Calculate status based on the check-in time
                $diff = strtotime($checkin) - strtotime($data['checkin']);
                $data['status'] = ($diff >= 0) ? 1 : 0;
            } else {
                // If the record exists, preserve the previous status
                $data['status'] = $lims_attendance_data->status;
            }

            // Insert the new attendance record
            Attendance::create($data);
        }

        return redirect()->back()->with('message', __('db.Attendance created successfully'));
    }


    public function edit($id)
    {
        $attendance = Attendance::findOrFail($id);
        return response()->json($attendance);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->update([
            'checkout' => $request->checkout,
            'note'     => $request->note,
        ]);
        return redirect()->route('attendance.index')->with('message', 'Checkout updated successfully.');
    }


    public function importDeviceCsv(Request $request)
    {
        $upload = $request->file('file');
        if ($request->Attendance_Device_date_format == null || $upload == null) {
            return redirect()->back()->with('not_permitted', __('db.Please select Attendance Device Date Format and upload a CSV file'));
        }

        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if ($ext != 'csv')
            return redirect()->back()->with('not_permitted', __('db.Please upload a CSV file'));

        $filename =  $upload->getClientOriginalName();
        $filePath = $upload->getRealPath();
        //open and read
        $file = fopen($filePath, 'r');
        $exclude_header = fgetcsv($file);

        $employee_all = Employee::all();
        $lims_hrm_setting_data = HrmSetting::latest()->first();
        $checkin = $lims_hrm_setting_data->checkin;
        $data = [];
        //looping through other columns
        while ($columns = fgetcsv($file)) {
            if ($columns[0] == "" || $columns[1] == "")
                continue;

            $staff_id = $columns[0];
            $employee = $employee_all->where('staff_id', $staff_id)->first();
            if (!$employee)
                return redirect()->back()->with('not_permitted', 'Staff id - ' . $staff_id . ' is not available within the POS system');

            $dt_time = explode(' ', $columns[1], 2);
            $attendance_date = Carbon::createFromFormat($request->Attendance_Device_date_format, $dt_time[0])->format('Y-m-d');
            $attendance_time = str_replace(' ', '', $dt_time[1]);
            $i = 0;
            $status = 0;
            foreach ($data as $key => $dt) {
                if ($dt['date'] == $attendance_date && $dt['employee_id'] == $employee->id) {
                    $status = $dt['status'];
                    $i++;
                    if ($dt['checkout'] == null) {
                        $data[$key]['checkout'] =  $attendance_time;
                        $i = -1;
                        break;
                    }
                }
            }
            //checkout update
            if ($i == -1) {
                continue;
            }
            //create attendance at first time for the employee and date
            elseif ($i == 0) {
                $diff = strtotime($checkin) - strtotime($attendance_time);
                if ($diff >= 0)
                    $status = 1;
                else
                    $status = 0;

                $data[] = [
                    'date' => $attendance_date,
                    'employee_id' => $employee->id,
                    'user_id' => Auth::id(),
                    'checkin' => $attendance_time,
                    'checkout' => null,
                    'status' => $status
                ];
            }
            //create attendance after first time
            else {
                $data[] = [
                    'date' => $attendance_date,
                    'employee_id' => $employee->id,
                    'user_id' => Auth::id(),
                    'checkin' => $attendance_time,
                    'checkout' => null,
                    'status' => $status
                ];
            }
        }
        //create composite via migration with this 2nd array parameter
        Attendance::upsert($data, ['date', 'employee_id', 'checkin'], ['checkout']);
        return redirect()->back()->with('message', __('db.Attendance created successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $attendance_selected = $request['attendanceSelectedArray'];
        foreach ($attendance_selected as $att_selected) {
            Attendance::wheredate('date', $att_selected[0])->where('employee_id', $att_selected[1])->delete();
        }
        return 'Attendance deleted successfully!';
    }

    public function delete($date, $employee_id)
    {
        Attendance::wheredate('date', $date)->where('employee_id', $employee_id)->delete();
        return redirect()->back()->with('not_permitted', __('db.Attendance deleted successfully'));
    }
}
