<?php

namespace App\Services;

/**
 * @property mixed $mms
 * @property mixed $sms
 * @property mixed $voice
 * @property mixed $fax
 */
class PhoneNumberCapabilities extends \Twilio\Base\PhoneNumberCapabilities
{
    public $mms;
    public $sms;

    public $voice;
    public $fax;
    public function __construct($capabilities)
    {
        $this->sms = $capabilities->sms;
        $this->mms = $capabilities->mms;
        $this->voice = $capabilities->voice;
        $this->fax = $capabilities->fax;
    }

    public function getSms(): bool
    {
        return $this->sms;
    }

    public function getMms(): bool
    {
        return $this->mms;
    }

    public function getVoice(): bool
    {
        return $this->voice;
    }

    public function getFax(): bool
    {
        return $this->voice;
    }
}
