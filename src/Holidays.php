<?php

namespace Google;

use Illuminate\Support\Facades\Http;

class Holidays
{
    /**
     * Google API Key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Country Code.
     *
     * @var string
     */
    private $country_code;

    /**
     * Start Date.
     *
     * @var string
     */
    private $start_date;

    /**
     * End Date.
     *
     * @var string
     */
    private $end_date;

    /**
     * Minimal output boolean.
     *
     * @var bool
     */
    private $minimal = false;

    /**
     * Dates only output boolean.
     *
     * @var bool
     */
    private $dates_only = false;

    /**
     * Construct!
     *
     * @return void
     */
    public function __construct()
    {
        $this->start_date = date('Y-m-d') . 'T00:00:00-00:00';
        $this->end_date = (date('Y') + 1) . '-01-01T00:00:00-00:00';
    }

    /**
     * Setting starting date.
     *
     * @param string Date in any format
     *
     * @return self
     */
    public function from($str)
    {
        $this->start_date = date('Y-m-d', strtotime($str)) . 'T00:00:00-00:00';

        return $this;
    }

    /**
     * Setting end date.
     *
     * @param string Date in any format
     *
     * @return self
     */
    public function to($str)
    {
        $this->end_date = date('Y-m-d', strtotime($str)) . 'T00:00:00-00:00';

        return $this;
    }

    /**
     * Setter of API key.
     *
     * @return void
     */
    public function withApiKey($api_key)
    {
        $this->api_key = $api_key;

        return $this;
    }

    /**
     * Define the country code to retrieve holidays for.
     *
     * @return void
     */
    public function inCountry($country_code)
    {
        $this->country_code = strtolower($country_code);

        return $this;
    }

    /**
     * Define the output result as minimal.
     *
     * @return Holidays
     */
    public function withMinimalOutput()
    {
        $this->minimal = true;

        return $this;
    }

    /**
     * Define the output as dates only.
     *
     * @return Holidays
     */
    public function withDatesOnly()
    {
        $this->dates_only = true;

        return $this;
    }

    /**
     * Get the list of holidays.
     *
     * @return mixed
     */
    public function list()
    {
        if (!$this->api_key) {
            throw new \Exception('Providing an API key might be a better start. RTFM.');
        }

        if (!$this->country_code) {
            throw new \Exception('Providing a Country Code is a good idea. RTFM.');
        }

        $result = [];

        $api_url = "https://content.googleapis.com/calendar/v3/calendars/en.{$this->country_code}%23holiday%40group.v.calendar.google.com/events";
        $query_params = [
            'singleEvents' => 'false',
            'timeMax' => $this->end_date,
            'timeMin' => $this->start_date,
            'key' => $this->api_key,
        ];

        $response = Http::get($api_url, $query_params)->json();

        if (isset($response['items'])) {
            if ($this->dates_only === true) {
                $result = array_map(function ($holiday) {
                    return $holiday['start']['date'];
                }, $response['items']);
                sort($result);
            } elseif ($this->minimal === true) {
                $result = array_map(function ($holiday) {
                    return [
                        'name' => $holiday['summary'],
                        'date' => $holiday['start']['date']
                    ];
                }, $response['items']);

                usort($result, function ($a, $b) {
                    return $a['date'] <=> $b['date'];
                });
            } else {
                $result = $response['items'];
                usort($result, function ($a, $b) {
                    return $a['start']['date'] <=> $b['start']['date'];
                });
            }
        }
        return $result;
    }
}
