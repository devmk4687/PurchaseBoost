<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Campaign::latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $campaign = Campaign::create([
            'name'=> $request->name,
            'description'=>$request->description,
            'status'=> 'draft'

        ]);

        return response()->json($campaign);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show(Campaign $campaign)
    {
        return $campaign;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $campaign
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Campaign $campaign)
    {
        $campaign->update($request->all());
        return $campaign;
    }

    /**
     * Publis the specified resource from storage.
     *
     * @param  int  $campaign
     * @return \Illuminate\Http\Response
     */
    public function publish(Campaign $campaign)
    {
        $campaign->update(['status'=>'published']);
        return $campaign->response()->json(['status'=>'published']);
    }
}
