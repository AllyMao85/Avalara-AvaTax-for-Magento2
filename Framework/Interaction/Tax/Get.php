<?php

namespace ClassyLlama\AvaTax\Framework\Interaction\Tax;

use AvaTax\GetTaxRequest;
use AvaTax\GetTaxResult;
use AvaTax\LineFactory;
use AvaTax\Message;
use AvaTax\SeverityLevel;
use AvaTax\TaxLine;
use ClassyLlama\AvaTax\Framework\Interaction\Address;
use ClassyLlama\AvaTax\Framework\Interaction\Tax;
use ClassyLlama\AvaTax\Model\Config;
use Magento\Framework\DataObject;

class Get
{
    /**
     * @var Address
     */
    protected $interactionAddress = null;

    /**
     * @var Tax
     */
    protected $interactionTax = null;

    /**
     * @var LineFactory
     */
    protected $lineFactory = null;

    /**
     * @var Config
     */
    protected $config = null;

    /**
     * @var null
     */
    protected $errorMessage = null;

    public function __construct(
        Address $interactionAddress,
        Tax $interactionTax,
        LineFactory $lineFactory,
        Config $config
    ) {
        $this->interactionAddress = $interactionAddress;
        $this->interactionTax = $interactionTax;
        $this->lineFactory = $lineFactory;
        $this->config = $config;
    }

    /**
     * Convert quote/order/invoice/creditmemo to the AvaTax object and request tax from the Get Tax API
     *
     * @author Jonathan Hodges <jonathan@classyllama.com>
     * @return bool|GetTaxResult
     */
    public function getTax($data)
    {
        $taxService = $this->interactionTax->getTaxService();

        /** @var $getTaxRequest GetTaxRequest */
        $getTaxRequest = $this->interactionTax->getGetTaxRequest($data);

        if (is_null($getTaxRequest)) {
            // TODO: Possibly refactor all usages of setErrorMessage to throw exception instead so that this class can be stateless
            $this->setErrorMessage('$data was empty or address was not valid so not running getTax request.');
            return false;
        }

        try {
            $getTaxResult = $taxService->getTax($getTaxRequest);

            if ($getTaxResult->getResultCode() == \AvaTax\SeverityLevel::$Success) {
                return $getTaxResult;
            } else {
                // TODO: Generate better error message
                $this->setErrorMessage('Bad result code: ' . $getTaxResult->getResultCode());
                return false;
            }
        } catch (\SoapFault $exception) {
            $message = "Exception: \n";
            if ($exception) {
                $message .= $exception->faultstring;
            }
            $message .= $taxService->__getLastRequest() . "\n";
            $message .= $taxService->__getLastResponse() . "\n";
            $this->setErrorMessage($message);
        }
        return false;
    }

    /**
     * Set error message
     *
     * @return void
     */
    public function setErrorMessage($message)
    {
        $this->errorMessage = $message;
    }

    /**
     * Return error message generated by calling the getTax method
     *
     * @return null|string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
