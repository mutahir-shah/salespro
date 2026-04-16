<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\{Warehouse, Biller, Employee, User, Department, Designation, Shift};
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;
use Illuminate\Support\Facades\{File, DB, Auth};
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    use TenantInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('employees-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            $lims_employee_all = Employee::with('user')->where('is_active', true)->get();
            $lims_department_list = Department::where('is_active', true)->get();
            $numberOfEmployee = Employee::where('is_active', true)->count();
            $lims_shift_list = Shift::where('is_active', true)->get();
            $lims_designation_list = Designation::active()->get();
            $lims_role_list = Role::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();

            return view('backend.employee.index', compact(
                'lims_biller_list',
                'lims_warehouse_list',
                'lims_role_list',
                'lims_designation_list',
                'lims_shift_list',
                'lims_employee_all',
                'lims_department_list',
                'all_permission',
                'numberOfEmployee'
            ));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('employees-add')) {
            $lims_role_list = Role::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_department_list = Department::where('is_active', true)->get();
            $lims_shift_list = Shift::where('is_active', true)->get();
            $lims_designation_list = Designation::active()->get();

            $numberOfEmployee = Employee::where('is_active', true)->count();
            $numberOfUserAccount = User::where('is_active', true)->count();

            $general_setting = \App\Models\GeneralSetting::first();
            if (in_array('project', explode(',', $general_setting->modules))) {
                $companies = \Modules\Project\Entities\Company::where('is_active', true)->get();
            } else {
                $companies = [];
            }

            return view('backend.employee.create', compact(
                'lims_role_list',
                'lims_warehouse_list',
                'lims_biller_list',
                'lims_department_list',
                'numberOfEmployee',
                'numberOfUserAccount',
                'companies',
                'lims_shift_list',
                'lims_designation_list'
            ));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function store(Request $request)
    {
        $data = $request->except('image');
        $message = 'Employee created successfully';

        $data['name'] = $data['employee_name'];
        $data['is_active'] = true;

        // Handle user creation if checkbox selected
        if (isset($data['user'])) {
            $this->validate($request, [
                'name' => [
                    'max:255',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'email' => [
                    'email',
                    'max:255',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
            ]);

            $data['is_deleted'] = false;
            $data['password'] = bcrypt($data['password']);
            $data['phone'] = $data['phone_number'];
            if (isset($data['company']))
                $data['company_name'] = $data['company'];

            User::create($data);
            $user = User::latest()->first();
            $data['user_id'] = $user->id;
            $message = 'Employee created successfully and added to user list';
        }

        // Validation for employee table
        $this->validate($request, [
            'email' => [
                'max:255',
                Rule::unique('employees')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        // Handle employee image upload
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/employee'), $imageName);
            } else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/employee'), $imageName);
            }
            $data['image'] = $imageName;
        }

        $isSaleAgent = $data['is_sale_agent'] ?? 0;

        Employee::create([
            'name'                  => $data['name'],
            'email'                 => $data['email'] ?? null,
            'phone_number'          => $data['phone_number'] ?? null,
            'address'               => $data['address'] ?? null,
            'city'                  => $data['city'] ?? null,
            'country'               => $data['country'] ?? null,
            'basic_salary'          => $data['basic_salary'] ?? null,
            'staff_id'              => $data['staff_id'] ?? null,
            'department_id'         => $data['department_id'] ?? null,
            'shift_id'              => $data['shift_id'] ?? null,
            'designation_id'        => $data['designation_id'] ?? null,
            'role_id'               => $data['role_id'] ?? null,
            'warehouse_id'          => $data['warehouse_id'] ?? null, // ← warehouse_id
            'biller_id'             => $data['biller_id'] ?? null,
            'user_id'               => $data['user_id'] ?? null,
            'image'                 => $data['image'] ?? null,
            'is_active'             => true,
            'is_sale_agent'         => $isSaleAgent,
            'sales_target'          => $isSaleAgent == 1 ? ($data['sales_target'] ?? null) : null,
        ]);

        if ($isSaleAgent) {
            return redirect('sale-agents')->with('message', $message);
        }

        return redirect('employees')->with('message', $message);
    }

    public function update(Request $request, $id)
    {
        $lims_employee_data = Employee::find($request->employee_id);

        if ($lims_employee_data->user_id) {
            $this->validate($request, [
                'name' => [
                    'max:255',
                    Rule::unique('users')->ignore($lims_employee_data->user_id)->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'email' => [
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($lims_employee_data->user_id)->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
            ]);
        }

        $this->validate($request, [
            'email' => [
                'email',
                'max:255',
                Rule::unique('employees')->ignore($lims_employee_data->id)->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
        ]);

        $data = $request->except('image');
        // Handle image update
        $image = $request->image;
        if ($image) {
            $this->fileDelete(public_path('images/employee/'), $lims_employee_data->image);
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/employee'), $imageName);
            } else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/employee'), $imageName);
            }
            $data['image'] = $imageName;
        }
        $lims_employee_data->update([
            'name'           => $data['name'],
            'email'          => $data['email'] ?? $lims_employee_data->email,
            'phone_number'   => $data['phone_number'] ?? $lims_employee_data->phone_number,
            'address'        => $data['address'] ?? $lims_employee_data->address,
            'city'           => $data['city'] ?? $lims_employee_data->city,
            'country'        => $data['country'] ?? $lims_employee_data->country,
            'basic_salary'   => $data['basic_salary'] ?? $lims_employee_data->basic_salary,
            'staff_id'       => $data['staff_id'] ?? $lims_employee_data->staff_id,
            'department_id'  => $data['department_id'] ?? $lims_employee_data->department_id,
            'shift_id'       => $data['shift_id'] ?? $lims_employee_data->shift_id,
            'designation_id' => $data['designation_id'] ?? $lims_employee_data->designation_id,
            'role_id'        => $data['role_id'] ?? $lims_employee_data->role_id,
            'warehouse_id'   => $data['warehouse_id'] ?? $lims_employee_data->warehouse_id, // ← warehouse_id
            'biller_id'      => $data['biller_id'] ?? $lims_employee_data->biller_id,
            'user_id'        => $data['user_id'] ?? $lims_employee_data->user_id,
            'image'          => $data['image'] ?? $lims_employee_data->image,
        ]);
        return redirect('employees')->with('message', __('db.Employee updated successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $employee_id = $request['employeeIdArray'];
        foreach ($employee_id as $id) {
            $lims_employee_data = Employee::find($id);
            if ($lims_employee_data->user_id) {
                $lims_user_data = User::find($lims_employee_data->user_id);
                $lims_user_data->is_deleted = true;
                $lims_user_data->save();
            }
            $lims_employee_data->is_active = false;
            $lims_employee_data->save();
            $this->fileDelete(public_path('images/employee/'), $lims_employee_data->image);
        }

        return 'Employee deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_employee_data = Employee::find($id);
        if ($lims_employee_data->user_id) {
            $lims_user_data = User::find($lims_employee_data->user_id);
            $lims_user_data->is_deleted = true;
            $lims_user_data->save();
        }

        $this->fileDelete(public_path('images/employee/'), $lims_employee_data->image);

        $lims_employee_data->is_active = false;
        $lims_employee_data->save();
        return redirect('employees')->with('not_permitted', __('db.Employee deleted successfully'));
    }

    /**
     * Display the report page.
     */
    public function commission()
    {
        $role = Role::find(Auth::user()->role_id);
        $permissions = Role::findByName($role->name)->permissions;
        foreach ($permissions as $permission)
            $all_permission[] = $permission->name;
        if (empty($all_permission))
            $all_permission[] = 'dummy text'; 
        $employees = Employee::select('id', 'name')->where('is_active', true)->get();
         
        return view('backend.employee.employee_commission', compact('employees', 'all_permission'));
    }

    /**
     * Return DataTables JSON for the report.
     * Query params: employee_id (required)
     */
    public function data(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        $employeeId = $request->employee_id;

        /*
         * Join chain:
         *  employees  →  users  (employees.user_id = users.id)
         *  users      →  sales  (users.id = sales.user_id)
         *  sales      →  product_sales  (sales.id = product_sales.sale_id)
         *  sales      →  biller_commissions  (sales.id = biller_commissions.sale_id)
         *
         * Commission per product line is calculated proportionally:
         *   line_commission = (product_sales.total / sales.grand_total) * biller_commissions.commission_amount
         *
         * Profit per line:
         *   profit = product_sales.total - (product_sales.net_unit_price - product_sales.discount + product_sales.tax) * product_sales.qty
         *   Simplified: product_sales.total - (product_sales.qty * product_sales.net_unit_price)
         *   Because net_unit_price already reflects cost, and total = qty * sale_price after discount/tax.
         *   We store cost in products table (not in schema provided), so we use:
         *   profit = sales.total_profit * (product_sales.total / sales.grand_total)  (proportional split)
         */

        $query = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->join('employees as e', 'e.user_id', '=', 'u.id')
            ->leftJoin('biller_commissions as bc', function ($join) {
                $join->on('bc.sale_id', '=', 's.id')
                    ->on('bc.biller_id', '=', 's.biller_id');
            })
            ->leftJoin('products as p', 'p.id', '=', 'ps.product_id')
            ->where('e.id', $employeeId)
            ->whereNull('s.deleted_at')
            ->select([
                'ps.id as ps_id',
                'p.name as product_name',

                // product cost (purchase price) — stored in products table
                DB::raw('COALESCE(p.cost, 0) as product_cost'),

                // net unit price = sale price per unit (after discount, before tax)
                DB::raw('ps.net_unit_price as product_sales_price'),

                // qty sold
                DB::raw('(ps.qty - ps.return_qty) as qty_sold'),

                // total sale amount for this line
                DB::raw('ps.total as total_sale'),

                // proportional profit for this line
                DB::raw('CASE WHEN s.grand_total > 0
                            THEN ROUND(s.total_profit * (ps.total / s.grand_total), 2)
                            ELSE 0 END as profit'),

                // proportional commission for this line
                DB::raw('CASE WHEN bc.commission_amount IS NOT NULL AND s.grand_total > 0
                            THEN ROUND(bc.commission_amount * (ps.total / s.grand_total), 2)
                            ELSE 0 END as salesman_commission'),

                // commission status
                DB::raw('CASE WHEN bc.is_paid = 1 THEN "paid" ELSE "unpaid" END as commission_status'),

                // commission date
                DB::raw('DATE(bc.calculated_at) as commission_date'),

                // sale date
                DB::raw('DATE(s.created_at) as sale_date'),

                // sale reference
                's.reference_no as sale_reference',

                // ids needed for action links
                's.id as sale_id',
                'bc.id as commission_id',
                'bc.is_paid',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('commission_status', function ($row) {
                if ($row->commission_status === 'paid') {
                    return '<span class="badge badge-success">Paid</span>';
                }
                return '<span class="badge badge-warning">Unpaid</span>';
            })
            ->editColumn('commission_date', function ($row) {
                return $row->commission_date
                    ? \Carbon\Carbon::parse($row->commission_date)->format('d/m/Y')
                    : '—';
            })
            ->editColumn('sale_date', function ($row) {
                return $row->sale_date
                    ? \Carbon\Carbon::parse($row->sale_date)->format('d/m/Y')
                    : '—';
            })
            ->addColumn('action', function ($row) {
                $viewUrl   = route('sales.show', $row->sale_id);
                $payUrl    = $row->commission_id && !$row->is_paid
                    ? route('employee.commission.pay', $row->commission_id)
                    : null;

                $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                        Action
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">';

                $btn .= '<a class="dropdown-item" href="' . $viewUrl . '">
                            <i class="fa fa-eye"></i> View Sale
                         </a>';

                if ($payUrl) {
                    $btn .= '<a class="dropdown-item text-success pay-commission-btn"
                                href="#"
                                data-url="' . $payUrl . '"
                                data-commission-id="' . $row->commission_id . '">
                                <i class="fa fa-money-bill"></i> Pay Commission
                             </a>';
                }

                $btn .= '</div></div>';

                return $btn;
            })
            ->rawColumns(['commission_status', 'action'])
            ->make(true);
    }

    /**
     * Mark a single commission record as paid.
     */
    public function payCommission(Request $request, $commissionId)
    {
        $commission = DB::table('biller_commissions')->where('id', $commissionId)->first();

        if (!$commission) {
            return response()->json(['success' => false, 'message' => 'Commission record not found.'], 404);
        }

        if ($commission->is_paid) {
            return response()->json(['success' => false, 'message' => 'Commission already paid.']);
        }

        DB::table('biller_commissions')->where('id', $commissionId)->update([
            'is_paid'      => 1,
            'paid_amount'  => $commission->commission_amount,
            'paid_at'      => now(),
            'updated_at'   => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Commission marked as paid.']);
    }

    /**
     * Pay ALL unpaid commissions for a given employee at once.
     */
    public function payAll(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer|exists:employees,id']);

        $employeeId = $request->employee_id;

        // Get all sale IDs for this employee
        $saleIds = DB::table('sales as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->join('employees as e', 'e.user_id', '=', 'u.id')
            ->where('e.id', $employeeId)
            ->whereNull('s.deleted_at')
            ->pluck('s.id');

        $updated = DB::table('biller_commissions')
            ->whereIn('sale_id', $saleIds)
            ->where('is_paid', 0)
            ->update([
                'is_paid'     => 1,
                'paid_at'     => now(),
                'updated_at'  => now(),
            ]);

        // Also update paid_amount = commission_amount for those records
        DB::table('biller_commissions')
            ->whereIn('sale_id', $saleIds)
            ->where('is_paid', 1)
            ->whereColumn('paid_amount', '<', 'commission_amount')
            ->update([
                'paid_amount' => DB::raw('commission_amount'),
                'updated_at'  => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => $updated . ' commission(s) marked as paid.',
        ]);
    }
}
