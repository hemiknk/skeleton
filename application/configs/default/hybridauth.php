<?php
/**
 * Created by PhpStorm.
 * User: yuklia
 * Date: 05.05.15
 * Time: 14:04
 */

/**
 * @link http://hybridauth.sourceforge.net/userguide/Configuration.html
 */
return array(
        //"base_url" the url that point to HybridAuth Endpoint (where index.php and config.php are found)
        "base_url" => "http://skeleton.tarasov.php.nixsolutions.com/auth/endpoint",

        "providers" => array(
            //
            "Jethub" => [
                "enabled" => true,
                "wrapper" => [ "path" => "Providers/Jethub.php", "class" => "Hybrid_Providers_Jethub"],
                "keys" => ["secret" => "z0wGR3rLy1ax", "id" => "eb121179-e0ee-4537-9e02-bae2fbb6ca8c"],
                "scope"   => "0-0-0-0-0",
                "response_type" => "code",
            ],
            // google
            "Google" => array( // 'id' is your google client id
                "enabled" => true,
                "wrapper" => array( "path" => "Providers/Google.php", "class" => "Hybrid_Providers_Google" ),
                "keys" => array("id" => "660729829372-e4ban0asg4pa8k0qhp7jtakubvr646se.apps.googleusercontent.com",
                    "secret" => "y9JFp7hxPcSNxOK25KRubYKX"),
                "scope"           => "https://www.googleapis.com/auth/userinfo.profile ". // optional
                    "https://www.googleapis.com/auth/userinfo.email"   , // optional
                "access_type"     => "offline",   // optional
                "approval_prompt" => "force",     // optional
            ),

            // facebook
            "Facebook" => array( // 'id' is your facebook application id
                "enabled" => true,
                "wrapper" => array( "path" => "Providers/Facebook.php", "class" => "Hybrid_Providers_Facebook" ),
                "keys" => array("id" => "%%appId%%", "secret" => "%%secret%%"),
                "scope"   => "email, user_about_me, user_birthday, user_hometown, publish_actions", // optional
            ),

            // twitter
            "Twitter" => array( // 'key' is your twitter application consumer key
                "enabled" => true,
                "wrapper" => array( "path" => "Providers/Twitter.php", "class" => "Hybrid_Providers_Twitter" ),
                "keys" => array("key" => "i2mHcUEYqDXN6RxViToLxn0KD", "secret" => "SY1zYcNQm7bTOaG5KzglmHr2gv2WbvFs84e3YXpaTYKEyTAJA2")
            )
        ),

        "debug_mode" => false,

        // to enable logging, set 'debug_mode' to true, then provide here a path of a writable file
        "debug_file" =>'/home/dev/src/skeleton/data/logs/hybriaauth_log.txt'
    );
