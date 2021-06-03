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
        if($totalActualLeads <= $totalCampaignsSelected){
            foreach($all_campaigns as $key=>$val){
                if(!empty($data[$key+1])){
                    $csvRow = $data[$key+1];
                    $varEmail = $csvRow[6];
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
                    //$jsonData = $objLemlistApi->callApiWithData($arrPostData,"{$val}/leads/{$varEmail}?deduplicate=true");
                    //var_dump($jsonData);
                    $is_inserted_lemlist = !empty($jsonData) ? 1 : 0;
                    $attributes = [
                        'campaign_id'=>$val,
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
                        'uploaded_sheet_id'=>$insertSheet->id
                    ];
                    
                }
            }
        }else{
            //echo "leads-".$totalActualLeads."<br>campaigns-".$totalCampaignsSelected."<br>";
            $dataCountToeachCampaign = ceil($totalActualLeads/$totalCampaignsSelected);
            //echo $dataCountToeachCampaign;
            $counter = 1;
            foreach($all_campaigns as $key=>$val){
                for($i=0;$i<$dataCountToeachCampaign;$i++){
                    // checks if reaches to end of the csv record
                    if($counter > $totalActualLeads){break;}
                    // chcek if that node exists
                    if(!empty($data[$counter])){
                        $csvRow = $data[$counter];
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

                        //$jsonData = $objLemlistApi->callApiWithData($arrPostData,"{$val}/leads/{$varEmail}?deduplicate=true");
                        //var_dump($jsonData);
                        $is_inserted_lemlist = !empty($jsonData) ? 1 : 0;
                        $attributes = [
                            'campaign_id'=>$val,
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
                            'uploaded_sheet_id'=>$insertSheet->id
                        ];
                        $this->_model->create($attributes);

                        $counter++;
                    }
                }

            }
            //exit;
        }
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
        if($user->role_id==2){
            $sheets = Sheet::with('user')->where('uploaded_by',$user->id)->orderBy('id','desc')->get();
        }else{
            $sheets = Sheet::with('user')->orderBy('id','desc')->get();
        }
        $table = new DataTables();
        return $table->of($sheets)
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
                })->addIndexColumn()
            ->toJson();
    }
    public function getLeadsWithDataTable($id){
        $lead = $this->_model->with('sheet')->where('uploaded_sheet_id',$id)->orderBy('id','desc')->get();
        $table = new DataTables();
        return $table->of($lead)
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

   
}