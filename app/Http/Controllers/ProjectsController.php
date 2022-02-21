<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
  // return projects (only number and names)
  public function simple_list(Request $request){
    $input = $request->json()->all();

    // print_r($input);

    // generate project query from user info
    $query = "SELECT projectNumber, projectName FROM projects WHERE year = ".$input['year']." AND active = 1 ORDER BY projectNumber ";
    //change query if user has not FullAccess
    if($input['fullAccess'] == 0){
      // delete some charaters from string
      $input['projectList'] = str_replace(  ['[', ']'],  '', $input['projectList'] );
      $query .= "AND projectNumber IN (".$input['projectList'].")";
    }
    $projects = DB::select($query);

    // print_r($projects);
    return response()->json([
      'res' => true,
      'status' => 200,
      'results' => $projects
    ]);
  }

  // list projects
  public function list(Request $request)
  {
    $input = $request->json()->all();

    // generate project query from user info
    $query = "SELECT * FROM projects WHERE year = ".$input['year']." AND active = 1 ";
      //change query if user has not FullAccess
      if($input['fullAccess'] == 0){
        // delete some charaters from string
        $input['projectList'] = str_replace(  ['[', ']'],  '', $input['projectList'] );
        $query .= "AND projectNumber IN (".$input['projectList'].")";
      }
    $projects = DB::select($query);

    //get project -> projectAccount data
    foreach ($projects as $key => $project) {
      $projectAccounts = DB::select("SELECT * FROM projectaccounts WHERE projectNumber = $project->projectNumber AND year = ".$input['year']." AND active = 1 ");
      $project->accounts = $projectAccounts;

      //get project -> accounts -> chapters
      foreach ($project->accounts as $key => $account) {
        $accountChapters = DB::select("SELECT * FROM accountchapters WHERE projectNumber = $account->projectNumber AND accountType = $account->accountType AND year = ".$input['year']." AND active = 1 ");
        $account->chapters = $accountChapters;
      }
    }

    return response()->json([
      'res' => true,
      'status' => 200,
      'results' => $projects
    ]);
  }

  // get project full data
  public function getProjectData($year, $projectNumber)
  {
    // get project data
    $projectData = DB::select(" SELECT * FROM projects WHERE projectNumber = $projectNumber AND year = $year AND active = 1 ");
    // if project data exists
    if( count($projectData) != 0) {
      //get project Accounts & assing
      $projectAccounts = DB::select(" SELECT * FROM projectaccounts WHERE projectNumber = $projectNumber AND year = $year AND active = 1 ");
      $projectData[0]->accounts = $projectAccounts;

      //get AccountsChapters and asign
      foreach ($projectAccounts as $key => $account) {
        $chapters = DB::select(" SELECT * FROM accountChapters WHERE projectNumber = $projectNumber AND accountType = $account->accountType AND year = $year AND active = 1 ");
        $projectData[0]->accounts[$key]->chapters = $chapters;

        //get account details for each account
        $parts = DB::select(" SELECT par.id, par.projectNumber, par.accountType, par.chapter, par.partNumber,
          par.month1, par.month2, par.month3, par.month4, par.month5, par.month6, par.month7, par.month8, par.month9,
          par.month10, par.month11, par.month12, par.total, par.year, par.active, lis.partName
          FROM projectparts AS  par JOIN partlist AS lis ON par.partNumber = lis.partNumber
          WHERE par.projectNumber = $projectNumber AND par.accountType = $account->accountType  AND par.year = $year AND par.active = 1 ");
        $projectData[0]->accounts[$key]->parts = $parts;
      }

      //return success
      return response()->json([
        'res' => true,
        'status' => 200,
        'data' => $projectData
      ]);
      return $projectData ;

    } else {
      //return error
      return response()->json([
        'res' => true,
        'status' => 404,
        'message' => "No se encontró el proyecto $projectNumber"
      ]);
    }
  }

  // create or update main project info
  public function save(Request $request)
  {
    // get post data
    $input = $request->json()->all();
    //check if project exists (in this year and is active)
    $projects = DB::select(" SELECT projectNumber FROM projects WHERE projectNumber = ".$input['projectNumber']." AND year = ".$input['year']." AND active = 1 ");
    if (count($projects) > 0) {
      // the project exist - lets update
      $projects = DB::update("UPDATE projects SET
        projectName = '".$input['projectName']."',
        type = '".$input['type']."',
        manager = '".$input['manager']."',
        degree = '".$input['degree']."'
        WHERE projectNumber = '".$input['projectNumber']."' AND year = '".$input['year']."' AND active = 1");
        $action = 'updated';
    } else {
      // project dosn't exist -> lets create
      $projects = DB::insert("INSERT INTO projects (projectNumber, projectName, type, manager, degree, year)
        VALUES ( '".$input['projectNumber']."', '".$input['projectName']."', '".$input['type']."', '".$input['manager']."', '".$input['degree']."', '".$input['year']."' ) ");
        $action = 'created';

      //automatically create "ACCOUNTS" && "CHAPTERS" registers
      //2 accounts
      for ($i=1; $i<=2 ; $i++) {
        $account = DB::insert("INSERT INTO projectaccounts (projectNumber, accountType, total, year) VALUES ( '".$input['projectNumber']."', ".$i.", 0, '".$input['year']."')");
        // 5 CHAPTERS
        for ($j=1; $j<=5 ; $j++) {
          $chapter = DB::insert("INSERT INTO accountchapters (projectNumber, accountType, chapter, year) VALUES ('".$input['projectNumber']."', ".$i.", ".$j.", '".$input['year']."')");
        }
      }
    }

    return response()->json([
      'res' => true,
      'status' => 200,
      'action' => $action
    ]);
  }

  // get project parts of selected month
  public function monthParts(Request $request)
  {
    $input = $request->json()->all();
    $parts = DB::select("SELECT pp.partNumber, pp.month".$input['month']." AS amount, pl.partName FROM projectParts AS pp JOIN partList AS pl ON pp.partNumber = pl.partNumber
      WHERE pp.projectNumber = '".$input['projectNumber']."' AND pp.accountType = ".$input['account']." AND pp.year = ".$input['year']." AND pp.month".$input['month']." > 0 AND pp.active = 1" );

    return response()->json([
      'res' => true,
      'status' => 200,
      'results' => $parts
    ]);
  }

  // get "comparativo de partidas"
  public function comp_parts(Request $request)
  {
    $input = $request->json()->all();

    // get  authorized project accounts
    $query = "SELECT acco.accountType, proj.projectNumber, proj.projectName, proj.type
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

      // get autorized per chapter
      $tAutorized = [0,0,0,0,0,0];
      $autorized = DB::select("SELECT projectNumber, accountType, chapter, total FROM accountchapters WHERE projectNumber = '$project->projectNumber' AND accountType = $project->accountType AND year = ".$input['year']." AND active = 1 ORDER BY chapter");
      foreach ($autorized as $key => $aut) {
        $tAutorized[($aut->chapter - 1)] += $aut->total;
        $tAutorized[5] += $aut->total;
      }
      // asign autorized amounts
      $project->tAutorized = $tAutorized;


      // get incomes
      $tMinistered = [0,0,0,0,0,0];
      $incomes = DB::select("SELECT sfId, account FROM incomes WHERE projectNumber = '$project->projectNumber' AND account = $project->accountType AND type = 'ing' AND year = ".$input['year']." AND active = 1");
      foreach ($incomes as $key => $inc) {
        $sfval = DB::select("SELECT sfId, cap1, cap2, cap3, cap4, cap5, total FROM sfval WHERE sfId = '$inc->sfId' AND year = ".$input['year']." AND active = 1");
        foreach ($sfval as $key => $val) {
            $tMinistered[0] += $val->cap1;
            $tMinistered[1] += $val->cap2;
            $tMinistered[2] += $val->cap3;
            $tMinistered[3] += $val->cap4;
            $tMinistered[4] += $val->cap5;
            $tMinistered[5] += $val->total;
        }
      }
      // asign ministered amounts
      $project->tMinistered = $tMinistered;


      // get re-incomes
      $tReincome = [0,0,0,0,0,0];
      $incomes = DB::select("SELECT sfId, account FROM incomes WHERE projectNumber = '$project->projectNumber' AND account = $project->accountType AND type = 'rei' AND year = ".$input['year']." AND active = 1");
      foreach ($incomes as $key => $inc) {
        $sfval = DB::select("SELECT sfId, cap1, cap2, cap3, cap4, cap5, total FROM sfval WHERE sfId = '$inc->sfId' AND year = ".$input['year']." AND active = 1");
        foreach ($sfval as $key => $val) {
            $tReincome[0] += $val->cap1;
            $tReincome[1] += $val->cap2;
            $tReincome[2] += $val->cap3;
            $tReincome[3] += $val->cap4;
            $tReincome[4] += $val->cap5;
            $tReincome[5] += $val->total;
        }
      }
      // asign re-income amounts
      $project->tReincome = $tReincome;

      // get outcomes
      $tOutcome = [0,0,0,0,0,0];
      $outcomes = DB::select("SELECT checkNumber, projectNumber, account, cap1, cap2, cap3, cap4, cap5, total FROM outcomes WHERE projectNumber = '$project->projectNumber' AND account = $project->accountType AND year = ".$input['year']." AND active = 1");
      foreach ($outcomes as $key => $out) {
        $tOutcome[0] += $out->cap1;
        $tOutcome[1] += $out->cap2;
        $tOutcome[2] += $out->cap3;
        $tOutcome[3] += $out->cap4;
        $tOutcome[4] += $out->cap5;
        $tOutcome[5] += $out->total;
      }
      // asign re-income amounts
      $project->tOutcome = $tOutcome;
    }

    return response()->json([
      'res' => true,
      'status' => 200,
      'results' => $projects
    ]);
  }

}
