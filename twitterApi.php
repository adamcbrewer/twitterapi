<?php

/**
* Twitter
*
* An interface to the Twitter OAuth API
* found in the libraries directory
*
* @author adamcbrewer
* @version 1.1
*
*/
class Twitter {

    /**
     * SHould contain an instance of the
     * Twitter Oauth library
     *
     */
    private static $api;


    /**
     * The twitter username of the account holder
     *
     */
    private static $screen_name;


    /**
     * Constructor
     *
     * @return object $config
     */
    public function __construct ($config) {

        static::$api = new tmhOAuth(array(
            'consumer_key'          => $config['consumer_key'],
            'consumer_secret'       => $config['consumer_secret'],
            'user_token'            => $config['user_token'],
            'user_secret'           => $config['user_secret'],
            'curl_ssl_verifypeer'   => true
        ));

        static::$screen_name = $config['screen_name'];

        return static::$api;

    }


    /**
     * Populate and set-up our class vars
     *
     * @return object $api
     */
    public static function initialize ($config) {

        return static::$api;

    }

    public static function &api() {
        if (static::$api === null) {

            static::initialize();
        }
        return static::$api;
    }



    /**
     * Get a number of specified tweets back from the user's timeline,
     * optionally JSON formatted
     *
     * @param  integer $count 	The number of tweets to get
     * @param  boolean $json_encode 	JSON encoded or not
     * @return object $response
     */
    public static function get ($count = 10, $count_replies = false ) {

        static::api()->request('GET', static::api()->url('1.1/statuses/user_timeline'), array(
            'include_entities' => 1,
            'include_rts'      => 1,
            'screen_name'      => static::$screen_name,
            'count'            => $count,
        ));

        return static::_response();

    }


    /**
     * Search for tweets via a query param
     *
     * @param  string $query      The query param. See: https://dev.twitter.com/rest/public/search
     * @param  integer $count     The number of tweets to get
     * @return object $response
     */
    public static function search ($query = '', $count = 100) {

        static::api()->request('GET', static::api()->url('1.1/search/tweets'), array(
            'lang'              => 'en',
            'q'                 => $query,
            'count'             => $count
        ));

        return static::_response();

    }


    /**
     * Fetch a particular tweet and all relevant data
     *
     * @param  integer $id 	The id of the tweet
     * @return object $response
     */
    public static function find ( $id = null ) {

        static::api()->request('GET', static::api()->url('1.1/statuses/show'), array(
            'id' => $id,
            'trim_user' => false,
            'include_entities' => true
        ));

        return static::_response();

    }


    /**
     * Return a list of followers
     *
     */
    public static function followers () {

        static::api()->request('GET', static::api()->url('1.1/followers/list'), array(
            'screen_name' => static::$screen_name
        ));

        return static::_response();

    }


    /**
     * Fetch a list of user objects by specifying either a
     * comma-separated list of user_ids or and array of them
     *
     * @param  string/array $user_ids An array or comma-separated string of user IDs
     * @return Twitter user objects
     */
    public static function users ( $user_ids = '' ) {

        if (is_array($user_ids)) {
            $user_ids = implode(',', $user_ids);
        }

        static::api()->request('GET', static::api()->url('1.1/users/lookup'), array(
            'user_id' => $user_ids,
            'include_entities' => true
        ));

        return static::_response();

    }


    /**
     * View the profile of a pecific Twitter user
     *
     * @param  string $username The twitter username/screen_name
     * @return Twitter user object
     */
    public static function user ( $username = '' ) {

        static::api()->request('GET', static::api()->url('1.1/users/show'), array(
            'screen_name' => $username,
            'include_entities' => true
        ));

        return static::_response();

    }


    /**
     * Fetch a list of user-mentions, optionally
     * specifying a count of returned tweet objects
     *
     * @param  integer $count
     * @return object tweets
     */
    public static function mentions ( $count = 50 ) {

        static::api()->request('GET', static::api()->url('1.1/statuses/mentions_timeline'), array(
            'count' => $count,
            'include_entities' => true
        ));

        return static::_response();

    }


    /**
     * Get replies to a message
     *
     * @param  string/int $tweet_id the ID of the tweet we want replies after
     * @param int $count the number of tweets to try and receive
     * @return mixed Tweets replies or a count
     */
    public static function replies ( $tweet_id = '', $count = 100 ) {

        $params = array(
            'include_entities' => true,
            'since_id' => $tweet_id,
            'count' => $count
        );

        static::api()->request('GET', static::api()->url('1.1/statuses/mentions_timeline'), $params);

        $response = static::api()->response['response'];
        $code = static::api()->response['code'];

        if ($code === 200) {

            $mentions = json_decode($response, false, 512, JSON_BIGINT_AS_STRING);

            // We have all replies since the tweet ID we've specified, but
            // here is where twe pick out only the ones that are specifically
            // replies to our ID
            $replies = array_filter($mentions, function ($mention) use ( $tweet_id ) {
                if ($mention->in_reply_to_status_id_str == $tweet_id) return get_object_vars($mention);
            });

            static::api()->response['response'] = json_encode($replies);

        }

        return static::_response();

    }


    /**
     * The home timeline is central to how most
     * users interact with the Twitter service. It's basically
     * the user's homepage on the web version.
     *
     * @param  integer $count
     * @return object tweets
     */
    public static function home_timeline ( $count = 20 ) {

        static::api()->request('GET', static::api()->url('1.1/statuses/home_timeline'), array(
            'count' => $count,
            'include_entities' => true
        ));

        return static::_response();

    }


    /**
     * Favourite a specific tweet
     *
     * @param  string  $type create (favourite) or destroy (unfavourite)
     * @param  string/int $id tweet id to favourite
     * @return object
     */
    public static function favourite ( $type = 'create', $id = '' ) {

        if ( ! in_array($type, array('destroy', 'create'))) {
            $type = 'create';
        }

        static::api()->request('POST', static::api()->url('1.1/favorites/' . $type), array(
            'id' => $id,
            'include_entities' => false
        ));

        return static::_response();

    }


    /**
     * Sending out a tweet through Twitter's oAuth API
     *
     * We can specify an ID of a message we're replying to,
     * but a reply will only be served if the status body contains
     * the username of the person/message we're replying to
     *
     * @param string $message 		The tweet you want to post
     * @param sting/int $in_reply_to_status_id The ID of the message we're replying to
     */
    public static function create ( $tweet = '', $in_reply_to_status_id = false ) {

        $params = array(
            'status' => $tweet
        );

        if ( $in_reply_to_status_id !== false ) {
            $params['in_reply_to_status_id'] = $in_reply_to_status_id;
        }

        static::api()->request('POST', static::api()->url('1.1/statuses/update'), $params);

        return static::_response();

    }


    /**
     * Delete a tweet
     *
     * @param int $id
     */
    public static function delete ( $id = null ) {

        $destroyed = static::api()->request('POST', static::api()->url('1.1/statuses/destroy/' .$id), array(
            'id' => $id,
            'trim_user' => true
        ));

        return static::_response();

    }


    /**
     * Nicely formatted API respnse
     *
     * @return json
     */
    private static function _response () {

        $response = static::api()->response['response'];
        $code = static::api()->response['code'];
        $query = static::api()->response['info']['url'];

        $data = array(
            'code' => $code,
            'query' => $query,
            'response' => json_decode($response, false, 512, JSON_BIGINT_AS_STRING)
        );

        return $data;

    }

}
