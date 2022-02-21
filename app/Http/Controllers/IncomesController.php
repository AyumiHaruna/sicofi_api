<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class IncomesController extends Controller
{
    // get last income id
    public function last_id($year){
         $incomes = DB::select("SELECT MAX(sfNum) AS max_id FROM incomes WHERE year = $year AND active = 1");

         if($incomes[0]->max_id == null){
           $incomes[0]->max_id = 0;
         }

         return response()->json([
           'res' => true,
           'status' => 200,
           'results' => $incomes[0]->max_id
         ]);
    }

    // create a new income
    public function create(Request $request){
      $input = $request->json()->all();

      //check if who sign exists (if exists get id, else insert and get id)
      $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['sign']."%' LIMIT 1");
      if( count($people) == 0 ){  //get people id
        DB::insert("INSERT INTO people (name) VALUES ('".$input['sign']."')");
        $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['sign']."%' LIMIT 1");
      }
      $peopleId = $people[0]->id;

      //create income & get id
      $query = "INSERT INTO incomes (sfNum, sfId, projectNumber, account, type, opType, elabDate, month, concept, sign, requested, obs, year )
        VALUES (".$input['sfNum'].", '".$input['sfId']."', '".$input['projectNumber']."', ".$input['account'].", '".$input['type']."', '".$input['opType']."',
        '".$input['elabDate']."', ".$input['month'].", '".$input['concept']."', ".$peopleId.", ".$input['requested'].", '".$input['obs']."', ".$input['year'].")";
      DB::insert($query);
      $income = DB::select("SELECT sfId FROM incomes WHERE sfId = '".$input['sfId']."' AND year = ".$input['year']." AND active = 1");
      $incomeId = $income[0]->sfId;

      //save sf parts
      $capAmount = [0,0,0,0,0,0];
      for ($i=0; $i < count($input['partList'])  ; $i++) {
        //add to CapAmount
        $capAmount[ ($input['partList'][$i]['partNumber'])[0] ] +=  $input['partList'][$i]['total'];
        //generate query
        $query = "INSERT INTO incomesfpart (incomeId, partNumber, cap, total, year) VALUES ('".$incomeId."', '".$input['partList'][$i]['partNumber']."', ".($input['partList'][$i]['partNumber'])[0].", ".$input['partList'][$i]['total'].", ".$input['year'].")";
        // insert
        DB::insert($query);
      }

      //save incomesf data
      $query = "INSERT INTO incomesf (incomeId, cap1, cap2, cap3, cap4, cap5, total, sfPrintType, sfTaxType, taxConfig, ivaTC, ivaRC, isrRC, year) VALUES ";
      $query .= "('".$incomeId."', ".$capAmount[1].", ".$capAmount[2].", ".$capAmount[3].", ".$capAmount[4].", ".$capAmount[5].", ".$input['requested'].",
        '".$input['sfAddData']['sfPrintType']."', '".$input['sfAddData']['sfTaxType']."', '".$input['sfAddData']['taxConfig'][0].$input['sfAddData']['taxConfig'][1].$input['sfAddData']['taxConfig'][2]."',
        ".$input['sfAddData']['ivaT'].", ".$input['sfAddData']['ivaR'].", ".$input['sfAddData']['isrR'].",  ".$input['year'].")";
      DB::insert($query);

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // update an income
    public function update(Request $request){
      $input = $request->json()->all();

      //check if who sign exists (if exists get id, else insert and get id)
      $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['sign']."%' LIMIT 1");
      if( count($people) == 0 ){  //get people id
        DB::insert("INSERT INTO people (name) VALUES ('".$input['sign']."')");
        $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['sign']."%' LIMIT 1");
      }
      $peopleId = $people[0]->id;

      // update selected income
      $query = "UPDATE incomes SET elabDate = '".$input['elabDate']."', concept = '".$input['concept']."',
        sign = $peopleId, requested = ".$input['requested'].", obs = '".$input['obs']."'
        WHERE sfId = '".$input['sfId']."'";
      DB::update( $query );

      // delete old sf parts && save new parts
      $query = "DELETE FROM incomesfpart WHERE incomeId = '".$input['sfId']."'";
      DB::delete($query);
      $capAmount = [0,0,0,0,0,0];
      for ($i=0; $i < count($input['partList'])  ; $i++) {
        //add to CapAmount
        $capAmount[ ($input['partList'][$i]['partNumber'])[0] ] +=  $input['partList'][$i]['total'];
        //generate query
        $query = "INSERT INTO incomesfpart (incomeId, partNumber, cap, total, year) VALUES ('".$input['sfId']."', '".$input['partList'][$i]['partNumber']."', ".($input['partList'][$i]['partNumber'])[0].", ".$input['partList'][$i]['total'].", ".$input['year'].")";
        // insert
        DB::insert($query);
      }

      //update incomesf data
      $query = "UPDATE incomesf SET cap1 = ".$capAmount[1].", cap2 = ".$capAmount[2].", cap3 = ".$capAmount[3].", cap4 = ".$capAmount[4].", cap5 = ".$capAmount[5].",
          total = ".$input['requested'].", sfPrintType = '".$input['sfAddData']['sfPrintType']."', sfTaxType = '".$input['sfAddData']['sfTaxType']."',
          taxConfig = '".$input['sfAddData']['taxConfig'][0].$input['sfAddData']['taxConfig'][1].$input['sfAddData']['taxConfig'][2]."',
          ivaTC = ".$input['sfAddData']['ivaT'].", ivaRC = ".$input['sfAddData']['ivaR'].", isrRC = ".$input['sfAddData']['isrR']."
          WHERE incomeId = '".$input['sfId']."'";
      DB::insert($query);

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // create an income return
    public function inc_return(Request $request){
      $input = $request->json()->all();

      // get last income id }
      $newId = 0;
      $lastId = DB::select("SELECT sfNum FROM incomes WHERE year = ".$input['year']." ORDER BY id DESC LIMIT 1");
      if( count($lastId) > 0 ){
        $newId = $lastId[0]->sfNum + 1;
      }

      // create new income return
      $query = "INSERT INTO incomes (sfNum, sfId, projectNumber, account, type, opType, elabDate, month, concept, requested, ministered, checked, year)
        VALUES ($newId, '".$input['year']."-".$input['month']."_REI-".$input['projectNumber']."-".$input['account']."_".$input['opType']."-".$newId."', '".$input['projectNumber']."', ".$input['account'].", '".$input['type']."', '".$input['opType']."', '".$input['elabDate']."',
          ".$input['month'].", '".$input['concept']."', ".$input['total'].", ".$input['total'].", ".$input['total'].", ".$input['year'].")";
      DB::insert($query);

      // create validation data
      $query = "INSERT INTO sfval (sfId, depDate, authNum, cap1, cap2, cap3, cap4, cap5, total, year)
        VALUES ('".$input['year']."-".$input['month']."_REI-".$input['projectNumber']."-".$input['account']."_".$input['opType']."-".$newId."', '".$input['elabDate']."', '".$input['authNum']."',
          ".$input['cap1'].", ".$input['cap2'].", ".$input['cap3'].", ".$input['cap4'].", ".$input['cap5'].", ".$input['total'].", ".$input['year'].")";
      DB::insert($query);

      // return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // delete an income return
    public function del_return(Request $request){
      $input = $request->json()->all();

      // deactive income
      DB::update("UPDATE incomes SET active = 0 WHERE sfId = '".$input['sfId']."'");

      // deactive income sfval
      DB::update("UPDATE sfVal SET active = 0 WHERE sfId = '".$input['sfId']."'");

      // return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // get selected SF info
    public function getSF($incId){
      // //get sfId
      // $incId = (explode("%22", $incId))[1];
      // //get year
      $year = (explode("-", $incId))[0];

      $income = DB::select("SELECT * FROM incomes WHERE sfId = '$incId' AND year = $year AND active = 1");
      $income = $income[0];
      // get projectName for income.projectNumber
      $pName = DB::select("SELECT projectName FROM projects WHERE projectNumber = '".$income->projectNumber."' AND year = $year AND active = 1");
      $income->projectName = $pName[0]->projectName;
      // get sign Name for income.sign
      $signName = DB::select("SELECT name FROM people WHERE id = $income->sign");
      $income->signName = $signName[0]->name;
      // get incomesSF Data
      $sfData = DB::select("SELECT * from incomesf WHERE incomeId = '$incId'");
      $income->sfData = $sfData[0];
      // get income PART LIST
      $income->partList = DB::select("SELECT inc.id, inc.incomeId, inc.partNumber, inc.cap, inc.total, inc.year, par.partName FROM incomesfpart AS inc
        JOIN partlist AS par ON inc.partNumber = par.partNumber
        WHERE inc.incomeId = '$incId'");
      // get income VALIDATIONS
      $income->validations = DB::select("SELECT * FROM sfval WHERE sfId = '$incId' AND year = $year AND active = 1");
      // get income CHECKINGS
      $income->checkings = DB::select("SELECT * FROM sfchecking WHERE sfId = '$incId' AND year = $year AND active = 1 ORDER BY cover");
      // get sf chek list for each checkings
      foreach ($income->checkings as $key => $check) {
        // get its sfchecklist
        $check->list = DB::select("SELECT sf.id, sf.sfId, sf.cover, sf.partNumber, sf.notes, sf.total, sf.updDate, sf.obs, sf.active, par.partName FROM sfchecklist AS sf
          JOIN partlist AS par ON sf.partNumber = par.partNumber WHERE sf.sfId = '$incId' AND sf.cover = $check->cover AND sf.active = 1");
      }

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $income
      ]);
    }

    // get s.f. list oredered by project
    public function sf_proy_list($year){
      //get projectList
      $projects = DB::select("SELECT projectNumber, projectName, totalAuth, coordAuth, instAuth, cap1, cap2, cap3, cap4, cap5 FROM projects WHERE year = $year AND active = 1 ORDER BY projectNumber");

      //for each project get its incomes
      foreach ($projects as $key => $proj) {
        $proj->sfList = DB::select("SELECT * FROM incomes WHERE projectNumber = '$proj->projectNumber' AND year = $year AND active = 1 ORDER BY type, sfNum");
        $proj->requested = 0;
        $proj->ministered = 0;
        $proj->checked = 0;
        //for each income get its SF details
        foreach ($proj->sfList as $key2 => $sf) {
          $proj->requested += $sf->requested;
          $proj->ministered += $sf->ministered;
          $proj->checked += $sf->checked;
          if($sf->type == 'ing'){
            $data = DB::select("SELECT * FROM incomesf WHERE incomeId = '$sf->sfId'");
          } else if( $sf->type == 'rei' ) {
            $data = DB::select("SELECT * FROM sfVal WHERE sfId = '$sf->sfId' AND active = 1");
          }
          if( count($data) > 0 ){
              $sf->data = $data[0];
          }
        }
      }

      // print_r($proyects);
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $projects
      ]);
    }

    //get previous captured SF from given data
    public function prevSF(Request $request){
      $input = $request->json()->all();

      $prevIncomes = DB::select("SELECT * FROM incomes WHERE type='inc' AND year = ".$input['year']." AND projectNumber = '".$input['projectNumber']."' AND month = ".$input['month']." AND account = ".$input['account']." AND active = 1");
      // get part List for each income
      foreach ($prevIncomes as $key => $income) {
        $income->partList = DB::select("SELECT inc.partNumber, inc.total, par.partName FROM incomesfpart AS inc JOIN partlist AS par ON inc.partNumber = par.partNumber WHERE inc.incomeId = '$income->sfId'");
      }

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $prevIncomes
      ]);
    }

    //save sf validation
    public function sfVal_save(Request $request){
      $input = $request->json()->all();

      // insert validation in sfVal
      $query = "INSERT INTO sfval (sfId, depDate, authNum, cap1, cap2, cap3, cap4, cap5, total, obs, year)
        VALUES( '".$input['sfId']."', '".$input['depDate']."', '".$input['authNum']."',
        ".$input['cap1'].", ".$input['cap2'].", ".$input['cap3'].", ".$input['cap4'].", ".$input['cap5'].",
        ".$input['total'].", '".$input['obs']."', ".$input['year'].")";
      DB::insert($query);

      // update income whith sfVal total
      //get sfId income
      $income = DB::select("SELECT sfId, ministered FROM incomes WHERE sfId = '".$input['sfId']."' LIMIT 1");
      $income = $income[0];
      //time to update ministered income
      $query = "UPDATE incomes SET ministered = ".($income->ministered += $input['total'])." WHERE sfId = '".$input['sfId']."'";
      DB::update($query);

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'OK'
      ]);
    }

    //delete sf validation
    public function sfVal_delete(Request $request){
      $input = $request->json()->all();

      // get selected sfVal total
      $sfVal = DB::select("SELECT total FROM sfval WHERE id = ".$input['id']." AND sfId = '".$input['sfId']."' LIMIT 1");
      $sfVal = $sfVal[0];
      // get affected income
      $income = DB::select("SELECT ministered FROM incomes WHERE sfId = '".$input['sfId']."' LIMIT 1");
      $income = $income[0];
      // update affected income substractin current sfVal
      $query = "UPDATE incomes SET ministered = ".($income->ministered - $sfVal->total)." WHERE sfId = '".$input['sfId']."'";
      DB::update($query);

      // delete selected sfVal (deactive)
      // DB::update("UPDATE sfVal SET active = 0 WHERE id = ".$input['id']." AND sfId = '".$input['sfId']."' LIMIT 1");
      DB::delete("DELETE FROM sfVal WHERE id = ".$input['id']." AND sfId = '".$input['sfId']."' ");

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'OK'
      ]);
    }

    //create incomes comprobation
    public function comp_create(Request $request){
      $input = $request->json()->all();
      // print_r($input);

      //insert sfChecking & get inserted checking id
      $query = "INSERT INTO sfchecking (sfId, cover, transfer, elabDate, checked, cap1, cap2, cap3, cap4, cap5, type, updDate, obs, year) VALUES (
        '".$input['sfId']."', ".$input['cover'].", '".$input['transfer']."', '".$input['elabDate']."', ".$input['checked'].",
        ".$input['caps'][0].", ".$input['caps'][1].", ".$input['caps'][2].", ".$input['caps'][3].", ".$input['caps'][4].",
        ".$input['type'].", '".$input['updDate']."', '".$input['obs']."', ".$input['year'].")";
      DB::insert($query);

      $lastId = DB::select("SELECT id FROM sfchecking WHERE sfId = '".$input['sfId']."' AND cover = ".$input['cover']." AND year = ".$input['year']." AND active = 1 ORDER BY id DESC LIMIT 1");
      $lastId = $lastId[0]->id;

      //insert each sfchecklist
      $query = "INSERT INTO sfchecklist (sfId, cover, partNumber, notes, total, updDate, obs) VALUES ";
      foreach ($input['partList'] as $key => $part) {
        if( !isset($part['obs']) )  {  $part['obs'] = '';  }
        $query .= "('".$input['sfId']."', ".$input['cover'].", '".$part['partNumber']."', ".$part['notes'].", ".$part['total'].", '".$input['updDate']."', '".$part['obs']."')";
        if($key != count($input['partList']) - 1 ){
          $query .= ", ";
        } else {
          $query .= ";";
        }
      }
      DB::insert($query);

      //get current income checked
      $income = DB::select("SELECT id, sfId, checked FROM incomes WHERE sfId = '".$input['sfId']."' AND year = ".$input['year']." AND active = 1 LIMIT 1");
      $income = $income[0];
      //add new check total and update
      $query = "UPDATE incomes SET checked = ".($income->checked + $input['checked'])." WHERE id = $income->id";
      DB::update($query);

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $lastId
      ]);
    }

    //update incomes comprobation
    public function comp_update(Request $request){
      $input = $request->json()->all();
      // get old sfChecking -> checked value (to substract on incomes )
      $old_checked = DB::select("SELECT checked FROM sfchecking WHERE id = ".$input['updId']);
      $old_checked = $old_checked[0]->checked;


      // get affected income checked value
      $old_inc_checked = DB::select("SELECT id, checked FROM incomes WHERE sfId = '".$input['sfId']."' AND year = ".$input['year']." AND active = 1");
      $incId = $old_inc_checked[0]->id;
      $old_inc_checked = $old_inc_checked[0]->checked;


      // delete (deactivate) old sfcheckList
      $query = "UPDATE sfchecklist SET obs = 'desactivada por ".$input['name']." (".$input['updDate'].")', updDate = '".$input['updDate']."', active = 0 WHERE sfId = '".$input['sfId']."' AND cover = ".$input['cover']." AND active = 1";
      DB::update($query);
      // $query = "DELETE FROM sfchecklist WHERE sfId = '".$input['sfId']."' AND cover = ".$input['cover']." AND active = 1";
      // DB::delete($query);


      // add new sfchecklist
      $query = "INSERT INTO sfchecklist (sfId, cover, partNumber, notes, total, updDate, obs) VALUES ";
      foreach ($input['partList'] as $key => $part) {
        if( !isset($part['obs']) )  {  $part['obs'] = '';  }
        $query .= "('".$input['sfId']."', ".$input['cover'].", '".$part['partNumber']."', ".$part['notes'].", ".$part['total'].", '".$input['updDate']."', '".$part['obs']."')";
        if($key != count($input['partList']) - 1 ){
          $query .= ", ";
        } else {
          $query .= ";";
        }
      }
      DB::insert($query);


      // update selected sfchecking
      $query = "UPDATE sfchecking SET checked = ".$input['checked'].", cap1 = ".$input['caps'][0].", cap2 = ".$input['caps'][1].",
       cap3 = ".$input['caps'][2].", cap4 = ".$input['caps'][3].", cap5 = ".$input['caps'][4].", updDate = '".$input['updDate']."',
       obs  = 'Actualizado por ".$input['name']." (".$input['updDate'].")'
        WHERE id = ".$input['updId'];
      DB::update($query);


      // update affected income
      $query = "UPDATE incomes SET checked = ".( $old_inc_checked - $old_checked + $input['checked'] )." WHERE id = $incId";
      DB::update($query);

      // return success response
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'OK'
      ]);
    }

    //delete incomes comprobation
    public function comp_delete(Request $request){
      $input = $request->json()->all();

      //get current income checked
      $income = DB::select("SELECT id, checked FROM incomes WHERE sfId = '".$input['sfId']."' AND active = 1 LIMIT 1");
      $income = $income[0];

      //get current comp checked
      $currentComp = DB::select("SELECT checked, cover FROM sfchecking WHERE id = ".$input['id']." AND sfId = '".$input['sfId']."' AND active = 1 LIMIT 1");
      $currentComp = $currentComp[0];

      //update incomes = income.checked - comp.checked
      $query = "UPDATE incomes SET checked = ".($income->checked - $currentComp->checked)." WHERE id = ".$income->id;
      DB::update($query);

      //delete (deactive) current comp
      $query = "UPDATE sfchecking SET updDate = '".$input['updDate']."', obs = 'Eliminada por ".$input['name']."', active = 0 WHERE id = ".$input['id'];
      DB::update($query);

      //delete (deactive) respective compList
      $query = "UPDATE sfchecklist SET updDate = '".$input['updDate']."', obs = 'Eliminada por ".$input['name']."', active = 0 WHERE sfId = '".$input['sfId']."' AND cover = ".$currentComp->cover." AND active = 1";
      DB::update($query);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // return last cover for selected S.F.
    public function compLastCover($sfId){
      $lastCover = DB::select("SELECT MAX(cover) maxCover FROM sfChecking WHERE sfId = '".$sfId."' AND active = 1");
      $last = 0;
      if( $lastCover[0]->maxCover != null ){
        $last = $lastCover[0]->maxCover;
      }
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $last
      ]);
    }

    // get checking info
    public function checkInfo($checkId){
      //get sfChecking data
      $checking = DB::select("SELECT * FROM sfchecking WHERE id = $checkId");
      $checking = $checking[0];
      //get sfcheckList  for sfcheking
      $checking->checkList = DB::select("SELECT lis.*, par.partName FROM sfcheckList AS lis
        JOIN partlist AS par ON par.partNumber = lis.partNumber
        WHERE lis.sfId = '$checking->sfId' AND lis.cover = $checking->cover AND lis.active = 1");
      //return data
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $checking
      ]);
    }

    // get full list of incomes
    public function full_list(Request $request){
      $input = $request->json()->all();

      $query = "SELECT inco.*, proj.projectName FROM incomes AS inco
      JOIN projects AS proj ON proj.projectNumber = inco.projectNumber
      WHERE inco.year = ".$input['year']." AND inco.active = 1 ORDER BY inco.sfNum";
      //change query if user has not FullAccess
      if($input['fullAccess'] == 0){
        // delete some charaters from string
        $input['projectList'] = str_replace(  ['[', ']'],  '', $input['projectList'] );
        $query .= "AND inco.projectNumber IN (".$input['projectList'].")";
      }

      $incomes = DB::select($query);
      foreach ($incomes as $key => $income) {
        $caps = DB::select("SELECT * FROM sfVal WHERE year = ".$input['year']." AND sfId = '".$income->sfId."'");

        if( count($caps) > 0 ){
            $income->caps = $caps[0];
        }
      }

      //return data
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $incomes
      ]);
    }

    // get incomes per project per account
    public function incXProj(Request $request){
      $input = $request->json()->all();

      // get  authorized project accounts
      $query = "SELECT acco.accountType, proj.id, proj.projectNumber, proj.projectName, proj.type
        FROM projectaccounts AS acco
        JOIN projects AS proj ON acco.projectNumber = proj.projectNumber
        WHERE acco.year = ".$input['year']." AND acco.active = 1 ";
        //change query if user has not FullAccess
        if($input['fullAccess'] == 0){
          // delete some charaters from string
          $input['projectList'] = str_replace(  ['[', ']'],  '', $input['projectList'] );
          $query .= "AND projectNumber IN (".$input['projectList'].") ";
        }
      $query .= "ORDER BY proj.projectNumber, acco.accountType";
      $projects = DB::select($query);

      // get income info for each project
      foreach ($projects as $key => $proj) {
        // get requested and ministered
        $query = "SELECT SUM(requested) AS requested, SUM(ministered) AS ministered FROM incomes WHERE projectNumber = '".$proj->projectNumber."' AND account = ".$proj->accountType." AND year = ".$input['year']." AND active = 1";
         $incInfo = DB::select($query);
         $proj->incomesInfo = $incInfo[0];

         $caps = [0,0,0,0,0];
         // get all incomes
         $incomes = DB::select("SELECT sfId FROM incomes WHERE year = ".$input['year']." AND active = 1 AND projectNumber = '".$proj->projectNumber."' AND account = ".$proj->accountType);
         foreach ($incomes as $key => $income) {
           $sfVal = DB::select("SELECT SUM(cap1) AS cap1, SUM(cap2) AS cap2, SUM(cap3) AS cap3, SUM(cap4) AS cap4, SUM(cap5) AS cap5 FROM sfval WHERE year = ".$input['year']." AND active = 1 AND sfId = '".$income->sfId."'");
           // $sfVal = (array) $sfVal;
           $sfVal = (array) $sfVal[0];
           $caps[0] += $sfVal['cap1']; $caps[1] += $sfVal['cap2']; $caps[2] += $sfVal['cap3'];
           $caps[3] += $sfVal['cap4']; $caps[4] += $sfVal['cap5'];
         }
         $proj->caps = $caps;
      }

      //return data
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $projects
      ]);
    }
}
