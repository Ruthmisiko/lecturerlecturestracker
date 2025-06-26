<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClasssRequest;
use App\Http\Requests\UpdateClasssRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\ClasssRepository;
use Illuminate\Http\Request;
use Flash;

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
        $classses = $this->classsRepository->paginate(10);

        return view('classses.index')
            ->with('classses', $classses);
    }

    /**
     * Show the form for creating a new Classs.
     */
    public function create()
    {
        return view('classses.create');
    }

    /**
     * Store a newly created Classs in storage.
     */
    public function store(CreateClasssRequest $request)
    {
        $input = $request->all();

        $classs = $this->classsRepository->create($input);

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
        $classs = $this->classsRepository->find($id);

        if (empty($classs)) {
            Flash::error('Classs not found');

            return redirect(route('classses.index'));
        }

        return view('classses.edit')->with('classs', $classs);
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
