<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class OutcomesController extends Controller
{
    // get avalible ammounts (inc-ministered - out-total)
    public function avalible(Request $request)
    {
      $input = $request->json()->all();

      //create response object
      $amounts = array(
        'total'=> 0,
        'cap1'=> 0,
        'cap2'=> 0,
        'cap3'=> 0,
        'cap4'=> 0,
        'cap5'=> 0
      );

      // get incomes
      $incomes = DB::select("SELECT id, sfId, projectNumber, account FROM incomes WHERE projectNumber = '".$input['projectNumber']."' AND account = ".$input['account']." AND year = ".$input['year']." AND active = 1");
      // get income validations
      foreach ($incomes as $key => $income) {
        $income->validations = DB::select("SELECT cap1, cap2, cap3, cap4, cap5, total FROM sfval WHERE sfId = '".$income->sfId."' AND year = ".$input['year']." AND active = 1");
      }

      // for each income, add -> for each validation
      foreach ($incomes as $key => $income) {
        foreach ($income->validations as $key => $val) {
            $amounts['total'] += $val->total;
            $amounts['cap1'] += $val->cap1;
            $amounts['cap2'] += $val->cap2;
            $amounts['cap3'] += $val->cap3;
            $amounts['cap4'] += $val->cap4;
            $amounts['cap5'] += $val->cap5;
        }
      }

      // get outcomes
      $query = "SELECT id, checkNumber, projectnumber, account, cap1, cap2, cap3, cap4, cap5, total FROM outcomes WHERE projectNumber = '".$input['projectNumber']."' AND account = ".$input['account']." AND year = ".$input['year']." AND active = 1 ";
      if($input['outcomeId'] != ''){
        $query .= "AND id != ".$input['outcomeId'];
      }

      $outcomes = DB::select($query);

      // substract outcomes
      foreach ($outcomes as $key => $out) {
        $amounts['total'] -= $out->total;
        $amounts['cap1'] -= $out->cap1;
        $amounts['cap2'] -= $out->cap2;
        $amounts['cap3'] -= $out->cap3;
        $amounts['cap4'] -= $out->cap4;
        $amounts['cap5'] -= $out->cap5;
      }

      // return info
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $amounts
      ]);
    }

    // get last id and checkNumber
    public function lastId(Request $request)
    {
      $input = $request->json()->all();

      $data = array(
        'id'=> 0,
        'checkNumber'=> 0,
      );

      $outcome = DB::select("SELECT id, checkNumber, payType, year FROM outcomes WHERE year = ".$input['year']." AND active = 1 AND payType = '".$input['payType']."' ORDER BY id DESC LIMIT 1");
      // print_r($outcome);

      if( count($outcome) > 0 ){
        $data['id'] = $outcome[0]->id;
        $data['checkNumber'] = $outcome[0]->checkNumber;
      }

      // return info
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $data
      ]);
    }

    // test if check number exists
    public function testCheckNumber(Request $request)
    {
      $input = $request->json()->all();
      $exists = false;

      // check if checkNumber exists in input->year
      $outcome = DB::select("SELECT id, checkNumber FROM outcomes WHERE checkNumber = '".$input['checkNumber']."' AND year = ".$input['year']." AND active = 1 LIMIT 1");
      if( count($outcome) > 0 ){
        $exists = true;
      }

      // return info
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $exists
      ]);
    }

    // crate an outcome
    public function create(Request $request)
    {
      $input = $request->json()->all();
      //check if who sign exists (if exists get id, else insert and get id)
      $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['outcome']['sign']."%' LIMIT 1");

      if( count($people) == 0 ){  //get people id
        DB::insert("INSERT INTO people (name) VALUES ('".$input['outcome']['sign']."')");
        $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['outcome']['sign']."%' LIMIT 1");
      }
      $peopleId = $people[0]->id;

      //create outcome
      // if viatics is 0, valStart&&valEnd == null
      if($input['outcome']['viatics'] == ''){
        $input['outcome']['viatics'] = 0;
        $input['outcome']['valStart'] = NULL;
        $input['outcome']['valEnd'] = NULL;
      }

      $query = "INSERT INTO outcomes (checkNumber, elabDate, sign, concept, projectNumber, account, foil, payType, obs, cap1, cap2, cap3, cap4, cap5, total, viatics, ";
      if( $input['outcome']['viatics'] != 0 )  {      $query .= " valStart, valEnd, ";      }
      $query .= "year) ";
      $query.= "VALUES ('".$input['outcome']['checkNumber']."', '".$input['outcome']['elabDate']."', $peopleId, '".$input['outcome']['concept']."',
      '".$input['outcome']['projectNumber']."', ".$input['outcome']['account'].", '".$input['outcome']['foil']."', '".$input['outcome']['payType']."',
      '".$input['outcome']['obs']."', ".$input['outcome']['cap1'].", ".$input['outcome']['cap2'].", ".$input['outcome']['cap3'].", ".$input['outcome']['cap4'].",
      ".$input['outcome']['cap5'].", ".$input['outcome']['total'].", '".$input['outcome']['viatics']."', ";
      if( $input['outcome']['viatics'] != 0 )  {      $query .= "'".$input['outcome']['valStart']."', '".$input['outcome']['valEnd']."', ";      }
      $query .= $input['outcome']['year'].")";
      DB::insert($query);

      // get last created id
      $query = "SELECT id FROM outcomes ORDER BY id DESC LIMIT 1";
      $outId = DB::select($query);
      $outId = $outId[0] -> id;

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $outId
      ]);
    }

    // update an outcome
    public function update(Request $request)
    {
      $input = $request->json()->all();

      //check if who sign exists (if exists get id, else insert and get id)
      $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['outcome']['sign']."%' LIMIT 1");
      if( count($people) == 0 ){  //get people id
        DB::insert("INSERT INTO people (name) VALUES ('".$input['sign']."')");
        $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['sign']."%' LIMIT 1");
      }
      $peopleId = $people[0]->id;

      //update outcome
      // if viatics is 0, valStart&&valEnd == null
      if($input['outcome']['viatics'] == ''){
        $input['outcome']['viatics'] = 0;
        $input['outcome']['valStart'] = NULL;
        $input['outcome']['valEnd'] = NULL;
      }

      $query = "UPDATE outcomes SET elabDate = '".$input['outcome']['elabDate']."', sign = $peopleId, concept = '".$input['outcome']['concept']."',
        projectNumber = '".$input['outcome']['projectNumber']."', account = ".$input['outcome']['account'].", foil = '".$input['outcome']['foil']."',
        payType = '".$input['outcome']['payType']."', obs = '".$input['outcome']['obs']."', cap1 = ".$input['outcome']['cap1'].", cap2 = ".$input['outcome']['cap2'].",
        cap3 = ".$input['outcome']['cap3'].", cap4 = ".$input['outcome']['cap4'].", cap5 = ".$input['outcome']['cap5'].", total = ".$input['outcome']['total'].", viatics = '".$input['outcome']['viatics']."'";
      if( $input['outcome']['viatics'] != 0 )  {  $query .= ", valStart = '".$input['outcome']['valStart']."', valEnd = '".$input['outcome']['valEnd']."'";  }
        $query .= " WHERE id = ".$input['outcomeId'];
      //save query;
      DB::update($query);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $input['outcomeId']
      ]);
    }

    // get all auth outcomes
    public function get_all_outcomes(Request $request)
    {
      $input = $request->json()->all();

      $query = "SELECT outc.*, peop.name FROM outcomes AS outc
       JOIN people AS peop ON outc.sign = peop.id
       WHERE outc.year = ".$input['year']." ORDER BY outc.checkNumber";
      $outcomes = DB::select($query);

      // get checked
      foreach ($outcomes as $key => $outc) {
        $query = "SELECT SUM(total) AS checked FROM outcomp WHERE year = ".$input['year']." AND active = 1 AND outcomeId = $outc->id";
        $checked = DB::select($query);

        $outc->checked = $checked[0]->checked;
      }

      //return data
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $outcomes
      ]);
    }

    // get all auth outcomes x proy
    public function get_all_outXProy(Request $request)
    {
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

      foreach ($projects as $key => $project) {
        // get outcome total sum
        $query = "SELECT SUM(total) AS total, SUM(cap1) AS cap1, SUM(cap2) AS cap2, SUM(cap3) AS cap3, SUM(cap4) AS cap4, SUM(cap5) AS cap5
        FROM outcomes WHERE year = ".$input['year']." AND ACTIVE = 1 AND projectNumber = '".$project->projectNumber."' AND account = ".$project->accountType;
        $outcomes = DB::select($query);
        $project->amounts = $outcomes[0];

        // get checked per outcome
        $checked = 0;
        $outcomes = DB::select("SELECT id FROM outcomes WHERE year = ".$input['year']." AND ACTIVE = 1 AND projectNumber = '".$project->projectNumber."' AND account = ".$project->accountType);
        foreach ($outcomes as $key => $outcome) {
          $outcomp = DB::select("SELECT SUM(total) AS total FROM outcomp WHERE outcomeId = $outcome->id");
          $checked += $outcomp[0]->total;
        }

        $project->checked = $checked;
      }

      //return data
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $projects
      ]);

    }

    // get outcomes per project
    public function proy_out($year)
    {
      // get project list
      $projects = DB::select("SELECT projectNumber, projectName, totalAuth, coordAuth, instAuth, cap1, cap2, cap3, cap4, cap5 FROM projects WHERE year = $year AND active = 1 ORDER BY projectNumber");

      // for each project
      foreach ($projects as $key => $proj) {

        //get total Ministered for full project
        $ministered = DB::select("SELECT SUM(ministered) ministered FROM incomes WHERE projectNumber = '$proj->projectNumber' AND year = $year AND active = 1");
        $proj->ministered = $ministered[0]->ministered;
        //get total outcomes for full project
        $spends = DB::select("SELECT SUM(total) spends FROM outcomes WHERE projectNumber = '$proj->projectNumber' AND year = $year AND active = 1");
        $proj->spends = $spends[0]->spends;

        // get list of project acconts
        $proj->accounts = DB::select("SELECT accountType FROM projectaccounts WHERE projectNumber = '$proj->projectNumber' AND year = $year AND active = 1");

        // for each account type
        foreach ($proj->accounts as $key => $account) {
          // get ministered value from incomes
          $ministered = DB::select("SELECT SUM(ministered) ministered FROM incomes WHERE projectNumber = '$proj->projectNumber' AND account = $account->accountType AND year = $year AND active = 1");
          $account->ministered = $ministered[0]->ministered;

          // get ministered for each cap
          $cap1 = 0; $cap2 = 0; $cap3 = 0; $cap4 = 0; $cap5 = 0;
          $incomes = DB::select("SELECT sfId FROM incomes WHERE projectNumber = '$proj->projectNumber' AND account = $account->accountType AND year = $year AND active = 1");
          foreach ($incomes as $key => $inc) {
            $sumCap = DB::select("SELECT SUM(cap1) cap1, SUM(cap2) cap2, SUM(cap3) cap3, SUM(cap4) cap4, SUM(cap5) cap5  FROM sfval WHERE sfId = '$inc->sfId' AND year = $year AND active = 1");
            $cap1 += $sumCap[0]->cap1;  $cap2 += $sumCap[0]->cap2;  $cap3 += $sumCap[0]->cap3;
            $cap4 += $sumCap[0]->cap4;  $cap5 += $sumCap[0]->cap5;
          }
          $account->minCaps = [$cap1, $cap2, $cap3, $cap4, $cap5];

          // get outcomes
          $account->outcomes = DB::select("SELECT outcomes.*, people.name FROM outcomes JOIN people ON outcomes.sign = people.id WHERE outcomes.projectNumber = '$proj->projectNumber' AND outcomes.account = $account->accountType AND outcomes.year = $year AND outcomes.active = 1 ORDER BY id");
          // get spend per chapter
          $cap1 = 0; $cap2 = 0; $cap3 = 0; $cap4 = 0; $cap5 = 0;
          $sumCap = DB::select("SELECT SUM(cap1) cap1, SUM(cap2) cap2, SUM(cap3) cap3, SUM(cap4) cap4, SUM(cap5) cap5  FROM outcomes WHERE projectNumber = '$proj->projectNumber' AND account = $account->accountType AND year = $year AND active = 1");
          $cap1 += $sumCap[0]->cap1;  $cap2 += $sumCap[0]->cap2;  $cap3 += $sumCap[0]->cap3;
          $cap4 += $sumCap[0]->cap4;  $cap5 += $sumCap[0]->cap5;
          $account->speCaps = [$cap1, $cap2, $cap3, $cap4, $cap5];
          // get total spend
          $totalSpend = DB::select("SELECT SUM(total) total FROM outcomes WHERE projectNumber = '$proj->projectNumber' AND account = $account->accountType AND year = $year AND active = 1");
          $account->totalSpend = $totalSpend[0]->total;

          // for each outcome, get its outComp && outCompbills
          foreach ($account->outcomes as $key => $outcome) {
            $outcome->comp = DB::select("SELECT * FROM outcomp WHERE outcomeId = '$outcome->id' AND year = $year AND active = 1");
            $outcome->bills = DB::select("SELECT * FROM outcompbills WHERE outcomeId = '$outcome->id' AND year = $year AND active = 1");
          }
        }
      }

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $projects
      ]);
    }

    // get a single outcome data
    public function get_outcome($id)
    {
      //get outcome info
      $outcome = DB::select("SELECT outc.*, proj.projectName, peop.name FROM outcomes AS outc
        JOIN projects AS proj ON outc.projectNumber = proj.projectNumber
        JOIN people AS peop ON outc.sign = peop.id
        WHERE outc.id = $id");
      $outcome = $outcome[0];

      // get gncSignName if exists
      $outcome->gncName = '';
      if( $outcome->gncSign ){
        $gncName = DB::select("SELECT name FROM people WHERE id = $outcome->gncSign");
        $outcome->gncName = $gncName[0]->name;
      }

      // get project income ministered
      $ministered = DB::select("SELECT SUM(ministered) ministered FROM incomes WHERE projectNumber = '$outcome->projectNumber' AND year = $outcome->year AND active = 1");
      $outcome->ministered = $ministered[0]->ministered;

      // get outcome comprobations
      $outcome->comp = DB::select("SELECT * FROM outcomp WHERE outcomeId = '$outcome->id' AND active = 1 AND year = $outcome->year");

      // get outcome comprobation bills
      $outcome->bills = DB::select("SELECT * FROM outCompbills WHERE outcomeId = '$outcome->id' AND active = 1 AND year = $outcome->year");

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $outcome
      ]);
    }

    // change outcome status
    public function set_status(Request $request)
    {
      $input = $request->json()->all();

      DB::update("UPDATE outcomes SET status = ".$input['status']." WHERE id = ".$input['id']);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // deactivate outcome
    public function delete(Request $request)
    {
      $input = $request->json()->all();

      // get outcome checkNumber
      $outNum = DB::select("SELECT checkNumber FROM outcomes WHERE id = ".$input['id']) ;
      $outNum = $outNum['0']->checkNumber;

      // deactivate all their outcomps,
      DB::update("UPDATE outcomp SET active = 0 WHERE checkNumber = '".$outNum."' ");

      // deactivate all their outcompbills
      DB::update("UPDATE outcompbills SET active = 0 WHERE checkNumber = '".$outNum."' ");

      // deactivate selected outcome
      DB::update("UPDATE outcomes SET active = 0 WHERE id = ".$input['id']);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // update gnc data
    public function gnc(Request $request)
    {
        $input = $request->json()->all();

        $peopleId = 0;

        if($input['gncName'] != ''){
          // get people_id
          //check if who sign exists (if exists get id, else insert and get id)
          $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['gncName']."%' LIMIT 1");
          if( count($people) == 0 ){  //get people id
            DB::insert("INSERT INTO people (name) VALUES ('".$input['gncName']."')");
            $people = DB::select("SELECT * FROM people WHERE name LIKE '%".$input['sign']."%' LIMIT 1");
          }
          $peopleId = $people[0]->id;
        }

        // update gnc data
        $query = "UPDATE outcomes SET gncSign = $peopleId, gncLocation = '".$input['gncLocation']."' WHERE id=".$input['id'];
        DB::update($query);

        // return success message
        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => 'ok'
        ]);
    }



    // -- OUTCOMES COMP
    // create a comprobation
    public function comp_create(Request $request)
    {
      $input = $request->json()->all();
      if( $input['gnc'] == '' ){
        $input['gnc'] = 0;
      }

      DB::insert("INSERT INTO outcomp (outcomeId, compDate, total, gnc, year) VALUES(".$input['id'].", '".$input['compDate']."', ".$input['total'].", ".$input['gnc'].", ".$input['year'].") ");

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // deactive a comprobations
    public function comp_delete(Request $request)
    {
      $input = $request->json()->all();

      // DB::update("UPDATE outcomp SET active = 0 WHERE id = ".$input['id']." AND year = ".$input['year']);
      DB::delete("DELETE FROM outcomp WHERE id = ".$input['id']." AND year = ".$input['year']);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // change gnc status
    public function comp_gnc(Request $request)
    {
      $input = $request->json()->all();
      if( $input['status'] == '' ){
        $input['status'] = 0;
      }

      DB::update("UPDATE outcomp SET gnc = ".$input['status']." WHERE id = ".$input['id']);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }


    // -- OUTCOMES BILLS
    // test if bill foil exists
    public function comp_test(Request $request)
    {
      $input = $request->json()->all();

      $bill = DB::select("SELECT bil.id, bil.foil, outc.checkNumber FROM outcompbills as bil JOIN outcomes AS outc ON bil.outcomeId = outc.id WHERE bil.foil LIKE '%".$input['subId']."%' AND bil.active = 1 LIMIT 1");

      $exists = null;
      if( count($bill) > 0){
        $exists = $bill[0]->checkNumber;
      }

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $exists
      ]);
    }

    // create a bill
    public function bill_create(Request $request)
    {
      $input = $request->json()->all();
      if($input['repeated'] == ''){
        $input['repeated'] = 0;
      }
      if($input['authorize'] == ''){
        $input['authorize'] = 0;
      }

      // insert outcome bill
      $query = ("INSERT INTO outcompbills ( outcomeId, foil, total, repeated, authorize, obs, year )
        VALUES (".$input['outcomeId'].", '".$input['foil']."', ".$input['total'].", ".$input['repeated'].", ".$input['authorize'].", '".$input['obs']."', ".$input['year']." )");
      DB::insert($query);

      // if is reapated, update all other outcome bills with the same foil
      if( $input['repeated'] != 0){
        $query = ("UPDATE outcompbills SET repeated = 1 WHERE foil LIKE '%".$input['foil']."%' ");
        DB::update($query);
      }

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // delete a bill
    public function bill_delete(Request $request)
    {
      $input = $request->json()->all();

      // DB::update("UPDATE outcompbills SET active = 0 WHERE id = ".$input['id']);
      DB::delete("DELETE FROM outcompbills WHERE id = ".$input['id']);

      //return success message
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }
}
