<?php
/**
 * Auth end-point controller
 *
 * @author yuklia
 * @created  05.05.15 17:30
 */
namespace Application;

use Application\Auth\AuthProvider;
use Application\Users;
use Bluz\Proxy\Request;

return
    /**
     * @return \closure
     */
    function () {

        $requestParams = Request::getParams();

        $tokenUrl = "http://jethub.nixsolutions.com:8080/hub/api/rest/oauth2/token";

        $opts = (new AuthProvider('jethub'))->getOptions();

        $clientId = $opts['providers']['Jethub']['keys']['id'];
        $clientSecret = $opts['providers']['Jethub']['keys']['secret'];
        
        $params = [
            'grant_type'    => 'authorization_code',
            'code'          => $requestParams['code'],
            'redirect_uri'  => $opts['base_url'],
        ];

        $sign = base64_encode($clientId . ":" . $clientSecret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $tokenUrl);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Authorization: Basic $sign",
            "Content-Type: application/x-www-form-urlencoded",
        ]);
        $result = curl_exec($curl);
        curl_close($curl);

        $tokenInfo = json_decode($result, true);
        var_dump($tokenInfo);

        $token = $tokenInfo['access_token'];


        $url = 'http://jethub.nixsolutions.com:8080/hub/rest/users/me';
        $sign = base64_encode($token);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//result as string
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
        ]);
        $result = curl_exec($curl);
        curl_close($curl);

        var_dump((array)json_decode($result, true));
       // var_dump($userInfo);
        // \Hybrid_Endpoint::process();
    };
