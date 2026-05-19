$admin = Get-Content -Raw -Path "admin/index.php" -Encoding UTF8
$admin_clean = $admin -replace "(?s)^<\?php.*?\?>\s*", ""
$admin_clean = $admin_clean -replace '<\?php\s+echo\s+\$userName;\s+\?>', 'Admin'
$admin_clean = $admin_clean -replace '<\?php\s+echo\s+\$userRole;\s+\?>', 'Admin'
$admin_clean = $admin_clean -replace '<\?php\s+echo\s+\$userEmail;\s+\?>', 'alcaeusposa@gmail.com'
[System.IO.File]::WriteAllText("admin_dashboard.html", $admin_clean, [System.Text.Encoding]::UTF8)
Write-Output "admin_dashboard.html sync complete!"

$client = Get-Content -Raw -Path "client/index.php" -Encoding UTF8
$client_clean = $client -replace "(?s)^<\?php.*?\?>\s*", ""
$client_clean = $client_clean -replace '<\?php\s+echo\s+\$userName;\s+\?>', 'Client User'
$client_clean = $client_clean -replace '<\?php\s+echo\s+\$userRole;\s+\?>', 'client'
$client_clean = $client_clean -replace '<\?php\s+echo\s+\$userEmail;\s+\?>', 'borgir@gmail.com'
$client_clean = $client_clean -replace '<\?php\s+echo\s+\$user_id;\s+\?>', '24'
[System.IO.File]::WriteAllText("client_dashboard.html", $client_clean, [System.Text.Encoding]::UTF8)
Write-Output "client_dashboard.html sync complete!"
