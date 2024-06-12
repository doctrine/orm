<?php

use phpDocumentor\Reflection\DocBlock\Tags\Var_;

if (!isset($argv[1])) {
    die("Usage: extract.php source-path output-dir\n");
}
$directory = realpath($argv[1]);
$targetDir = $argv[2];
@mkdir($targetDir, recursive: true);
if (!is_dir($targetDir)) {
    die("Could not create target directory\n");
}

/**
 * @var array<string, list<list<string>>> $blocks
 */
$blocks = [];


/**
 * @return array<string, list<list<string>>>
 */
function extractBlocksFromDirectory(string $path): array {
    $result = [];
    /**
     * @var SplFileInfo $fileInfo
     */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $fileInfo) {
        if (!str_ends_with($fileInfo->getPathname(), ".rst")) {
            continue;
        }
        $result[$fileInfo->getPathname()] = extractBlocksFromFile($fileInfo);
    }

    return $result;
}


function readBlock($handle, int $indent): array {
    $result  = [];
    while(false !== ($line = fgets($handle)) && preg_match("/^\s{{$indent}}(.*)|^()$/", $line, $matches)) {
        $result[] = ($matches[1] ?? ''); // . "\t\t\t\t# Extracted from line $i";
    }
    return $result;
}
/**
 * @return list<list<string>>
 */
function extractBlocksFromFile(SplFileInfo $fileInfo): array
{
    $result = [];
    $handle = fopen($fileInfo->getPathname(), 'r', false);
    if ($handle === false) {
        fwrite(STDERR, "Failed to open file {$fileInfo->getFilename()}\n");
        return [];
    }

    $lineNumber = 1;
    while(!feof($handle)) {
        $line = fgets($handle);
        if (preg_match('/^(\s*)(<\?php.*)/', $line, $matches)) {
            $indent = strlen($matches[1]);
            $blockContents = readBlock($handle, $indent, $lineNumber, $matches[2]);
            $block = [
                $matches[2],
                ...$blockContents
            ];
            $result["line$lineNumber"] = $block;
            $lineNumber += count($blockContents) + 1;
        }
        $lineNumber++;
    }
    return $result;
}

$blocks = extractBlocksFromDirectory($directory);
$identifyBadBlocks = false;
/**
 * This is a list of md5 hashes of examples in the docs that have syntax errors.
 * We ignore them as a kind of baseline.
 */
$badBlocks = [
    "c76a7b29e6f1dff46b58453e9627532a",
    "541f1129625a6d096f7577ddee67dde0",
    "155be0e25d9c0eec47abbbc2cca3989a",
    "ad5c7a0ddb65990eb15081984baf4b9c",
    "25ffce18875a32bb5fa38f4de6e76122",
    "7e30ef71c83f33788ceb53d417c79880",
    "0139765cb0cc865f199bcab1a12152b0",
    "6ddcbe78abe8aff0a77f166cc22c4408",
    "8c015584d0d4ad61ed301d3c56535db4",
    "99745199fd0e6bb37e36eb6aec4ca1b1",
    "309638bebe1ea8319ccf70bdaaa3591c",
    "53f4d71e79bc1779f44f5bf8ea1c99e2",
    "a4ee414d1e19ed3def54bd179a3010b4",
    "843c4b3b54da61d22a614dd247791562",
    "a0bbb36011ea1f87ac7317c7fb61d00f",
    "9691825222e6af51815155f3734c7a22",
    "32c7ae1c7d23fdb10df7e9645e79d70a",
    "4a034b7b118868300f3efa58414f58ea",
    "44479e009b1a72f59a3ece9745fae0de",
    "246956f226e3c8c23af7791f7d0b095e",
    "19987bdc872a4bc756bff62504fa8216",
    "0eba33bed50a6ac105083d07e30285ab",
    "7758d82a3083507dd5f78bfd48c79cb9",
    "e2908b905eee5883a5bae8dec908f270",
    "973a1a7163945e0a2e9d247668a7ef5d",
    "7ed0c11408ecce0b2b0f2b7e42c830b5",
    "b35871afed2a4faf5544058c356adfee",
    "82b474c8aee8e2ef7fd17d4bc1f979da",
    "279e8ab796574234662e74b24b1e1420",
    "c50a99477efdab10094083fe55c5e369",
    "d89e2f9377262b68d91fb4b756560cb5",
    "fae8ded0a1a77ebf11582d3fdcfcb3a6",
    "7a2191cb6a602310a20e29aaf8afa229",
    "e1e9b94ff08d17a138311357bde8ef24",
    "f622bf266cf17513aea1d2ebdf8d6149",
    "ce68ccf9457bc185a618b74187d4979f",
    "de513ee2dd52ce8e3386b5c46380cd79",
    "a02700783d1d6007f61db7cd9c0d239f",
    "8575364649a6b4c1f955d86c675ea78b",
    "b30201b6deb47a76840f29f3e1788492",
    "f2b4ede9ebedcfc0692571bb27602443",
    "786cf395b2b8b105e6970f06b5ce0591",
    "14a0db8a541cbb6bfe7e993711b734a3",
    "f182e5d8a61bd364834ba8c98ee48e26",
    "0de0ea05fc2dd9a307b0ad966f90a1ef",
    "5f5db9578313b9529cb869d3b811c648",
    "1ebce6c3124fe31138a6b1ecdd132888",
    "d9086e1756deae90af5c3e4c3d0d2a34",
    "f8bc20d21a34e281ccd75b622824129e",
    "93399561960626760110036f84d4a4a6",
    "4d823dda8228ba30a1bf4c2adc517c5b",
    "fe45940420c2dc6883721d9d50bafa2b",
    "b1d51b18bec1a40ec35312ee862d81e0",
    "e514033f4c4e35bed35296b078a61fe9",
    "5514b7a02cec50619c2ce4a82daf47dc",
    "a91b62b3f1f97ec9ca2ab888996f5a78",
    "8e95061931013203c345e1c08092b767",

    // Added manually, non-ignorable phpstan errors, but no php syntax errors.
    "ef84da83c2312775fcb0b859ec9d8329",
    "05da9f82c7be2452c540626dd873f50c",
    "f5ea05d484f39386fb18ef7d10a410d1",
    "797b4946c9a49e9d564f61016cb5772e",
    "def6381fee62cf4b25e499f712cac53fe",
    "cdcbdb9bc2a1c1cab763e3a94c6452ca",
    "75a1f8de78b9d755f160cd6d00ce99a1",
    "d70c86cee2a90e6ee28835762a8c86f5",
    "78f2682d534b2fdf059cf91df1d8d0c5",
    "2be2aecf652e9d18d02d9b870380d935",
    "0f793d3b07a521f9c622eafa00abb329",
    "02609e5b8603e3b1f4d33c9130ec77be",
    "38477822a20a9a3230e43da62f33bfcf",
    "90ef6f399c3c617fdb8d6216a75b0ff8",
    "d82c7b849042ff84a98cf1edb1cc5244",
    "05da9f82c7be2452c540626dd873f50c",
    "86ac896f9d474e7eb0a988eebe5b4940",
    "34d90436df75d979f1343c2c4b87fb6d",
    "a341d8f16350e38f86923c7e8a0710ac",
    "0de9143c9a4d3ce7ca3dbe5f0392d0a4",
    "52ca68dbf88f62db2cc02034cb534062",
    "c03f74b6f16b8dd9b1a9a3c1a81893fe",
    "ef6381fee62cf4b25e499f712cac53fe"
];
if ($identifyBadBlocks) {
    $badBlocks = [];
}
foreach($blocks as $sourceFile => $fileBlocks) {
    $baseFileName = substr(basename($sourceFile), 0, -4);
    foreach($fileBlocks as $startLine => $block) {
        $contents = implode("\n", $block);
        $pathName = "{$targetDir}/{$baseFileName}_{$startLine}.php";
        if (in_array(md5($contents), $badBlocks)) {
            continue;
        }
        file_put_contents($pathName, $contents);
        if ($identifyBadBlocks) {
            exec("php -l $pathName 2>&1", result_code: $result_code);
            if ($result_code !== 0) {
                $badBlocks[] = md5($contents);
            }
        }
    }
}

if ($identifyBadBlocks) {
    echo json_encode($badBlocks, JSON_PRETTY_PRINT);
}