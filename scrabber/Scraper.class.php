<?php 
require_once('phpQuery.php');

var_dump('ads');exit;



class Scraper
{
    private $content;
    protected $rules;
    protected $processed_data;
    protected $base_url;
    protected $final_url;
    protected $proxyHandler = null;


    public function __construct()
    {
    
     $this->proxyHandler = new ProxyHandler();

    //     $this->rules = null;
    //     $this->final_url = '';
    //     $this->base_url = '';
         
    }

    public function setRules($rules)
    {
        $this->rules = (array)$rules;
       // var_dump($this->rules);
        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getFinalURL()
    {
        return $this->final_url;
    }

    public function setBaseURL($url)
    {
        $this->base_url = $url;
    }

    public function setContent($content)
    {
        if (!empty($content))
            $this->content = $content;
        return $this;
    }

    public function processRule($contents=null, $rule, $string_mode=true, $separator="\n")
    {
        $out = array();
        $contents = ($contents == null) ? $this->content : $contents;
        $contents = preg_replace("#[\x95-\x96]\x20#", '', $contents);

        $doc = phpQuery::newDocument($contents);

        if (empty($rule))
            return null;

        if (is_array($rule)) {
            if (is_array($rule[1])) {
                $result = $doc->find($rule[0]);
                foreach ($rule[1] as $func) {
                    $result = call_user_func_array(array($result, $func), array());
                }
                return $result->text();
            } else {
                switch ($rule[1]) {
                    case 'href':
                        foreach ($doc->find($rule[0]) as $element) {
                            $out[] = $element->getAttribute('href');
                        }
                        break;
                    case 'html-elements':
                        foreach ($doc->find($rule[0]) as $element) {
                            $out[] = pq($element)->html();
                        }
                        break;
                    case 'html':
                        $out[] = $doc->find($rule[0])->html();
                        break;
                    case 'value':
                        foreach ($doc->find($rule[0]) as $element) {
                            $out[] = $element->getAttribute('value');
                        }
                        break;
                    case 'src':
                        foreach ($doc->find($rule[0]) as $element) {
                            $out[] = $element->getAttribute('src');
                        }
                        break;
                    default:
                        foreach ($doc->find($rule[0]) as $element) {
                            $out[] = $element->getAttribute($rule[1]);
                        }
                        break;
                }
            }
        } else {

            foreach ($doc->find($rule) as $element) {

                $doc = new DOMDocument();
                foreach ($element->childNodes as $child)
                    $doc->appendChild($doc->importNode($child, true));

                $html = $doc->saveHTML();

                $doc = null;

                $html = str_replace(array('<br>', '<br />', '<br/>', '&nbsp;'), "\n", $html);
                $out[] = strip_tags($html);
            }
        }

        phpQuery::unloadDocuments();
        
        return ($string_mode) ? trim(implode($separator, $out)) : $out;
    } //EO Method

    public static function getInteger($data)
    {
        if (is_numeric($data)) {
            return (int)$data;
        }
        return (int)self::getFloat($data);
    }

    public static function getFloat($data)
    {
        if (is_float($data)) {
            return $data;
        }
        if (preg_match('/((\d+([\.,]?\d+)?)([\.]\d{1,2})?)/', $data, $match)) {
            if (isset($match[3]) && !isset($match[4]) && strlen($match[3]) > 3) {
                return (float)str_replace(array(',', '.'), array('', ''), $match[0]);
            }

            return (float)(isset($match[4]) ? str_replace(',', '', $match[2]) . $match[4]
                    : str_replace(',', '.', $match[1]));
        }
    }

    public static function getLongString($data)
    {
        return trim(str_replace(array("\r\n", "\n"), ' ', $data));
    }

    public static function getCleanString($data)
    {

        $data = trim(str_replace(array("\r\n", "\n"), ' ', strip_tags($data)));
        $data = preg_replace('/[\t]/', ' ', $data);
        $data = preg_replace('/[ ]{2,}/', ' ', $data);
        return trim($data);
    }

    public static function getUrlParams($url)
    {
        $params = array();
        $match = array();
        $parts = parse_url($url);
        if (isset($parts['query'])) {
            $parts['query'] = str_replace('&amp;', '%26', $parts['query']);
            $paramsPairs = explode("&", $parts['query']);
            foreach ($paramsPairs as $pair) {
                if (strpos($pair, '=') !== FALSE) {
                    list($paramName, $paramValue) = explode("=", $pair, 2);
                    $params[trim($paramName)] = urldecode(trim($paramValue));
                }
            }
        }
        return $params;
    }

    // Page scraping using cURL
    function getPage($page, $redirect = 0, $cookieFile = '', $referer = '')
    {
        if ($cookieFile == '') {
            $cookieFile = dirname(__FILE__) . '/tmp_files/cookies.txt';
        } else {
            $cookieFile = dirname(__FILE__) . '/tmp_files/' . $cookieFile;
        }

        $ch = curl_init();

        // to speed up curl
        $headers = array("Expect:");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($redirect) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }

        if ($referer != '') {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        curl_setopt($ch, CURLOPT_URL, $page);

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.6) Gecko/20060728 Firefox/1.5.0.6');

        $return = curl_exec($ch);

        $this->final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        return $this->prepareContent($return);
    } //EO Method

    function postData($page, $data, $redirect = 0, $cookieFile = '', $referer = '')
    {
        $ch = curl_init();

        if ($cookieFile == '') {
            $cookieFile = dirname(__FILE__) . '/tmp_files/cookies.txt';
        } else {
            $cookieFile = dirname(__FILE__) . '/tmp_files/' . $cookieFile;
        }

        //$headers = array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Connection: keep-alive', 'Accept-Encoding: gzip, deflate', 'Accept-Language: en-US,en;q=0.5');
        
        $headers = array(
           'Accept: application/json, text/javascript, */*; q=0.01',
           'Content-Type: application/json; charset=utf-8',
           'Connection: keep-alive', 
           'Accept-Encoding: gzip, deflate', 
           'Accept-Language: en-US,en;q=0.5'
        );

        // to speed up curl
        $headers = array("Expect:");
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ($redirect) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }

        if ($referer != '') {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        curl_setopt($ch, CURLOPT_URL, $page);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.6) Gecko/20060728 Firefox/1.5.0.6');

        $return = curl_exec($ch);

        $this->final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        echo curl_error($ch);

        curl_close($ch);

        return $this->prepareContent($return);
    }
        
    private function prepareContent($content)
    {
        //return str_replace(array("\x00", '®'), array('', '&reg;'), utf8_decode($content));
        return str_replace("\x00", '', $this->UTF8ToEntities($content));
    }

    public function UTF8ToEntities($string)
    {
        if (!preg_match("/[\200-\237]/", $string) and !preg_match("/[\241-\377]/", $string)) {
            return $string;
        }
        
        // reject too-short sequences
        $string = preg_replace("/[\302-\375]([\001-\177])/", "&#65533;\\1", $string);
        $string = preg_replace("/[\340-\375].([\001-\177])/", "&#65533;\\1", $string);
        $string = preg_replace("/[\360-\375]..([\001-\177])/", "&#65533;\\1", $string);
        $string = preg_replace("/[\370-\375]...([\001-\177])/", "&#65533;\\1", $string);
        $string = preg_replace("/[\374-\375]....([\001-\177])/", "&#65533;\\1", $string);

        // reject illegal bytes & sequences
        // 2-byte characters in ASCII range
        $string = preg_replace("/[\300-\301]./", "&#65533;", $string);
        // 4-byte illegal codepoints (RFC 3629)
        $string = preg_replace("/\364[\220-\277]../", "&#65533;", $string);
        // 4-byte illegal codepoints (RFC 3629)
        $string = preg_replace("/[\365-\367].../", "&#65533;", $string);
        // 5-byte illegal codepoints (RFC 3629)
        $string = preg_replace("/[\370-\373]..../", "&#65533;", $string);
        // 6-byte illegal codepoints (RFC 3629)
        $string = preg_replace("/[\374-\375]...../", "&#65533;", $string);
        // undefined bytes
        $string = preg_replace("/[\376-\377]/", "&#65533;", $string);

        // reject consecutive start-bytes
        $string = preg_replace("/[\302-\364]{2,}/", "&#65533;", $string);

        // decode four byte unicode characters
        $string = preg_replace(
            "/([\360-\364])([\200-\277])([\200-\277])([\200-\277])/e",
            "'&#'.((ord('\\1')&7)<<18 | (ord('\\2')&63)<<12 |" .
            " (ord('\\3')&63)<<6 | (ord('\\4')&63)).';'",
            $string);

        // decode three byte unicode characters
        $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
                               "'&#'.((ord('\\1')&15)<<12 | (ord('\\2')&63)<<6 | (ord('\\3')&63)).';'",
                               $string);

        // decode two byte unicode characters
        $string = preg_replace("/([\300-\337])([\200-\277])/e",
                               "'&#'.((ord('\\1')&31)<<6 | (ord('\\2')&63)).';'",
                               $string);

        // reject leftover continuation bytes
        $string = preg_replace("/[\200-\277]/", "&#65533;", $string);

        return $string;
    }

    public function getAbsoluteURL($url = NULL)
    {
        if (is_null($url)) {
            return $this->base_url;
        }

        $matches = array();
        if (preg_match("/((http(s?)):\/\/(.+))?[\/]?(.+)([\/]?)/", $url, $matches) && empty($matches[2])) {
            $url = $this->base_url . '/' . $matches[0];
        }

        list($urlPart1, $urlPart2) = explode('://', $url, 2);
        $urlPart2 = str_replace('../', '/', $urlPart2);

        return $urlPart1 . '://' . preg_replace('#//+#', '/', $urlPart2);

    }
}

//EO Class
?>