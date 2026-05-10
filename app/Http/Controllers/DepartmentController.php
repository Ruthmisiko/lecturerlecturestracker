<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Repositories\DepartmentRepository;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Controllers\AppBaseController;

class DepartmentController extends AppBaseController
{
    private $departmentRepository;

    public function __construct(DepartmentRepository $departmentRepo)
    {
        $this->departmentRepository = $departmentRepo;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $query = Department::where('user_id', $ownerId);
        if ($user->scopedDepartmentId()) {
            $query->where('id', $user->scopedDepartmentId());
        }
        $departments = $query->paginate(15);

        return view('departments.index')->with('departments', $departments);
    }

    public function create()
    {
        return view('departments.create');
    }

    public function store(CreateDepartmentRequest $request)
    {
        $input = $request->all();
        $user = Auth::user();
        $input['user_id'] = $user->user_id ?? $user->id;

        $this->departmentRepository->create($input);

        Flash::success('Department saved successfully.');

        return redirect(route('departments.index'));
    }

    public function show($id)
    {
        $department = $this->departmentRepository->find($id);

        if (empty($department)) {
            Flash::error('Department not found');
            return redirect(route('departments.index'));
        }

        $department->load('units', 'lecturers');

        return view('departments.show')->with('department', $department);
    }

    public function edit($id)
    {
        $department = $this->departmentRepository->find($id);

        if (empty($department)) {
            Flash::error('Department not found');
            return redirect(route('departments.index'));
        }

        return view('departments.edit')->with('department', $department);
    }

    public function update($id, UpdateDepartmentRequest $request)
    {
        $department = $this->departmentRepository->find($id);

        if (empty($department)) {
            Flash::error('Department not found');
            return redirect(route('departments.index'));
        }

        $this->departmentRepository->update($request->all(), $id);

        Flash::success('Department updated successfully.');

        return redirect(route('departments.index'));
    }

    public function destroy($id)
    {
        $department = $this->departmentRepository->find($id);

        if (empty($department)) {
            Flash::error('Department not found');
            return redirect(route('departments.index'));
        }

        $this->departmentRepository->delete($id);

        Flash::success('Department deleted successfully.');

        return redirect(route('departments.index'));
    }
}
