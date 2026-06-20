param(
	[string]$Version
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$mainFile = Join-Path $root 'wp-event-monitor.php'
$zipPath = Join-Path $root 'wp-event-monitor.zip'
$slug = 'wp-event-monitor'

if (-not (Test-Path -LiteralPath $mainFile)) {
	throw "Main plugin file not found: $mainFile"
}

$main = Get-Content -Raw -LiteralPath $mainFile

if ($Version) {
	$main = [regex]::Replace(
		$main,
		'(\* Version:\s*)[0-9A-Za-z\.\-\+]+',
		[System.Text.RegularExpressions.MatchEvaluator] { param($match) $match.Groups[1].Value + $Version },
		1
	)
	$main = [regex]::Replace(
		$main,
		"(define\(\s*'WEM_VERSION'\s*,\s*')[^']+('\s*\);)",
		[System.Text.RegularExpressions.MatchEvaluator] { param($match) $match.Groups[1].Value + $Version + $match.Groups[2].Value },
		1
	)
	Set-Content -LiteralPath $mainFile -Value $main -Encoding UTF8
}

$main = Get-Content -Raw -LiteralPath $mainFile
$headerVersion = [regex]::Match($main, '\* Version:\s*([^\r\n]+)').Groups[1].Value.Trim()
$constantVersion = [regex]::Match($main, "define\(\s*'WEM_VERSION'\s*,\s*'([^']+)'\s*\);").Groups[1].Value.Trim()

if (-not $headerVersion) {
	throw 'Plugin header Version is missing.'
}

if ($headerVersion -ne $constantVersion) {
	throw "Version mismatch: header is $headerVersion, WEM_VERSION is $constantVersion."
}

$requiredHeader = @(
	'Plugin Name:',
	'Version:',
	'Update URI:',
	'Text Domain:'
)

foreach ($field in $requiredHeader) {
	if ($main -notmatch [regex]::Escape($field)) {
		throw "Required plugin header field is missing: $field"
	}
}

if (Test-Path -LiteralPath $zipPath) {
	Remove-Item -LiteralPath $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$includeFiles = @(
	'.editorconfig',
	'CHANGELOG.md',
	'uninstall.php',
	'wp-event-monitor.php'
)

$includeDirs = @(
	'admin',
	'includes',
	'languages'
)

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

try {
	foreach ($file in $includeFiles) {
		$fullPath = Join-Path $root $file
		if (-not (Test-Path -LiteralPath $fullPath)) {
			throw "Build file is missing: $file"
		}

		$entryName = "$slug/$file"
		[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $fullPath, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
	}

	foreach ($dir in $includeDirs) {
		$dirPath = Join-Path $root $dir
		if (-not (Test-Path -LiteralPath $dirPath)) {
			throw "Build directory is missing: $dir"
		}

		Get-ChildItem -LiteralPath $dirPath -Recurse -File | ForEach-Object {
			$relativePath = $_.FullName.Substring($root.Length + 1).Replace('\', '/')
			$entryName = "$slug/$relativePath"
			[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
		}
	}
}
finally {
	$zip.Dispose()
}

$checkZip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
	$mainEntry = $checkZip.Entries | Where-Object { $_.FullName -eq "$slug/wp-event-monitor.php" } | Select-Object -First 1
	if (-not $mainEntry) {
		throw "Zip does not contain $slug/wp-event-monitor.php"
	}

	$badEntry = $checkZip.Entries | Where-Object { $_.FullName -match '\\' } | Select-Object -First 1
	if ($badEntry) {
		throw "Zip contains a Windows-style path: $($badEntry.FullName)"
	}
}
finally {
	$checkZip.Dispose()
}

Get-Item -LiteralPath $zipPath | Select-Object FullName, Length, LastWriteTime
