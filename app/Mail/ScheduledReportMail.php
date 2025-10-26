<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reportData;
    public $period;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reportData, $period)
    {
        $this->reportData = $reportData;
        $this->period = $period;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Scheduled Inventory Report - " . ucfirst($this->period))
                    ->view('emails.scheduled-report')
                    ->with([
                        'reportData' => $this->reportData,
                        'period' => $this->period,
                        'generatedAt' => now()->format('Y-m-d H:i:s')
                    ]);
    }
}