param(
    [string] $Version = '',
    [string] $OutputPath = ''
)

$ErrorActionPreference = 'Stop'

$workspace = Split-Path -Parent $PSScriptRoot
$pluginSlug = 'der-flugschreiber-subscriptions'
$sourceRoot = Join-Path $workspace $pluginSlug
$mainFile = Join-Path $sourceRoot "$pluginSlug.php"

if (-not (Test-Path -LiteralPath $mainFile -PathType Leaf)) {
    throw "Main plugin file not found: $mainFile"
}

$mainContents = Get-Content -LiteralPath $mainFile -Raw

if (-not $Version) {
    $match = [regex]::Match($mainContents, '(?m)^\s*\*\s*Version:\s*([0-9A-Za-z.+-]+)\s*$')

    if (-not $match.Success) {
        throw 'Could not determine the plugin version from the main file.'
    }

    $Version = $match.Groups[1].Value
}

if (-not $OutputPath) {
    $OutputPath = Join-Path $workspace "$pluginSlug.zip"
}

$OutputPath = [System.IO.Path]::GetFullPath($OutputPath)
$releaseFiles = @(
    'assets',
    'includes',
    'languages',
    'templates',
    "$pluginSlug.php",
    'readme.txt',
    'uninstall.php'
)

$files = foreach ($relativePath in $releaseFiles) {
    $path = Join-Path $sourceRoot $relativePath

    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required release path not found: $path"
    }

    $item = Get-Item -LiteralPath $path

    if ($item.PSIsContainer) {
        Get-ChildItem -LiteralPath $path -Recurse -File
    } else {
        $item
    }
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

if (Test-Path -LiteralPath $OutputPath) {
    Remove-Item -LiteralPath $OutputPath -Force
}

$stream = [System.IO.File]::Open($OutputPath, [System.IO.FileMode]::CreateNew)
$archive = [System.IO.Compression.ZipArchive]::new(
    $stream,
    [System.IO.Compression.ZipArchiveMode]::Create,
    $false
)

try {
    foreach ($file in $files) {
        $relative = $file.FullName.Substring($sourceRoot.Length).TrimStart('\', '/')
        $entryName = "$pluginSlug/$($relative.Replace('\', '/'))"
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $file.FullName,
            $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
    }
} finally {
    $archive.Dispose()
    $stream.Dispose()
}

$archive = [System.IO.Compression.ZipFile]::OpenRead($OutputPath)

try {
    $entries = @($archive.Entries)
    $rootPrefix = "$pluginSlug/"
    $mainEntry = "$rootPrefix$pluginSlug.php"
    $forbiddenPatterns = @(
        '(^|/)tests/',
        '(^|/)\.git/',
        '(^|/)\.tools/',
        '(^|/)\.release-',
        '(^|/)\.zip-check',
        '\.zip$',
        '\.phpunit\.result\.cache$',
        'phpunit\.xml'
    )

    if (-not $entries.Count) {
        throw 'Release archive is empty.'
    }

    if (-not ($entries.FullName -contains $mainEntry)) {
        throw "Main plugin entry is missing: $mainEntry"
    }

    foreach ($entry in $entries) {
        if ($entry.FullName.Contains('\')) {
            throw "Invalid Windows path separator in ZIP entry: $($entry.FullName)"
        }

        if (-not $entry.FullName.StartsWith($rootPrefix, [System.StringComparison]::Ordinal)) {
            throw "ZIP entry is outside the required plugin directory: $($entry.FullName)"
        }

        foreach ($pattern in $forbiddenPatterns) {
            if ($entry.FullName -match $pattern) {
                throw "Forbidden release entry: $($entry.FullName)"
            }
        }
    }
} finally {
    $archive.Dispose()
}

$checkRoot = Join-Path ([System.IO.Path]::GetTempPath()) "$pluginSlug-release-check-$([guid]::NewGuid().ToString('N'))"

try {
    [System.IO.Compression.ZipFile]::ExtractToDirectory($OutputPath, $checkRoot)
    $extractedMainFile = Join-Path $checkRoot "$pluginSlug/$pluginSlug.php"

    if (-not (Test-Path -LiteralPath $extractedMainFile -PathType Leaf)) {
        throw "Extracted archive does not contain the main plugin file: $extractedMainFile"
    }

    $php = Get-Command php -ErrorAction SilentlyContinue

    if ($php) {
        foreach ($phpFile in Get-ChildItem -LiteralPath $checkRoot -Recurse -Filter '*.php' -File) {
            $lintOutput = & $php.Source -l $phpFile.FullName 2>&1

            if ($LASTEXITCODE -ne 0) {
                throw "PHP lint failed for $($phpFile.FullName): $lintOutput"
            }
        }
    }
} finally {
    if (Test-Path -LiteralPath $checkRoot) {
        Remove-Item -LiteralPath $checkRoot -Recurse -Force
    }
}

$hash = (Get-FileHash -LiteralPath $OutputPath -Algorithm SHA256).Hash
$size = (Get-Item -LiteralPath $OutputPath).Length

[pscustomobject]@{
    Path = $OutputPath
    Version = $Version
    Entries = $entries.Count
    Bytes = $size
    SHA256 = $hash
}
