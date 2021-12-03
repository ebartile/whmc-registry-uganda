<?php

namespace WHMCS\Module\Registrar\Lugacloud;

/**
 * API Client.
 *
 * A simple API Client for communicating with an external API endpoint.
 */
class ApiClient
{
    const API_URL = 'https://registry.co.ug/api/';

    protected $results = array();

    /**
     * Make external API call to registrar API.
     *
     * @param string $action
     * @param array $postfields
     *
     * @throws \Exception Connection error
     * @throws \Exception Bad API response
     *
     * @return array
     */
    public function call($action, $postfields, $testMode)
    {
        if($testMode == "on" && $action != "Avialability") {
            switch ($action) {
                case "Register":
                    $response = <<<DATA
                    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <response cmd="register" status="1">
                        <success code="2" message="Successfully registered i3c.co.ug" />
                        <domain name="i3c.co.ug" status="UNPAID" expiry_date="" />
                    </response>
                    DATA;    
                    break;
                case "Register Specific":
                    $response = <<<DATA
                    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <response cmd="register" status="1">
                        <success code="2" message="Successfully registered i3c.co.ug" />
                        <domain name="i3c.co.ug" status="UNPAID" expiry_date="" />
                    </response>
                    DATA;    
                    break;
                case "Request Transfer":
                    $response = <<<DATA
                    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <response cmd="request_transfer" status="1">
                        <success code="2" message="Request to transfer i3c.co.ug has been processed" />
                    </response>
                    DATA;    
                    break;
                case "Confirm Transfer":
                    $response = <<<DATA
                    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <response cmd="confirm_transfer" status="1">
                        <success code="2" message="Successfully transfered i3c.co.ug" />
                    </response>
                    DATA;    
                    break;
                case "Renew":
                    $response = <<<DATA
                    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <response cmd="renew" status="1">
                        <success code="2" message="Successfully renewed i3c.co.ug" />
                        <domain name="i3c.co.ug" status="UNPAID" expiry_date="2016-10-16" />
                    </response>
                    DATA;    
                    break;
                case "GetNameservers":
                    $response = <<<DATA
                    <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <response cmd="whois" status="1">
                        <success code="2" message="Whois information for i3c.co.ug">
                        <domain 
                                name="i3c.co.ug" 
                                status="ACTIVE" 
                                registration_date="2012-02-22" 
                                expiry_date="2015-02-22" />
                        <contacts>
                            <registrant 
                                firstname="Charles" 
                                lastname="Musisi" 
                                email="registrar@i3c.co.ug" 
                                organization="Infinity Computers and Communications Company Limited" 
                                country="Uganda" 
                                city="kampala" 
                                street_address="Plot 6b Windsor Loop" 
                                phone="+256 31 230 1800" 
                                fax="" />
                            <admin 
                                firstname="Charles" 
                                lastname="Musisi" 
                                email="cmusisi@cfi.co.ug" 
                                organization="Infinity Computers and Communications Company" 
                                country="Uganda" 
                                city="Kampala" 
                                street_address="Plot 6b Windsor Loop" 
                                phone="0312301800" 
                                fax="" />
                            <tech 
                                firstname="Charles" 
                                lastname="Musisi" 
                                email="cmusisi@cfi.co.ug" 
                                organization="Infinity Computers and Communications Company" 
                                country="Uganda" 
                                city="Kampala" 
                                street_address="Plot 6B Windsor Loop" 
                                phone="+256 31 230 1800" 
                                fax="+256 41 434 0456"/>
                            <billing 
                                firstname="Charles" 
                                lastname="Musisi" 
                                email="cmusisi@cfi.co.ug" 
                                organization="Infinity Computers and Communications Company" 
                                country="Uganda" 
                                city="Kampala" 
                                street_address="Plot 6B Windsor Loop" 
                                phone="+256 31 230 1800" 
                                fax="+256 41 434 0456"/>
                        </contacts>
                        <nameservers>
                            <ns1>ns1.cfi.co.ug</ns1>
                            <ns2>ns2.cfi.co.ug</ns2>
                            <ns3 />
                            <ns4 />
                        </nameservers>
                    </response>                
                    DATA;    
                    break;
                default:
                    throw new \Exception('Bad response received from API');
            }            

            $returnCode = 200;

        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::API_URL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            //for debug only!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
            curl_setopt($ch, CURLOPT_TIMEOUT, 100);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Cache-Control: no-cache",
                "Content-Type: application/xml"
              )
            );
    
            $response = curl_exec($ch);
    
            if (curl_errno($ch)) {
                throw new \Exception('Connection Error: ' . curl_errno($ch) . ' - ' . curl_error($ch));
            } else {
                $returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                switch($returnCode){
                    case 200:
                        break;
                    default:
                        $result = 'HTTP ERROR -> ' . $returnCode;
                        break;
                }
            }
            curl_close($ch);    
        }

        $this->results = $this->processResponse($response);
    
        logModuleCall(
            'Registrar Api',
            $action,
            $postfields,
            $this->results
        );

        if ($returnCode !== 200  or $this->results === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Bad response received from API');
        }

        return $this->results;    
    }

    /**
     * Process API response.
     *
     * @param string $response
     *
     * @return array
     */
    public function processResponse($response)
    {
        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    /**
     * Get from response results.
     *
     * @param string $key
     *
     * @return string
     */
    public function getFromResponse($key)
    {
        return isset($this->results[$key]) ? $this->results[$key] : '';
    }
}
