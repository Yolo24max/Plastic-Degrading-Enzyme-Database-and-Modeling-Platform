$inputFile = 'c:\xampp\htdocs\plaszymedb\V9.html'
$outputFile = 'c:\xampp\htdocs\plaszymedb\V9_fixed.html'

$content = Get-Content $inputFile
$outputLines = @()
$foundEnd = $false

foreach ($line in $content) {
    $outputLines += $line
    if ($line -match '</html>') {
        $foundEnd = $true
        break
    }
}

if ($foundEnd) {
    $outputLines | Set-Content $outputFile
    Write-Host "File fixed successfully. Output lines: $($outputLines.Count)"
    Write-Host "Output file: $outputFile"
} else {
    Write-Host "Could not find </html> tag"
}
