<?php

namespace Libs\Pdf;

class Invoice extends FPDF {
    protected $col = 0; // Current column
    protected $y0;      // Ordinate of column start

    private $amount;
    private $created_at;
    private $invoice_no;
    private $currency;
    private $payer;

    function Header() {
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->Cell(0,10,'Company',0,0,'L');
        $this->SetX($this->lMargin);
        $this->Cell( 0, 25, 'Invoice No.', 0, 0, 'R' );
        $this->SetX($this->rMargin);
        $this->Cell(0,48,'Address',0,0,'L');
        $this->Cell(0,65,'Date',0,0,'R');


        $this->SetFont('Arial','B',16);
        $this->SetTextColor(0,0,0);
        $this->Cell(0,38, '#'.$this->invoice_no,0,0,'R');
        $this->SetX($this->rMargin);
        $this->Cell(0,23,'WIN ALWAYS 1900 LTD',0,0,'L');


        $this->SetFont('Arial','',13);
        $this->SetX($this->rMargin);
        $this->Cell(0,64,'91 Battersea Park Road,',0,0,'L');
        $this->SetX($this->rMargin);
        $this->Cell(0,77,'London, England, SW8 4DU',0,0,'L');
        $this->SetX($this->lMargin);
        $this->Cell(0,77,$this->created_at,0,0,'R');

        $this->ChapterTitle(1);
        //$this->Cell(0,10,'Center text:',0,0,'C');
        //$this->SetX($this->lMargin);

        $this->Ln(10);
        $this->y0 = $this->GetY();
    }


    function SetCol($col) {
        $this->col = $col;
        $x = 10 + $col * 65;
        $this->SetLeftMargin($x);
        $this->SetX($x);
    }

    function AcceptPageBreak() {
        if($this->col < 2) {
            $this->SetCol($this->col+1);
            $this->SetY($this->y0);
            return false;
        } else {
            $this->SetCol(0);
            return true;
        }
    }

    function ChapterTitle() {
        $this->SetFont('Arial','',12);
        $this->SetFillColor(255,181,96);
        $this->Cell(0,0.5,"",0,1,'L',true);
        $this->Ln(37);
        //$this->y0 = $this->GetY();
    }

    function ChapterBody($conf) {
        $this->Ln(-25);

        $this->SetFont('Arial','B',13);
        $this->Cell(0,5,$this->currency . ' Service provider Payment details',0,0,'C');

        $this->Ln(10);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(80,0);
        $this->Cell(0,10,'Beneficiary Name: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 55);
        $this->Cell(0,10,'WIN ALWAYS 1900 LTD',0,0,'C');

        $this->Ln(9);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(128,0);
        $this->Cell(0,10,'Beneficiary Account: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 122);
        $this->Cell(0,10,$conf['number'],0,0,'C');

        $this->Ln(8);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(195,0);
        $this->Cell(0,10,'Registration Number: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 199);
        $this->Cell(0,10,'12053345',0,0,'C');


        $this->Ln(12);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(265,0);
        $this->Cell(0,10,'Beneficiary Bank: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);
        $this->SetX($this->rMargin);
        $this->Cell(0,10,'STPVHKHH - STERLING',0,0,'L');
        $this->SetX($this->rMargin);
        $this->Cell(0,23,'PAYMENT SERVICES LIMITED',0,0,'L');


        $this->Ln(14);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(143,0);
        $this->Cell(0,10,'Address: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);
        $this->SetX($this->rMargin);
        $this->Cell(0,10,'Hong Kong: 1801-03, 18/F,',0,0,'L');
        $this->SetX($this->rMargin);
        $this->Cell(0,23,'East Town Building,',0,0,'L');
        $this->SetX($this->rMargin);
        $this->Cell(0,36,'41 Lockhart Road, Wan Chai',0,0,'L');


        $this->Ln(21);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(143,0);
        $this->Cell(0,10,'Email: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);
        $this->SetX($this->rMargin);
        $this->Cell(0,10,'info@sterlingsafepayment.com',0,0,'L');

        $this->Ln(9);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(143,0);
        $this->Cell(0,10,'Phone: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);
        $this->SetX($this->rMargin);
        $this->Cell(0,10,'+852 5801 4396',0,0,'L');

        $this->Ln(12);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(165,0);
        $this->Cell(0,10,'Correspondent Bank: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);
        $this->SetX($this->rMargin);
        $this->Cell(0,10,'MUGAAZ22 - MUGANBANK OJSC',0,0,'L');

        $this->Ln(14);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(160,0);
        $this->Cell(0,10,'Intermediary bank: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);

        $this->SetX($this->rMargin);
        $this->Cell(0,10, $conf['intermediary'][0],0,0,'L');

        if (isset($conf['intermediary'][1])) {
            $this->SetX($this->rMargin);
            $this->Cell(0,23,$conf['intermediary'][1],0,0,'L');
        }

//        $this->SetX($this->rMargin);
//        $this->Cell(0,10,,0,0,'L');
//        $this->SetX($this->rMargin);
//        $this->Cell(0,23,'INTERNATIONAL AG',0,0,'L');


        $this->Ln(18);
        $this->SetX($this->rMargin);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->SetMargins(158,0);
        $this->Cell(0,10,'BIC/SWIFT code: ',0,0,'C');
        $this->SetFont('Arial','',13);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->SetMargins(0,0, 92);
        $this->SetX($this->rMargin);
        $this->Cell(0,10,'STPVHKHH',0,0,'L');

        $this->SetX($this->rMargin);
        $this->SetMargins(10,0, 10);
        $this->Ln(19);
        $this->SetFillColor(255,181,96);
        $this->Cell(0,0.5,"",0,1,'L',true);
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(128);
    }

    function Footer() {
        $this->Ln(-60);
        $this->SetTextColor(128,128,128);
        $this->SetFont('Arial','',11);
        $this->Cell(0,10,'Payer',0,0,'L');
        $this->SetX($this->lMargin);
        $this->Cell(0,38,'Purpose of Payment',0,0,'L');
        $this->Cell( 0, 10, 'Due ' . $this->currency, 0, 0, 'R' );

        $this->SetFont('Arial','',16);
        $this->SetTextColor(0,0,0);
        $this->SetX($this->rMargin);
        $this->Cell(0,24, $this->payer,0,0,'L');
        $this->SetX($this->rMargin);
        $this->Cell(0,52,'Electronic Wallet Replenishment',0,0,'L');
        $this->SetTextColor(0,0,0);
        $this->Cell(0,24, $this->amount,0,0,'R');
    }

    function PrintChapter($amount, $created_at, $invoice_no, $currency, $payer, $conf) {
        $this->amount = $amount;
        $this->created_at = $created_at;
        $this->invoice_no = $invoice_no;
        $this->currency = $currency;
        $this->payer = $payer;
        $this->AddPage();
        $this->ChapterTitle();
        $this->ChapterBody($conf);
    }
}