<?php

namespace steevanb\DoctrineMappingValidator\Report;

class PassedReport
{
    use AddCodeTrait;

    /** @var string */
    protected $message;

    /** @var string[] */
    protected $infos = [];

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $info
     * @return $this
     */
    public function addInfo($info)
    {
        $this->infos[] = $info;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getInfos()
    {
        return $this->infos;
    }
}
