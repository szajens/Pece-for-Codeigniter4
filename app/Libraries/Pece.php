<?php declare(strict_types = 1);

namespace App\Libraries;
use CodeIgniter\Exceptions\ConfigException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Router\Router;



/**
 * PECE Routing
 *
 * @link https://github.com/szajens/Pece-for-Codeigniter4 Pece for CI4
 * @author Szajens
 * @version   1.0
 * @date      07.05.2022
 */
class Pece{


    private Router $router;
    private IncomingRequest $request;
    private array $filteredDomain = [];
    private array $outHost = [];

    private string $defaultMessage = 'Please correct the Pece library configuration in file: "'.APPPATH.'Config'.DIRECTORY_SEPARATOR.'App.php"';


    public function __construct(Router $router, IncomingRequest $request){
        $this->router = $router;
        $this->request = $request;

        $this->runFilter();
    }



    /**
     * use this function if you want change host from subdomain to domain
     */
    public function setDomainInCI(){
        $this->setInURI($this->getScheme(), $this->getDomain());
        $this->setInConfigApp($this->getScheme().$this->getDomain().'/');
    }

    /**
     * use this function if you want change host from domain to subdomain
     */
    public function setSubdomainInCI(){
        if(!empty($this->getSubdomain())){
            $this->setInURI($this->getScheme(), $this->getSubdomain().'.'.$this->getDomain());
            $this->setInConfigApp($this->getScheme().$this->getSubdomain().'.'.$this->getDomain().'/');
        } else {
            $this->setDomainInCI();
        }
    }

    /**
     * @param string $subdomain use this function if you want change subdomain in host
     */
    public function changeSubdomainInCI(string $subdomain){
        $this->setInURI($this->getScheme(), $subdomain.'.'.$this->getDomain());
        $this->setInConfigApp($this->getScheme().$subdomain.'.'.$this->getDomain().'/');
    }

    /**
     * @param string $subdomain use this function if you want add to subdomain in host
     */
    public function addSubdomainInCI(string $subdomain){
        if(!empty($this->getSubdomain())){
            $this->setInURI($this->getScheme(), $subdomain.'.'.$this->getSubdomain().'.'.$this->getDomain());
            $this->setInConfigApp($this->getScheme().$subdomain.'.'.$this->getSubdomain().'.'.$this->getDomain().'/');
        } else {
            $this->changeSubdomainInCI($subdomain);
        }
    }

    private function runFilter(){

        $filtered = $this->domainFilter($this->getAllowedDomains(), $this->request->getServer('HTTP_HOST'));

        if($filtered['matches'] == 0){//if not allowed, show 404
            $this->error404();
        }

        $this->filteredDomain['domain'] = $filtered[0][2]; // domain 'example.com'
        $this->filteredDomain['subdomain'] = $filtered[0][1]; //string '' or 'subdomain'

        // ---------------------------------------------------------------

        if($this->checkDomain() and $this->checkSSL() and $this->checkSubdomain()){

            if($this->getOptions_pSubhost()){//if set host from domain and subdomain
                $this->setSubdomainInCI();
            } else {//if set only from domain
                $this->setDomainInCI();
            }

        } else {
            $this->error404();
        }

    }

    /**
     * @param string $scheme e.g 'http://'
     * @param string $host e.g 'sub.domain.com' or 'domain.com'
     */
    private function setInURI(string $scheme,string $host){

        if($scheme === 'http://'){
            $this->request->uri->setScheme('http');
        } else {
            $this->request->uri->setScheme('https');
        }

        $this->request->uri->setHost($host);

    }

    /**
     * @param string $host example 'http://localhost/'
     */
    private function setInConfigApp(string $host){

        $this->request->config->baseURL = $host;

    }

    /**
     * @return bool if the server scheme is the same as expected returned true
     */
    private function checkSSL():bool{

        if ($this->getOptions_pSSL() === null){//if route scheme not set

            if ($this->getDefaultSSL() === null){//if default ssl not set
                $this->setSSL($this->getCurrentSSL());//set scheme from user request


            } elseif ($this->getDefaultSSL() === true){
                $this->setSSL();

            } else {
                $this->setSSL(false);
            }

        } elseif($this->getOptions_pSSL() === true){
            $this->setSSL();
        } else {
            $this->setSSL(false);
        }
        // ---------------------------------------------------------------
        if($this-> getScheme() === $this->getCurrentSSL(true)) return true;
        else return false;

    }

    /**
     * @return bool if not set domain, allow all allowed or predefine domain and filtered domain is same returned true, otherwise false
     */
    private function checkDomain():bool{
        //if not set domain, allow all allowed or predefine domain and filtered domain is same
        if($this->getOptions_pDomain() === null || $this->getOptions_pDomain() === $this->filteredDomain['domain']){
            $this->setDomain($this->filteredDomain['domain']);
            return true;
        } else {
            return false;
        }

    }

    /**
     * @return bool
     */
    private function checkSubdomain():bool{
        if ($this->getOptions_pSubdomain() === null){//if not defined
            $this->setSubdomain($this->filteredDomain['subdomain']);
            return true;
        } else {

            preg_match('/^'.$this->getOptions_pSubdomain().'$/', $this->filteredDomain['subdomain'], $result);
            $result = $result[0] ?? null;//if no match set null

            if($result === null){
                return false;
            } else {
                $this->setSubdomain($result);
                return true;
            }



        }


    }

    /*
     * ---------------------------------------------------------------
     * section public - outhost - getter
     * ---------------------------------------------------------------
     */


    /**
     * @return mixed|string return request domain
     */
    public function getDomain(){
        return $this->outHost['domain'] ?? '';
    }

    /**
     * @return mixed|string return request subdomain
     */
    public function getSubdomain(){
        return $this->outHost['subdomain'] ?? '';
    }

    /**
     * @return mixed|string return request scheme e.g. 'https://' or 'http://'
     */
    public function getScheme(){
        return $this->outHost['scheme'] ?? '';
    }

    // setter --------------------------------------------------------

    private function setDomain(string $name){
        $this->outHost['domain'] = $name;
    }

    private function setSubdomain(string $name){
        $this->outHost['subdomain'] = $name;
    }

    /**
     * @param bool $https if true set 'https://', false set 'http://'
     */
    private function setSSL(bool $https = true){

        if($https === true) $this->outHost['scheme'] = 'https://';
        else $this->outHost['scheme'] = 'http://';

    }


    /*
     * ---------------------------------------------------------------
     * section get Options from config with CI files
     * ---------------------------------------------------------------
     */

    /**
     * get from Routes.php
     *
     * @return string|null
     */
    private function getOptions_pSubdomain():?string {

        $subdomain = $this->router->getMatchedRouteOptions()['pSubdomain'] ?? null;

        if(is_string($subdomain)){
            return $subdomain;
        } else {
            return null;
        }
    }

    /**
     * get from Routes.php
     *
     * @return string|null will return 'null' if not set or domain name e.g. "example.com"
     */
    private function getOptions_pDomain():?string {
        $domain = $this->router->getMatchedRouteOptions()['pDomain'] ?? null;

        if(is_string($domain)) {
            return $domain;
        } else {
            return null;
        }
    }

    /**
     * get from Routes.php
     *
     * @return bool
     */
    private function getOptions_pSubhost():bool {
        $subhost = $this->router->getMatchedRouteOptions()['pSubhost'] ?? false;

        if(is_bool($subhost)){
            return $subhost;
        } else {
            return false;
        }
    }

    /**
     * get from Routes.php
     *
     * @return bool|null if true https, false http, null if not set
     */
    private function getOptions_pSSL():?bool {
        $ssl = $this->router->getMatchedRouteOptions()['pSSL'] ?? null;

        if(is_bool($ssl)){
            return $ssl;
        } else {
            return null;
        }
    }



    // ---------------------------------------------------------------
    // ---------------------------------------------------------------
    // ---------------------------------------------------------------

    /**
     * @param bool $returnString if you want scheme return in string like 'https://' you must set this value to true
     *
     * @return bool|string if ssl on(https://) return true, otherwise return false
     */
    private function getCurrentSSL(bool $returnString = false):bool|string{
        $scheme = $this->request->getServer('REQUEST_SCHEME') ?? 'http';

        if($returnString === false) {
            if($scheme === 'http'){
                return false;
            } else {
                return true;
            }
        } else {
            if($scheme === 'http'){
                return 'http://';
            } else {
                return 'https://';
            }
        }

    }


    // ---------------------------------------------------------------



    /**
     * Domain Filter - Pece szajens
     *
     * @param array $allowed_domain - allowed domain format ['example.net','subdomain.example.com'] , don't use http://
     * @param string $domain_to_check - input domain to check e.g. www.www.www.www.a1.g.h.r.r.e.e.e.e.e.e.d.subdomain.example.com
     *
     * @return array associates
     *
     *               if not found:
     *    ['matches'] type (int) == 0
     *    [0] == empty array()
     *
     *               if checked:
     *
     *    ['matches'] type (int) == 1
     *    [0][0] => www. - prefix
     *    [0][1] => a1.g.h.r.r.e.e.e.e.e.e.d - subdomain or null if not check
     *    [0][2] => subdomain.example.com - domain
     *
     *
     *
     */

    private function domainFilter(array $allowed_domain, string $domain_to_check):array {

        $result = [];//only for IDE (warning undefined variable)

        $allowed_domain = implode('|', $allowed_domain);

        $result['matches'] = preg_match('/^(?:(w{3})\.)*(?:((?:[a-z0-9\-_]+\.)*[a-z0-9\-_]+)\.)*('.$allowed_domain.')$/', $domain_to_check, $result[0]);

        //remove first element from array, but first array return input param $allowed_domain
        array_shift($result[0]);

        return $result;
    }

    /**
     * @return array Return allowed domains in app
     */
    private function getAllowedDomains():array {//get options from app config

        $peceAllowedDomains = $this->request->config->peceAllowedDomains ?? null;

        if(is_array($peceAllowedDomains)){//if from config file
            return $peceAllowedDomains;

        } elseif (is_string($peceAllowedDomains)) {//if from class

            $peceAllowedDomains = explode('::', $peceAllowedDomains);

            $className = $peceAllowedDomains[0] ?? '';

            if(class_exists($className)) {

                $runningClass = new $className();

                $methodName = $peceAllowedDomains[1] ?? '';


                $isCallable = is_callable([$runningClass, $methodName]);

                if ($isCallable) {

                    return $runningClass->$methodName();

                } else {

                    $this->configError('Call to undefined method '.$className.'::'.$methodName.'() - ');

                }

            } else {

                $this->configError('Class "'.$className.'" not found - ');
            }


        } else {//if wrong setup
            $this->configError('error in $peceAllowedDomains - ');
        }
    }

    /**
     * Get default limiting to scheme from App Config
     *
     * @return bool|null true - only https, false - only http, null - no limiting
     */
    private function getDefaultSSL():?bool {//get options from app config
        $defaultSSL = $this->request->config->peceDefaultSSL ?? null;
        if(is_bool($defaultSSL)){
            return $defaultSSL;
        } else {
            return null;
        }


    }


    /*
     * ---------------------------------------------------------------
     * Error section
     * ---------------------------------------------------------------
     */

    /**
     * PageNotFoundException Page not found
     */
    private function error404(){
        throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Show error - config type
     *
     * @param string|null $message
     *
     */
    private function configError(?string $message = null){
        throw new ConfigException($message.$this->defaultMessage);
    }


    // ---------------------------------------------------------------


}