<?php
require_once 'fpdf/fpdf.php';

class CertificateGenerator extends FPDF
{
    public function Header()
    {
        // Background color
        $this->SetFillColor(11, 15, 26);
        $this->Rect(0, 0, $this->GetPageWidth(), $this->GetPageHeight(), 'F');

        // Border
        $this->SetDrawColor(0, 98, 155);
        $this->SetLineWidth(2);
        $this->Rect(10, 10, $this->GetPageWidth() - 20, $this->GetPageHeight() - 20);

        // Secondary glow border
        $this->SetDrawColor(0, 255, 170);
        $this->SetLineWidth(0.5);
        $this->Rect(12, 12, $this->GetPageWidth() - 24, $this->GetPageHeight() - 24);
    }

    public function generate($userName, $courseName, $date = null)
    {
        $this->AddPage('L', 'A4');
        $date = $date ?? date('F d, Y');

        // Logo
        if (file_exists('logo.png')) {
            $this->Image('logo.png', ($this->GetPageWidth() / 2) - 25, 30, 50);
        }

        $this->SetTextColor(255, 255, 255);

        // Title
        $this->SetFont('Arial', 'B', 40);
        $this->Ln(60);
        $this->Cell(0, 20, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');

        // Content
        $this->SetFont('Arial', '', 18);
        $this->Ln(10);
        $this->Cell(0, 10, 'This is to certify that', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 30);
        $this->SetTextColor(0, 255, 170); // Secondary Neon
        $this->Ln(5);
        $this->Cell(0, 20, strtoupper($userName), 0, 1, 'C');

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', '', 18);
        $this->Ln(5);
        $this->Cell(0, 10, 'has successfully completed the course', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 25);
        $this->SetTextColor(0, 98, 155); // Primary Blue
        $this->Ln(5);
        $this->Cell(0, 15, '"' . strtoupper($courseName) . '"', 0, 1, 'C');

        // Footer
        $this->SetTextColor(153, 153, 153);
        $this->SetFont('Arial', 'I', 12);
        $this->Ln(20);
        $this->Cell(0, 10, 'Awarded on ' . $date . ' by IEEE MIU Student Branch', 0, 1, 'C');

        // Signature line
        $this->SetDrawColor(153, 153, 153);
        $this->Line($this->GetPageWidth() / 2 - 40, 180, $this->GetPageWidth() / 2 + 40, 180);

        return $this->Output('S'); // Return as string
    }
}
?>