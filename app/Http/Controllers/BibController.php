<?php

namespace App\Http\Controllers;

use App\Bib;
use Illuminate\Http\Request;
use App\BibVolume;
use App\BibMarcTag;

class BibController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bibs = Bib::with('marc_tags', 'subjects', 'volumes')->limit(5)->get();
        // dd($bibs);
        if ($request->ajax()) {
            return response()->json($bibs);
        }

        return view('bibs');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $data = $request->g
        $subjects = $request->get('subjects');
        $volumes = $request->get('volumes');
        $marc_tags = $request->get('marc_tags');
        $bib_volumes = [];
        $bib_subjects = [];
        $bib_tags = [];

        foreach ($subjects as  $index => $subject) {
            if (!$subject) {
                unset($subjects[$index]);
            } else {
                $bib_subjects[] = $subject;
            }
        }

        foreach ($volumes as $volume) {
            array_push($bib_volumes, new BibVolume($volume));
        }

        foreach ($marc_tags as  $bib_tag) {
            print_r($bib_tag);
            array_push($bib_tags, new BibMarcTag([
                'marc_tag_id' => $bib_tag['id'],
                'value' => isset($bib_tag['value']) ? $bib_tag['value'] : '',
            ]));
        }
        // dd($bib_volumes);
        $bib = Bib::create();
        $bib->volumes()->saveMany($bib_volumes);
        $bib->subjects()->sync($bib_subjects);
        $bib->bib_marc_tags()->saveMany($bib_tags);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Bib  $bib
     * @return \Illuminate\Http\Response
     */
    public function show(Bib $bib)
    { }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Bib  $bib
     * @return \Illuminate\Http\Response
     */
    public function edit(Bib $bib)
    { }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Bib  $bib
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Bib $bib)
    {
        $subject_ids = $request->get('subjects');
        $volumes = $request->get('volumes');
        $bib_tags = $request->get('marc_tags');
        $bib_subjects = [];
        $volume_ids = [];
        $bib_tag_ids = [];

        foreach ($subject_ids as  $index => $subject_id) {
            if (!$subject_id) {
                unset($subject_ids[$index]);
            } else {
                array_push($bib_subjects, $subject_ids);
            }
        }

        foreach ($bib_tags as $bib_tag) {
            if (!isset($bib_tag['id']) || in_array($bib_tag['id'], $bib_tag_ids)) {
                $bib_tag['id'] = $bib->bib_marc_tags()->max('id') + 1;
                // dd($bib_tag_ids, $bib_tag);
            }

            $bib->bib_marc_tags()->updateOrCreate(['id' => $bib_tag['id']], [
                'marc_tag_id' => $bib_tag['pivot']['marc_tag_id'],
                'value' => $bib_tag['pivot']['value'],
            ]);

            array_push($bib_tag_ids, $bib_tag['id']);
        }


        foreach ($volumes as $volume) {
            if (!isset($volume['id'])) {
                $volume['id'] = $bib->volumes()->max('id') + 1;
            }
            $bib->volumes()->updateOrCreate(['id' => $volume['id']], $volume);
            array_push($volume_ids, $volume['id']);
        }

        $bib->bib_marc_tags()->whereNotIn('id', $bib_tag_ids)->delete();
        $bib->volumes()->whereNotIn('id', $volume_ids)->delete();
        $bib->subjects()->sync($bib_subjects);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Bib  $bib
     * @return \Illuminate\Http\Response
     */
    public function destroy(Bib $bib)
    {
        $bib->delete();
    }
}
