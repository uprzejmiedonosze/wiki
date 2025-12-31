<?php

use dokuwiki\Extension\AuthPlugin;
use dokuwiki\Logger;
use dokuwiki\Utf8\Sort;

/**
 * UD authentication backend
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Lukasz Biegaj <ud@x93.org>
 * Based on authad plugin, with following authors:
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author  Chris Smith <chris@jalakai.co.uk>
 * @author  Dominik Eckelmann <dokuwiki@cosmocode.de>
 * @author  James Van Lommel <james@nosq.com>
 * @author  Jan Schumann <js@schumann-it.com>
 */
class auth_plugin_authud extends AuthPlugin
{
    /**
     * @var array user cache 
     */
    protected $users;

    /**
     * @var array filter pattern 
     */
    protected $pattern = [];

    /**
     * @var bool safe version of preg_split 
     */
    protected $pregsplit_safe = false;

    /**
     * @var helper_plugin_authud 
     */
    protected $helper;

    /**
     * Constructor
     *
     * Carry out sanity checks to ensure the object is
     * able to operate. Set capabilities.
     */
    public function __construct()
    {
        parent::__construct();
        global $config_cascade;

        // Load helper plugin
        $this->helper = plugin_load('helper', 'authud');

        $this->cando['external'] = true; // enable external auth flow
        $this->cando['addUser']   = false; // no manual registration
        $this->cando['modPass']   = false; // no password changing
        $this->cando['delUser']   = false;
        $this->cando['modLogin']  = false;
        $this->cando['modName']   = false;
        $this->cando['modMail']   = false;
        $this->cando['modGroups'] = true;
        $this->cando['getUsers']     = true;
        $this->cando['getUserCount'] = true;
        $this->cando['getGroups']    = false;

        $this->success =  @is_readable($config_cascade['plainauth.users']['default'])
                        &&
                        @is_writable($config_cascade['plainauth.users']['default']);

    }

    /**
     * Check user against external API source
     *
     * This method is called when a user has a valid external session
     * but may not be logged into DokuWiki yet.
     *
     * @param  string $user       Username to check
     * @param  string $pass       Password (not used for external auth)
     * @param  bool   $rememberMe Remember login
     * @return bool Success
     */
    public function trustExternal($name, $pass="", $rememberMe=false)
    {
        if ($this->helper === null) {
            Logger::error('authud', 'Helper plugin not loaded');
            return false;
        }

        $remoteUserData = $this->helper->validateSession();
        if (!$remoteUserData) { return false;
        }
        // fetch user data either from remote id or from local id (if passed)
        $localUserData = 
            empty($name)
            ?
              $this->getUserData($remoteUserData['user_id'])
            :
              $this->getUserDataByName($this->getUserDataByName($name));
        ;

        
        if ($localUserData) {
            Logger::debug("authud", "trustExternal has validated remote user ${remoteUserData['user_id']} as local user ${localUserData['name']}");
            global $USERINFO;
            $USERINFO = $localUserData;
            $_SERVER['REMOTE_USER'] = $localUserData['name'];
            $_SESSION[DOKU_COOKIE]['auth']['user'] = $localUserData['name'];
            $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
            return true;
        };

        if (!empty($name)) {
            // we have an externally validated user that wants to have a local account
            Logger::debug("authud", "trustExternal: creating user ".$remoteUserData['user_id']." with name: $name");
            $result = $this->createUser(
                $remoteUserData['user_id'],
                auth_pwgen(), // Generate random password (won't be used)
                $name,
                $remoteUserData['user_email'],
                ['user'] // Default group
            );

            if ($result === false || $result === null) {
                Logger::error("authud", "trustExternal: failed to create user $name");
                return false;
            } else {
                Logger::debug("authud", "create local user ".$remoteUserData['user_id']." with name $name");
            }


        };

        return false;
        

        if (empty($user)) {
            // No user provided - try to validate session from cookie

            if ($userData === false) {
                Logger::debug("authud", "trustExternal: no valid session found");
                return false;
            }

            // Use the user_id from validated session
            $user = $userData['user_id'];
        }

        // Check if user exists in DokuWiki database
        $userInfo = $this->getUserData($user);

        if ($userInfo === false) {
            // User doesn't exist in DokuWiki yet
            Logger::debug("authud", "trustExternal: user $user not in DokuWiki database");
            return false; // user has to complete first login manually to enter his name
           

            // Get fresh user data from session validation if we don't have it
            /*
            if (!isset($userData)) {
                if ($this->helper === null) return false;
                $userData = $this->helper->validateSession();
                if ($userData === false) return false;
            }
             */

            // Auto-create the user in DokuWiki with data from API
            // Only if createUser capability exists
            /*
            if ($this->cando['addUser']) {
                Logger::debug("authud", "trustExternal: auto-created user $user");
            } else {
                Logger::debug("authud", "trustExternal: user creation disabled, cannot auto-create $user");
                return false;
            }
             */
        }
        // User exists or was created - complete login
        Logger::debug("authud", "trustExternal: user was sucesfully authenticated against remote and local database");

        Logger::debug("authud", "trustExternal: successfully authenticated user $user");
        return true;
    }

    /**
     * Check user+password
     *
     * Checks if the given user exists and the given
     * plaintext password is correct
     *
     * @param  string $user
     * @param  string $pass
     * @return bool
     */
    public function checkPass($user, $pass)
    {
        return false; // failsafe, as this should not be called
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  nickname of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param  string $user
     * @param  bool   $requireGroups (optional) ignored by this plugin, grps info always supplied
     * @return array|false
     */
    public function getUserData($user, $requireGroups = true)
    {
        if ($this->users === null) { $this->loadUserData();
        }
        return $this->users[$user] ?? false;
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  nickname of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @param  string $user
     * @param  bool   $requireGroups (optional) ignored by this plugin, grps info always supplied
     * @return array|false
     */
    public function getUserDataByName($name, $requireGroups = true)
    {
        if ($this->users === null) { $this->loadUserData();
        }
        // naive and slow but good enough for our scale
        foreach ($this->users as $user) {
            if ($user['name']==$name) { return $user;
            }
        }
        
        return false;
        
    }

    /**
     * Creates a string suitable for saving as a line
     * in the file database
     * (delimiters escaped, etc.)
     *
     * @param  string $user
     * @param  string $pass
     * @param  string $name
     * @param  string $mail
     * @param  array  $grps list of groups the user is in
     * @return string
     */
    protected function createUserLine($user, $pass, $name, $mail, $grps)
    {
        $groups   = implode(',', $grps);
        $userline = [$user, $pass, $name, $mail, $groups];
        $userline = str_replace('\\', '\\\\', $userline); // escape \ as \\
        $userline = str_replace(':', '\\:', $userline); // escape : as \:
        $userline = str_replace('#', '\\#', $userline); // escape # as \
        $userline = implode(':', $userline) . "\n";
        return $userline;
    }

    /**
     * Create a new User
     *
     * Returns false if the user already exists, null when an error
     * occurred and true if everything went well.
     *
     * The new user will be added to the default group by this
     * function if grps are not specified (default behaviour).
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Chris Smith <chris@jalakai.co.uk>
     *
     * @param  string $user
     * @param  string $pwd
     * @param  string $name
     * @param  string $mail
     * @param  array  $grps
     * @return bool|null|string
     */
    public function createUser($user, $pwd, $name, $mail, $grps = null)
    {
        global $conf;
        global $config_cascade;

        // user mustn't already exist
        if ($this->getUserData($user) !== false) {
            msg($this->getLang('userexists'), -1);
            return false;
        }

        // name must also be unique
        if ($this->getUserDataByName($name) !== false) {
            msg($this->getLang('userexists'), -1);
            return false;
        }

        $pass = auth_cryptPassword($pwd);

        // set default group if no groups specified
        if (!is_array($grps)) { $grps = [$conf['defaultgroup']];
        }

        // prepare user line
        $userline = $this->createUserLine($user, $pass, $name, $mail, $grps);

        if (!io_saveFile($config_cascade['plainauth.users']['default'], $userline, true)) {
            msg($this->getLang('writefail'), -1);
            return null;
        }

        $this->users[$user] = [
            'pass' => $pass,
            'name' => $name,
            'mail' => $mail,
            'grps' => $grps
        ];
        return $pwd;
    }

    /**
     * Return a count of the number of user which meet $filter criteria
     *
     * @author Chris Smith <chris@jalakai.co.uk>
     *
     * @param  array $filter
     * @return int
     */
    public function getUserCount($filter = [])
    {

        if ($this->users === null) { $this->loadUserData();
        }

        if ($filter === []) { return count($this->users);
        }

        $count = 0;
        $this->constructPattern($filter);

        foreach ($this->users as $user => $info) {
            $count += $this->filter($user, $info);
        }

        return $count;
    }

    /**
     * Bulk retrieval of user data
     *
     * @author Chris Smith <chris@jalakai.co.uk>
     *
     * @param  int   $start  index of first user to be returned
     * @param  int   $limit  max number of users to be returned
     * @param  array $filter array of field/pattern pairs
     * @return array userinfo (refer getUserData for internal userinfo details)
     */
    public function retrieveUsers($start = 0, $limit = 0, $filter = [])
    {

        if ($this->users === null) { $this->loadUserData();
        }

        Sort::ksort($this->users);

        $i     = 0;
        $count = 0;
        $out   = [];
        $this->constructPattern($filter);

        foreach ($this->users as $user => $info) {
            if ($this->filter($user, $info)) {
                if ($i >= $start) {
                    $out[$user] = $info;
                    $count++;
                    if (($limit > 0) && ($count >= $limit)) { break;
                    }
                }
                $i++;
            }
        }

        return $out;
    }

    /**
     * Retrieves groups.
     * Loads complete user data into memory before searching for groups.
     *
     * @param  int $start index of first group to be returned
     * @param  int $limit max number of groups to be returned
     * @return array
     */
    public function retrieveGroups($start = 0, $limit = 0)
    {
        $groups = [];

        if ($this->users === null) { $this->loadUserData();
        }
        foreach ($this->users as $info) {
            $groups = array_merge($groups, array_diff($info['grps'], $groups));
        }
        Sort::ksort($groups);

        if ($limit > 0) {
            return array_splice($groups, $start, $limit);
        }
        return array_splice($groups, $start);
    }

    /**
     * Only valid pageid's (no namespaces) for usernames
     *
     * @param  string $user
     * @return string
     */
    public function cleanUser($user)
    {
        global $conf;

        return cleanID(str_replace([':', '/', ';'], $conf['sepchar'], $user));
    }

    /**
     * Only valid pageid's (no namespaces) for groupnames
     *
     * @param  string $group
     * @return string
     */
    public function cleanGroup($group)
    {
        global $conf;

        return cleanID(str_replace([':', '/', ';'], $conf['sepchar'], $group));
    }

    /**
     * Load all user data
     *
     * loads the user file into a datastructure
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    protected function loadUserData()
    {
        global $config_cascade;
        $this->users = $this->readUserFile($config_cascade['plainauth.users']['default']);

        // support protected users
        if (!empty($config_cascade['plainauth.users']['protected'])) {
            $protected = $this->readUserFile($config_cascade['plainauth.users']['protected']);
            foreach (array_keys($protected) as $key) {
                $protected[$key]['protected'] = true;
            }
            $this->users = array_merge($this->users, $protected);
        }
    }

    /**
     * Read user data from given file
     *
     * ignores non existing files
     *
     * @param  string $file the file to load data from
     * @return array
     */
    protected function readUserFile($file)
    {
        $users = [];
        if (!file_exists($file)) { return $users;
        }

        $lines = file($file);
        foreach ($lines as $line) {
            $line = preg_replace('/(?<!\\\\)#.*$/', '', $line); //ignore comments (unless escaped)
            $line = trim($line);
            if (empty($line)) { continue;
            }

            $row = $this->splitUserData($line);
            $row = str_replace('\\:', ':', $row);
            $row = str_replace('\\\\', '\\', $row);
            $row = str_replace('\\#', '#', $row);

            $groups = array_values(array_filter(explode(",", $row[4])));

            $users[$row[0]]['pass'] = $row[1];
            $users[$row[0]]['name'] = urldecode($row[2]);
            $users[$row[0]]['mail'] = $row[3];
            $users[$row[0]]['grps'] = $groups;
        }
        return $users;
    }

    /**
     * Get the user line split into it's parts
     *
     * @param  string $line
     * @return string[]
     */
    protected function splitUserData($line)
    {
        $data = preg_split('/(?<![^\\\\]\\\\)\:/', $line, 5);       // allow for : escaped as \:
        if (count($data) < 5) {
            $data = array_pad($data, 5, '');
            Logger::error('User line with less than 5 fields. Possibly corruption in your user file', $data);
        }
        return $data;
    }

    /**
     * return true if $user + $info match $filter criteria, false otherwise
     *
     * @author Chris Smith <chris@jalakai.co.uk>
     *
     * @param  string $user User login
     * @param  array  $info User's userinfo array
     * @return bool
     */
    protected function filter($user, $info)
    {
        foreach ($this->pattern as $item => $pattern) {
            if ($item == 'user') {
                if (!preg_match($pattern, $user)) { return false;
                }
            } elseif ($item == 'grps') {
                if (!count(preg_grep($pattern, $info['grps']))) { return false;
                }
            } elseif (!preg_match($pattern, $info[$item])) {
                return false;
            }
        }
        return true;
    }

    /**
     * construct a filter pattern
     *
     * @param array $filter
     */
    protected function constructPattern($filter)
    {
        $this->pattern = [];
        foreach ($filter as $item => $pattern) {
            $this->pattern[$item] = '/' . str_replace('/', '\/', $pattern) . '/i'; // allow regex characters
        }
    }

    public function logOff()
    {
        setcookie(
            $this->getConf('cookiename'), '', [
            'expires' => time() - 600000,
            'path' => '/']
        );
        send_redirect(wl('start', '', true, '&'));
        return true;
    }
}
