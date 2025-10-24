$content = Get-Content 'c:\xampp\htdocs\plaszymedb\V9.html'
$endIndex = -1
for ($i = 0; $i -lt $content.Length; $i++) {
    if ($content[$i] -match '</html>') {
        $endIndex = $i
        break
    }
}

if ($endIndex -gt 0) {
    $cleanContent = $content[0..$endIndex]
    $cleanContent | Set-Content 'c:\xampp\htdocs\plaszymedb\V9_temp.html'
    Write-Host "File cleaned. Lines: $($endIndex + 1)"
} else {
    Write-Host "Could not find </html> tag"
}
