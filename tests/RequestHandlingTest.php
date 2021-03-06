<?php
namespace PEAR2\Net\RouterOS;

use PEAR2\Net\Transmitter as T;

class RequestHandlingTest extends \PHPUnit_Framework_TestCase
{

    public function testNonAbsoluteCommand()
    {
        $nonAbsoluteCommands = array(
            'print',
            '',
            'ip arp print',
            'login'
        );
        foreach ($nonAbsoluteCommands as $command) {
            try {
                $invalidCommand = new Request($command);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    202, $e->getCode(),
                    "Improper exception thrown for the command '{$command}'."
                );
            }
        }
    }

    public function testUnresolvableCommand()
    {
        $unresolvableCommands = array(
            '/ip .. ..',
            '/ip .. arp .. arp .. .. print'
        );
        foreach ($unresolvableCommands as $command) {
            try {
                $invalidCommand = new Request($command);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    203, $e->getCode(),
                    "Improper exception thrown for the command '{$command}'."
                );
            }
        }
    }

    public function testInvalidCommand()
    {
        $invalidCommands = array(
            '/ip/arp/ print',
            '/ip /arp /print',
            '/ip /arp /print',
        );
        foreach ($invalidCommands as $command) {
            try {
                $invalidCommand = new Request($command);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    204, $e->getCode(),
                    "Improper exception thrown for the command '{$command}'."
                );
            }
        }
    }

    public function testCommandTranslation()
    {
        $commands = array(
            '/ip arp print' => '/ip/arp/print',
            '/ip arp .. address print' => '/ip/address/print',
            '/queue simple .. tree .. simple print' => '/queue/simple/print',
            '/login goback ..' => '/login'
        );
        $request = new Request('/cancel');
        foreach ($commands as $command => $expected) {
            $request->setCommand($command);
            $this->assertEquals(
                $expected, $request->getCommand(),
                "Command '{$command}' was not translated properly."
            );
        }
    }

    public function testCommandTranslationWithArguments()
    {
        $commands = array(
            '/ip arp print detail=""' => '/ip/arp/print',
            '/ip arp add address=192.168.0.1' => '/ip/arp/add',
            '/ip arp add address="192.168.0.1"' => '/ip/arp/add',
            '/ip arp add comment="hello world"' => '/ip/arp/add',
            '/ip arp add comment=hello world' => '/ip/arp/add',
            
            '/ip arp add address=192.168.0.1 comment=hello world'
                => '/ip/arp/add',
            '/ip arp add address="192.168.0.1" comment=hello world'
                => '/ip/arp/add',
            '/ip arp add address=192.168.0.1 comment="hello world"'
                => '/ip/arp/add',
            '/ip arp add address="192.168.0.1" comment="hello world"'
                => '/ip/arp/add',
            '/ip arp add comment="hello world"' => '/ip/arp/add',
            '/ip arp add comment=hello world' => '/ip/arp/add',
            '/ip arp add address=192.168.0.1 comment=hello big world'
                => '/ip/arp/add',
            '/ip arp add address="192.168.0.1" comment=hello big world'
                => '/ip/arp/add',
            '/ip arp add address=192.168.0.1 comment="hello big world"'
                => '/ip/arp/add',
            '/ip arp add address="192.168.0.1" comment="hello big world"'
                => '/ip/arp/add',
            '/ip arp add comment="hello big world"' => '/ip/arp/add',
            '/ip arp add comment=hello big world' => '/ip/arp/add',
            '/ip arp add comment="\""' => '/ip/arp/add',
            '/ip/arp/add comment="\\\"' => '/ip/arp/add',
            '/ip/arp/add comment="\\\\""' => '/ip/arp/add',
            '/ip/arp/add comment="\~t\"\\\"' => '/ip/arp/add',
            '/ip/arp/add comment="\~t\\\\""' => '/ip/arp/add',
            
            '/ip/arp/print detail=""' => '/ip/arp/print',
            '/ip/arp/add address=192.168.0.1' => '/ip/arp/add',
            '/ip/arp/add address="192.168.0.1"' => '/ip/arp/add',
            '/ip/arp/add comment="hello world"' => '/ip/arp/add',
            '/ip/arp/add comment=hello world' => '/ip/arp/add',
            
            '/ip/arp/add address=192.168.0.1 comment=hello world'
                => '/ip/arp/add',
            '/ip/arp/add address="192.168.0.1" comment=hello world'
                => '/ip/arp/add',
            '/ip/arp/add address=192.168.0.1 comment="hello world"'
                => '/ip/arp/add',
            '/ip/arp/add address="192.168.0.1" comment="hello world"'
                => '/ip/arp/add',
            '/ip/arp/add comment="hello world"' => '/ip/arp/add',
            '/ip/arp/add comment=hello world' => '/ip/arp/add',
            '/ip/arp/add address=192.168.0.1 comment=hello big world'
                => '/ip/arp/add',
            '/ip/arp/add address="192.168.0.1" comment=hello big world'
                => '/ip/arp/add',
            '/ip/arp/add address=192.168.0.1 comment="hello big world"'
                => '/ip/arp/add',
            '/ip/arp/add address="192.168.0.1" comment="hello big world"'
                => '/ip/arp/add',
            '/ip/arp/add comment="hello big world"' => '/ip/arp/add',
            '/ip/arp/add comment=hello big world' => '/ip/arp/add',
            '/ip/arp/add comment="\""' => '/ip/arp/add',
            '/ip/arp/add comment="\\\"' => '/ip/arp/add',
            '/ip/arp/add comment="\\\\""' => '/ip/arp/add',
            '/ip/arp/add comment="\~t\"\\\"' => '/ip/arp/add',
            '/ip/arp/add comment="\~t\\\\""' => '/ip/arp/add',
            
            '/ping address=192.168.0.1' => '/ping',
            '/ping address="192.168.0.1"' => '/ping',
            '/ping address=192.168.0.1 count=2' => '/ping',
            '/ping address="192.168.0.1" count=2' => '/ping',
            '/ping address=192.168.0.1 count="2"' => '/ping',
            '/ping address="192.168.0.1" count="2"' => '/ping'
        );
        foreach ($commands as $command => $expected) {
            $request = new Request($command);
            $this->assertEquals(
                $expected, $request->getCommand(),
                "Command '{$command}' was not parsed properly."
            );
        }
    }

    public function testCommandArgumentParsing()
    {
        $commands = array(
            '/ip arp print detail=""' => array('detail' => ''),
            '/ip arp add address=192.168.0.1'
                => array('address' => '192.168.0.1'),
            '/ip arp add address="192.168.0.1"'
                => array('address' => '192.168.0.1'),
            '/ip arp add comment="hello world"'
                => array('comment' => 'hello world'),
            '/ip arp add comment=hello world'
                => array('comment' => 'hello', 'world' => ''),
            
            '/ip arp add address=192.168.0.1 comment=hello world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'world' => ''
                   ),
            '/ip arp add address="192.168.0.1" comment=hello world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'world' => ''
                   ),
            '/ip arp add address=192.168.0.1 comment="hello world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello world'
                   ),
            '/ip arp add address="192.168.0.1" comment="hello world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello world'
                   ),
            '/ip arp add comment="hello world"'
                => array('comment' => 'hello world'),
            '/ip arp add comment=hello world'
                => array('comment' => 'hello', 'world' => ''),
            '/ip arp add address=192.168.0.1 comment=hello big world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'big' => '',
                    'world' => ''
                   ),
            '/ip arp add address="192.168.0.1" comment=hello big world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'big' => '',
                    'world' => ''
                   ),
            '/ip arp add address=192.168.0.1 comment="hello big world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello big world'
                   ),
            '/ip arp add address="192.168.0.1" comment="hello big world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello big world'
                   ),
            '/ip arp add comment="hello big world"'
                => array('comment' => 'hello big world'),
            '/ip arp add comment=hello big world'
                => array('comment' => 'hello', 'big' => '', 'world' => ''),
            '/ip arp add comment="\""'
                => array('comment' => '"'),
            '/ip arp add comment="\\\"'
                => array('comment' => '\\'),
            '/ip arp add comment="\\\""'
                => array('comment' => '\"'),
            '/ip arp add comment="\~t\"\\\"'
                => array('comment' => '\~t"\\'),
            '/ip arp add comment="\~t\\\\""'
                => array('comment' => '\~t\\"'),
            
            '/ip/arp/print detail=""' => array('detail' => ''),
            '/ip/arp/add address=192.168.0.1'
                => array('address' => '192.168.0.1'),
            '/ip/arp/add address="192.168.0.1"'
                => array('address' => '192.168.0.1'),
            '/ip/arp/add comment="hello world"'
                => array('comment' => 'hello world'),
            '/ip/arp/add comment=hello world'
                => array('comment' => 'hello', 'world' => ''),
            
            '/ip/arp/add address=192.168.0.1 comment=hello world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'world' => ''
                   ),
            '/ip/arp/add address="192.168.0.1" comment=hello world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'world' => ''
                   ),
            '/ip/arp/add address=192.168.0.1 comment="hello world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello world'
                   ),
            '/ip/arp/add address="192.168.0.1" comment="hello world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello world'
                   ),
            '/ip/arp/add comment="hello world"'
                => array('comment' => 'hello world'),
            '/ip/arp/add comment=hello world'
                => array('comment' => 'hello', 'world' => ''),
            '/ip/arp/add address=192.168.0.1 comment=hello big world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'big' => '',
                    'world' => ''
                   ),
            '/ip/arp/add address="192.168.0.1" comment=hello big world'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello',
                    'big' => '',
                    'world' => ''
                   ),
            '/ip/arp/add address=192.168.0.1 comment="hello big world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello big world'
                   ),
            '/ip/arp/add address="192.168.0.1" comment="hello big world"'
                => array(
                    'address' => '192.168.0.1',
                    'comment' => 'hello big world'
                   ),
            '/ip/arp/add comment="hello big world"'
                => array('comment' => 'hello big world'),
            '/ip/arp/add comment=hello big world'
                => array('comment' => 'hello', 'big' => '', 'world' => ''),
            '/ip/arp/add comment="\""'
                => array('comment' => '"'),
            '/ip/arp/add comment="\\\"'
                => array('comment' => '\\'),
            '/ip/arp/add comment="\\\""'
                => array('comment' => '\"'),
            '/ip/arp/add comment="\~t\"\\\"'
                => array('comment' => '\~t"\\'),
            '/ip/arp/add comment="\~t\\\\""'
                => array('comment' => '\~t\\"'),
            
            '/ping address=192.168.0.1' => array('address' => '192.168.0.1'),
            '/ping address="192.168.0.1"' => array('address' => '192.168.0.1'),
            '/ping address=192.168.0.1 count=2'
                => array('address' => '192.168.0.1', 'count' => '2'),
            '/ping address="192.168.0.1" count=2'
                => array('address' => '192.168.0.1', 'count' => '2'),
            '/ping address=192.168.0.1 count="2"'
                => array('address' => '192.168.0.1', 'count' => '2'),
            '/ping address="192.168.0.1" count="2"'
                => array('address' => '192.168.0.1', 'count' => '2'),
        );
        foreach ($commands as $command => $expected) {
            $request = new Request($command);
            $this->assertEquals(
                $expected, $request->getAllArguments(),
                "Command '{$command}' was not parsed properly."
            );
        }
    }
    
    public function testCommandArgumentParsingExceptions()
    {
        try {
            $request = new Request('/ip arp add comment="""');
            $this->fail('Command had to fail.');
        }catch(InvalidArgumentException $e) {
            $this->assertEquals(206, $e->getCode(), 'Improper exception code');
        }
        try {
            $request = new Request('/ip arp add comment= address=192.168.0.1');
            $this->fail('Command had to fail.');
        }catch(InvalidArgumentException $e) {
            $this->assertEquals(207, $e->getCode(), 'Improper exception code');
        }
    }

    public function testInvalidArgumentName()
    {
        $invalidNames = array(
            '=',
            '',
            '=eqStart',
            'eq=middle',
            'eqEnd=',
            'name spaced',
            'name with multiple spaces',
            "Two\nLines"
        );
        foreach ($invalidNames as $name) {
            try {
                $request = new Request('/ping');
                $request->setArgument($name);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    200, $e->getCode(),
                    "Improper exception thrown for the name '{$name}'."
                );
            }
        }
    }

    public function testNonSeekableArgumentValue()
    {
        $value = fopen('php://input', 'r');
        $request = new Request('/ping');
        $request->setArgument('address', $value);
        $actual = $request->getArgument('address');
        $this->assertNotSame($value, $actual);
        $this->assertInternalType('string', $actual);
        $this->assertStringStartsWith('Resource id #', $actual);
    }

    public function testInvalidQueryArgumentName()
    {
        $invalidNames = array(
            '=',
            '',
            '=eqStart',
            'eq=middle',
            'eqEnd=',
            'name spaced',
            'name with multiple spaces',
            "Two\nLines"
        );
        foreach ($invalidNames as $name) {
            try {
                $query = Query::where($name);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    200, $e->getCode(),
                    "Improper exception thrown for the name '{$name}'."
                );
            }
        }
    }

    public function testInvalidQueryArgumentAction()
    {
        $invalidActions = array(
            ' ',
            '?',
            '#',
            'address',
            '>=',
            '<=',
            '=>',
            '=<',
            1,
            0
        );
        foreach ($invalidActions as $action) {
            try {
                $query = Query::where('address', null, $action);
            } catch (UnexpectedValueException $e) {
                $this->assertEquals(
                    208, $e->getCode(),
                    "Improper exception thrown for the action '{$action}'."
                );
                $this->assertEquals($action, $e->getValue());
            }
        }
    }

    public function testNonSeekableCommunicatorWord()
    {
        $value = fopen('php://input', 'r');
        $com = new Communicator(HOSTNAME, PORT);
        Client::login($com, USERNAME, PASSWORD);
        try {
            $com->sendWordFromStream('', $value);
            $this->fail('Call had to fail.');
        }catch(InvalidArgumentException $e) {
            $this->assertEquals(10, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testNonSeekableQueryArgumentValue()
    {
        $value = fopen('php://input', 'r');
        $stringValue = (string) $value;
        $query1 = Query::where('address', $stringValue);
        $query2 = Query::where('address', $value);
        $this->assertEquals($query1, $query2);
    }

    public function testArgumentRemoval()
    {
        $request = new Request('/ip/arp/add');
        $this->assertEmpty($request->getAllArguments());

        $request->setArgument('address', HOSTNAME_INVALID);
        $this->assertNotEmpty($request->getAllArguments());
        $this->assertEquals(HOSTNAME_INVALID, $request->getArgument('address'));

        $request->removeAllArguments();
        $this->assertEmpty($request->getAllArguments());
        $this->assertEquals(null, $request->getArgument('address'));

        $request->setArgument('address', HOSTNAME_INVALID);
        $this->assertNotEmpty($request->getAllArguments());
        $this->assertEquals(HOSTNAME_INVALID, $request->getArgument('address'));
        $request->setArgument('address', null);
        $this->assertEmpty($request->getAllArguments());
        $this->assertEquals(null, $request->getArgument('address'));
    }

    public function testLengthEncoding()
    {
        $lengths = array(
            chr(0) => 0,
            chr(0x1) => 0x1,
            chr(0x7E) => 0x7E,
            chr(0x7F) => 0x7F,
            chr(0x80) . chr(0x80) => 0x80,
            chr(0x80) . chr(0x81) => 0x81,
            chr(0xBF) . chr(0xFE) => 0x3FFE,
            chr(0xBF) . chr(0xFF) => 0x3FFF,
            chr(0xC0) . chr(0x40) . chr(0x00) => 0x4000,
            chr(0xC0) . chr(0x40) . chr(0x01) => 0x4001,
            chr(0xDF) . chr(0xFF) . chr(0xFE) => 0x1FFFFE,
            chr(0xDF) . chr(0xFF) . chr(0xFF) => 0x1FFFFF,
            chr(0xE0) . chr(0x20) . chr(0x00) . chr(0x00) => 0x200000,
            chr(0xE0) . chr(0x20) . chr(0x00) . chr(0x01) => 0x200001,
            chr(0xEF) . chr(0xFF) . chr(0xFF) . chr(0xFE) => 0xFFFFFFE,
            chr(0xEF) . chr(0xFF) . chr(0xFF) . chr(0xFF) => 0xFFFFFFF,
            chr(0xF0) . chr(0x10) . chr(0x00) . chr(0x00) . chr(0x00) =>
            0x10000000,
            chr(0xF0) . chr(0x10) . chr(0x00) . chr(0x00) . chr(0x01) =>
            0x10000001,
            chr(0xF0) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFE) =>
            0xFFFFFFFE,
            chr(0xF0) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFF) =>
            0xFFFFFFFF,
            chr(0xF1) . chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00) =>
            0x100000000,
            chr(0xF1) . chr(0x00) . chr(0x00) . chr(0x00) . chr(0x01) =>
            0x100000001,
            chr(0xF7) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFE)
            => 0x7FFFFFFFE,
            chr(0xF7) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFF)
            => 0x7FFFFFFFF
        );
        foreach ($lengths as $expected => $length) {
            $actual = Communicator::encodeLength($length);
            $this->assertEquals(
                $expected, $actual,
                "Length '0x" . dechex($length) .
                "' is not encoded correctly. It was encoded as '0x" .
                bin2hex($actual) . "' instead of '0x" .
                bin2hex($expected) . "'."
            );
        }
    }

    public function testLengthEncodingExceptions()
    {
        $smallLength = -1;
        try {
            Communicator::encodeLength($smallLength);
        } catch (LengthException $e) {
            $this->assertEquals(
                12, $e->getCode(),
                "Length '{$smallLength}' must not be encodable."
            );
            $this->assertEquals(
                $smallLength, $e->getLength(), 'Exception is misleading.'
            );
        }
        $largeLength = 0x800000000;
        try {
            Communicator::encodeLength($largeLength);
        } catch (LengthException $e) {
            $this->assertEquals(
                13, $e->getCode(),
                "Length '{$largeLength}' must not be encodable."
            );
            $this->assertEquals(
                $largeLength, $e->getLength(), 'Exception is misleading.'
            );
        }
    }

    public function testControlByteException()
    {
        $stream = fopen('php://temp', 'r+b');


        $controlBytes = array(
            0xF8,
            0xF9,
            0xFA,
            0xFB,
            0xFC,
            0xFD,
            0xFE,
            0xFF
        );

        foreach ($controlBytes as $controlByte) {
            fwrite($stream, chr($controlByte));
        }
        rewind($stream);
        $trans = new T\Stream($stream);

        foreach ($controlBytes as $controlByte) {
            try {
                Communicator::decodeLength($trans);
            } catch (NotSupportedException $e) {
                $this->assertEquals(
                    14, $e->getCode(), 'Improper exception code.'
                );
                $this->assertEquals(
                    $controlByte, $e->getValue(), 'Improper exception value.'
                );
            }
        }
    }

    public function testLengthDecoding()
    {
        $lengths = array(
            chr(0) => 0,
            chr(0x1) => 0x1,
            chr(0x7E) => 0x7E,
            chr(0x7F) => 0x7F,
            chr(0x80) . chr(0x80) => 0x80,
            chr(0x80) . chr(0x81) => 0x81,
            chr(0xBF) . chr(0xFE) => 0x3FFE,
            chr(0xBF) . chr(0xFF) => 0x3FFF,
            chr(0xC0) . chr(0x40) . chr(0x00) => 0x4000,
            chr(0xC0) . chr(0x40) . chr(0x01) => 0x4001,
            chr(0xDF) . chr(0xFF) . chr(0xFE) => 0x1FFFFE,
            chr(0xDF) . chr(0xFF) . chr(0xFF) => 0x1FFFFF,
            chr(0xE0) . chr(0x20) . chr(0x00) . chr(0x00) => 0x200000,
            chr(0xE0) . chr(0x20) . chr(0x00) . chr(0x01) => 0x200001,
            chr(0xEF) . chr(0xFF) . chr(0xFF) . chr(0xFE) => 0xFFFFFFE,
            chr(0xEF) . chr(0xFF) . chr(0xFF) . chr(0xFF) => 0xFFFFFFF,
            chr(0xF0) . chr(0x10) . chr(0x00) . chr(0x00) . chr(0x00) =>
            0x10000000,
            chr(0xF0) . chr(0x10) . chr(0x00) . chr(0x00) . chr(0x01) =>
            0x10000001,
            chr(0xF0) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFE) =>
            0xFFFFFFFE,
            chr(0xF0) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFF) =>
            0xFFFFFFFF,
            chr(0xF1) . chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00) =>
            0x100000000,
            chr(0xF1) . chr(0x00) . chr(0x00) . chr(0x00) . chr(0x01) =>
            0x100000001,
            chr(0xF7) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFE)
            => 0x7FFFFFFFE,
            chr(0xF7) . chr(0xFF) . chr(0xFF) . chr(0xFF) . chr(0xFF)
            => 0x7FFFFFFFF
        );
        $stream = fopen('php://temp', 'r+b');
        foreach ($lengths as $length) {
            fwrite($stream, Communicator::encodeLength($length));
        }
        rewind($stream);
        $trans = new T\Stream($stream);

        foreach ($lengths as $length => $expected) {
            $this->assertEquals(
                $expected, Communicator::decodeLength($trans),
                "{$length} is not properly decoded."
            );
        }
    }

    public function testQuitMessage()
    {
        $com = new Communicator(HOSTNAME, PORT);
        Client::login($com, USERNAME, PASSWORD);

        $quitRequest = new Request('/quit');
        $quitRequest->send($com);
        $quitResponse = new Response($com);
        $this->assertEquals(
            1, count($quitResponse->getUnrecognizedWords()), 'No message.'
        );
        $this->assertEquals(
            0, count($quitResponse->getAllArguments()),
            'There should be no arguments.'
        );
        $com->close();
    }

    public function testQuitMessageStream()
    {
        $com = new Communicator(HOSTNAME, PORT);
        Client::login($com, USERNAME, PASSWORD);

        $quitRequest = new Request('/quit');
        $quitRequest->send($com);
        $quitResponse = new Response($com, true);
        $this->assertEquals(
            1, count($quitResponse->getUnrecognizedWords()), 'No message.'
        );
        $this->assertEquals(
            0, count($quitResponse->getAllArguments()),
            'There should be no arguments.'
        );
        $com->close();
    }
    
    public function testSetDefaultCharset()
    {
        $com = new Communicator(HOSTNAME, PORT);
        $this->assertNull($com->getCharset(Communicator::CHARSET_REMOTE));
        $this->assertNull($com->getCharset(Communicator::CHARSET_LOCAL));
        Communicator::setDefaultCharset('windows-1251');
        $this->assertNull($com->getCharset(Communicator::CHARSET_REMOTE));
        $this->assertNull($com->getCharset(Communicator::CHARSET_LOCAL));
        
        $com = new Communicator(HOSTNAME, PORT);
        $this->assertEquals(
            'windows-1251', $com->getCharset(Communicator::CHARSET_REMOTE)
        );
        $this->assertEquals(
            'windows-1251', $com->getCharset(Communicator::CHARSET_LOCAL)
        );
        Communicator::setDefaultCharset(
            array(
                Communicator::CHARSET_REMOTE => 'ISO-8859-1',
                Communicator::CHARSET_LOCAL  => 'ISO-8859-1'
            )
        );
        $this->assertEquals(
            'windows-1251', $com->getCharset(Communicator::CHARSET_REMOTE)
        );
        $this->assertEquals(
            'windows-1251', $com->getCharset(Communicator::CHARSET_LOCAL)
        );
        
        $com = new Communicator(HOSTNAME, PORT);
        $this->assertEquals(
            'ISO-8859-1', $com->getCharset(Communicator::CHARSET_REMOTE)
        );
        $this->assertEquals(
            'ISO-8859-1', $com->getCharset(Communicator::CHARSET_LOCAL)
        );
        Communicator::setDefaultCharset(null);
        $this->assertEquals(
            'ISO-8859-1', $com->getCharset(Communicator::CHARSET_REMOTE)
        );
        $this->assertEquals(
            'ISO-8859-1', $com->getCharset(Communicator::CHARSET_LOCAL)
        );
        
        $com = new Communicator(HOSTNAME, PORT);
        $this->assertNull($com->getCharset(Communicator::CHARSET_REMOTE));
        $this->assertNull($com->getCharset(Communicator::CHARSET_LOCAL));
        Communicator::setDefaultCharset(
            'windows-1251', Communicator::CHARSET_REMOTE
        );
        Communicator::setDefaultCharset(
            'ISO-8859-1', Communicator::CHARSET_LOCAL
        );
        $this->assertNull($com->getCharset(Communicator::CHARSET_REMOTE));
        $this->assertNull($com->getCharset(Communicator::CHARSET_LOCAL));
        
        $com = new Communicator(HOSTNAME, PORT);
        $this->assertEquals(
            'windows-1251', $com->getCharset(Communicator::CHARSET_REMOTE)
        );
        $this->assertEquals(
            'ISO-8859-1', $com->getCharset(Communicator::CHARSET_LOCAL)
        );
        Communicator::setDefaultCharset(null);
    }
    
    public function testInvokability()
    {
        $com = new Communicator(HOSTNAME, PORT);
        Client::login($com, USERNAME, PASSWORD);
        $request = new Request('/ping');
        $request('address', HOSTNAME)->setTag('p');
        $this->assertEquals(HOSTNAME, $request('address'));
        $this->assertEquals('p', $request->getTag());
        $this->assertEquals(array('address' => HOSTNAME), $request());
        $request($com);
        $response = new Response($com);
        $this->assertInternalType('array', $response());
        $this->assertEquals(HOSTNAME, $response('host'));
        
        $request = new Request('/queue/simple/print');
        $query = Query::where('target-addresses', HOSTNAME_INVALID . '/32');
        $request($query);
        $this->assertSame($query, $request->getQuery());
        $com('/quit');
        $com('');
    }

    public function testReceivingLargeWords()
    {
        try {
            $com = new Communicator('127.0.0.1', PSEUDO_SERVER_PORT);
        } catch (\Exception $e) {
            $this->markTestSkipped('The testing server is not running.');
        }


        $lengths = array(
            0x1,
            0x7E,
            0x7F,
            0x80,
            0x81,
            0x3FFE,
            0x3FFF,
            0x4000,
            0x4001,
            0x1FFFFE,
            0x1FFFFF,
            0x200000,
            0x200001,
            0xFFFFFFE,
            0xFFFFFFF,
            0x10000000,
            //0x10000001,
            //0xFFFFFFFE,
            //0xFFFFFFFF,
            //0x100000000,
            //0x100000001,
            //0x7FFFFFFFE,
            //0x7FFFFFFFF
        );

        foreach ($lengths as $length) {

            $com->sendWord(
                'r' .
                str_pad(base_convert($length, 10, 16), 9, '0', STR_PAD_LEFT)
            );
            $response = $com->getNextWordAsStream();

            $responseSize = 0;
            while (!feof($response)) {
                $responseSize += strlen(fread($response, 0xFFFFF));
            }
            $this->assertEquals(
                $length, $responseSize, 'Content mismatch!'
            );
        }

        $com->sendWord('q000000000');
        $com->close();
    }

    public function testSendingLargeWords()
    {
        try {
            $com = new Communicator('127.0.0.1', PSEUDO_SERVER_PORT);
        } catch (\Exception $e) {
            $this->markTestSkipped('The testing server is not running.');
        }

        $lengths = array(
            0x1,
            0x7E,
            0x7F,
            0x80,
            0x81,
            0x3FFE,
            0x3FFF,
            0x4000,
            0x4001,
            0x1FFFFE,
            0x1FFFFF,
            0x200000,
            0x200001,
            0xFFFFFFE,
            0xFFFFFFF,
            //0x10000000,
            //0x10000001,
            //0xFFFFFFFE,
            //0xFFFFFFFF,
            //0x100000000,
            //0x100000001,
            //0x7FFFFFFFE,
            //0x7FFFFFFFF
        );

        foreach ($lengths as $length) {

            $com->sendWord(
                's' .
                str_pad(base_convert($length, 10, 16), 9, '0', STR_PAD_LEFT)
            );

            $stream = fopen('php://temp', 'r+b');
            $streamSize = 0;
            while ($streamSize < $length) {
                $streamSize += fwrite(
                    $stream,
                    str_pad('t', min($length - $streamSize, 0xFFFFF), 't')
                );
            }
            rewind($stream);

            $com->sendWordFromStream('', $stream);

            $recvLength = (double) base_convert($com->getNextWord(), 16, 10);
            $this->assertEquals(
                (double) $length, $recvLength, 'Content mismatch!'
            );
        }

        $com->sendWord('q000000000');
        $com->close();
    }

    public function testPrematureDisconnect()
    {
        try {
            $com = new Communicator('127.0.0.1', PSEUDO_SERVER_PORT);
        } catch (\Exception $e) {
            $this->markTestSkipped('The testing server is not running.');
        }

        $com->sendWord('p0000fffff');
        try {
            $com->sendWord(str_pad('t', 0xFFFFF, 't'));
            $this->fail('Sending had to fail.');
        } catch (SocketException $e) {
            $this->assertEquals(2, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testPrematureDisconnectWithStream()
    {
        try {
            $com = new Communicator('127.0.0.1', PSEUDO_SERVER_PORT);
        } catch (\Exception $e) {
            $this->markTestSkipped('The testing server is not running.');
        }

        $com->sendWord('p0000fffff');
        try {
            $stream = fopen('php://temp', 'r+b');
            fwrite($stream, str_pad('t', 0xFFFFF, 't'));
            rewind($stream);
            $com->sendWordFromStream('', $stream);
            $this->fail('Sending had to fail.');
        } catch (SocketException $e) {
            $this->assertEquals(3, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testIncompleteResponse()
    {
        try {
            $com = new Communicator('127.0.0.1', PSEUDO_SERVER_PORT);
        } catch (\Exception $e) {
            $this->markTestSkipped('The testing server is not running.');
        }
        $oldTimeout = ini_set('default_socket_timeout', 2);
        $com->sendWord('i00000000f');
        try {
            $response = $com->getNextWordAsStream();
            ini_set('default_socket_timeout', $oldTimeout);
            $this->fail('Receiving had to fail.');
        } catch (SocketException $e) {
            ini_set('default_socket_timeout', $oldTimeout);
            $this->assertEquals(5, $e->getCode(), 'Improper exception code.');
        }
    }

}