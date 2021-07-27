<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Api\LemlistApi;
use App\Lead;
use App\Sheet;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class LeadRepository extends BaseRepository
{
    
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Lead::class;
    }

    /*
    * function to upload on the lemlist and also save on the database as well
    * @param Request $request
    * @return void
    * @author Shiv Kumar Tiwari
    */
    public function uploadLeadOnLemlist($request){
        //$user = Auth::user();
        $file_name = $request->post('file_uploaded');
        $all_campaigns = $request->post('campaigns');
        $insertSheet = new Sheet();
        $insertSheet->sheet_name = url('public/uploads/csv/'.$file_name);
        $insertSheet->sheet_short_name = $file_name;
        $insertSheet->uploaded_by = Auth::id();
        $insertSheet->save();
        $data = array_map('str_getcsv', file(public_path('uploads/csv/'.$file_name)));
        $objLemlistApi = new LemlistApi('campaigns');
        $arrCampaignsData=[];
        $totalCampaignsSelected = count($all_campaigns);
        $totalActualLeads = count($data)-1;
        //echo "<pre>";var_dump($data);exit;
        $arrChunckData = array_chunk($data,10);
        $e=0;
        foreach($arrChunckData as $key=>$checkData){
            if($e == $totalCampaignsSelected){
                $e=0;
            }
            if(!empty($all_campaigns[$e])){
                foreach($checkData as $csvRow){
                    $varEmail = $csvRow[6];
                    // data to post on lemlist
                    $arrPostData = [
                        'companyName'=>$csvRow[0],
                        'Keyword'=>$csvRow[1],
                        'URL'=>$csvRow[2],
                        'Outreach Description'=>$csvRow[3],
                        'firstName'=>$csvRow[4],
                        'lastName'=>$csvRow[5],
                        'Area of interest'=>$csvRow[7],
                        'Source'=>$csvRow[8],
                        'SDR'=>$csvRow[9],
                    ];
                    $jsonData = $objLemlistApi->callApiWithData($arrPostData,"{$all_campaigns[$e]}/leads/{$varEmail}?deduplicate=true");
                    //var_dump($jsonData);
                    $is_inserted_lemlist = !empty($jsonData) ? 1 : 0;
                    $attributes = [
                        'campaign_id'=>$all_campaigns[$e],
                        'company'=>$csvRow[0],
                        'keyword'=>$csvRow[1],
                        'url'=>$csvRow[2],
                        'description'=>$csvRow[3],
                        'first_name'=>$csvRow[4],
                        'last_name'=>$csvRow[5],
                        'email'=>$varEmail,
                        'area_interest'=>$csvRow[7],
                        'source'=>$csvRow[8],
                        'sdr'=>$csvRow[9],
                        'uploaded_by'=>Auth::id(),
                        'is_inserted_lemlist'=>$is_inserted_lemlist,
                        'sheet_id'=>$insertSheet->id
                    ];
                    $this->_model->create($attributes);
                }
            }
            $e=$e+1;
        }
        // if($totalActualLeads <= $totalCampaignsSelected){
        //     foreach($all_campaigns as $key=>$val){
        //         if(!empty($data[$key+1])){
        //             $csvRow = $data[$key+1];
        //             $varEmail = $csvRow[6];
        //             $arrPostData = [
        //                 'companyName'=>$csvRow[0],
        //                 'Keyword'=>$csvRow[1],
        //                 'URL'=>$csvRow[2],
        //                 'Outreach Description'=>$csvRow[3],
        //                 'firstName'=>$csvRow[4],
        //                 'lastName'=>$csvRow[5],
        //                 'Area of interest'=>$csvRow[7],
        //                 'Source'=>$csvRow[8],
        //                 'SDR'=>$csvRow[9],
        //             ];
        //             $jsonData = $objLemlistApi->callApiWithData($arrPostData,"{$val}/leads/{$varEmail}?deduplicate=true");
        //             //var_dump($jsonData);
        //             $is_inserted_lemlist = !empty($jsonData) ? 1 : 0;
        //             $attributes = [
        //                 'campaign_id'=>$val,
        //                 'company'=>$csvRow[0],
        //                 'keyword'=>$csvRow[1],
        //                 'url'=>$csvRow[2],
        //                 'description'=>$csvRow[3],
        //                 'first_name'=>$csvRow[4],
        //                 'last_name'=>$csvRow[5],
        //                 'email'=>$varEmail,
        //                 'area_interest'=>$csvRow[7],
        //                 'source'=>$csvRow[8],
        //                 'sdr'=>$csvRow[9],
        //                 'uploaded_by'=>Auth::id(),
        //                 'is_inserted_lemlist'=>$is_inserted_lemlist,
        //                 'sheet_id'=>$insertSheet->id
        //             ];
                    
        //         }
        //     }
        // }else{
        //     //echo "leads-".$totalActualLeads."<br>campaigns-".$totalCampaignsSelected."<br>";
        //     $dataCountToeachCampaign = ceil($totalActualLeads/$totalCampaignsSelected);
        //     //echo $dataCountToeachCampaign;
        //     $counter = 1;
        //     foreach($all_campaigns as $key=>$val){
        //         for($i=0;$i<$dataCountToeachCampaign;$i++){
        //             // checks if reaches to end of the csv record
        //             if($counter > $totalActualLeads){break;}
        //             // chcek if that node exists
        //             if(!empty($data[$counter])){
        //                 $csvRow = $data[$counter];
        //                 $varEmail = $csvRow[6];

        //                 // data to post on lemlist
        //                 $arrPostData = [
        //                     'companyName'=>$csvRow[0],
        //                     'Keyword'=>$csvRow[1],
        //                     'URL'=>$csvRow[2],
        //                     'Outreach Description'=>$csvRow[3],
        //                     'firstName'=>$csvRow[4],
        //                     'lastName'=>$csvRow[5],
        //                     'Area of interest'=>$csvRow[7],
        //                     'Source'=>$csvRow[8],
        //                     'SDR'=>$csvRow[9],
        //                 ];

        //                 $jsonData = $objLemlistApi->callApiWithData($arrPostData,"{$val}/leads/{$varEmail}?deduplicate=true");
        //                 //var_dump($jsonData);
        //                 $is_inserted_lemlist = !empty($jsonData) ? 1 : 0;
        //                 $attributes = [
        //                     'campaign_id'=>$val,
        //                     'company'=>$csvRow[0],
        //                     'keyword'=>$csvRow[1],
        //                     'url'=>$csvRow[2],
        //                     'description'=>$csvRow[3],
        //                     'first_name'=>$csvRow[4],
        //                     'last_name'=>$csvRow[5],
        //                     'email'=>$varEmail,
        //                     'area_interest'=>$csvRow[7],
        //                     'source'=>$csvRow[8],
        //                     'sdr'=>$csvRow[9],
        //                     'uploaded_by'=>Auth::id(),
        //                     'is_inserted_lemlist'=>$is_inserted_lemlist,
        //                     'sheet_id'=>$insertSheet->id
        //                 ];
        //                 $this->_model->create($attributes);

        //                 $counter++;
        //             }
        //         }

        //     }
        //     //exit;
        // }
        //unlink(public_path('uploads/csv/'.$file_name));
    }

    /*
    * function to get all capaigns from database
    * @param void
    * @return array of objects
    * @author Shiv Kumar Tiwari
    */
    public function getAllCampaignsWithDataTable(){

        $campaigns = $this->_model->where(DB::raw("1"),1);
        $table = new DataTables();
        return $table->of($campaigns)
                    ->editColumn('updated_at',function($data){
                        return date("d M, Y H:i:s", strtotime($data->updated_at));
                    })->addIndexColumn()->toJson();
    }
    public function getAllSheetsWithDataTable(){
        $user = Auth::user();
        $sheets = Sheet::with('user')->orderBy('id','desc')->get();
        $table = new DataTables();
        return $table->of($sheets)
        ->addColumn('totalLead', function ($row) {
             $totalLeadPath = route('leads.list',['id'=>$row->id]);
             $totalLead = '<a href="'.$totalLeadPath.'">'.$row->lead->count().'</a>';
             return $totalLead;
            })
            ->addColumn('duplicateLead', function ($row) {
                $duplicatePath = route('leads.list',['id'=>$row->id]);
                $duplicates = DB::table('tbl_leads')
						->select(DB::raw('COUNT(*) as `count`'))
						->where('sheet_id',$row->id)
						->where('is_inserted_lemlist',0)
						->get();
			 $duplicateLead = !empty($duplicates[0]) ? '<a href="'.$duplicatePath.'">'.($duplicates[0]->count.'</a>') : 0;
			 return $duplicateLead;
            })
            ->addColumn('action', function ($row) {
                $viewPath = route('leads.list',['id'=>$row->id]);
                 $filePath = url('public/uploads/csv/'.$row->sheet_short_name);
                 $downloadPath = $filePath;
                 $view = '<a href="'.$viewPath.'" class="btn btn-sm btn-icon btn-light-success mr-2" title="View"><i class="la la-eye view"></i></a>';
                 $csvDownload = '<a href="'.$downloadPath.'" class="btn btn-sm btn-icon btn-light-success mr-2" title="Download CSV"><i class="la la-download  download"></i></a>';
                 if(!empty(is_file(public_path('uploads/csv/' . $row->sheet_short_name)))){
                   $action = $csvDownload.' '.$view;
                 }else{
                    $action = $view;
                 }
                return $action;
                })
                ->editColumn('created_at',function($data){
                    return date("d M, Y H:i:s", strtotime($data->created_at));
                })
                ->rawColumns(['totalLead','duplicateLead','action'])
                ->addIndexColumn()
            ->toJson();
    }
    public function getLeadsWithDataTable($id){
        $lead = $this->_model->with('sheet')->where('sheet_id',$id)->orderBy('id','desc')->get();
        $table = new DataTables();
        return $table->of($lead)
           ->addColumn('full_name', function ($row) {
            $fullName = $row->first_name.' '.$row->last_name;
            return $fullName;
            })
            ->addColumn('is_inserted_lemlist', function ($row) {
                $is_inserted_lemlist = ($row->is_inserted_lemlist == 1) ? 'Yes' : 'No';
                return $is_inserted_lemlist;
                })
            ->addColumn('action', function ($row) {
                $viewPath = route('leads.view',['id'=>$row->id]);
                $view = '<a href="'.$viewPath.'" class="btn btn-sm btn-icon btn-light-success mr-2" title="View"><i class="la la-eye view"></i></a>';
                $action = $view;
                return $action;
                })
                ->editColumn('created_at',function($data){
                    return date("d M, Y H:i:s", strtotime($data->created_at));
                })->addIndexColumn()
            ->toJson();
    }
    public function getLeadsWithId($id){
        $lead = $this->_model->with('sheet')->where('id',$id)->first();
        return $lead;
    }
    public function getSheetWithId($id){
        $sheet = Sheet::where('id',$id)->first();
        return $sheet;
    }
    public function getLatestSheets(){
        $sheets = Sheet::orderBy('id', 'desc');
        $sheets = $sheets->limit(5)->get();
		return $sheets;
    }
    public function getLatestLeads(){
        $leads = Lead::orderBy('id', 'desc');
        $leads = $leads->limit(5)->get();
		return $leads;
    }

   
}