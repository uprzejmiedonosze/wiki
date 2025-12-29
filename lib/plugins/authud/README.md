# Quickstart

Make sure authplain is installed.

Put plugin files in `var/plugins/authud`.

Make sure `conf/acl.auth.php` exists.

Make sure `conf/users.auth.php` exists and is readable&writable by webserver (may be empty).

Edit `conf/local.php` and following config settings:
```
$conf['authtype'] = 'authud';
$conf['plugin']['authud']['endpoint']='https://domain.tld/user/validate';
$conf['plugin']['authud']['cookiename']='PHPSESSID';
$conf['plugin']['authud']['apikey']='<match BACKEND_API_KEY>
$conf['plugin']['captcha']['loginprotect']=0; // captcha not really required with authud
$conf['superuser'] = '@admin'; // group name
// $conf['dontlog']=''; // enable all logs if required
```

# First login

After registering/logging for the first time, edit `conf/users.auth.php`, find your username (probably on last line) and change user groups in last column from `user` to `admin,user`.

