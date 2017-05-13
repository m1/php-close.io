<?php

namespace m1\Tepilo;

class Lead
{
    public $title;
    public $name;
    public $url;
    public $description;
    public $email;
    public $emailType;

    /**
     * @return array
     */
    public function toArray(): array
    {
        $cfValuations = sprintf('custom.%s', CloseService::CF_NUMBER_OF_VALUATIONS);

        return [
            'name'        => $this->name,
            'url'         => $this->url,
            'description' => $this->description,
            'contacts'    => [
                [
                    'name'   => $this->name,
                    'title'  => $this->title,
                    'emails' => [
                        [
                            'type'  => $this->emailType,
                            'email' => $this->email,
                        ],
                    ],
                ],
            ],
            $cfValuations => 0,
        ];
    }
}