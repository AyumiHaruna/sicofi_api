<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Codedge\Fpdf\Fpdf\Fpdf;
use URL;
use DB;
use Carbon\Carbon;

class PrintController extends Controller
{
  // call for fpdf
  private $fpdf;

  // print sf format
  public function income($start, $end = NULL)
  {
    // get selected range income data
    $query = "SELECT inco.*, proj.projectName, peop.name FROM incomes AS inco
      JOIN projects AS proj ON inco.projectNumber = proj.projectNumber
      JOIN people AS peop ON inco.sign = peop.id ";
    if($end == NULL){
      $query .= "WHERE inco.type = 'ing' AND inco.id = $start";
    } else {
      $query .= "WHERE inco.type = 'ing' AND inco.id BETWEEN $start AND $end ORDER BY inco.id";
    }
    $incomes = DB::select($query);

    // get authority names
    $query ="SELECT * FROM authTable WHERE id = 1";
    $auth = DB::select($query);
    $auth = $auth[0];

    // add aditional sf info for each sf
    foreach ($incomes as $key => $income) {
      // add sfData
      $sfData = DB::select("SELECT * from incomesf WHERE incomeId = '$income->sfId' LIMIT 1");
      $income->sfData = $sfData[0];

      // add sf partList
      $income->partList = DB::select("SELECT inc.id, inc.incomeId, inc.partNumber, inc.cap, inc.total, inc.year, par.partName FROM incomesfpart AS inc
        JOIN partlist AS par ON inc.partNumber = par.partNumber
        WHERE inc.incomeId = '$income->sfId' ORDER BY inc.id");
    }

    // printing time
    $pdf = new Fpdf('P','cm', array(21.59 , 29.00 ));
    foreach ($incomes as $key => $income) {
      // FIRST FORMAT -------------------------------------------------------
      // prepare some variables
        // what kind of SF are we printing?
        $sfType = $income->sfData->sfPrintType;

        // add a new page in pdf
        $pdf -> AddPage();

        if( $sfType == 'pro') {
            // logo inah full
            $pdf->Image(URL::to('/img/inah-logo2.png'), 1,0.5, 4.5,2.1, 'PNG');
        } else if ( $sfType == 'ser') {
            // logo cultura
            $pdf->Image(URL::to('/img/logo_cultura.png'), 1,0.8, 5.5,1.7, 'PNG');
            // logo inah
            $pdf->Image(URL::to('/img/inah-logo.png'), 19,0.7, 1.8,1.8, 'PNG');
            // titulo inah
            $pdf->SetFont('Arial','',12);
            $pdf->Cell(23,0.8, utf8_decode('INSTITUTO NACIONAL DE ANTROPOLOGÍA E HISTORIA'), 0, 1, 'C');
        }

        // subtitlo SICOFI
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(21,1, utf8_decode('SOLICITUD DE FONDOS'), 0, 1, 'C');

        // first table
        // table head
        $pdf->SetY(2.8);
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.3;
        $pdf->Rect($x, $y, $w, 1.7, 'D');
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(5,0.7, utf8_decode('NO. DE SOLICITUD DE FONDOS'), 'LTRB',0,'C',0);
        $pdf->Cell(2.5,0.7, utf8_decode('FECHA'), 'LTRB',0,'C',0);
        $pdf->Cell(6.3,0.7, utf8_decode('NOMBRE DEL CENTRO DE TRABAJO'), 'LTRB',0,'C',0);
        $pdf->Cell(5.5,0.7, utf8_decode('CLAVE DEL CENTRO DE TRABAJO'), 'LTRB',1,'C',0);
        // table body
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(5,1, utf8_decode($income->sfId), 'LTRB',0,'C',0);
        $pdf->Cell(2.5,1, utf8_decode($income->elabDate), 'LTRB',0,'C',0);
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=6.3;
        $pdf->MultiCell($w,0.5,utf8_decode('Coordinación Nacional de Conservación del Patrimonio Cultural'), 'LTRB','C');
        $pdf->SetXY($x+$w, $y);
        $pdf->Cell(5.5,1, utf8_decode('530000'), 'LTRB',1,'C',0);

        // second table
        // table head
        $y=$pdf->GetY(); $pdf->SetY( $y + 0.5 );
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.3;
        $pdf->Rect($x, $y, $w, 2.5, 'D');
        $pdf->Rect($x, $y, 5, 2.5, 'D');
        $pdf->Rect($x, $y, 7, 2.5, 'D');

        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(5,0.7, utf8_decode('TIPO DE FINANCIAMIENTO'), 'LTRB',0,'C',0);
        $pdf->Cell(2,0.7, utf8_decode('FOLIO'), 'LTRB',0,'C',0);
        $pdf->Cell(12.3,0.7, utf8_decode('NOMBRE DEL PROYECTO'), 'LTRB',1,'C',0);
        // table body
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(5,1.5, utf8_decode( $this->opType($income->opType) ), '',0,'C',0);
        $pdf->Cell(2,1.5, utf8_decode( $income->projectNumber ), '',0,'C',0);
        $pdf->MultiCell(12.3,0.5,utf8_decode( $income->projectName ), '','C');

        // third table
        $pdf->SetXY(1,8);
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.3;
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(7,0.7, utf8_decode( 'A FAVOR DE' ), 'LTRB',0,'C',0);
        if( $sfType == 'pro') {
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(12.3,0.7, utf8_decode( $income->name ), 'LTRB',0,'C',0);
        } else if ( $sfType == 'ser') {
            $pdf->Rect($x, $y, $w, 1.7, 'D');
            $pdf->Rect($x, $y, 7, 1.7, 'D');
            $pdf->Cell(12.3,0.7, utf8_decode( 'CONCEPTO' ), 'LTRB',1,'C',0);
            $pdf->SetFont('Arial','',9);
            $x=$pdf->GetX(); $y=$pdf->GetY(); $w=7;
            $pdf->MultiCell($w,0.5,utf8_decode( $income->name ), 'T','C');
            $pdf->SetXY($x+$w, $y);
            $x=$pdf->GetX(); $y=$pdf->GetY(); $w=12.3;
            $pdf->MultiCell($w,0.5,utf8_decode( $income->concept ), 'T','C');
        }

        // fourht table
        $y=$pdf->GetY();
        $pdf->SetXY(1,$y + 0.8);
        $pdf->SetFont('Arial','B',9);
        // table title
        $pdf->Cell(19.3,0.7, utf8_decode( 'APLICACIÓN CONTABLE Y PRESUPUESTAL ' ), '',1,'C',0);
        // table head
        $pdf->Cell(2.7,0.7, utf8_decode( 'CTA. CONTABLE' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.5,0.7, utf8_decode( 'DEBE' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.5,0.7, utf8_decode( 'HABER' ), 'LTRB',0,'C',0);
        $pdf->Cell(10.2,0.7, utf8_decode( 'PARTIDA' ), 'LTRB',0,'C',0);
        $pdf->Cell(3.4,0.7, utf8_decode( 'IMPORTE PARCIAL' ), 'LTRB',1,'C',0);

        // table body
        $pdf->SetFont('Arial','',9);
        foreach ($income->partList as $key => $part) {
          if( $sfType == 'pro') {
              $pdf->Cell(2.7,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
          } else if ( $sfType == 'ser') {
              $pdf->Cell(2.7,0.5, utf8_decode( $key + 1 ), 'LTRB',0,'C',0);
          }
          $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
          $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
          if( $sfType == 'pro') {
              $pdf->Cell(10.2,0.5, utf8_decode( $part->partNumber ), 'LTRB',0,'C',0);
          } else if ( $sfType == 'ser') {
              $pdf->Cell(10.2,0.5, utf8_decode( substr($part->partNumber.'- '.$part->partName, 0, 45).(( strlen($part->partNumber.'- '.$part->partName) > 45 )? '...' : '') ), 'LTRB',0,'L',0);
          }
          $pdf->Cell(3.4,0.5, '$'.number_format($part->total, 2, ".", ","), 'LTRB',1,'R',0);
        }
        // // simulate multiple parts
        // for ($i=0; $i < 23; $i++) {
        //   $pdf->Cell(2.7,0.5, utf8_decode( 'CTA. CONTABLE' ), 'LTRB',0,'C',0);
        //   $pdf->Cell(1.5,0.5, utf8_decode( 'DEBE' ), 'LTRB',0,'C',0);
        //   $pdf->Cell(1.5,0.5, utf8_decode( 'HABER' ), 'LTRB',0,'C',0);
        //   $pdf->Cell(10.2,0.5, utf8_decode( 'PARTIDA' ), 'LTRB',0,'C',0);
        //   $pdf->Cell(3.4,0.5, utf8_decode( 'IMPORTE PARCIAL' ), 'LTRB',1,'C',0);
        // }
        $pdf->SetFont('Arial','B',9);
        if( $sfType == 'pro') {
          $pdf->Cell(15.9,0.7, 'TOTAL', 'LTRB',0,'R',0);
          $pdf->Cell(3.4,0.7, '$'.number_format($income->requested, 2, ".", ","), 'LTRB',1,'R',0);

          $y=$pdf->GetY();
          $pdf->SetXY(1,$y + 0.35);
          $pdf->Cell(3.4,0.7, 'TOTAL SOLICITADO', 'LTB',0,'C',0);
          $pdf->SetFont('Arial','',9);
          $pdf->Cell(15.9,0.7, $this->moneyToString($income->requested), 'TBR',1,'C',0);
          // $pdf->Cell(15.9,0.7, $this->moneyToString(22538255.89), 'LTRB',0,'C',0);
        } else if ( $sfType == 'ser') {
          $pdf->Cell(15.9,0.7, utf8_decode('TOTAL SOLICITADO EN LETRA'), 'LTRB',0,'C',0);
          $pdf->Cell(3.4,0.7, utf8_decode('TOTAL'), 'LTRB',1,'C',0);
          $pdf->SetFont('Arial','',8.5);
          $pdf->Cell(15.9,0.7, $this->moneyToString($income->requested), 'LTRB',0,'C',0);
          // $pdf->Cell(15.9,0.7, $this->moneyToString(22538255.89), 'LTRB',0,'C',0);
          $pdf->Cell(3.4,0.7, '$'.number_format($income->requested, 2, ".", ","), 'LTRB',1,'R',0);
        }

        // obs table
        $y=$pdf->GetY();
        $pdf->SetXY(1,$y + 0.5);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(19.3,0.7, utf8_decode('OBSERVACIONES'), 'LTRB',1,'L',0);
        $pdf->MultiCell(19.3,0.5, utf8_decode( $income->obs ), 'LTRB','L');

        $y=$pdf->GetY();
        $pdf->SetXY(1,$y + 0.5);
        if ( $sfType == 'ser') {
          // taxes table
          // table head
          $pdf->Cell(2.7,1, utf8_decode('ACTIVIDAD'), 'LTRB',0,'C',0);
          $pdf->Cell(2.6,1, utf8_decode('(1)AUTORIZADO'), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, utf8_decode('16%(IVA)'), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, utf8_decode('SUBTOTAL'), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, utf8_decode('2/3 IVA'), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, utf8_decode('ISR'), 'LTRB',0,'C',0);
          $x=$pdf->GetX(); $y=$pdf->GetY(); $w=2.6;
          $pdf->MultiCell($w,0.5,utf8_decode('SUBTOTAL RETENCIONES'), 'LTRB','C');
          $pdf->SetXY($x+$w, $y);
          $pdf->MultiCell($w,0.5,utf8_decode('TOTAL A MINISTRAR'), 'LTRB','C');
          // table body
          $y=$pdf->GetY();
          $pdf->SetXY(1,$y);
          $pdf->SetFont('Arial','',9.5);
          $x=$pdf->GetX(); $y=$pdf->GetY(); $w=2.7;
          $pdf->MultiCell($w,0.5,utf8_decode( $this->opType($income->opType) ), 'LTRB','C');
          $pdf->SetXY($x+$w, $y);
          $pdf->Cell(2.6,1, '$'.number_format($income->requested, 2, ".", ","), 'LTRB',0,'C',0);

          // calculate taxes
          $ivaT = ( isset($income->sfData->taxConfig[0]) )? ($income->requested * $income->sfData->ivaTC) : 0;
          $ivaR = ( isset($income->sfData->taxConfig[1]) )? ($income->requested * $income->sfData->ivaRC) : 0;
          $isrR = ( isset($income->sfData->taxConfig[2]) )? ($income->requested * $income->sfData->isrRC) : 0;

          $pdf->Cell(2.2,1, '$'.number_format($ivaT, 2, ".", ","), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, '$'.number_format( ($income->requested + $ivaT), 2, ".", ","), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, '$'.number_format($ivaR, 2, ".", ","), 'LTRB',0,'C',0);
          $pdf->Cell(2.2,1, '$'.number_format($isrR, 2, ".", ","), 'LTRB',0,'C',0);
          $pdf->Cell(2.6,1, '$'.number_format( ($ivaR + $isrR), 2, ".", ","), 'LTRB',0,'C',0);
          $pdf->Cell(2.6,1, '$'.number_format( ($income->requested + $ivaT - $ivaR - $isrR), 2, ".", ","), 'LTRB',1,'C',0);
          $y=$pdf->GetY();
          $pdf->SetXY(1,$y + 0.5);
        }

        if ($pdf->GetY() + 4.5 >= $pdf->GetPageHeight() )
        {
            $pdf->AddPage();
            $pdf->SetY(1);
        }

        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.3;
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(7,1, utf8_decode('NOMBRE Y FIRMA DEL TITULAR'), 'LTRB',0,'C',0);
        $pdf->Cell(4,1, utf8_decode('PRESUPUESTO'), 'LTRB',0,'C',0);
        $pdf->Cell(4,1, utf8_decode('FISCALIZACIÓN'), 'LTRB',0,'C',0);
        $pdf->Cell(4.3,1, utf8_decode('CONTABILIDAD'), 'LTRB',0,'C',0);

        $pdf->Rect($x, $y, $w, 3.5, 'D');
        $pdf->Rect($x, $y, 7, 3.5, 'D');
        $pdf->Rect($x, $y, 11, 3.5, 'D');
        $pdf->Rect($x, $y, 15, 3.5, 'D');

        $pdf->SetXY(1, $y + 2.7);
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(7,1, utf8_decode($auth->coord), '',0,'C',0);

        // SECOND FORMAT ----------------------------------------
        $pdf -> AddPage();

        // main info table
        $pdf->SetY(1.25);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(10,0.7, utf8_decode('COMPROBANTE DE GASTOS'),'', 1,'L');
        $pdf->Image(URL::to('/img/coninah.png'), 14.5,1.4, 5.8,0.5, 'PNG');

        // op type table
        $pdf->SetY(2.4);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(12,0.7, utf8_decode('CENTRO DE TRABAJO'), 'LTRB',0,'C',0);
        $pdf->Cell(7.4,0.7, utf8_decode('CVE. CENTRO DE TRABAJO'), 'LTRB',1,'C',0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(12,0.7, utf8_decode('Coordinación Nacional de Conservación del Patrimonio Cultural'), 'LTRB',0,'C',0);
        $pdf->Cell(7.4,0.7, utf8_decode('530000'), 'LTRB',1,'C',0);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(4.4,0.7, utf8_decode('Gasto Básico'), 'LTRB',0,'C',0);
        $pdf->Cell(2.067,0.7, utf8_decode( ($income->opType == 'gba')?'X':'-' ), 'LTRB',0,'C',0);
        $pdf->Cell(4.4,0.7, utf8_decode('Gastos de operación'), 'LTRB',0,'C',0);
        $pdf->Cell(2.067,0.7, utf8_decode( ($income->opType == 'gop')?'X':'-' ), 'LTRB',0,'C',0);
        $pdf->Cell(4.4,0.7, utf8_decode('Inversión'), 'LTRB',0,'C',0);
        $pdf->Cell(2.067,0.7, utf8_decode( ($income->opType == 'inv')?'X':'-' ), 'LTRB',1,'C',0);
        $pdf->Cell(4.4,0.7, utf8_decode('Proyecto'), 'LTRB',0,'C',0);
        $pdf->Cell(2.067,0.7, utf8_decode( ($income->opType == 'pro')?'X':'-' ), 'LTRB',0,'C',0);
        $pdf->Cell(4.4,0.7, utf8_decode('Gasto de administración'), 'LTRB',0,'C',0);
        $pdf->Cell(2.067,0.7, utf8_decode( ($income->opType == 'gad')?'X':'-' ), 'LTRB',0,'C',0);
        $pdf->Cell(4.4,0.7, utf8_decode('Terceros'), 'LTRB',0,'C',0);
        $pdf->Cell(2.067,0.7, utf8_decode( ($income->opType == 'ter')?'X':'-' ), 'LTRB',1,'C',0);

        // project info table
        $y = $pdf->GetY();
        $pdf->SetY( $y + 0.5 );
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.4;
        $pdf->Rect($x, $y, $w, 2.5, 'D');
        $pdf->Rect($x, $y, 4.5, 2.5, 'D');
        $pdf->Rect($x, $y, 14.9, 2.5, 'D');
        $pdf->Rect($x, $y, 17.4, 2.5, 'D');

        $pdf->Cell(4.5,0.7, utf8_decode( 'Folio' ), 'LTRB',0,'C',0);
        $pdf->Cell(10.4,0.7, utf8_decode( 'Nombre del proyecto' ), 'LTRB',0,'C',0);
        $pdf->Cell(2.5,0.7, utf8_decode( 'Mes' ), 'LTRB',0,'C',0);
        $pdf->Cell(2,0.7, utf8_decode( 'Año' ), 'LTRB',1,'C',0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(4.5,1.5, utf8_decode( $income->sfId ), '',0,'C',0);
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=10.4;
        $pdf->MultiCell($w,0.5,utf8_decode( $income->projectName ), '','L');
        $pdf->SetXY($x+$w, $y);
        $pdf->Cell(2.5,1.5, utf8_decode( $this->fullMonth( $income->month ) ), '',0,'C',0);
        $pdf->Cell(2,1.5, utf8_decode( $income->year ), '',1,'C',0);

        // part list
        $y = $pdf->GetY();
        $pdf->SetY( $y + 0.8 );
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(1.5,0.7, utf8_decode( 'Cuenta' ), 'LTRB',0,'C',0);
        $pdf->Cell(2,0.7, utf8_decode( 'Sub-cuenta' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.9,0.7, utf8_decode( 'SS-Cuenta' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.4,0.7, utf8_decode( 'Partida' ), 'LTRB',0,'C',0);
        $pdf->Cell(8.4,0.7, utf8_decode( 'Descripción' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.7,0.7, utf8_decode( 'No. Notas' ), 'LTRB',0,'C',0);
        $pdf->Cell(2.5,0.7, utf8_decode( 'Importe' ), 'LTRB',1,'C',0);
        $pdf->SetFont('Arial','',9);
        foreach ( $income->partList as $key => $part ) {
            $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
            $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
            $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
            $pdf->Cell(1.4,0.5, utf8_decode( $part->partNumber ), 'LTRB',0,'C',0);
            $pdf->Cell(8.4,0.5, utf8_decode( (substr($part->partName, 0, 40)).(( strlen($part->partName) > 40 )? '...' : '') ), 'LTRB',0,'L',0);
            $pdf->Cell(1.7,0.5, '', 'LTRB',0,'C',0);
            $pdf->Cell(2.5,0.5, utf8_decode( '$'.number_format($part->total, 2, ".", ",") ), 'LTRB',1,'R',0);
        }
        // // simulate multiple parts
        // for ($i=0; $i < 29; $i++) {
        //     $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
        //     $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
        //     $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
        //     $pdf->Cell(1.4,0.5, utf8_decode( '0000' ), 'LTRB',0,'C',0);
        //     $pdf->Cell(8.4,0.5, utf8_decode( (substr('PARTIDA DE PRUEBA PARA HMMMM PROBAR EL ESPACIO, SI ESO', 0, 40)).(( strlen('PARTIDA DE PRUEBA PARA HMMMM PROBAR EL ESPACIO, SI ESO') > 40 )? '...' : '') ), 'LTRB',0,'L',0);
        //     $pdf->Cell(1.7,0.5, utf8_decode( 'NNN' ), 'LTRB',0,'C',0);
        //     $pdf->Cell(2.5,0.5, utf8_decode( '$1,000,000.00' ), 'LTRB',1,'R',0);
        // }
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(16.9,0.7, utf8_decode( 'Total' ), 'LTRB',0,'C',0);
        $pdf->Cell(2.5,0.7, utf8_decode( '$'.number_format($income->requested, 2, ".", ",") ), 'LTRB',1,'C',0);


        if ($pdf->GetY() + 3.8 >= $pdf->GetPageHeight() )
        {
            $pdf->AddPage();
            $pdf->SetY(1);
        }

        // sign table
        $y = $pdf->GetY();
        $pdf->SetY( $y + 0.5 );
        $pdf->SetFont('Arial','',10);
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.4;
        $pdf->Rect($x, $y, $w, 3.1, 'D');
        $pdf->Rect($x, $y, 3.9, 3.1, 'D');
        $pdf->Rect($x, $y, 9.4, 3.1, 'D');
        $pdf->Rect($x, $y, 14.9, 3.1, 'D');

        $pdf->Cell(3.9,0.7, utf8_decode('Fecha de elaboración'), '',0,'C',0);
        $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.5;
        $pdf->MultiCell($w,0.5,utf8_decode( 'Nombre y firma del titular de la dependencia' ), '','C');
        $pdf->SetXY($x+$w, $y);
        $pdf->Cell(5.5,0.7, utf8_decode('Recibió'), '',0,'C',0);
        $pdf->Cell(4.5,0.7, utf8_decode('Fecha de autorización'), '',1,'C',0);

        $y = $pdf->GetY();
        $pdf->SetXY( 4.9, $y+1.4 );
        $pdf->MultiCell($w,0.5,utf8_decode( $auth->coord ), '','C');
        $pdf->SetXY( 16.1, $y+0.7);
        $pdf->Cell(4.3,0.7, utf8_decode('No. Comprobación'), '',1,'C',0);

        // special table
        $y = $pdf->GetY();
        $pdf->SetY($y+1.5);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(19.4,0.7, utf8_decode('PARA USO EXCLUSIVO DE LA SUBDIRECCIÓN DE FISCALIZACIÓN'), 'TRBL',1,'C',0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(7.5,0.7, utf8_decode('NÚMERO DE PÓLIZA DE COMPROBACIÓN'), 'TRBL',0,'C',0);
        $pdf->Cell(4,0.7, utf8_decode(''), 'TRBL',0,'C',0);
        $pdf->Cell(3.9,0.7, utf8_decode('FECHA'), 'TRBL',0,'C',0);
        $pdf->Cell(4,0.7, utf8_decode(''), 'TRBL',0,'C',0);
    }

    $pdf->Output();
    exit;
  }

  // print sf check
  public function income_comp($incomeId, $checkingId)
  {
    // get income comp data
    $query = "SELECT inco.*, proj.projectName, peop.name FROM incomes AS inco
      JOIN projects AS proj ON inco.projectNumber = proj.projectNumber
      JOIN people AS peop ON inco.sign = peop.id
      WHERE inco.id = $incomeId LIMIT 1";
    $income = DB::select($query);
    $income = (array) $income[0];

    // get authority names
    $query ="SELECT * FROM authTable WHERE id = 1";
    $auth = DB::select($query);
    $auth = $auth[0];

    // get income checking
    $query = "SELECT * FROM sfchecking WHERE id = $checkingId LIMIT 1";
    $checking = DB::select($query);
    $income['checking'] = (array) $checking[0];

    // get income checking parts
    $query = "SELECT chec.*, part.partName FROM sfchecklist AS chec
      JOIN partlist AS part ON chec.partNumber = part.partNumber
      WHERE chec.sfId = '".$income['sfId']."' AND cover = ".$income['checking']['cover']." AND chec.active = 1";
    $income['checkList'] = DB::select($query);

    // printing time
    $pdf = new Fpdf('P','cm', array(21.59 , 29.00 ));
    $pdf -> AddPage();

    // main info table
    $pdf->SetY(0.9);
    $pdf->SetFont('Arial','',14);
    $pdf->Cell(10,0.7, utf8_decode('COMPROBANTE DE GASTOS'),'', 1,'L');
    $pdf->Cell(10,0.7, utf8_decode('C - '.$income['checking']['cover']),'', 1,'L');
    $pdf->Image(URL::to('/img/coninah.png'), 14.5,1.4, 5.8,0.5, 'PNG');

    // op type table
    $pdf->SetY(2.4);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(12,0.7, utf8_decode('CENTRO DE TRABAJO'), 'LTRB',0,'C',0);
    $pdf->Cell(7.4,0.7, utf8_decode('CVE. CENTRO DE TRABAJO'), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(12,0.7, utf8_decode('Coordinación Nacional de Conservación del Patrimonio Cultural'), 'LTRB',0,'C',0);
    $pdf->Cell(7.4,0.7, utf8_decode('530000'), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(4.4,0.7, utf8_decode('Gasto Básico'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'gba')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Gastos de operación'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'gop')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Inversión'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'inv')?'X':'-' ), 'LTRB',1,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Proyecto'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'pro')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Gasto de administración'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'gad')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Terceros'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'ter')?'X':'-' ), 'LTRB',1,'C',0);

    // project info table
    $y = $pdf->GetY();
    $pdf->SetY( $y + 0.5 );
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.4;
    $pdf->Rect($x, $y, $w, 2.5, 'D');
    $pdf->Rect($x, $y, 4.5, 2.5, 'D');
    $pdf->Rect($x, $y, 14.9, 2.5, 'D');
    $pdf->Rect($x, $y, 17.4, 2.5, 'D');

    $pdf->Cell(4.5,0.7, utf8_decode( 'Folio' ), 'LTRB',0,'C',0);
    $pdf->Cell(10.4,0.7, utf8_decode( 'Nombre del proyecto' ), 'LTRB',0,'C',0);
    $pdf->Cell(2.5,0.7, utf8_decode( 'Mes' ), 'LTRB',0,'C',0);
    $pdf->Cell(2,0.7, utf8_decode( 'Año' ), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(4.5,1.5, utf8_decode( $income['sfId'] ), '',0,'C',0);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=10.4;
    $pdf->MultiCell($w,0.5,utf8_decode( $income['projectName'] ), '','L');
    $pdf->SetXY($x+$w, $y);
    $pdf->Cell(2.5,1.5, utf8_decode( $this->fullMonth( $income['month'] ) ), '',0,'C',0);
    $pdf->Cell(2,1.5, utf8_decode( $income['year'] ), '',1,'C',0);

    // part list
    $y = $pdf->GetY();
    $pdf->SetY( $y + 0.8 );
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(1.5,0.7, utf8_decode( 'Cuenta' ), 'LTRB',0,'C',0);
    $pdf->Cell(2,0.7, utf8_decode( 'Sub-cuenta' ), 'LTRB',0,'C',0);
    $pdf->Cell(1.9,0.7, utf8_decode( 'SS-Cuenta' ), 'LTRB',0,'C',0);
    $pdf->Cell(1.4,0.7, utf8_decode( 'Partida' ), 'LTRB',0,'C',0);
    $pdf->Cell(8.4,0.7, utf8_decode( 'Descripción' ), 'LTRB',0,'C',0);
    $pdf->Cell(1.7,0.7, utf8_decode( 'No. Notas' ), 'LTRB',0,'C',0);
    $pdf->Cell(2.5,0.7, utf8_decode( 'Importe' ), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','',9);
    foreach ($income['checkList'] as $key => $part) {
        $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
        $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
        $pdf->Cell(1.4,0.5, utf8_decode( $part->partNumber ), 'LTRB',0,'C',0);
        if($part->obs == '' || $part->obs == NULL){
          $pdf->Cell(8.4,0.5, utf8_decode( (substr($part->partName, 0, 40)).(( strlen($part->partName) > 40 )? '...' : '') ), 'LTRB',0,'L',0);
        } else {
          $pdf->Cell(8.4,0.5, utf8_decode( $part->obs ), 'LTRB',0,'L',0);
        }
        $pdf->Cell(1.7,0.5, utf8_decode( $part->notes ), 'LTRB',0,'C',0);
        $pdf->Cell(2.5,0.5, utf8_decode( '$'.number_format($part->total, 2, ".", ",") ), 'LTRB',1,'R',0);
    }
    // // simulate multiple parts
    // for ($i=0; $i < 29; $i++) {
    //     $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(1.4,0.5, utf8_decode( '0000' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(8.4,0.5, utf8_decode( (substr('PARTIDA DE PRUEBA PARA HMMMM PROBAR EL ESPACIO, SI ESO', 0, 40)).(( strlen('PARTIDA DE PRUEBA PARA HMMMM PROBAR EL ESPACIO, SI ESO') > 40 )? '...' : '') ), 'LTRB',0,'L',0);
    //     $pdf->Cell(1.7,0.5, utf8_decode( 'NNN' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(2.5,0.5, utf8_decode( '$1,000,000.00' ), 'LTRB',1,'R',0);
    // }
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(16.9,0.7, utf8_decode( 'Total' ), 'LTRB',0,'C',0);
    $pdf->Cell(2.5,0.7, utf8_decode( '$'.number_format($income['checking']['checked'], 2, ".", ",") ), 'LTRB',1,'C',0);


    if ($pdf->GetY() + 3.8 >= $pdf->GetPageHeight() )
    {
        $pdf->AddPage();
        $pdf->SetY(1);
    }

    // sign table
    $y = $pdf->GetY();
    $pdf->SetY( $y + 0.5 );
    $pdf->SetFont('Arial','',10);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.4;
    $pdf->Rect($x, $y, $w, 3.1, 'D');
    $pdf->Rect($x, $y, 3.9, 3.1, 'D');
    $pdf->Rect($x, $y, 9.4, 3.1, 'D');
    $pdf->Rect($x, $y, 14.9, 3.1, 'D');

    $pdf->Cell(3.9,0.7, utf8_decode('Fecha de elaboración'), '',0,'C',0);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.5;
    $pdf->MultiCell($w,0.5,utf8_decode( 'Nombre y firma del titular de la dependencia' ), '','C');
    $pdf->SetXY($x+$w, $y);
    $pdf->Cell(5.5,0.7, utf8_decode('Recibió'), '',0,'C',0);
    $pdf->Cell(4.5,0.7, utf8_decode('Fecha de autorización'), '',1,'C',0);

    $y = $pdf->GetY();
    $pdf->SetXY( 4.9, $y+1.4 );
    $pdf->MultiCell($w,0.5,utf8_decode( $auth->coord ), '','C');
    $pdf->SetXY( 16.1, $y+0.7);
    $pdf->Cell(4.3,0.7, utf8_decode('No. Comprobación'), '',1,'C',0);

    // special table
    $y = $pdf->GetY();
    $pdf->SetY($y+1.5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(19.4,0.7, utf8_decode('PARA USO EXCLUSIVO DE LA SUBDIRECCIÓN DE FISCALIZACIÓN'), 'TRBL',1,'C',0);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(7.5,0.7, utf8_decode('NÚMERO DE PÓLIZA DE COMPROBACIÓN'), 'TRBL',0,'C',0);
    $pdf->Cell(4,0.7, utf8_decode(''), 'TRBL',0,'C',0);
    $pdf->Cell(3.9,0.7, utf8_decode('FECHA'), 'TRBL',0,'C',0);
    $pdf->Cell(4,0.7, utf8_decode(''), 'TRBL',0,'C',0);


    $pdf->Output();
    exit;
    // return $income;

    // print_r($income);
  }

  // print a global checking for one SF
  public function SFgobal_comp($incId)
  {
    // echo $incId;
    // get income comp data
    $query = "SELECT inco.*, proj.projectName, peop.name FROM incomes AS inco
      JOIN projects AS proj ON inco.projectNumber = proj.projectNumber
      JOIN people AS peop ON inco.sign = peop.id
      WHERE inco.id = $incId LIMIT 1";
    $income = DB::select($query);
    $income = (array) $income[0];

    // get authority names
    $query ="SELECT * FROM authTable WHERE id = 1";
    $auth = DB::select($query);
    $auth = $auth[0];

    // // get income checking
    // $query = "SELECT * FROM sfchecklist WHERE sfId = '".$income['sfId']."' AND active = 1 ORDER BY partNumber";
    // $checking = DB::select($query);
    // $income['chekList'] = (array) $checking;

    // get income checking parts
    $query = "SELECT chec.*, part.partName FROM sfchecklist AS chec
      JOIN partlist AS part ON chec.partNumber = part.partNumber
      WHERE chec.sfId = '".$income['sfId']."' AND chec.active = 1";
    $income['checkList'] = DB::select($query);

    // JOIN SIMILAR PARTS ------------------------
    $pcNameList = [];
    $pcList = [];
    //generate a printing cheklist table
    foreach ($income['checkList'] as $key => $part) {
      //get arrayName
      $aName = $part->partNumber;
      $aName .= ($part->obs== ''||$part->obs==NULL)? '/o' : '/r';

      //check if element is not in array -> true -> create
      if(!in_array($aName, $pcNameList)){
        array_push( $pcNameList, $aName );
        $pcList[$aName] = [$part->partNumber, $part->partName, $part->obs, $part->notes, $part->total];
      } else {
        $pcList[$aName] = [$part->partNumber, $part->partName, $part->obs, ($pcList[$aName][3] += $part->notes), ($pcList[$aName][4] += $part->total)];
      }
    }

    // print_r($pcNameList);
    // echo '<br><br>';
    // print_r($pcList);

    // printing time
    $pdf = new Fpdf('P','cm', array(21.59 , 29.00 ));
    $pdf -> AddPage();

    // main info table
    $pdf->SetY(0.9);
    $pdf->SetFont('Arial','',14);
    $pdf->Cell(10,0.7, utf8_decode('COMPROBANTE DE GASTOS'),'', 1,'L');
    $pdf->Cell(10,0.7, utf8_decode('GLOBAL'),'', 1,'L');
    $pdf->Image(URL::to('/img/coninah.png'), 14.5,1.4, 5.8,0.5, 'PNG');

    // op type table
    $pdf->SetY(2.4);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(12,0.7, utf8_decode('CENTRO DE TRABAJO'), 'LTRB',0,'C',0);
    $pdf->Cell(7.4,0.7, utf8_decode('CVE. CENTRO DE TRABAJO'), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(12,0.7, utf8_decode('Coordinación Nacional de Conservación del Patrimonio Cultural'), 'LTRB',0,'C',0);
    $pdf->Cell(7.4,0.7, utf8_decode('530000'), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(4.4,0.7, utf8_decode('Gasto Básico'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'gba')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Gastos de operación'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'gop')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Inversión'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'inv')?'X':'-' ), 'LTRB',1,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Proyecto'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'pro')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Gasto de administración'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'gad')?'X':'-' ), 'LTRB',0,'C',0);
    $pdf->Cell(4.4,0.7, utf8_decode('Terceros'), 'LTRB',0,'C',0);
    $pdf->Cell(2.067,0.7, utf8_decode( ($income['opType'] == 'ter')?'X':'-' ), 'LTRB',1,'C',0);

    // project info table
    $y = $pdf->GetY();
    $pdf->SetY( $y + 0.5 );
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.4;
    $pdf->Rect($x, $y, $w, 2.5, 'D');
    $pdf->Rect($x, $y, 4.5, 2.5, 'D');
    $pdf->Rect($x, $y, 14.9, 2.5, 'D');
    $pdf->Rect($x, $y, 17.4, 2.5, 'D');

    $pdf->Cell(4.5,0.7, utf8_decode( 'Folio' ), 'LTRB',0,'C',0);
    $pdf->Cell(10.4,0.7, utf8_decode( 'Nombre del proyecto' ), 'LTRB',0,'C',0);
    $pdf->Cell(2.5,0.7, utf8_decode( 'Mes' ), 'LTRB',0,'C',0);
    $pdf->Cell(2,0.7, utf8_decode( 'Año' ), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(4.5,1.5, utf8_decode( $income['sfId'] ), '',0,'C',0);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=10.4;
    $pdf->MultiCell($w,0.5,utf8_decode( $income['projectName'] ), '','L');
    $pdf->SetXY($x+$w, $y);
    $pdf->Cell(2.5,1.5, utf8_decode( $this->fullMonth( $income['month'] ) ), '',0,'C',0);
    $pdf->Cell(2,1.5, utf8_decode( $income['year'] ), '',1,'C',0);

    // part list
    $y = $pdf->GetY();
    $pdf->SetY( $y + 0.8 );
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(1.5,0.7, utf8_decode( 'Cuenta' ), 'LTRB',0,'C',0);
    $pdf->Cell(2,0.7, utf8_decode( 'Sub-cuenta' ), 'LTRB',0,'C',0);
    $pdf->Cell(1.9,0.7, utf8_decode( 'SS-Cuenta' ), 'LTRB',0,'C',0);
    $pdf->Cell(1.4,0.7, utf8_decode( 'Partida' ), 'LTRB',0,'C',0);
    $pdf->Cell(8.4,0.7, utf8_decode( 'Descripción' ), 'LTRB',0,'C',0);
    $pdf->Cell(1.7,0.7, utf8_decode( 'No. Notas' ), 'LTRB',0,'C',0);
    $pdf->Cell(2.5,0.7, utf8_decode( 'Importe' ), 'LTRB',1,'C',0);
    $pdf->SetFont('Arial','',9);
    foreach ($pcList as $key => $part) {
          $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
          $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
          $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
          $pdf->Cell(1.4,0.5, utf8_decode( $part[0] ), 'LTRB',0,'C',0);
          if($part[2] == '' || $part[2] == NULL){
            $pdf->Cell(8.4,0.5, utf8_decode( (substr($part[1], 0, 40)).(( strlen($part[1]) > 40 )? '...' : '') ), 'LTRB',0,'L',0);
          } else {
            $pdf->Cell(8.4,0.5, utf8_decode( $part[2] ), 'LTRB',0,'L',0);
          }
          $pdf->Cell(1.7,0.5, utf8_decode( $part[3] ), 'LTRB',0,'C',0);
          $pdf->Cell(2.5,0.5, utf8_decode( '$'.number_format($part[4], 2, ".", ",") ), 'LTRB',1,'R',0);
    }
    // // simulate multiple parts
    // for ($i=0; $i < 3; $i++) {
    //     $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(1.4,0.5, utf8_decode( '0000' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(8.4,0.5, utf8_decode( (substr('PARTIDA DE PRUEBA PARA HMMMM PROBAR EL ESPACIO, SI ESO', 0, 40)).(( strlen('PARTIDA DE PRUEBA PARA HMMMM PROBAR EL ESPACIO, SI ESO') > 40 )? '...' : '') ), 'LTRB',0,'L',0);
    //     $pdf->Cell(1.7,0.5, utf8_decode( 'NNN' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(2.5,0.5, utf8_decode( '$1,000,000.00' ), 'LTRB',1,'R',0);
    // }
    // foreach ($income['checkList'] as $key => $part) {
    //     $pdf->Cell(1.5,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(2,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(1.9,0.5, utf8_decode( '' ), 'LTRB',0,'C',0);
    //     $pdf->Cell(1.4,0.5, utf8_decode( $part->partNumber ), 'LTRB',0,'C',0);
    //     if($part->obs == '' || $part->obs == NULL){
    //       $pdf->Cell(8.4,0.5, utf8_decode( (substr($part->partName, 0, 40)).(( strlen($part->partName) > 40 )? '...' : '') ), 'LTRB',0,'L',0);
    //     } else {
    //       $pdf->Cell(8.4,0.5, utf8_decode( $part->obs ), 'LTRB',0,'L',0);
    //     }
    //     $pdf->Cell(1.7,0.5, utf8_decode( $part->notes ), 'LTRB',0,'C',0);
    //     $pdf->Cell(2.5,0.5, utf8_decode( '$'.number_format($part->total, 2, ".", ",") ), 'LTRB',1,'R',0);
    // }
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(16.9,0.7, utf8_decode( 'Total' ), 'LTRB',0,'C',0);
    $pdf->Cell(2.5,0.7, utf8_decode( '$'.number_format($income['checked'], 2, ".", ",") ), 'LTRB',1,'C',0);

    if ($pdf->GetY() + 3.8 >= $pdf->GetPageHeight() )
    {
        $pdf->AddPage();
        $pdf->SetY(1);
    }

    // sign table
    $y = $pdf->GetY();
    $pdf->SetY( $y + 0.5 );
    $pdf->SetFont('Arial','',10);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.4;
    $pdf->Rect($x, $y, $w, 3.1, 'D');
    $pdf->Rect($x, $y, 3.9, 3.1, 'D');
    $pdf->Rect($x, $y, 9.4, 3.1, 'D');
    $pdf->Rect($x, $y, 14.9, 3.1, 'D');

    $pdf->Cell(3.9,0.7, utf8_decode('Fecha de elaboración'), '',0,'C',0);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.5;
    $pdf->MultiCell($w,0.5,utf8_decode( 'Nombre y firma del titular de la dependencia' ), '','C');
    $pdf->SetXY($x+$w, $y);
    $pdf->Cell(5.5,0.7, utf8_decode('Recibió'), '',0,'C',0);
    $pdf->Cell(4.5,0.7, utf8_decode('Fecha de autorización'), '',1,'C',0);

    $y = $pdf->GetY();
    $pdf->SetXY( 4.9, $y+1.4 );
    $pdf->MultiCell($w,0.5,utf8_decode( $auth->coord ), '','C');
    $pdf->SetXY( 16.1, $y+0.7);
    $pdf->Cell(4.3,0.7, utf8_decode('No. Comprobación'), '',1,'C',0);

    // special table
    $y = $pdf->GetY();
    $pdf->SetY($y+1.5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(19.4,0.7, utf8_decode('PARA USO EXCLUSIVO DE LA SUBDIRECCIÓN DE FISCALIZACIÓN'), 'TRBL',1,'C',0);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(7.5,0.7, utf8_decode('NÚMERO DE PÓLIZA DE COMPROBACIÓN'), 'TRBL',0,'C',0);
    $pdf->Cell(4,0.7, utf8_decode(''), 'TRBL',0,'C',0);
    $pdf->Cell(3.9,0.7, utf8_decode('FECHA'), 'TRBL',0,'C',0);
    $pdf->Cell(4,0.7, utf8_decode(''), 'TRBL',0,'C',0);

    $pdf->Output();
    exit;
  }

  // print "Poliza de cheque"
  public function poliza($start, $end = NULL)
  {
    // get selected range income data
    $query = "SELECT outc.*, proj.projectName, peop.name FROM outcomes AS outc
      JOIN projects AS proj ON outc.projectNumber = proj.projectNumber
      JOIN people AS peop ON outc.sign = peop.id ";
    if($end == NULL){
      $query .= "WHERE outc.id = $start";
    } else {
      $query .= "WHERE outc.id BETWEEN $start AND $end ORDER BY outc.id";
    }
    $outcomes = DB::select($query);

    // get authority names
    $query ="SELECT * FROM authTable WHERE id = 1";
    $auth = DB::select($query);
    $auth = $auth[0];

    // printing time
    $pdf = new Fpdf('P','cm', array(21.59 , 29.00 ));
    foreach ($outcomes as $key => $outcome) {
      $pdf -> AddPage();

      // logo cultura
      $pdf->Image(URL::to('/img/logo_cultura.png'), 1,0.8, 4.2,1.2, 'PNG');
      // logo inah
      $pdf->Image(URL::to('/img/inah-logo.png'), 19,0.7, 1.6,1.6, 'PNG');
      // titulo inah
      $pdf->SetFont('Arial','',13);
      $pdf->SetY(2.1);
      $pdf->Cell(19.3,0.7, utf8_decode('COORDINACIÓN NACIONAL DE CONSERVACIÓN DEL PATRIMONIO CULTURAL'), '',1,'C',0);
      $pdf->Cell(19.3,0.7, utf8_decode('SUBDIRECCIÓN ADMINISTRATIVA'), '',1,'C',0);
      $pdf->Cell(19.3,0.7, utf8_decode('POLIZA DE '.strtoupper($outcome->payType)), '',1,'C',0);

      // main outcome info
      $pdf->SetY( $pdf->GetY() );
      $x=$pdf->GetX(); $y=$pdf->GetY(); $w=19.3;
      $pdf->Rect($x, $y, $w, 3.2, 'D');

      $pdf->SetY( $pdf->GetY() );
      $pdf->SetFont('Arial','',10);
      $pdf->Cell(18.9,0.8, utf8_decode($this->stringDate( $outcome->elabDate )), '',1,'R',0);
      $pdf->Cell(13,0.8, utf8_decode( $outcome->name ), '',0,'C',0);
      $pdf->SetFont('Arial','',11.5);
      $pdf->Cell(6.3,0.8, '$'.number_format($outcome->total, 2, ".", ","), '',1,'C',0);
      $pdf->SetFont('Arial','',8.5);
      $pdf->Cell(19.3,0.8, utf8_decode("(".$this->moneyToString($outcome->total).")"), '',1,'C',0);
      $pdf->SetY( $pdf->GetY() );
      $pdf->SetFont('Arial','',10);
      $pdf->Cell(13,1, utf8_decode( '  CUENTA: 8436835-563' ), '',0,'L',0);
      $pdf->Cell(6,1, utf8_decode($outcome->checkNumber), '',1,'R',0);

      // concept and proyect info
      $pdf->SetY( $pdf->GetY() + 0.1);
      $x=$pdf->GetX(); $y=$pdf->GetY();
      $pdf->Rect($x, $y, 9.6, 4.4, 'D');

      // sign square
      $pdf->Rect(10.8, $y, 9.5, 4.4, 'D');

      $x=$pdf->GetX(); $y=$pdf->GetY(); $w=9.6;
      $pdf->SetFont('Arial','',7.5);
      $pdf->MultiCell($w,0.5,utf8_decode( $outcome->concept ), '','L');
      $pdf->SetXY($x+$w + 0.2, $y);
      $pdf->SetFont('Arial','',12);
      $pdf->Cell(9.5,1, utf8_decode( 'FIRMA' ), '',0,'C',0);
      $pdf->SetY( $pdf->GetY() + 2.2 );
      $pdf->SetFont('Arial','',12);
      $pdf->Cell(2.7,1, utf8_decode( ' PROYECTO ' ), '',0,'L',0);
      $pdf->SetXY( 3.8 ,$pdf->GetY() + 0.2 );
      $pdf->SetFont('Arial','',10);
      $x=$pdf->GetX(); $y=$pdf->GetY(); $w=6.8;
      $pdf->MultiCell($w,0.5,utf8_decode( " - ".$outcome->projectNumber." - ".$outcome->projectName ), '','L');

      // amounts table
      $pdf->SetY( $pdf->GetY() + 0.3);
      $y = $pdf->GetY();
      $pdf->Rect(1, $y, 2.3, 9, 'D');
      $pdf->Rect(1, $y, 4.8, 9, 'D');
      $pdf->Rect(1, $y, 9.8, 9, 'D');
      $pdf->Rect(1, $y, 12.3, 9, 'D');
      $pdf->Rect(1, $y, 15.8, 9, 'D');
      $pdf->Rect(1, $y, 19.3, 9, 'D');

      $pdf->Cell(2.3,1, utf8_decode( 'CUENTA' ), 'TRBL',0,'C',0);
      $pdf->Cell(2.5,1, utf8_decode( 'SUB CUENTA' ), 'TRBL',0,'C',0);
      $pdf->Cell(5,1, utf8_decode( 'NOMBRE' ), 'TRBL',0,'C',0);
      $pdf->Cell(2.5,1, utf8_decode( 'PARCIAL' ), 'TRBL',0,'C',0);
      $pdf->Cell(3.5,1, utf8_decode( 'DEBE' ), 'TRBL',0,'C',0);
      $pdf->Cell(3.5,1, utf8_decode( 'HABER' ), 'TRBL',0,'C',0);

      $pdf->SetXY(5.8, $pdf->GetY() + 2);
      $pdf->Cell(5,1, utf8_decode( 'GASTO' ), '',0,'L',0);
      $pdf->SetX( $pdf->GetX() + 2.5 );
      $pdf->SetFont('Arial','',11);
      $pdf->Cell(3.3,1, '$'.number_format($outcome->total, 2, ".", ","), '',1,'R',0);
      $pdf->SetXY(5.8, $pdf->GetY() + 0.3);
      $pdf->SetFont('Arial','',10);
      $pdf->Cell(5,1, utf8_decode( 'BANCO CTA. 8436835-563' ), '',0,'L',0);
      $pdf->SetX( $pdf->GetX() + 6 );
      $pdf->SetFont('Arial','',11);
      $pdf->Cell(3.3,1, '$'.number_format($outcome->total, 2, ".", ","), '',1,'R',0);

      // totals block
      $pdf->SetXY( 5.8, $pdf->GetY() + 5 );
      $pdf->Cell(3.5,1, utf8_decode( 'IGUALES' ), '',0,'L',0);
      $pdf->Cell(4,1, utf8_decode( 'SUMAS' ), '',0,'L',0);
      $pdf->SetX( $pdf->GetX() +0.1 );
      $pdf->Cell(3.3,1, '$'.number_format($outcome->total, 2, ".", ","), 'TRBL',0,'R',0);
      $pdf->SetX( $pdf->GetX() +0.2 );
      $pdf->Cell(3.3,1, '$'.number_format($outcome->total, 2, ".", ","), 'TRBL',1,'R',0);

      // authorization table
      $pdf->SetY( $pdf->GetY() + 0.3);
      $pdf->Cell(2.3,1, utf8_decode( 'HECHO' ), 'TBL',0,'C',0);
      $pdf->Cell(2.5,1, utf8_decode( 'REVISADO' ), 'TB',0,'C',0);
      $pdf->Cell(5,1, utf8_decode( 'AUTORIZADO' ), 'TB',0,'C',0);
      $pdf->Cell(2.5,1, utf8_decode( 'AUXILIARES' ), 'TB',0,'C',0);
      $pdf->Cell(3.5,1, utf8_decode( 'DIARIO' ), 'TB',0,'C',0);
      $pdf->Cell(3.5,1, utf8_decode( 'POLIZA' ), 'TRB',1,'C',0);

      $y = $pdf->GetY();
      $pdf->Rect(1, $y, 2.3, 3.5, 'D');
      $pdf->Rect(1, $y, 4.8, 3.5, 'D');
      $pdf->Rect(1, $y, 9.8, 3.5, 'D');
      $pdf->Rect(1, $y, 12.3, 3.5, 'D');
      $pdf->Rect(1, $y, 15.8, 3.5, 'D');
      $pdf->Rect(1, $y, 19.3, 3.5, 'D');

      $pdf->SetY( $pdf->GetY() + 0.3 );
      $pdf->Cell(2.3,1, $this->getInitials( $auth->auth ), '',0,'C',0);
      $pdf->Cell(2.5,1, $this->getInitials( $auth->admin ), '',0,'C',0);
      $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5;
      $pdf->MultiCell($w,0.5,utf8_decode( $auth->coord ), '','C');
      $pdf->SetXY($x+$w, $y);
      // $pdf->Cell(5,1, utf8_decode( 'LIC. MARÍA DEL CARMEN CASTRO BARRERA' ), 'TRBL',0,'C',0);
      $pdf->Cell(2.5,1, utf8_decode( '' ), '',0,'C',0);
      $pdf->Cell(3.5,1, utf8_decode( '' ), '',0,'C',0);
      $pdf->Cell(3.5,1, utf8_decode( $outcome->checkNumber ), '',0,'C',0);
    }

    $pdf->Output();
    exit;
  }

  // print "Cheque"
  public function cheque($start, $end = NULL)
  {
    // get selected range income data
    $query = "SELECT outc.*, peop.name FROM outcomes AS outc
      JOIN people AS peop ON outc.sign = peop.id ";
    if($end == NULL){
      $query .= "WHERE outc.id = $start";
    } else {
      $query .= "WHERE outc.id BETWEEN $start AND $end ORDER BY outc.id";
    }
    $outcomes = DB::select($query);

    // printing time
    $pdf = new Fpdf('L','cm', array(21 , 8 ));
    foreach ($outcomes as $key => $outcome) {
      $pdf -> AddPage();

      //Fecha del Cheque
			$pdf -> Ln(0.28);
			$pdf -> SetRightMargin(1);			//margen derecho para la fecha
			$pdf -> SetFont('Arial', '', 10); //tipo de fuente
			$pdf -> Cell(0, 1, utf8_decode($this->stringDate( $outcome->elabDate )), 0, 1, 'R');

			//Nombre del destinatario
			$pdf -> Ln(0.6);
			$pdf -> SetLeftMargin(3);
			$pdf -> SetFont('Arial', '', 9);
			$pdf -> Cell(0, 1, utf8_decode( $outcome->name ), 0, 1, 'L');

			//Monto del cheque
			$pdf -> Ln(-1);
			$pdf -> SetRightMargin(1.5);
			$pdf -> SetFont('Arial', '', 10);
			$pdf -> Cell(0,1, number_format($outcome->total, 2, ".", ","), 0,1, 'R');
			//Monto del cheque en letra
			$pdf -> Ln(-0.5);
			$pdf -> SetrightMargin(3.5);
			$pdf -> SetFont('Arial', '', 8);
			$pdf -> Cell(0, 1, utf8_decode($this->moneyToString($outcome->total)), 0, 1, 'L');
    }

    $pdf->Output();
    exit;
  }

  // print "recibo gnc"
  public function recibo_gnc($outId)
  {
    // get outcome gnc data
    $outcome = DB::select("SELECT outc.gncLocation, peop.name, outc.valStart, outc.valEnd FROM outcomes AS outc
      JOIN people AS peop ON outc.gncSign = peop.id
      WHERE outc.id = $outId");
    $outcome = (array) $outcome[0];

    // get authority names
    $query ="SELECT * FROM authTable WHERE id = 1";
    $auth = DB::select($query);
    $auth = $auth[0];

    // get outcome gnc sum
    $total = DB::select("SELECT SUM(total) total FROM outcomp WHERE outcomeId = $outId AND gnc = 1 AND active = 1");
    $outcome['gnc_total'] = $total[0]->total;

    // new Date() FECHA DE HOY

    // Printig time
    $pdf = new Fpdf('P','cm', array(21.59 , 29.00 ));
    $pdf -> AddPage();

    // logos
    // logo cultura
    $pdf->Image(URL::to('/img/logo_cultura.png'), 1,0.8, 4.2,1.2, 'PNG');
    // logo inah
    $pdf->Image(URL::to('/img/inah-large.png'), 16.5,0.7, 4,1.4, 'PNG');

    $pdf->SetFont('Arial','',14);
    $pdf->SetY(3);
    $pdf->Cell(19.3,0.7, utf8_decode('INSTITUTO NACIONAL DE ANTROPOLOGÍA E HISTORIA'), '',1,'R',0);
    $pdf->Cell(19.3,0.7, utf8_decode('SECRETARIA ADMINISTRATIVA'), '',1,'R',0);

    $pdf->SetY( $pdf->GetY() + 0.5);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(19.3,0.7, utf8_decode($this->stringDate( Carbon::now() )), '',1,'R',0);

    $pdf->SetY( $pdf->GetY() + 0.5);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(19.3,0.7, utf8_decode( 'BUENO POR: $'.number_format($outcome['gnc_total'], 2, ".", ",") ), '',1,'R',0);

    $pdf->SetXY( 2, $pdf->GetY() + 2);
    $pdf->SetFont('Arial','',14);
    $pdf->MultiCell(17.7, 0.7,utf8_decode(
      "Recibí del Instituto Nacional de Antropología e Historia de la Coordinación Nacional de Conservación".
      " del Patrimonio Cultural, la cantidad de ".number_format($outcome['gnc_total'], 2, ".", ",")." (".$this->moneyToString($outcome['gnc_total']).
      ") por concepto de gastos no comprobables, conforme lo establece el Oficio - Circular N°401B (17)33.2014/11, ".
      "con base en las normas publicadas en el Diario Oficial de la Federación el 28 de diciembre del 2007, VIÁTICOS, ".
      "originados de la comisión realizada a ".$outcome['gncLocation'].", del ".date("d", strtotime( $outcome['valStart'] )).
      " de ".$this->fullMonth( intval(date("m", strtotime( $outcome['valStart'] ))) )." del ".date("Y", strtotime( $outcome['valStart'] )).
      " al ".date("d", strtotime( $outcome['valEnd'] ))." de ".$this->fullMonth( intval(date("m", strtotime( $outcome['valEnd'] ))) )." del ".date("Y", strtotime( $outcome['valEnd'] ))."."
    ), '','L');

    $pdf->SetXY( 2, $pdf->GetY() + 2);
    $y=$pdf->GetY(); $w=17.7;
    $pdf->Rect(2, $y, $w, 5, 'D');
    $pdf->Rect(2, $y, 5.9, 5, 'D');
    $pdf->Rect(2, $y, 11.8, 5, 'D');

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(5.9,1, utf8_decode('ATENTAMENTE'), 'TRBL',0,'C',0);
    $pdf->Cell(5.9,1, utf8_decode('VISTO BUENO'), 'TRBL',0,'C',0);
    $pdf->Cell(5.9,1, utf8_decode('AUTORIZADO'), 'TRBL',1,'C',0);

    $pdf->SetXY( 2, $pdf->GetY() + 2.5);
    $pdf->SetFont('Arial','',10);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.9;
    $pdf->MultiCell($w,0.5,utf8_decode( $outcome['name'] ), '','C');
    $pdf->SetXY($x+$w, $y);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.9;
    $pdf->MultiCell(5.9,0.5,utf8_decode( $auth->admin ), '','C');
    $pdf->SetXY($x+$w, $y);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.9;
    $pdf->MultiCell(5.9,0.5,utf8_decode( $auth->coord ), '','C');
    $pdf->SetXY($x+$w, $y);

    $pdf->SetXY( 2, $pdf->GetY() + 1.7);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(5.9,1, utf8_decode('COMISIONADO'), '',0,'C',0);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.9;
    $pdf->MultiCell($w,0.5,utf8_decode('SUBDIRECTORA ADMINISTRATIVA'), '','C');
    $pdf->SetXY($x+$w, $y);
    $x=$pdf->GetX(); $y=$pdf->GetY(); $w=5.9;
    $pdf->MultiCell($w,0.5,utf8_decode('COORDINADORA NACIONAL'), '','C');
    $pdf->SetXY($x+$w, $y);

    $x=$pdf->GetX(); $y=$pdf->GetY();
    $pdf->Rect(2, $y-0.2, 5.9, 1.5, 'D');
    $pdf->Rect(2, $y-0.2, 11.8, 1.5, 'D');
    $pdf->Rect(2, $y-0.2, 17.7, 1.5, 'D');


    $pdf->Output();
    exit;

    // print_r( $outcome);
  }

  // COMMON FUNCTIONS ----------------------------------
  public function opType( $type )
  {
        switch ( $type ) {
          case 'gba':
                  return 'Gasto Básico';
              break;
          case 'gop':
                  return 'Gastos de operación';
              break;
          case 'inv':
                  return 'Inversión';
              break;
          case 'pro':
                  return 'Recursos fiscales';
              break;
          case 'gad':
                  return 'Gastos de administración';
              break;
          case 'ter':
                  return 'Con aportación de terceros';
              break;

          default:
                  return '';
              break;
          }
  }

  // GIVE FULL MONTH NAME
  public function fullMonth( $num )
  {
    $monthList= ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    return $monthList[ $num - 1 ];
  }

  // GET DATE IN STRING FORMAT
  public function stringDate( $date )
  {
      // return $date;
      $weekDays = ["Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado"];
      $months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

      $dateText = "México, Ciudad de México a ".( $weekDays[ date("w", strtotime($date)) ] ).", "
        .( date("d", strtotime( $date )) )." de ".( $months[ intval(date("m", strtotime($date))) - 1 ] ).
        " del ".( date("Y", strtotime($date)) );

      return $dateText;
  }

  //CONVERTIR NUMEROS A LETRAS PARA EL MONTO
	public function numToString( $monto )
  {
      $maximo = pow(10,9);
          $unidad            = array(1=>"UNO", 2=>"DOS", 3=>"TRES", 4=>"CUATRO", 5=>"CINCO", 6=>"SEIS", 7=>"SIETE", 8=>"OCHO", 9=>"NUEVE");
          $decena            = array(10=>"DIEZ", 11=>"ONCE", 12=>"DOCE", 13=>"TRECE", 14=>"CATORCE", 15=>"QUINCE", 20=>"VEINTE", 30=>"TREINTA", 40=>"CUARENTA", 50=>"CINCUENTA", 60=>"SESENTA", 70=>"SETENTA", 80=>"OCHENTA", 90=>"NOVENTA");
          $prefijo_decena    = array(10=>"DIECI", 20=>"VEINTI", 30=>"TREINTA Y ", 40=>"CUARENTA Y ", 50=>"CINCUENTA Y ", 60=>"SESENTA Y ", 70=>"SETENTA Y ", 80=>"OCHENTA Y ", 90=>"NOVENTA Y ");
          $centena           = array(100=>"CIEN", 200=>"DOSCIENTOS", 300=>"TRESCIENTOS", 400=>"CUATROCIENTOS", 500=>"QUINIENTOS", 600=>"SEISCIENTOS", 700=>"SETECIENTOS", 800=>"OCHOCIENTOS", 900=>"NOVECIENTOS");
          $prefijo_centena   = array(100=>"CIENTO ", 200=>"DOSCIENTOS ", 300=>"TRESCIENTOS ", 400=>"CUATROCIENTOS ", 500=>"QUINIENTOS ", 600=>"SEISCIENTOS ", 700=>"SETECIENTOS ", 800=>"OCHOCIENTOS ", 900=>"NOVECIENTOS ");
          $sufijo_miles      = "MIL";
          $sufijo_millon     = "UN MILLON";
          $sufijo_millones   = "MILLONES";

          //echo var_dump($monto); die;

          $base         = strlen(strval($monto));
          $pren         = intval(floor($monto/pow(10,$base-1)));
          $prencentena  = intval(floor($monto/pow(10,3)));
          $prenmillar   = intval(floor($monto/pow(10,6)));
          $resto        = $monto%pow(10,$base-1);
          $restocentena = $monto%pow(10,3);
          $restomillar  = $monto%pow(10,6);

          if (!$monto) return "";

      if (is_int($monto) && $monto>0 && $monto < abs($maximo))
      {
                  switch ($base) {
                          case 1: return $unidad[$monto];
                          case 2: return array_key_exists($monto, $decena)  ? $decena[$monto]  : $prefijo_decena[$pren*10]   . $this->numToString($resto);
                          case 3: return array_key_exists($monto, $centena) ? $centena[$monto] : $prefijo_centena[$pren*100] . $this->numToString($resto);
                          case 4: case 5: case 6: return ($prencentena>1) ? $this->numToString($prencentena). " ". $sufijo_miles . " " . $this->numToString($restocentena) : $sufijo_miles. " " . $this->numToString($restocentena);
                          case 7: case 8: case 9: return ($prenmillar>1)  ? $this->numToString($prenmillar). " ". $sufijo_millones . " " . $this->numToString($restomillar)  : $sufijo_millon. " " . $this->numToString($restomillar);
                  }
      } else {
          echo "ERROR con el numero - $monto<br/> Debe ser un numero entero menor que " . number_format($maximo, 0, ".", ",") . ".";
      }
          //return $texto;
  }

	//FUNCION PARA AGREGAR EL TEXTO DE MONEDA
  public function moneyToString($monto)
  {

          $monto = str_replace(',','',$monto); //ELIMINA LA COMA

          $pos = strpos($monto, '.');

          if ($pos == false)      {
                  $monto_entero = $monto;
                  $monto_decimal = '00';
          }else{
                  $monto_entero = substr($monto,0,$pos);
                  $monto_decimal = substr($monto,$pos,strlen($monto)-$pos);
                  $monto_decimal = $monto_decimal * 100;
          }

          $monto = (int)($monto_entero);

		if($monto_decimal < 10)
			{$texto_con = " PESOS 0$monto_decimal/100 M.N.";}
		else
			{$texto_con = " PESOS $monto_decimal/100 M.N.";}

		return $this->numToString($monto).$texto_con;
        // echo numToString($monto).$texto_con;
  }

  // EXPLODE NAMES AND GET FIRST LETTER OF EACH WORD
  public function getInitials( $name ){
    $nameExp = explode(" ", $name);
    $initials = "";
    foreach ($nameExp as $key => $letter) {
      $initials .= $letter[0];
    }

    return $initials;
  }
}
