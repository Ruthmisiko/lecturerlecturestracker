<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Unit;
use App\Models\Classs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ClasssRepository;
use App\Http\Requests\CreateClasssRequest;
use App\Http\Requests\UpdateClasssRequest;
use App\Http\Controllers\AppBaseController;

class ClasssController extends AppBaseController
{
    /** @var ClasssRepository $classsRepository*/
    private $classsRepository;

    public function __construct(ClasssRepository $classsRepo)
    {
        $this->classsRepository = $classsRepo;
    }

    /**
     * Display a listing of the Classs.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $ownerId = $user->user_id ?? $user->id;

        $classses = Classs::where('user_id', $ownerId)->paginate(10);

        return view('classses.index')
            ->with('classses', $classses);
    }

    /**
     * Show the form for creating a new Classs.
     */
    public function create()
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        // Only fetch units that belong to this tenant
        $units = Unit::where('user_id', $ownerId)->get();

        return view('classses.create', compact('units'));
    }
    /**
     * Store a newly created Classs in storage.
     */
    public function store(CreateClasssRequest $request)
    {
        $input = $request->all();

        $user = Auth::user();

        $ownerId = $user->user_id ?? $user->id;

        $input['user_id'] = $user->user_id ?? $user->id;

        $classs = $this->classsRepository->create($input);

        if ($request->has('units')) {
            $validUnitIds = Unit::where('user_id', $ownerId)
                                ->whereIn('id', $request->units)
                                ->pluck('id')
                                ->toArray();

            $classs->units()->sync($validUnitIds);
        }

        Flash::success('Classs saved successfully.');

        return redirect(route('classses.index'));
    }

    /**
     * Display the specified Classs.
     */
    public function show($id)
    {
        $classs = $this->classsRepository->find($id);

        if (empty($classs)) {
            Flash::error('Classs not found');

            return redirect(route('classses.index'));
        }

        return view('classses.show')->with('classs', $classs);
    }

    /**
     * Show the form for editing the specified Classs.
     */
    public function edit($id)
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $classs = $this->classsRepository->find($id);
        if (empty($classs)) {
            Flash::error('Classs not found');
            return redirect(route('classses.index'));
        }

        // Only fetch units that belong to this tenant
        $units = Unit::where('user_id', $ownerId)->get();
        $selectedUnits = $classs->units->pluck('id')->toArray();

        return view('classses.edit', compact('classs', 'units', 'selectedUnits'));
    }

    /**
     * Update the specified Classs in storage.
     */
    public function update($id, UpdateClasssRequest $request)
    {
        $classs = $this->classsRepository->find($id);

        if (empty($classs)) {
            Flash::error('Classs not found');

            return redirect(route('classses.index'));
        }

        $classs = $this->classsRepository->update($request->all(), $id);

        Flash::success('Classs updated successfully.');

        return redirect(route('classses.index'));
    }

    /**
     * Remove the specified Classs from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $classs = $this->classsRepository->find($id);

        if (empty($classs)) {
            Flash::error('Classs not found');

            return redirect(route('classses.index'));
        }

        $this->classsRepository->delete($id);

        Flash::success('Classs deleted successfully.');

        return redirect(route('classses.index'));
    }
}
