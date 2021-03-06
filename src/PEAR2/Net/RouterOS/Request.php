<?php

/**
 * ~~summary~~
 * 
 * ~~description~~
 * 
 * PHP version 5
 * 
 * @category  Net
 * @package   PEAR2_Net_RouterOS
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   GIT: $Id$
 * @link      http://pear2.php.net/PEAR2_Net_RouterOS
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Net\RouterOS;

/**
 * Represents a RouterOS request.
 * 
 * @category Net
 * @package  PEAR2_Net_RouterOS
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_RouterOS
 */
class Request extends Message
{

    /**
     * @var string The command to be executed.
     */
    private $_command;

    /**
     * @var Query A query for the command.
     */
    private $_query = null;

    /**
     * Creates a request to send to RouterOS.
     * 
     * @param string $command The command to send. Can also contain arguments
     * expressed in a shell-like syntax.
     * @param string $tag     The tag for the request.
     * @param Query  $query   A query to associate with the request.
     * 
     * @see setCommand()
     * @see setArgument()
     * @see setTag()
     * @see setQuery()
     */
    public function __construct($command, $tag = null, Query $query = null)
    {
        if (false !== ($firstEquals = strpos($command, '='))
            && false !== ($spaceBeforeEquals = strrpos(
                strstr($command, '=', true), ' '
            ))
        ) {
            $this->parseArgumentString(substr($command, $spaceBeforeEquals));
            $command = rtrim(substr($command, 0, $spaceBeforeEquals));
        }
        $this->setCommand($command);
        $this->setTag($tag);
        $this->setQuery($query);
    }
    
    /**
     * A shorthand gateway.
     * 
     * This is a magic PHP method that allows you to call the object as a
     * function. Depending on the argument given, one of the other functions in
     * the class is invoked and its returned value is returned by this function.
     * 
     * @param mixed $arg A {@link Query} to associate the request
     * with, a {@link Communicator} to send the request over, or an argument to
     * get the value of. If a second argument is provided, this becomes the name
     * of the argument to set the value of.
     * 
     * @return mixed Whatever the long form function would have returned.
     */
    public function __invoke($arg = null)
    {
        if (func_num_args() > 1) {
            return $this->setArgument(func_get_arg(0), func_get_arg(1));
        }
        if ($arg instanceof Query) {
            return $this->setQuery($arg);
        }
        if ($arg instanceof Communicator) {
            return $this->send($arg);
        }
        return parent::__invoke($arg);
    }

    /**
     * Sets the command to send to RouterOS.
     * 
     * Sets the command to send to RouterOS. The command can use the API or CLI
     * syntax of RouterOS, but either way, it must be absolute (begin  with a
     * "/") and without arguments.
     * 
     * @param string $command The command to send.
     * 
     * @return Request The request object.
     * @see getCommand()
     * @see setArgument()
     */
    public function setCommand($command)
    {
        $command = (string) $command;
        if (strpos($command, '/') !== 0) {
            throw new InvalidArgumentException(
                'Commands must be absolute.', 202
            );
        }
        if (substr_count($command, '/') === 1) {
            //Command line syntax convertion
            $cmdParts = preg_split('#[\s/]+#sm', $command);
            $cmdRes = array($cmdParts[0]);
            for ($i = 1, $n = count($cmdParts); $i < $n; $i++) {
                if ('..' === $cmdParts[$i]) {
                    $delIndex = count($cmdRes) - 1;
                    if ($delIndex < 1) {
                        throw new InvalidArgumentException(
                            'Unable to resolve command', 203
                        );
                    }
                    unset($cmdRes[$delIndex]);
                    $cmdRes = array_values($cmdRes);
                } else {
                    $cmdRes[] = $cmdParts[$i];
                }
            }
            $command = implode('/', $cmdRes);
        }
        if (!preg_match('#^/\S+$#sm', $command)) {
            throw new InvalidArgumentException(
                'Invalid command supplied.', 204
            );
        }
        $this->_command = $command;
        return $this;
    }

    /**
     * Gets the command that will be send to RouterOS.
     * 
     * Gets the command that will be send to RouterOS in its API syntax.
     * 
     * @return string The command to send.
     * @see setCommand()
     */
    public function getCommand()
    {
        return $this->_command;
    }

    /**
     * Sets the query to send with the command.
     * 
     * @param Query $query The query to be set. Setting NULL will remove the
     * currently associated query.
     * 
     * @return Request The request object.
     * @see getQuery()
     */
    public function setQuery(Query $query = null)
    {
        $this->_query = $query;
        return $this;
    }

    /**
     * Gets the currently associated query
     * 
     * @return Query The currently associated query.
     * @see setQuery()
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Sets the tag to associate the request with.
     * 
     * Sets the tag to associate the request with. Setting NULL erases the
     * currently set tag.
     * 
     * @param string $tag The tag to set.
     * 
     * @return Request The request object.
     * @see getTag()
     */
    public function setTag($tag)
    {
        return parent::setTag($tag);
    }

    /**
     * Sets an argument for the request.
     * 
     * @param string $name  Name of the argument.
     * @param string $value Value of the argument. Setting the value to NULL
     * removes an argument of this name.
     * 
     * @return Request The request object.
     * @see getArgument()
     */
    public function setArgument($name, $value = '')
    {
        return parent::setArgument($name, $value);
    }

    /**
     * Removes all arguments from the request.
     * 
     * @return Request The request object.
     */
    public function removeAllArguments()
    {
        return parent::removeAllArguments();
    }

    /**
     * Sends a request over a communicator.
     * 
     * @param Communicator $com The communicator to send the request over.
     * 
     * @return int The number of bytes sent.
     * @see Client::sendSync()
     * @see Client::sendAsync()
     */
    public function send(Communicator $com)
    {
        if (!$com->getTransmitter()->isAcceptingData()) {
            throw new SocketException(
                'Transmitter is invalid. Sending aborted.', 205
            );
        }
        $bytes = 0;
        $bytes += $com->sendWord($this->getCommand());
        if (null !== ($tag = $this->getTag())) {
            $bytes += $com->sendWord('.tag=' . $tag);
        }
        foreach ($this->getAllArguments() as $name => $value) {
            $prefix = '=' . $name . '=';
            if (is_string($value)) {
                $bytes += $com->sendWord($prefix . $value);
            } else {
                $bytes += $com->sendWordFromStream($prefix, $value);
            }
        }
        $query = $this->getQuery();
        if ($query instanceof Query) {
            $bytes += $query->send($com);
        }
        $bytes += $com->sendWord('');
        return $bytes;
    }
    
    /**
     * Parses the arguments of a command.
     * 
     * @param string $string The argument string to parse.
     * 
     * @return void
     */
    protected function parseArgumentString($string)
    {
        /*
         * Grammar:
         * 
         * <arguments> := (<<\s+>>, <argument>)*,
         * <argument> := <name>, <value>?
         * <name> := <<[^\=\s]+>>
         * <value> := "=", (<quoted string> | <unquoted string>)
         * <quotedString> := <<">>, <<([^"]|\\")*>>, <<">>
         * <unquotedString> := <<\S+>>
         */
        
        $token = '';
        $name = null;
        while ($string = substr($string, strlen($token))) {
            if (null === $name) {
                if (preg_match('/^\s+([^\s=]+)/sm', $string, $matches)) {
                    $token = $matches[0];
                    $name = $matches[1];
                } else {
                    throw new InvalidArgumentException(
                        "Parsing of argument name failed near '{$string}'", 206
                    );
                }
            } elseif (preg_match('/^\s/sm', $string, $matches)) {
                //Empty argument
                $token = '';
                $this->setArgument($name);
                $name = null;
            } elseif (
                preg_match('/^="(([^\\\"]|\\\"|\\\\)*)"/sm', $string, $matches)
            ) {
                $token = $matches[0];
                $this->setArgument(
                    $name,
                    str_replace(
                        array('\\"', '\\\\'), array('"', '\\'), $matches[1]
                    )
                );
                $name = null;
            } elseif (preg_match('/^=(\S+)/sm', $string, $matches)) {
                $token = $matches[0];
                $this->setArgument($name, $matches[1]);
                $name = null;
            } else {
                throw new InvalidArgumentException(
                    "Parsing of argument value failed near '{$string}'", 207
                );
            }
        }
        
        if (null !== $name && ('' !== ($name = trim($name)))) {
            $this->setArgument($name, '');
        }
        
    }

}