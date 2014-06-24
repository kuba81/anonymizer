Anonymizer is a simple helper that allows on-the-fly creation of interfaces and abstract classes.

Right now it’s pretty crude and simple, and it could certainly do with some refactoring (which
I’ll eventually get around to), but it does the job.

Sample usage:
``` php
interface Writer {
  public function write($message);
}

$writer = \Anonymizer\Anonymizer::generate('Writer', [
  'write' => function ($message) {
    echo "{$message}\n";
  }
]);

$writer instanceof Writer // true
$writer->write('x'); // outputs: “x”
```

Using this little trick it’s possible to call methods of the interface from within itself:
``` php
interface Writer {
  public function write($message);
  public function writeln($message);
}

$writer = \Anonymizer\Anonymizer::generate('Writer', [
  'write' => function ($message) {
    echo "{$message}";
  },
  'writeln' => function ($message) use (& $writer) {
    $writer->write($message . PHP_EOL);
  }
]);

$writer->writeln('x'); // outputs “x” using write()
```

Instantiating abstract classes is also possible:
``` php
abstract class Test {
    abstract public function a();
    public function b() {
        echo "b\n";
    }
}

/** @var Test $test */
$test = \Anonymizer\Anonymizer::generate('Test', [
    'a' => function () {
        echo "a\n";
    }
]);

$test->a(); // outputs: “a“
$test->b(); // outputs: “b“
```