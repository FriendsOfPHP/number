<?php

define('TIME_START', microtime(true));

require __DIR__.'/../../src/Number.php';

/**
 * @internal This file is used by the Number package to generate documentation for the package, using reflection and source code evaluation.
 *           It is not intended to be used outside the package, and it's not designed to be pretty or efficient.
 */

use FriendsOfPhp\Number\Number;

final class DocumentationGenerator
{
    private readonly ReadmeData $readme;
    private readonly MethodExamples $examples;
    private readonly FrontMatter $matter;
    private readonly array $composerData;

    private array $markdownSections = [];
    private string $markdown;

    public function __construct(FrontMatter $matter, MethodExamples $examples)
    {
        $this->examples = $examples;
        $this->matter = $matter;
    }

    public function generate(): void
    {
        $this->loadAndParseReadmeData();
        $this->loadAndParseComposerData();
        $this->assembleDocument();
        $this->compileDocument();
    }

    public function getMarkdown(): string
    {
        return "$this->matter\n$this->markdown";
    }

    private function loadAndParseReadmeData(): void
    {
        $this->readme = new ReadmeData();
    }

    private function loadAndParseComposerData(): void
    {
        $this->composerData = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);
    }

    private function assembleDocument(): void
    {
        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading($this->readme->title . ' - by Friends of PHP', 1),
                $this->readme->description
            )
        );

        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading('Installation', 2),
                $this->generateInstallationMarkdown(),
            )
        );

        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading('Basic Usage', 2),
                [
                    'The Number package provides a single class, `Number`, which can be used to format numbers in a variety of ways. Here are some example, with the full reference below.',
                    $this->readme->getBlock('basic-usage')->getContent(),
                ]
            )
        );

        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading('Full Reference', 2),
                $this->generateMethodDocumentation(),
            )
        );

        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading('License', 2),
                $this->readme->license,
            )
        );

        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading('Attributions', 2),
                $this->readme->attributions,
            )
        );

        $this->addBlock(
            new MarkdownBlock(
                new MarkdownHeading('Contributing', 2),
                $this->readme->contributing ?? 'Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.',
            )
        );
    }

    private function compileDocument(): void
    {
        $this->markdown = implode("\n\n", $this->markdownSections);
    }

    private function addBlock(MarkdownBlock $block): void
    {
        $this->markdownSections[] = $block;
    }

    private function generateInstallationMarkdown(): array
    {
        return [
            'Install the package using Composer:',
            new MarkdownCodeBlock("composer require {$this->composerData['name']}", 'bash'),
        ];
    }

    private function generateMethodDocumentation(): string
    {
        return (new MethodDocumentationGenerator($this->examples))->generate();
    }
}

/**
 * Data object for the Readme
 *
 * @property-read string $title
 * @property-read string $description
 * @property-read string $license
 * @property-read string $attributions
 */
final class ReadmeData
{
    private readonly string $contents;
    private readonly array $lines;
    private readonly array $blocks;
    private array $data = [];

    public function __construct()
    {
        $this->contents = file_get_contents(__DIR__.'/../../README.md');
        $this->lines = explode("\n", $this->contents);
        $this->parseReadme();
        $this->parseData();
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function getBlock(string $id): MarkdownBlock
    {
        return $this->blocks[$id];
    }

    private function parseReadme(): void
    {
        // Each block starts with a heading, and ends with the next heading or the end of the file.
        $blocks = [];
        $currentBlock = null;
        foreach ($this->lines as $line) {
            if (str_starts_with($line, '#')) {
                if ($currentBlock) {
                    $blocks[] = $currentBlock;
                }
                $currentBlock = new MarkdownBlock(
                    new MarkdownHeading(
                        trim($line, '# '),
                        substr_count($line, '#')
                    ),
                    ''
                );
            } else {
                $currentBlock->addLine($line);
            }
        }

        // Add the last block
        $blocks[] = $currentBlock;

        // Iterate over blocks to set string identifiers
        $parsedBlocks = [];
        foreach ($blocks as $block) {
            // kebab-case of title
            $id = strtolower(str_replace(' ', '-', $block->getHeading()->getText()));
            // If id is already in use, append a number
            if (isset($parsedBlocks[$id])) {
                $number = 1;
                while (isset($parsedBlocks[$id.'-'.$number])) {
                    $number++;
                }
                $id .= '-'.$number;
            }
            $parsedBlocks[$id] = $block;
        }

        $this->blocks = $parsedBlocks;
    }

    private function parseData(): void
    {
        $this->data['title'] = $this->blocks[array_key_first($this->blocks)]->getHeading()->getText();
        $this->data['description'] = $this->blocks[array_key_first($this->blocks)]->getContent();
        $this->data['license'] = $this->blocks['license']->getContent();
        $this->data['attributions'] = $this->blocks['attributions']->getContent();
    }
}

/** Represents a Markdown section */
final class MarkdownBlock implements Stringable
{
    private MarkdownHeading $heading;
    private string $content;

    public function __construct(MarkdownHeading $heading, string|array $content)
    {
        $this->heading = $heading;
        $this->content = trim(is_array($content) ? implode("\n\n", $content) : $content);
    }

    public function __toString(): string
    {
        return $this->heading."\n\n".$this->getContent();
    }

    public function addLine(string $line): void
    {
        $this->content .= $line."\n";
    }

    public function getHeading(): MarkdownHeading
    {
        return $this->heading;
    }

    public function getContent(): string
    {
        return trim($this->content);
    }
}

/** Represents a Markdown heading */
final class MarkdownHeading implements Stringable
{
    private string $text;
    private int $level;

    public function __construct(string $text, int $level)
    {
        $this->text = $text;
        $this->level = $level;
    }

    public function __toString(): string
    {
        return str_repeat('#', $this->level).' '.$this->text;
    }

    public function getText(): string
    {
        return $this->text;
    }
}

/** Represents a Markdown code block */
final class MarkdownCodeBlock implements Stringable
{
    private string $code;
    private string $language;

    public function __construct(string|array $code, string $language = '')
    {
        $this->code = trim(is_array($code) ? implode("\n", $code) : $code);
        $this->language = $language;
    }

    public function __toString(): string
    {
        return '```'.$this->language."\n".$this->code."\n```";
    }
}

/** Generates method documentation for the Number class */
final class MethodDocumentationGenerator
{
    private readonly ReflectionClass $reflectionClass;
    private readonly MethodExamples $examples;
    /** @var array<string, ReflectionMethod> */
    private array $methodsToDocument;
    /** @var array<string, MarkdownBlock> */
    private array $methodDocumentation;

    public function __construct(MethodExamples $examples)
    {
        $this->reflectionClass = new ReflectionClass(Number::class);
        $this->examples = $examples;
    }

    public function generate(): string
    {
        $this->discoverMethodsToDocument();
        $this->generateMethodsDocumentation();

        return $this->compile();
    }

    private function discoverMethodsToDocument(): void
    {
        $this->methodsToDocument = [];
        foreach ($this->reflectionClass->getMethods() as $method) {
            if ($method->isPublic() && !$method->isConstructor() && !$method->isDestructor()) {
                $this->methodsToDocument[$method->getName()] = $method;
            }
        }
    }

    private function generateMethodsDocumentation(): void
    {
        $this->methodDocumentation = [];
        foreach ($this->methodsToDocument as $methodName => $method) {
            $this->methodDocumentation[$methodName] = $this->generateMethodDocumentation($method);
        }
    }

    private function generateMethodDocumentation(ReflectionMethod $method): MarkdownBlock
    {
        $phpDoc = new PHPDoc($method->getDocComment());
        $examples = $this->examples->getExamplesForMethod($method->getName());

        return new MarkdownBlock(
            new MarkdownHeading("`Number::{$method->getName()}()`", 3),
            [
                $phpDoc->description,
                new MarkdownCodeBlock($this->generateMethodSignature($method, $phpDoc), 'php'),
                $examples ? new MarkdownBlock(
                    new MarkdownHeading('Usage', 4),
                    new MarkdownCodeBlock($examples, 'php')
                ) : null,
            ]
        );
    }

    private function generateMethodSignature(ReflectionMethod $method, PHPDoc $phpDoc): string
    {
        return sprintf(
            "Number::%s(%s): %s",
            $method->getName(),
            implode(', ', array_map(function (ReflectionParameter $parameter) use ($phpDoc): string {
                return $this->generateParameterList($parameter, $phpDoc);
            }, $method->getParameters())),
            $phpDoc->returnType ?? $method->getReturnType() ?? 'mixed'
        );
    }

    private function compile(): string
    {
        $markdown = [];
        foreach ($this->methodDocumentation as $method) {
            $markdown[] = $method;
        }
        return implode("\n\n", $markdown);
    }

    private function generateParameterList(ReflectionParameter $parameter, PHPDoc $phpDoc): string
    {
        $type = $parameter->getType();
        if ($type) {
            $type = method_exists($type, 'getTypes') ? implode('|', $type->getTypes()) : $type->getName();
        }
        $docParam = $phpDoc->params[$parameter->getName()] ?? null;
        if ($docParam) {
            $type = $docParam;
        }

        $addNullShorthand = ($parameter->isOptional() && $parameter->allowsNull()) && !str_contains($type, 'null');
        $typeString = ($addNullShorthand ? '?' : '') . $type . ' ';

        return $typeString . '$' . $parameter->getName();
    }
}

/**
 * Represents a PHPDoc comment
 *
 * @property-read string $comment
 * @property-read string $description
 * @property-read string|null $returnType
 * @property-read array<string, string> $params
 * @property-read array<string, string> $extraTags
 */
final class PHPDoc
{
    private string $comment;
    private string $description;
    private ?string $returnType = null;
    private array $params = [];
    private array $extraTags = [];

    public function __construct(string $comment)
    {
        $this->comment = self::stripCommentDirectives($comment);
        $this->parseTags();
    }

    private function parseTags(): void
    {
        $lines = explode("\n", $this->comment);
        $description = '';
        foreach ($lines as $line) {
            if (str_starts_with($line, '@')) {
                $parts = explode(' ', $line);
                $tag = substr(array_shift($parts), 1);

                if ($tag === 'return') {
                    $this->returnType = array_shift($parts);
                    continue;
                }

                if ($tag === 'param') {
                    $paramName = trim($parts[1], '$');
                    $paramType = $parts[0];

                    $this->params[$paramName] = $paramType;

                    continue;
                }

                $tagId = $tag;
                if (isset($this->extraTags[$tagId])) {
                    $count = 1;
                    while (isset($this->extraTags[$tagId.'-'.$count])) {
                        $count++;
                    }
                    $tagId .= '-'.$count;
                }
                $this->extraTags[$tagId] = implode(' ', $parts);
            } else {
                $description .= $line."\n";
            }
        }
        if ($description) {
            $this->description = trim($description);
        }
    }

    public function __get(string $name): null|string|array
    {
        return $this->{$name} ?? $this->extraTags[$name] ?? null;
    }

    private static function stripCommentDirectives(string $comment): string
    {
        return trim(implode("\n", array_map(function (string $line): string {
            return trim(str_replace(['*', '/'], '', $line));
        }, explode("\n", $comment))));
    }
}

/** Front matter container */
class FrontMatter implements Stringable
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __toString(): string
    {
        return sprintf("---\n%s\n---\n", implode("\n", array_map(
            fn (string $key, string $value): string => "$key: $value",
            array_keys($this->data),
            $this->data
        )));
    }
}

/** Parses the examples returned by the examples function to a more usable format */
final class MethodExamples
{
    private array $examples;

    /** @param array $examples Array of the return values */
    public function __construct(array $examples)
    {
        $this->examples = $this->parseExamples($examples);
    }

    public function getExamplesForMethod(string $method): array
    {
        return array_filter($this->examples, function (Example $example) use ($method) {
            return str_starts_with($example->source, "Number::$method");
        });
    }

    private function parseExamples(array $input): array
    {
        $contents = explode("\n", file_get_contents(__FILE__));
        $functionCallLine = debug_backtrace()[1]['line'] - count($input);
        $examples = [];

        foreach ($input as $index => $result) {
            $source = trim($contents[$functionCallLine + $index], "\t\n\r\0\x0B, ");
            $examples[] = new Example($source, $result);
        }

        return $examples;
    }
}

/** Represents a method example */
final class Example implements Stringable
{
    public readonly string $source;
    public readonly string $result;

    public function __construct($source, $result)
    {
        $this->source = $source;
        $this->result = $result;
    }

    public function __toString(): string
    {
        return "echo $this->source; // $this->result";
    }
}

// Helper functions

function dd(...$data): void
{
    var_dump(...$data) && die;
}

function handleOutput(DocumentationGenerator $generator): void
{
    global $argv;
    if (in_array('--output', $argv)) {
        $outputIndex = array_search('--output', $argv);
        $outputPath = $argv[$outputIndex + 1] ?? null;

        file_put_contents($outputPath, $generator->getMarkdown());
        echo "Wrote documentation to $outputPath\n";
    } else {
        echo "No output path specified, displaying raw contents\n\n---\n\n";
        echo $generator->getMarkdown() . "\n\n";
    }
}

function finishUp(DocumentationGenerator $generator): void
{
    echo sprintf("\033[32mAll done!\033[0m Generated in: %sms (SHA1: %s)\n", Number::format((microtime(true) - TIME_START) * 1000), sha1($generator->getMarkdown()));
}

// Run the generator

$generator = new DocumentationGenerator(new FrontMatter([
    'title' => 'Documentation',
    'navigation.title' => 'Documentation',
]), new MethodExamples([
    Number::format(1234567.89),
    Number::spell(1234),
    Number::ordinal(42),
    Number::percentage(0.75),
    Number::currency(1234.56, 'EUR'),
    Number::fileSize(1024),
    Number::forHumans(1234567.89),
]));

$generator->generate();

handleOutput($generator);
finishUp($generator);
