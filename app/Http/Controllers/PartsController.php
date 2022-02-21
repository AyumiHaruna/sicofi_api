<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;

class PartsController extends Controller
{
    //list all total parts
    public function list_all() {
      $parts = DB::select("SELECT * FROM partlist ORDER BY partNumber");
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $parts
      ]);
    }

    //list all avalible and active parts
    public function list()
    {
      $parts = DB::select(" SELECT * FROM partlist WHERE active = 1 ORDER BY partNumber");
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $parts
      ]);
    }

    //INSERT a new part
    public function save(Request $request)
    {
        $input = $request->json()->all();

        /*----------------- RPOJECTPARTS -------------------*/
        //generate query dynamically -> then insert part
        $query = "INSERT INTO projectparts (projectNumber, accountType, chapter, partNumber, total, year, month1, month2, month3, month4, month5, month6, month7, month8, month9, month10, month11, month12) ";
        $query .= "VALUES('".$input[0]['projectNumber']."', ".$input[0]['accountType'].", ".$input[0]['chapter'].", '".$input[0]['partNumber']."', ".$input[0]['total'].", ".$input[0]['year'].", ";
        for ($i=0; $i<count($input[0]['months']) ; $i++) {
          $query .= $input[0]['months'][$i];
          if($i != count($input[0]['months']) - 1 ){
            $query .= ", ";
          }
        }
        $query .= ")";
        // Time to insert into projectParts
        $part = DB::insert($query);

        /*----------------- ACCOUNTCHAPTERS -------------------*/
        // get Affected accountChapters & update accountChapters data
        $chapter = DB::select("SELECT * FROM accountchapters WHERE projectNumber = '".$input[0]['projectNumber']."' AND accountType = '".$input[0]['accountType']."' AND chapter = ".$input[0]['chapter']." AND year = ".$input[0]['year']." AND active = 1");
        $chapter = (array) $chapter[0];
        //update
        $query = "UPDATE accountChapters SET total = ".($chapter['total'] +  $input[0]['total']).", ";
          for ($i=0; $i<12 ; $i++) {
            $query .= "month".($i+1)." = ".( $chapter['month'.$i+1] + $input[0]['months'][$i]);
            if($i < 12 - 1){
              $query .= ", ";
            }
          }
        $query .=  " WHERE id = ".$chapter['id'];
        $chapter = DB::update($query);

        /*----------------- PROJECTACCONTS -------------------*/
        // get affected projectAccount and update
        $account = DB::select("SELECT * FROM projectaccounts WHERE projectNumber = '".$input[0]['projectNumber']."' AND accountType = '".$input[0]['accountType']."' AND year = ".$input[0]['year']." AND active = 1");
        $account = (array) $account[0];
        $account['total'] += $input[0]['total'];
        $account = DB::update("UPDATE projectaccounts SET total = ".$account['total']." WHERE id = ".$account['id']);

        /*----------------- PROJECTS -------------------*/
        // get main project and update ammounts
        $project = DB::select("SELECT * FROM projects WHERE projectNumber = '".$input[0]['projectNumber']."' AND year = ".$input[0]['year']." AND active = 1");
        $project = (array) $project[0];
        $project['totalAuth'] += $input[0]['total'];
        if( $input[0]['accountType'] == 1 ){
          $project['coordAuth'] += $input[0]['total'];
        } else if( $input[0]['accountType'] == 2 ){
          $project['instAuth'] += $input[0]['total'];
        }
        $project[ 'cap'.$input[0]['chapter'] ] += $input[0]['total'];
        //update
        $project = DB::update("UPDATE projects SET totalAuth = ".$project['totalAuth'].", coordAuth = ".$project['coordAuth'].", instAuth = ".$project['instAuth'].",
          cap1 = ".$project['cap1'].", cap2 = ".$project['cap2'].", cap3 = ".$project['cap3'].", cap4 = ".$project['cap4'].", cap5 = ".$project['cap5']."
          WHERE id = ".$project['id']);

        return response()->json([
          'res' => true,
          'status' => 200,
          'action' => 'created'
        ]);
    }

    //UPDATE post part and all project table ammounts
    public function update(Request $request)
    {
      //get post data
      $new_part = $request->json()->all();
      $new_part = (array) $new_part[0];

      /*----------------- RPOJECTPARTS -------------------*/
      //get old part data  -> update the old part, with new part data
      $old_part = DB::select("SELECT * FROM projectparts WHERE id = ".$new_part['partId']);
      $old_part = (array) $old_part[0];
      $query = "UPDATE projectparts SET chapter = ".$new_part['chapter'].", partNumber= '".$new_part['partNumber']."',total = ".$new_part['total'].",";
      for ($i=0; $i < 12; $i++) {
        $query .= " month".($i+1)." = ".$new_part['months'][$i];
        if( $i != 11 ){ $query .= ", "; }
      }
      $query .= " WHERE id = '".$new_part['partId']."'";
      $update = DB::update($query);

      /*----------------- ACCOUNTCHAPTERS -------------------*/
      // SUBSTRACTING --- get affected by the old data accountChapters & update substractin old_part
      $chapter = DB::select("SELECT * FROM accountchapters WHERE projectNumber = '".$old_part['projectNumber']."' AND accountType = '".$old_part['accountType']."' AND chapter = ".$old_part['chapter']." AND year = ".$old_part['year']." AND active = 1");
      $chapter = (array) $chapter[0];
      $query = "UPDATE accountchapters SET total = ".($chapter['total'] - $old_part['total']).", ";
      for ($i=1; $i <= 12; $i++) {
        $query .= "month$i = ".($chapter['month'.$i] - $old_part['month'.$i]);
        if( $i != 12){  $query.= ", ";  }
      }
      $query .= " WHERE id = ".$chapter['id'];
      $update = DB::update($query);
      // ADDING --- get new affected accountChapter and add the new_part
      $chapter = DB::select("SELECT * FROM accountchapters WHERE projectNumber = '".$new_part['projectNumber']."' AND accountType = '".$new_part['accountType']."' AND chapter = ".$new_part['chapter']." AND year = ".$new_part['year']." AND active = 1");
      $chapter = (array) $chapter[0];
      $query = "UPDATE accountchapters SET total = ".($chapter['total'] + $new_part['total']).", ";
      for ($i=1; $i <= 12; $i++) {
        $query .= "month$i = ".($chapter['month'.$i] + $new_part['months'][$i-1]);
        if( $i != 12){  $query.= ", ";  }
      }
      $query .= " WHERE id = ".$chapter['id'];
      $update = DB::update($query);

      /*----------------- PROJECTACCOUNTS -------------------*/
      // SUBSTRACTING/ADDING --- get affected by the old data projeaccount & update substractin old_part and adding new part
      $account = DB::select("SELECT * FROM projectaccounts WHERE projectNumber = '".$new_part['projectNumber']."' AND accountType = ".$new_part['accountType']." AND year = ".$new_part['year']." AND active = 1");
      $account = (array) $account[0];
      // generate query & update original part using the id
      $query = "UPDATE projectaccounts SET total = ".($account['total'] - $old_part['total'] + $new_part['total'])." WHERE id = ".$account['id'];
      $update = DB::update($query);

      /*----------------- PROJECTS -------------------*/
      // SUBSTRACTING --- get project and substract the old data
      $project = DB::select("SELECT * FROM projects WHERE projectNumber = '".$new_part['projectNumber']."' AND year = ".$new_part['year']." AND active = 1");
      $project = (array) $project[0];
      $query = "UPDATE projects SET totalAuth = ".( $project['totalAuth'] - $old_part['total'] + $new_part['total'] ).", ";
      if($new_part['accountType'] == 1) {  $accountType = 'coordAuth'; }
      else if($new_part['accountType'] == 2) { $accountType = 'instAuth'; }
      $query .= $accountType." = ".($project[$accountType] - $old_part['total'] + $new_part['total']).", ";
      for ($i=1; $i <= 5; $i++) {
        if($old_part['chapter'] == $i){          $project['cap'.$i] = $project['cap'.$i] - $old_part['total'];        }
        if($new_part['chapter'] == $i){          $project['cap'.$i] = $project['cap'.$i] + $new_part['total'];        }
        $query .= "cap$i = ".$project['cap'.$i];
        if($i != 5){  $query .= ", "; }
      }
      $query .= " WHERE id=".$project['id'];
      $update = DB::update($query);

      return response()->json([
        'res' => true,
        'status' => 200,
        'action' => 'updated'
      ]);
    }

    //DELETE selected part
    public function delete(Request $request)
    {
        $sel_id = $request->json()->all();

        //get part data
        $part = DB::select("SELECT * FROM projectparts WHERE id = ".$sel_id['id']);
        $part = (array) $part[0];

        /*----------------- ACCOUNTCHAPTERS -------------------*/
        // SUBSTRACTING --- get affected by the part in accountChapters & update substractin the part
        $chapter = DB::select("SELECT * FROM accountchapters WHERE projectNumber = '".$part['projectNumber']."' AND accountType = '".$part['accountType']."' AND chapter = ".$part['chapter']." AND year = ".$part['year']." AND active = 1");
        $chapter = (array) $chapter[0];
        $query = "UPDATE accountchapters SET total = ".($chapter['total'] - $part['total']).", ";
        for ($i=1; $i <= 12; $i++) {
          $query .= "month$i = ".($chapter['month'.$i] - $part['month'.$i]);
          if( $i != 12){  $query.= ", ";  }
        }
        $query .= " WHERE id = ".$chapter['id'];
        $update = DB::update($query);

        /*----------------- PROJECTACCOUNTS -------------------*/
        // SUBSTRACTING --- get affected by the old data projeaccount & update substractin part
        $account = DB::select("SELECT * FROM projectaccounts WHERE projectNumber = '".$part['projectNumber']."' AND accountType = ".$part['accountType']." AND year = ".$part['year']." AND active = 1");
        $account = (array) $account[0];
        // generate query & update original part using the id
        $query = "UPDATE projectaccounts SET total = ".($account['total'] - $part['total'])." WHERE id = ".$account['id'];
        $update = DB::update($query);

        /*----------------- PROJECTS -------------------*/
        // SUBSTRACTING --- get project and substract the part
        $project = DB::select("SELECT * FROM projects WHERE projectNumber = '".$part['projectNumber']."' AND year = ".$part['year']." AND active = 1");
        $project = (array) $project[0];
        $query = "UPDATE projects SET totalAuth = ".( $project['totalAuth'] - $part['total'] ).", ";
        if($part['accountType'] == 1) {  $accountType = 'coordAuth'; }
        else if($part['accountType'] == 2) { $accountType = 'instAuth'; }
        $query .= $accountType." = ".($project[$accountType] - $part['total']).", ";
        for ($i=1; $i <= 5; $i++) {
          if($part['chapter'] == $i){          $project['cap'.$i] = $project['cap'.$i] - $part['total'];        }
          $query .= "cap$i = ".$project['cap'.$i];
          if($i != 5){  $query .= ", "; }
        }
        $query .= " WHERE id=".$project['id'];
        $update = DB::update($query);

        //delete selected part
        $deletePart = DB::delete("DELETE FROM projectparts WHERE id =".$sel_id['id']);

        return response()->json([
          'res' => true,
          'status' => 200,
          'action' => 'deleted',
          'project' => $part['projectNumber']
        ]);
    }


    // create partList
    public function plCreate(Request $request)
    {
      $input = $request->json()->all();

      if( $input['active'] == null ){   $input['active'] = 0;   }

      // test if selected partnumber is free
      $exist = DB::select("SELECT id FROM partList WHERE partNumber = '".$input['partNumber']."'");

      // if exists return error
      if( count($exist) > 0 ){
        return response()->json([
          'res' => true,
          'status' => 200,
          'action' => 'exist'
        ]);
      } else {
        // create part
        DB::insert("INSERT INTO partList (partNumber, partName, active) VALUES('".$input['partNumber']."', '".$input['partName']."', ".$input['active'].")");

        // return success msg
        return response()->json([
          'res' => true,
          'status' => 200,
          'action' => 'ok'
        ]);
      }
    }

    // update partList
    public function plUpdate(Request $request)
    {
      $input = $request->json()->all();

      if( $input['active'] == null ){   $input['active'] = 0;   }

      // test if selected partnumber is free
      $exist = DB::select("SELECT id FROM partList WHERE id != ".$input['id']." AND  partNumber = '".$input['partNumber']."'");

      // if exists return error
      if( count($exist) > 0 ){
        return response()->json([
          'res' => true,
          'status' => 200,
          'action' => 'exist'
        ]);
      } else {
        // UPDATE part
        DB::update("UPDATE partList SET partNumber = '".$input['partNumber']."', partName = '".$input['partName']."', active = ".$input['active']." WHERE id = ".$input['id']);

        // return success msg
        return response()->json([
          'res' => true,
          'status' => 200,
          'action' => 'ok'
        ]);
      }
    }
}
