<!DOCTYPE html>
<html lang="en">
<head>
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <div class="container">
            <div class="row mt-5">
                <div class="col-md-3"></div>
                <div class="col-md-3">
                    <input class="form-control" type="file" name="upload">
                </div>
                <div class="col-md-1">
                    <input class="btn btn-success" type="submit" name="sub" value="Upload">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#myModal">
                        Create Folder
                    </button>
                </div>
            </div>
        </div>

        <!-- The Modal -->
        <div class="modal fade" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Create Folder</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <input class="form-control" type="text" name="folderName" placeholder="Folder Name"> <br>
                <input class="btn btn-success float-end" type="submit" name="create" value="Create">
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>

            </div>
        </div>
        </div>
    </form>
</body>
</html>

<?php
$ClientId = "69795139151-1u5ueihm422l871fr9753s330625rf1e.apps.googleusercontent.com";
$ClientSecret = "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";
date_default_timezone_set('Asia/Karachi');

if (isset($_GET["code"]) && !isset($_COOKIE['GoogleDriveToken'])) {
    $Curl = curl_init("https://oauth2.googleapis.com/token");

    $Param = [
        "client_id" => $ClientId,
        "client_secret" => $ClientSecret,
        "code" => $_GET["code"],
        "grant_type" => "authorization_code", 
        "redirect_uri" => "http://localhost/new_work/Verge/NewTasks/GoogleDrive/GoogleDriveAuth.php",
    ];

    $Header = ["Content-Type" => "application/x-www-form-urlencoded"];

    $Response = json_decode(CURL ("POST", $Curl, $Param, $Header), true);
    $TokenData = json_encode([ "access_token" => $Response['access_token'], "refresh_token" => $Response['refresh_token'], "expires_in" => time() + $Response['expires_in'] ]);
    setcookie('GoogleDriveToken', $TokenData, time() + (86400 * 30), '/');
} else {
    $Token_Data = json_decode($_COOKIE['GoogleDriveToken'], true);
    $sFolderid = "root";
    if(isset($_GET["Folderid"]) && $_GET["Folderid"] != "") $sFolderid = $_GET["Folderid"];

    if (time() > $Token_Data['expires_in']) {
        $Curl = curl_init("https://oauth2.googleapis.com/token");
        $Param = [
            "client_id" => $ClientId,
            "client_secret" => $ClientSecret,
            "refresh_token" => $Token_Data["refresh_token"],
            "grant_type" => "refresh_token", 
        ];
        $Header = ["Content-Type" => "application/x-www-form-urlencoded"];
        $Response = json_decode(CURL ("POST", $Curl, $Param, $Header), true);
        $Token_Data["access_token"] = $Response["access_token"];
        $Token_Data["expires_in"] = time() + $Response["expires_in"];
        setcookie('GoogleDriveToken', json_encode($Token_Data), time() + (86400 * 30), '/');
    }

    if (isset($_POST["sub"])) {
        
        $sFolderPath = "";
        if(isset($_GET["FolderRoute"]) && $_GET["FolderRoute"] != ""){
            $sFolderPath =  $_GET["FolderRoute"];
        }
        $file = $_FILES["upload"];
        $dir = "IMG";
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $fileName = rand(0000,9999) . "_" . str_replace(" ", "", $file["name"]);
        $filePath = $dir . "/" . $fileName;
        $fileTmp_Name = $file["tmp_name"];
        move_uploaded_file($fileTmp_Name, $filePath);
        
        $filePathDrive = rawurlencode($fileName);
        $fileContent = file_get_contents($filePath);

        $Curl = curl_init("https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart");

        $boundary = uniqid();
        $Param = [
            "--$boundary",
            'Content-Type: application/json; charset=UTF-8',
            'Content-Disposition: form-data; name="metadata"',
            '',
            json_encode([
                'name' => $fileName,
                'parents' => [$sFolderid],
            ]),
            "--$boundary",
            'Content-Type: application/octet-stream',
            'Content-Disposition: form-data; name="file"; filename="' . $fileName . '"',
            '',
            $fileContent,
            "--$boundary--",
        ];

        $Header = [
            "Authorization: Bearer " . $Token_Data["access_token"],
            'Content-Type: multipart/related; boundary=' . $boundary,
        ];

        $Response = json_decode(CURL ("POST", $Curl, implode("\r\n", $Param), $Header), true);

    }

    if (isset($_POST["create"])) {
        $sFoldeName = $_POST['folderName'];
        $sFolderid = "root";
        if(isset($_GET["Folderid"]) && $_GET["Folderid"] != "") $sFolderid = $_GET["Folderid"];

        $Curl = curl_init("https://www.googleapis.com/drive/v3/files?fields=id");
        
        $Header = [
            'Authorization: Bearer ' . $Token_Data['access_token'],
            'Content-Type: application/json',
        ];

        $Param = json_encode([
            "name" => $sFoldeName,
            "mimeType" => "application/vnd.google-apps.folder",
            "parents" => [$sFolderid],
        ]);

        $Response = CURL ("POST", $Curl, $Param, $Header);
    }

    $Curl = curl_init("https://www.googleapis.com/drive/v3/files?q='". $sFolderid ."'+in+parents");

    $Header = [
        "Authorization: Bearer " . $Token_Data["access_token"],
    ];

    $Response = json_decode(CURL ("GET", $Curl, "", $Header), true);

    echo "<center><table class='mt-5 table table-dark table-striped' border='1' cellspacing='0' style='width: 80%;'>
        <tr align='center'>
            <th>S.No</th>
            <th>Files</th>
        </tr>";

        $Sno = 1;
        foreach ($Response['files'] as $key => $value) {
            if($value["mimeType"] == "application/vnd.google-apps.folder"){
                $sName = '<a href="GoogleDriveAuth.php?Folderid='.$value["id"].'" class="btn btn-primary">'.$value["name"].'</a>';
            } 
            else {
                $sName = '<a href="ViewFile.php?Fileid='.$value["id"].'&fileName='.$value["name"].'" target="_blank" style="color: white; text-decoration: none;">'.$value["name"].'</a>';
            }
            echo "<tr align='center'>
                <td>$Sno</td>
                <td>". $sName ."</td>
            </tr>";

            $Sno++;        
        }
    echo "</table></center>";
}

function CURL ($Method, $Curl, $Param, $Header) {
    if ($Method != "GET") {
        curl_setopt($Curl, CURLOPT_POST, true);
    }
    if ($Param != "" || $Param != NULL)
        curl_setopt($Curl, CURLOPT_POSTFIELDS, $Param);
    curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
    if ($Header != "" || $Header != NULL)
        curl_setopt($Curl, CURLOPT_HTTPHEADER, $Header);
    $Response = curl_exec($Curl);
    curl_close($Curl);
    return $Response;
}

?>