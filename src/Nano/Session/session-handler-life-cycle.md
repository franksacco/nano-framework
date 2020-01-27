
## A complete overview of PHP session handler life cycle
The purpose of this document is to provide a complete overview of the PHP session
handler life cycle updated to version 7.0 or above. In particular, I want to
emphasize what methods and in what order are called when the native PHP functions
are used for session management.\
I created this document because the information on the web and the official
documentation are very superficial on this topic, in particular on what
concerns the implementation of a safe and stable session handler.

### Session handler interfaces
There are three interfaces that can be implemented to create a session handler.
The first one is mandatory and the other two are optional.

* `SessionHandlerInterface` _(PHP 5 >= 5.4.0, PHP 7)_\
**SessionHandlerInterface** is an interface which defines a prototype
for creating a custom session handler.

    * `open( string $savePath , string $sessionName ) : bool`\
    Re-initialize existing session, or creates a new one. Called when a session
    starts or when `session_start()` is invoked.\
    Return value should be `true` for success or `false` for failure.
    
    * `read ( string $sessionId ) : string`\
    Reads the session data from the session storage, and returns the results.
    Before this method is called `SessionHandlerInterface::open()` is invoked.\
    The data returned by this method will be decoded internally by PHP using
    the unserialization method specified in `session.serialize_handler`.
    The resulting data will be used to populate the `$_SESSION` superglobal.\
    Return value should be the session data or an empty string.
    
    * `write ( string $sessionId , string $sessionData ) : bool`\
    Writes the session data to the session storage.\
    `SessionHandlerInterface::close()` is called immediately after this function.
    This method encodes the session data from the `$_SESSION` superglobal to a
    serialized string and passes this along with the session ID to this method
    for storage. The serialization method used is specified in the
    `session.serialize_handler` ini setting.\
    Return value should be `true` for success or `false` for failure.
    
    * `close ( void ) : bool`\
    Closes the current session.\
    This function is automatically executed when closing the session, or
    explicitly via `session_write_close()`.\
    Return value should be `true` for success or `false` for failure.
    
    * `destroy ( string $sessionId ) : bool`\
    Destroys a session.\
    Called by `session_regenerate_id()` (with $destroy = TRUE),
    `session_destroy()` and when `session_decode()` fails.\
    Return value should be `true` for success or `false` for failure.
    
    * `gc ( int $maxlifetime ) : int`\
    Cleans up expired sessions.\
    Called by `session_start()`, based on `session.gc_divisor`,
    `session.gc_probability` and `session.gc_maxlifetime` settings.\
    Return value should be `true` for success or `false` for failure.

* `SessionIdInterface` _(PHP 5 >= 5.5.1, PHP 7)_\
**SessionIdInterface** is an additional interface that gives the possibility
to manage the creation of a session ID in a personalized way.

    * `create_sid ( void ) : string`\
    This method is invoked internally when a new session id is needed.\
    The returned ID should be generated checking for collision with other saved
    sessions. However, from PHP 7 `session_create_id ([ string $prefix ] ) : string`
    ([documentation](https://secure.php.net/manual/en/function.session-create-id.php))
    is available. This function creates new collision free (if session is active)
    session ID for the current session in according to ini settings: 
    `session.sid_length` (length of session ID string) and
    `session.sid_bits_per_character` (number of bits in encoded session ID
    character).\
    No parameter is needed and return value should be the new session id created.

* `SessionUpdateTimestampHandlerInterface` _(PHP 7)_\
**SessionUpdateTimestampHandlerInterface** is an additional interface that
completes the functionalities of a session handler object.

    * `validateId( string $sessionId ) : bool`\
    Validate session ID.\
    This method is called when the `session.use_strict_mode` ini setting is set
    to `1` in order to avoid uninitialized session ID. The validity of session
    ID is checked on starting and on regenerating if _strict mode_ is enabled.\
    Return value should be `true` if the session ID is valid otherwise `false`.
    If `false` is returned a new session id will be generated.
    
    * `updateTimestamp( string $sessionId, string $sessionData ) : bool`\
    Update timestamp of a session.\
    This method is called when the `session.lazy_write` ini setting is set to
    `1` and no changes are made to session variables. In other words, when the
    session need to be closed, if _lazy_write mode_ is enabled and `$_SESSION`
    is not modified, this method is called instead of
    `SessionHandlerInterface::write()` in order to update session timestamp
    without rewriting all session data.\
    Return value should be `true` for success or `false` for failure.

### SessionHandler life cycle
In this part I tried to translate the behavior of native PHP session function
from the point-of-view of a session handler.\
Note that the code written below does not work at all, it has only to be
understood such as an explanation of what PHP does when a custom session
handler is set and native session functions are invoked.

* `session_start();` 
  ([documentation](https://secure.php.net/manual/en/function.session-start.php))\
    Creates a session or resumes the current one based on a session identifier
    passed via a GET or POST request, or passed via a cookie.
    ```php
    $savePath    = ini_get('session.save_path');
    $sessionName = ini_get('session.name');
    SessionHandlerInterface::open($savePath, $sessionName);
    
    // find $sessionId from server request, for example from $_COOKIES superglobal.
    if (!isset($sessionId)) {
        $sessionId = SessionIdInterface::create_sid();
    }
    if (ini_get('session.use_strict_mode') && !SessionUpdateTimestampHandlerInterface::validateId($sessionId)) {
        $sessionId = SessionIdInterface::create_sid();
    }
    
    $data = SessionHandlerInterface::read($sessionId);
    // here PHP does Garbage Collection based on probability
  
    if (ini_get('session.serialize_handler') === 'php_serialize') {
        $_SESSION = unserialize($data);
    } else {
        session_decode($data);
    }
    ```

* `session_commit();` or `session_write_close();`
  ([documentation](https://secure.php.net/manual/en/function.session-write-close.php))\
    End the current session and store session data.
    ```php
    if (ini_get('session.serialize_handler') === 'php_serialize') {
        $sessionData = serialize($_SESSION);
    } else {
        $sessionData = session_encode();
    }
    
    // if session.lazy_write is enabled and $_SESSION is NOT changed:
        SessionUpdateTimestampHandlerInterface::updateTimestamp($sessionId, $sessionData);
    // else:
        SessionHandlerInterface::write($sessionId, $sessionData);
    
    SessionHandlerInterface::close();
    ```

* `session_regenerate_id($deleteOldSession);`
  ([documentation](https://secure.php.net/manual/en/function.session-regenerate-id.php))\
    Update the current session id with a newly generated one.
    ```php
    if ($deleteOldSession) {
        SessionHandlerInterface::destroy($sessionId);
    } else {
        SessionHandlerInterface::write($sessionId, $sessionData);
    }
    SessionHandlerInterface::close();
    
    $savePath    = ini_get('session.save_path');
    $sessionName = ini_get('session.name');
    SessionHandlerInterface::open($savePath, $sessionName);
    
    $sessionId = SessionIdInterface::create_sid();
    if (ini_get('session.use_strict_mode') && !SessionUpdateTimestampHandlerInterface::validateId($sessionId)) {
        // A session ID is recreated even if it is collision free.
        // See the note below for more details.
        $sessionId = SessionIdInterface::create_sid();
    }
    
    SessionHandlerInterface::read($sessionId);
    ```

* `session_destroy();`
  ([documentation](https://secure.php.net/manual/en/function.session-destroy.php))\
  Destroys all data registered to a session.
  ```php
  SessionHandlerInterface::destroy($sessionId);
  SessionHandlerInterface::close();
  ```

* `session_unset();`
  ([documentation](https://secure.php.net/manual/en/function.session-unset.php))\
  Free all session variables.
  ```php
  $_SESSION = [];
  ```

* `session_gc();`
  ([documentation](https://secure.php.net/manual/en/function.session-gc.php))\
  Perform session data garbage collection.
  ```php
  $maxlifetime = (int) ini_get('session.gc_maxlifetime');
  SessionHandlerInterface::gc($maxlifetime);
  ```
\
**Note**: there is a bug in functions `session_regenerate_id()` and
`session_create_id()` (you can see
[https://bugs.php.net/bug.php?id=77178](https://bugs.php.net/bug.php?id=77178)
for more information) that forces the recreation of session ID even when
it is collision free.\
After new session ID is created with `create_sid()`, the method `validateId()`
is called and if it returns `false`, which means that the session does not
exist in storage, session ID is recreated.\
While we wait for the fixation of this bug, a simple workaround can
be the following:
```php
class CustomSessionHandler implements SessionHandlerInterface,
                                      SessionIdInterface,
                                      SessionUpdateTimestampHandlerInterface
{
    private $lastCreatedId;
  
    // ...methods implementation...
    
    public function create_sid()
    {
        $this->lastCreatedId = // create session ID
        return $this->lastCreatedId;
    }
 
    public function validateId($sessionId)
    {
        if ($sessionId === $this->lastCreatedId) {
            return true;
        }
        // checks session existance
    }
}
```
\
I hope this analysis will help all the developers interested in understanding
in detail the native session management performed by PHP and what a custom
session handler should do.\
Any comment or suggestion is appreciated.
